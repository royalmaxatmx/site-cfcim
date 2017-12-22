<?php

/**
 *
 */
class Buwd_Destination_Folder
{
    protected static $instance = null;

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to folder', 'buwd');
        $this->info['sync_title'] = __('Syncing files to folder', 'buwd');
        $this->info['name'] = __('Folder', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = true;
    }

    public function defaults()
    {
        $wp_upload_dir = Buwd::get_upload_dir();
        $wp_home_path = Buwd::get_plugin_data('home_path');

        $defaults = array(
            'folderpath'       => str_replace($wp_home_path, '', $wp_upload_dir) . Buwd::get_plugin_data('folder_name') . '-{hash_key}/',
            'folderdelete'     => 15,
            'folderdeletesync' => '1'
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $wp_upload_dir = Buwd::get_upload_dir();
        $options = array(
            'key'    => 'destination-folder',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Folder to store', 'buwd'),
                    'header' => __('Folder Options', 'buwd'),
                    'id'     => 'folderpath',
                    'name'   => 'folderpath',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'folderpath')),
                    'hint'   => array(
                        'html' => $wp_upload_dir . '<p class="description">Setup the local folder where the backup files will be stored. You can write any name for the folder, but make sure the path starts with root directory. We recommend keeping <b>{hash_key}</b> component.</p>'
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'folderdelete',
                    'name'       => 'folderdelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min' => "0"
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 'folderdelete')),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in the dedicated folder. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'folderdeletesync',
                    'name'       => 'folderdeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => array(
                        '1' => 'Keep deleted files from previous backup sync.'
                    ),
                    'value'      => Buwd_Options::get($job_id, 'folderdeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p><span class="buwd-error">Please note that if you uncheck this option, you will loose all previous files in backup storage folder</span>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to %s folder.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step'], $job_object->backup_folder));
        $job_object->update_progress();

        $job_object->check_if_stopped();

        if (!is_dir($job_object->backup_folder)) {
            //mkdir( $job_object->backup_folder );
            wp_mkdir_p($job_object->backup_folder);
        }

        $folder_protect = Buwd_Options::getSetting('folder_protect');
        if (!empty($folder_protect)) {
            Buwd_File::protect_folder($job_object->backup_folder);
        }

        $job_object->buwd_logger->set_log(__('Upload to folder has started.', 'buwd'));
        $job_object->update_progress();


        if (copy(Buwd::get_plugin_data('temp_folder_dir') . $job_object->backup_file, $job_object->backup_folder . $job_object->backup_file)) {
            $job_object->buwd_logger->set_log(__('Backup was transferred to folder.', 'buwd'));

            @unlink(Buwd::get_plugin_data('temp_folder_dir') . $job_object->backup_file);
        } else {
            $job_object->buwd_logger->set_log(__('An error occurred while trying to transfer backup to folder.', 'buwd'), E_USER_ERROR);
        }
        $job_object->update_progress();

        $last_file = array();
        $last_file['file'] = $job_object->backup_file;
        $last_file['folder'] = substr($job_object->backup_folder, strlen(get_home_path()), strlen($job_object->backup_folder));
        $last_file['folder_path'] = $job_object->backup_folder;
        $last_file['time'] = filemtime($job_object->backup_folder . $job_object->backup_file);
        $last_file['size'] = filesize($job_object->backup_folder . $job_object->backup_file);
        $last_file['jid'] = $job_object->job_id;
        $last_file['dest'] = 'folder';
        $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

        $dest_files = get_site_option('buwd-dest-folder-' . $job_object->job_id, array());
        $dest_files[] = $last_file;

        update_site_option('buwd-dest-folder-' . $job_object->job_id, $dest_files);

        $backup_files = array();
        if (is_writable($job_object->backup_folder)) {
            if ($handle = opendir($job_object->backup_folder)) {
                while (false !== ($file = readdir($handle))) {
                    $job_object->check_if_stopped();
                    if (in_array($file, array('.', '..'), true)) {
                        continue;
                    }
                    if (!is_dir($job_object->backup_folder . $file) && !is_link($job_object->backup_folder . $file)) {
                        $backup_files[filemtime($job_object->backup_folder . $file)] = $file;
                    }
                }
                closedir($handle);
            }

            $deleted = 0;
            $files_to_delete = array();
            if (!empty($job_object->job['folderdelete']) && $job_object->job['folderdelete'] > 0 && count($backup_files) > $job_object->job['folderdelete']) {
                ksort($backup_files);
                while (count($backup_files) > $job_object->job['folderdelete']) {
                    $file = array_shift($backup_files);
                    unlink($job_object->backup_folder . $file);
                    $deleted++;
                    $files_to_delete[] = $file;
                }
            }

            if (!empty($files_to_delete)) {
                Buwd_Options::backup_bulk_delete('buwd-dest-folder-' . $job_object->job_id, $files_to_delete);
            }

            if ($deleted > 0) {
                $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from folder.', 'buwd'), $deleted));
                $job_object->update_progress();
            }
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $job_object->check_if_stopped();

        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to folder.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $files_folders = array();
        $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
        if (file_exists($files_folders_dir)) {
            $files_folders = unserialize(file_get_contents($files_folders_dir));
        }

        if (!isset($files_folders['files'])) {
            $files_folders['files'] = array();
        }

        $sync_folder = Buwd_File::get_absolute_path(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), $job_object->job['folderpath']));

        if (!is_dir($sync_folder)) {
            wp_mkdir_p($sync_folder);
        }

        $folder_protect = Buwd_Options::getSetting('folder_protect');
        if (!empty($folder_protect)) {
            Buwd_File::protect_folder($sync_folder);
        }

        if (!isset($job_object->steps_data[$job_object->current_step]['file_key'])) {
            $job_object->steps_data[$job_object->current_step]['file_key'] = -1;
            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'folder_files';
            $job_object->steps_data[$job_object->current_step]['files_count'] = 0;
            $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
            $job_object->steps_data[$job_object->current_step]['sync_files'] = array();
        }


        if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'folder_files') {
            $this->get_sync_files($sync_folder, $job_object->steps_data[$job_object->current_step]['sync_files']);

            $job_object->buwd_logger->set_log(__('File list from folder was retrieved.', 'buwd'));
            $job_object->update_progress();

            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_files';
        }


        if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_files') {
            if (!empty($files_folders['files'])) {
                $job_object->buwd_logger->set_log(__('Modified files were copied to folder.', 'buwd'));
                $job_object->update_progress();
                $files = array_reduce($files_folders['files'], 'array_merge', array());
                foreach ($files as $file_key => $file) {
                    if ($job_object->steps_data[$job_object->current_step]['file_key'] >= $file_key) {
                        continue;
                    }

                    $job_object->check_if_stopped();
                    $job_object->restart_if_needed();

                    if (strpos($file, $job_object->job['abs_path']) !== false) {
                        $file_dirname = $sync_folder . substr($file, strlen($job_object->job['abs_path']));
                    } else {
                        $file_dirname = $sync_folder . substr($file, strlen(dirname($job_object->job['abs_path'])));
                    }

                    if (isset($job_object->steps_data[$job_object->current_step]['sync_files'][$file_dirname]) && $job_object->steps_data[$job_object->current_step]['sync_files'][$file_dirname] == md5_file($file)) {
                        unset($job_object->steps_data[$job_object->current_step]['sync_files'][$file_dirname]);
                        continue;
                    }

                    if (!is_dir(dirname($file_dirname))) {
                        wp_mkdir_p(dirname($file_dirname));
                    }
                    copy($file, $file_dirname);
                    $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($file);

                    $job_object->buwd_logger->set_log(sprintf(__('%s file was copied.', 'buwd'), $file));
                    $job_object->update_progress();

                    if (isset($job_object->steps_data[$job_object->current_step]['sync_files'][$file_dirname])) {
                        unset($job_object->steps_data[$job_object->current_step]['sync_files'][$file_dirname]);
                    }

                    $job_object->steps_data[$job_object->current_step]['file_key'] = $file_key;
                }
                foreach ($files_folders['folders'] as $folder) {
                    $folder_name = $sync_folder . substr($folder, strlen($job_object->job['abs_path']));

                    if (!is_dir($folder_name)) {
                        wp_mkdir_p($folder_name);
                    }
                }
            }

            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_extra_files';
        }

        if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_extra_files') {
            if ($job_object->extra_files) {
                $job_object->buwd_logger->set_log(__('Modified extra files were copied to folder.', 'buwd'));
                $job_object->update_progress();

                foreach ($job_object->extra_files as $extra_file) {
                    $job_object->check_if_stopped();
                    $job_object->restart_if_needed();
                    $base_file = $sync_folder . basename($extra_file);
                    if (isset($job_object->steps_data[$job_object->current_step]['sync_files'][$base_file]) && $job_object->steps_data[$job_object->current_step]['sync_files'][$base_file] == md5_file($extra_file)) {
                        unset($job_object->steps_data[$job_object->current_step]['sync_files'][$base_file]);
                        continue;
                    }
                    copy($extra_file, $base_file);
                    $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                    $job_object->buwd_logger->set_log(sprintf(__('%s extra file was copied.', 'buwd'), $extra_file));
                    $job_object->update_progress();
                    if (isset($job_object->steps_data[$job_object->current_step]['sync_files'][$base_file])) {
                        unset($job_object->steps_data[$job_object->current_step]['sync_files'][$base_file]);
                    }
                }
            }
            $job_object->steps_data[$job_object->current_step]['sub_step'] = 'delete_files';
        }

        $sync_file = get_site_option('buwd-dest-folder-sync-' . $job_object->job_id, array());
        $sync_file['file'] = 'Synchronized';
        $sync_file['folder'] = substr($sync_folder, strlen(get_home_path()), strlen($sync_folder));
        $last_file['folder_path'] = $sync_folder;
        $sync_file['time'] = current_time('timestamp', true);
        $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
        $sync_file['jid'] = $job_object->job_id;
        $sync_file['dest'] = 'folder';
        $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
        $sync_file['sync'] = 1;

        update_site_option('buwd-dest-folder-sync-' . $job_object->job_id, $sync_file);

        if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'delete_files') {
            if (!$job_object->job['folderdeletesync'] && !empty($job_object->steps_data[$job_object->current_step]['sync_files'])) {
                $job_object->buwd_logger->set_log(__('Non-existent files were deleted from folder.', 'buwd'));
                $folders_to_check = array();
                foreach ($job_object->steps_data[$job_object->current_step]['sync_files'] as $sync_file => $sync_file_hash) {

                    $job_object->check_if_stopped();
                    $job_object->restart_if_needed();
                    if (file_exists($sync_file)) {
                        unlink($sync_file);
                        $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from folder.', 'buwd'), $sync_file));
                        $job_object->update_progress();
                    }

                    unset($job_object->steps_data[$job_object->current_step]['sync_files'][$sync_file]);
                    $folders_to_check[] = dirname($sync_file);
                }

                Buwd_File::delete_if_empty(array_unique($folders_to_check));
            }
        }

        return true;
    }

    private function get_sync_files($folder, &$job_obj_sync_files)
    {
        if ($dir = opendir($folder)) {
            while (false !== ($file = readdir($dir))) {
                if (in_array($file, array('.', '..'), true)) {
                    continue;
                }

                if (is_dir($folder . $file)) {
                    $this->get_sync_files(trailingslashit($folder . $file), $job_obj_sync_files);
                } else if (is_readable($folder . $file) && !is_link($folder . $file)) {
                    $job_obj_sync_files[$folder . $file] = md5_file($folder . $file);
                }
            }
            closedir($dir);
        }
    }

    public static function display_messages()
    {

    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';

            if ($field_name == 'folderpath') {
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim(sanitize_text_field($field_value)))));
            }

            if ($field_name == 'folderdelete') {
                $field_value = (int)$field_value;
            }
            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
    }

    public function delete_file($backup)
    {
        $file = $backup['file'];
        $folder = $backup['folder_path'];

        //delete from folder
        if (file_exists($folder . $file)) {
            if (!unlink($folder . $file)) {
                return false;
            }
        }

        return true;
    }

    public function download_file($backup)
    {
        $file = $backup['file'];
        $folder_path = $backup['folder_path'];
        $size = $backup['size'];
        $file_url = $folder_path . $file;

        if (isset($file_url) && is_readable($file_url) && !is_link($file_url)) {
            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            header("Content-Description: File Transfer");
            header("Content-Type:  application/octet-stream");
            header("Content-disposition: attachment; filename=\"" . basename($file) . "\"");
            header("Content-Transfer-Encoding: Binary");
            header("Connection: Keep-Alive");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: public");
            header("Content-Length: " . $size);
            readfile($file_url);
            die();
        } else {
            set_site_transient('buwd_backups_error', __('File not found.', 'buwd'));
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

    }

    public function is_valid($job_options)
    {
        if (empty($job_options['folderpath'])) {
            return false;
        }

        return true;
    }

    /**
     * Return an instance of this class.
     */
    public static function get_instance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}