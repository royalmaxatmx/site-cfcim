<?php
if (!class_exists('Buwd_Sugarsync')) {
    require_once BUWD_DIR . '/assets/sugarsync.php';
}

/**
 *
 */
class Buwd_Destination_Sugarsync
{
    protected static $instance = null;
    private $sugar_key;
    private $sugar_secret_key;
    private $sugar_app_id;

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Sugarsync', 'buwd');
        $this->info['name'] = __('SugarSync', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = false;
        $this->info['tab_name'] = 'destination-sugarsync';
        $this->set_keys();
    }

    public function defaults()
    {
        $defaults = array(
            'sugartoken'      => '',
            'sugaruser'       => '',
            'sugarsyncfolder' => '',
            'sugarfolder'     => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'sugarfiledelete' => 15,
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $disabled = '';
        $readonly = false;
        if (!$this->is_keys_setted() && Buwd_Helper::get('tab') == $this->info['tab_name']) {
            echo '<div class="buwd-messages">' . Buwd_Helper::message(sprintf(__('SugarSync API: You should set up SugarSync API keys first in %s', 'buwd'), '<a href="' . admin_url("admin.php?page=buwd_api_keys&tab=sugar-sync") . '" target="_blank">API keys</a>'), 'error') . '</div>';
            $disabled = 'buwd-disabled';
            $readonly = 'readonly';
        }
        $error_message = '';
        $error_visibility = false;
        $syncfolder_options = array();
        $sugartoken = Buwd_Options::get($job_id, 'sugartoken');
        if (empty($sugartoken)) {
            $sugartoken = $this->get_sugarsync_token($job_id);
        }

        if (!empty($sugartoken) && !is_array($sugartoken)) {
            $auth_visibility = false;
            $buttons = '<span class="auth">Authenticated</span><input name="sugar_auth_delete" type="submit" class="buwd-button" value="Delete Authentication" />';
            try {
                $sugarsync = new Buwd_SugarSync(array(
                    'app_key'    => $this->sugar_key,
                    'secret_key' => $this->sugar_secret_key,
                    'app_id'     => $this->sugar_app_id,
                ), $sugartoken);

                $user = $sugarsync->user();
                $sync_folders = $sugarsync->get($user->syncfolders);
                if (isset($sync_folders) && is_object($sync_folders)) {
                    foreach ($sync_folders->collection as $sync_folder) {
                        $syncfolder_options[(string)$sync_folder->ref] = (string)$sync_folder->displayName;
                    }
                } else {
                    echo Buwd_Helper::message('No Syncfolders found!', 'error');
                }
            } catch (Exception $e) {
                echo Buwd_Helper::message($e->getMessage(), 'error');
            }

        } else {
            $auth_visibility = true;
            if (is_array($sugartoken)) {
                $error_message = $sugartoken['error'];
                $error_visibility = true;
            } else {
                $error_message = '';//'Authenticate to get folders';
            }
            $buttons = '<a class="buwd-button ' . $disabled . '" id="buwd_sugarsync_auth" name="sugar_auth">Authenticate</a><p class="description">Authenticate your SugarSync account by providing its user email address and password. If you don’t have an account yet, please <a target="_blank" href="https://www.sugarsync.com/pricing">create an account.</a></p><div class="buwd-sugar-loading buwd-hide"></div>';
        }

        $options = array(
            'key'    => 'destination-sugarsync',
            'title'  => '',
            'fields' => array(
                array(
                    'label'      => __('Authentication', 'buwd'),
                    'header'     => __('Login', 'buwd'),
                    'id'         => 'sugar_email',
                    'name'       => 'sugar_email',
                    'type'       => 'text',
                    'class'      => array(
                        'buwd-large-text',
                    ),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => 'Email Address',
                    ),
                    'attr'       => array(
                        'readonly' => $readonly
                    ),
                    'value'      => Buwd_Options::get($job_id, 'sugar_email'),
                    'visibility' => $auth_visibility
                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'sugar_pass',
                    'name'       => 'sugar_pass',
                    'type'       => 'password',
                    'class'      => array(
                        'buwd-large-text',
                    ),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => 'Password',
                    ),
                    'attr'       => array(
                        'readonly' => $readonly
                    ),
                    'value'      => Buwd_Options::get($job_id, 'sugar_pass'),
                    'visibility' => $auth_visibility
                ),
                array(
                    'label' => !empty($sugartoken) && !is_array($sugartoken) ? __('Authentication', 'buwd') : __('', 'buwd'),
                    'id'    => 'sugartoken',
                    'name'  => 'sugartoken',
                    'type'  => 'hidden',
                    'hint'  => array(
                        'html' => $buttons
                    ),
                    'value' => !empty($sugartoken) && !is_array($sugartoken) ? $sugartoken : '',
                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'sugar_error',
                    'name'       => 'sugar_error',
                    'type'       => 'paragraph',
                    'class'      => array(
                        'buwd-large-text', 'buwd-error'
                    ),
                    'value'      => $error_message,
                    'visibility' => $error_visibility,
                ),
                array(
                    'label'      => __('Select Sync folder', 'buwd'),
                    'header'     => !$auth_visibility ? __('SugarSync Root', 'buwd') : '',
                    'id'         => 'sugarsyncfolder',
                    'name'       => 'sugarsyncfolder',
                    'type'       => 'select',
                    'class'      => array(
                        'buwd-large-text',
                    ),
                    'choices'    => $syncfolder_options,
                    'value'      => esc_html(Buwd_Options::get($job_id, 'sugarsyncfolder')),
                    'visibility' => !$auth_visibility,
                    'hint'       => array(
                        'html' => '<p class="description">  Select the root folder of your SugarSync account where the backup sync will be uploaded.</p>'
                    ),
                ),
                array(
                    'label'  => __('Folder in SugarSync', 'buwd'),
                    'header' => __('Backup settings', 'buwd'),
                    'id'     => 'sugarfolder',
                    'name'   => 'sugarfolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'attr'   => array(
                        'readonly' => $readonly
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'sugarfolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the SugarSync folder where the backup sync will be uploaded.</p>'
                    ),
                ),
                array(
                    'label' => __('File deletion', 'buwd'),
                    'id'    => 'sugarfiledelete',
                    'name'  => 'sugarfiledelete',
                    'type'  => 'number', // to number
                    'class' => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'  => array(
                        'min'      => "0",
                        'readonly' => $readonly
                    ),
                    'value' => esc_html(Buwd_Options::get($job_id, 'sugarfiledelete')),
                    'hint'  => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in your SugarSync folder for backup. When the limit is reached, the oldest backup file will be deleted.</p>'
                    ),
                ),

            ),
        );

        return $options;
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        die;
    }

    public function get_sugarsync_token($job_id)
    {
        if (isset($_POST['sugar_auth'])) {
            if (!empty($_POST['sugar_email']) && !empty($_POST['sugar_pass'])) {
                try {
                    $sugarsync = new Buwd_SugarSync(array(
                        'app_key'    => $this->sugar_key,
                        'secret_key' => $this->sugar_secret_key,
                        'app_id'     => $this->sugar_app_id,
                    ));

                    $refresh_token = $sugarsync->getRefreshToken(sanitize_email($_POST['sugar_email']), sanitize_text_field($_POST['sugar_pass']));
                    if (!empty($refresh_token)) {
                        $sugarsync->setRefreshToken($refresh_token);
                        Buwd_Options::update_job_option($job_id, 'sugartoken', $refresh_token);

                        return $refresh_token;
                    }
                } catch (Exception $e) {
                    return array('error' => __('Invalid Username or Password', 'buwd'));
                }

            } else {
                return array('error' => __('Username or Password missing', 'buwd'));
            }
        }
    }

    public function set_keys()
    {
        $this->sugar_key = Buwd_Options::getSetting('sugar_key');
        $this->sugar_secret_key = Buwd_Encrypt::decrypt(Buwd_Options::getSetting('sugar_secret_key'));
        $this->sugar_app_id = Buwd_Options::getSetting('sugar_app_id');
    }

    public function is_keys_setted()
    {
        if (empty($this->sugar_key) || empty($this->sugar_secret_key) || empty($this->sugar_app_id)) {
            return false;
        }

        return true;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to SugarSync.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $sugartoken = $job_object->job['sugartoken'];
        try {
            $sugarsync = new Buwd_SugarSync(array(
                'app_key'    => $this->sugar_key,
                'secret_key' => $this->sugar_secret_key,
                'app_id'     => $this->sugar_app_id,
            ), $sugartoken);

            $user = $sugarsync->user();
            if (!empty($user->nickname)) {
                $job_object->buwd_logger->set_log(sprintf(__('SugarSync account was authenticated with %s nickname.', 'buwd'), $user->nickname));
                $job_object->update_progress();
            }

            $sugar_free_spase = (float)$user->quota->limit - (float)$user->quota->usage;
            if ($job_object->backup_file_size >= $sugar_free_spase) {
                $job_object->buwd_logger->set_log(sprintf(__('There is not enough storage space available on SugarSync. Available: %s.', 'buwd'), size_format($sugar_free_spase, 2)));
            }

            $job_object->check_if_stopped();

            //Create and change folder
            $sugarsync->mkdir($job_object->job['sugarfolder'], $job_object->job['sugarsyncfolder']);
            $folder_id = $sugarsync->chdir($job_object->job['sugarfolder'], $job_object->job['sugarsyncfolder']);
            $job_object->buwd_logger->set_log(__('Upload to SugarSync has started.', 'buwd'));
            $job_object->update_progress();

            $response = $sugarsync->upload($job_object->backup_folder . $job_object->backup_file);
            if (is_object($response)) {
                $folder_dir = $sugarsync->showdir($folder_id);
                $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to %s.', 'buwd'), 'https://' . $user->nickname . '.sugarsync.com/' . $folder_dir . $job_object->backup_file));
                $job_object->update_progress();

                $uploaded_file = (string)$response;
                $file_info = $sugarsync->get($uploaded_file);

                $last_file = array();
                $UTC = new DateTimeZone("UTC");
                $tc = new DateTime($file_info->timeCreated);
                $last_file['file'] = $job_object->backup_file;
                $last_file['folder'] = $folder_dir;
                $last_file['time'] = $tc->setTimezone($UTC)->getTimestamp();
                $last_file['size'] = (int)$file_info->size;
                $last_file['file_info'] = $uploaded_file;
                $last_file['jid'] = $job_object->job_id;
                $last_file['dest'] = 'sugarsync';
                $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                $dest_files = get_site_option('buwd-dest-sugarsync-' . $job_object->job_id, array());
                $dest_files[] = $last_file;

                update_site_option('buwd-dest-sugarsync-' . $job_object->job_id, $dest_files);
            } else {
                $job_object->buwd_logger->set_log(__('Could not transfer backup to SugarSync.', 'buwd'), E_USER_ERROR);

                return false;
            }

            $backup_files = array();
            $files_list = $sugarsync->getcontents('file');
            if (is_object($files_list)) {
                foreach ($files_list->file as $file) {
                    $lm = new DateTime($file->lastModified);
                    $backup_files[$lm->setTimezone($UTC)->getTimestamp()] = (string)$file->ref;
                }
            }

            $deleted = 0;
            $files_to_delete[] = $file;
            if (!empty($job_object->job['sugarfiledelete']) && $job_object->job['sugarfiledelete'] > 0 && count($backup_files) > $job_object->job['sugarfiledelete']) {
                ksort($backup_files);
                while (count($backup_files) > $job_object->job['sugarfiledelete']) {
                    $file = array_shift($backup_files);
                    $sugarsync->delete($file);
                    $deleted++;
                    $files_to_delete[] = basename($file);
                }
            }

            if (!empty($files_to_delete)) {
                Buwd_Options::backup_bulk_delete('buwd-dest-sugarsync-' . $job_object->job_id, $files_to_delete);
            }

            if ($deleted > 0) {
                $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from SugarSync.', 'buwd'), $deleted));
                $job_object->update_progress();
            }
        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('SugarSync API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR, $e->getFile(), $e->getLine());
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';

            if (in_array($field_name, array('sugarsyncfolder', 'sugarfolder', 'sugartoken'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 'sugar_email') {
                $field_value = sanitize_email($field_value);
            }

            if ($field_name == 'sugar_pass') {
                continue;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
        if (isset($_POST['sugar_auth_delete'])) {
            Buwd_Options::delete_job_option($job_id, 'sugartoken');
            Buwd_Options::delete_job_option($job_id, 'sugar_email');
        }

    }

    public function get_footer()
    {
        $disabled = '';
        if (!$this->is_keys_setted()) {
            $disabled = 'buwd-disabled';
        }

        return '<button class="buwd-button button-save ' . $disabled . '" onclick="if(jQuery(this.form).valid()){this.form.submit()}"><span></span>Save</button>';
    }

    public function delete_file($backup)
    {
        $job_id = $backup['jid'];
        $file_info = $backup['file_info'];

        $sugartoken = esc_html(Buwd_Options::get($job_id, 'sugartoken'));
        try {
            $sugarsync = new Buwd_SugarSync(array(
                'app_key'    => $this->sugar_key,
                'secret_key' => $this->sugar_secret_key,
                'app_id'     => $this->sugar_app_id,
            ), $sugartoken);

            $sugarsync->delete($file_info);

        } catch (Exception $e) {
            //set_site_transient('buwd_backups_error', __('SugarSync: ' . $e->getMessage(), 'buwd'));
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $job_id = $backup['jid'];
        $file = $backup['file'];
        $file_info = $backup['file_info'];

        $sugartoken = Buwd_Options::get($job_id, 'sugartoken');
        try {
            $sugarsync = new Buwd_SugarSync(array(
                'app_key'    => $this->sugar_key,
                'secret_key' => $this->sugar_secret_key,
                'app_id'     => $this->sugar_app_id,
            ), $sugartoken);

            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            header("Content-Description: File Transfer");
            header("Content-Type:  application/octet-stream");
            header("Content-disposition: attachment; filename=\"" . $file . "\"");
            header("Content-Transfer-Encoding: Binary");
            header("Connection: Keep-Alive");
            header("Expires: 0");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Pragma: public");
            header("Content-Length: " . $backup['size']);
            echo $sugarsync->download($file_info);
            die();

        } catch (Exception $e) {
            set_site_transient('buwd_backups_error', __('SugarSync: ' . $e->getMessage(), 'buwd'));

            return false;
        }
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['sugartoken']) || empty($job_options['sugarsyncfolder'])) {
            return false;
        }

        return true;
    }

    private function render_tab()
    {
        $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $options = $this->get_options($job_id);
        $group_class = new Buwd_Form_Group(array($options));
        $groups = $group_class->get_groups();
        $group_html = array();
        foreach ($groups as $g_name => $group) {
            $group_html['title'] = $group->title;
            $group_html['content'] = $group_class->render_group($g_name);
        }

        return '<table>' . $group_html['content'] . '</table>';
    }

    public static function display_messages()
    {
        echo Buwd_Helper::message("Please do not use SugarSync if your backup archive size is too large as SugarSync doesn't have chunked file upload", 'warning');
        if ($error = get_site_transient('buwd_sugar_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_sugar_error');
        } else if ($updated = get_site_transient('buwd_sugar_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_sugar_updated');
        }
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
