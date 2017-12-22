<?php
require_once BUWD_DIR . '/vendor/autoload.php';

/**
 *
 */
class Buwd_Destination_GDrive
{
    protected static $instance = null;
    private $gd_client_id;
    private $gd_client_secret;
    private $service;

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Google Drive', 'buwd');
        $this->info['sync_title'] = __('Syncing files to Google Drive', 'buwd');
        $this->info['name'] = __('GDrive', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = false;
        $this->info['tab_name'] = 'destination-gdrive';

        $this->set_keys();
    }

    public function defaults()
    {
        $defaults = array(
            'gdrivefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'gdrivefiledelete'     => 15,
            'gdrivefiledeletesync' => '1'
        );

        return $defaults;
    }

    /**
     * @param $job_id
     *
     * @return array
     */
    public function get_options($job_id)
    {
        set_site_transient('buwd_gdrive_jobid', $job_id, HOUR_IN_SECONDS);

        $disabled = '';
        $readonly = false;

        if (!$this->is_keys_setted() && Buwd_Helper::get('tab') == $this->info['tab_name']) {
            echo '<div class="buwd-messages">' . Buwd_Helper::message(sprintf(__('GDrive API: Please configure Google Drive API keys on %s page of Backup WD plugin.', 'buwd'), '<a href="' . admin_url("admin.php?page=buwd_api_keys") . '" target="_blank">API keys</a>'), 'error') . '</div>';
            $disabled = 'buwd-disabled';
            $readonly = 'readonly';
        }


        $gdrivetoken = Buwd_Options::get($job_id, 'gdrivetoken');
        $client = $this->GDrive();
        if (!empty($gdrivetoken)) {
            $client->refreshToken($gdrivetoken['refresh_token']);
            $auth_text = 'Authenticated';
            $auth_class = 'auth';
        } else {
            $auth_text = 'Not Authenticated';
            $auth_class = 'not-auth';
        }

        $options = array(
            'key'    => 'destination-gdrive',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Authentication', 'buwd'),
                    'header' => __('Login', 'buwd'),
                    'id'     => 'gdriveauth',
                    'name'   => 'gdriveauth',
                    'type'   => 'hidden',
                    'class'  => array(),
                    'hint'   => array(
                        'html' => '<span class="' . $auth_class . '">' . $auth_text . '</span><a  class="gdrive-auth buwd-button ' . $disabled . '" href="' . admin_url("admin-ajax.php") . '?action=buwd-gdrive&job_id=' . $job_id . '" >Reautenticate</a><p class="description">Press <b>Authenticate</b> to connect Backup WD to your Google Drive. Use the Google account, where you wish to keep the backup files.</p>'
                    ),
                    'value'  => Buwd_Options::get($job_id, 'gdriveauth'),
                ),
                array(
                    'label'  => __('Folder in Google Drive', 'buwd'),
                    'header' => __('GDrive Backup settings', 'buwd'),
                    'id'     => 'gdrivefolder',
                    'name'   => 'gdrivefolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'attr'   => array(
                        'readonly' => $readonly
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'gdrivefolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Set the title of your Google Drive folder, where the backup files will be uploaded.</p>'
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'gdrivefiledelete',
                    'name'       => 'gdrivefiledelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min' => "0"
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 'gdrivefiledelete')),
                    'attr'       => array(
                        'readonly' => $readonly
                    ),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in Google Drive folder. When the limit is reached, the oldest backup file will be deleted.</p>'
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'gdrivefiledeletesync',
                    'name'       => 'gdrivefiledeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => array(
                        '1' => 'Keep deleted files from previous backup sync.'
                    ),
                    'attr'       => array(
                        str_replace('buwd-', '', $disabled) => str_replace('buwd-', '', $disabled)
                    ),
                    'value'      => Buwd_Options::get($job_id, 'gdrivefiledeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function set_keys()
    {
        $this->gd_client_id = Buwd_Options::getSetting('gd_client_id');
        $this->gd_client_secret = Buwd_Encrypt::decrypt(Buwd_Options::getSetting('gd_client_secret'));
    }

    public function is_keys_setted()
    {
        if (empty($this->gd_client_id) || empty($this->gd_client_secret)) {
            return false;
        }

        return true;
    }

    public function run_ajax()
    {
        $job_id = get_site_transient('buwd_gdrive_jobid');

        $redirectUri = admin_url('admin-ajax.php') . '?action=buwd-gdrive';
        $config = array('approval_prompt' => 'force');
        //	$config      = array();
        $client = $this->GDrive($config, $redirectUri);

        $redirect = array(
            'page'   => 'buwd_editjob',
            'job_id' => $job_id,
            'tab'    => 'destination-gdrive',
        );

        if ($job_id) {
            if (isset($_GET['code'])) {
                try {
                    $client->fetchAccessTokenWithAuthCode($_GET['code']);
                    $access_token = $client->getAccessToken();

                    if (!empty($access_token)) {
                        Buwd_Options::update_job_option($job_id, 'gdrivetoken', $access_token);
                    } else {
                        Buwd_Options::delete_job_option($job_id, 'gdrivetoken');
                        set_site_transient('buwd_gdrive_error', __('GDrive: The refresh token was not received. Please try to authenticate again.', 'buwd'));
                    }

                    Buwd_Helper::redirect($redirect);
                    //wp_die();
                } catch (Exception $e) {
                    set_site_transient('buwd_gdrive_error', sprintf(__('GDrive API: %s', 'buwd'), $e->getMessage()));
                    Buwd_Options::delete_job_option($job_id, 'gdrivetoken');
                    Buwd_Helper::redirect($redirect);
                    //wp_die();
                }
            } else {
                try {
                    $access_token = Buwd_Options::get($job_id, 'gdrivetoken');
                    if (!empty($access_token)) {
                        $client->setAccessToken($access_token);
                        if ($client->isAccessTokenExpired()) {
                            $client->revokeToken($access_token);
                            Buwd_Options::delete_job_option($job_id, 'gdrivetoken');
                        }
                    }

                    $auth_url = $client->createAuthUrl();

                    wp_redirect($auth_url);
                    /*wp_die();*/
                } catch (Exception $e) {
                    set_site_transient('buwd_gdrive_error', sprintf(__('GDrive API: %s', 'buwd'), $e->getMessage()));
                    Buwd_Options::delete_job_option($job_id, 'gdrivetoken');
                    Buwd_Helper::redirect($redirect);
                    //	wp_die();
                }
            }
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

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to upload backup file to Google Drive.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        try {
            $client = $this->GDrive();
            $gdrivetoken = $job_object->job['gdrivetoken'];
            $client->refreshToken($gdrivetoken['refresh_token']);

            $this->service = new Google_Service_Drive($client);
            if (empty($job_object->steps_data[$job_object->current_step]['folder_id'])) {
                $job_object->steps_data[$job_object->current_step]['folder_id'] = $this->get_folder_id($job_object->job['gdrivefolder']);
            }

            $access_token = $client->getAccessToken();
            if (empty($job_object->steps_data[$job_object->current_step]['session_uri'])) {
                $post_data = new stdClass();
                $post_data->title = $job_object->backup_file;
                $post_data->mimeType = Buwd_File::mime_content_type($job_object->backup_folder . $job_object->backup_file);

                if ($job_object->steps_data[$job_object->current_step]['folder_id'] !== 'root') {
                    $post_data_parent = new stdClass();
                    $post_data_parent->kind = 'drive#fileLink';
                    $post_data_parent->id = $job_object->steps_data[$job_object->current_step]['folder_id'];
                    $post_data->parents = array($post_data_parent);
                }

                $data = json_encode($post_data);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/upload/drive/v2/files?uploadType=resumable');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($ch, CURLOPT_HEADER, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if (Buwd::get_plugin_data('cacert')) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_CAINFO, Buwd::get_plugin_data('cacert'));
                    curl_setopt($ch, CURLOPT_CAPATH, dirname(Buwd::get_plugin_data('cacert')));
                } else {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                }

                curl_setopt(
                    $ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: Bearer ' . $access_token['access_token'],
                        'Content-Length: ' . strlen($data),
                        'X-Upload-Content-Type: ' . Buwd_File::mime_content_type(
                            $job_object->backup_folder . $job_object->backup_file
                        ),
                        'Content-Type: application/json; charset=UTF-8',
                        'X-Upload-Content-Length: ' . $job_object->backup_file_size,
                    )
                );

                $response = curl_exec($ch);
                $curl_info = curl_getinfo($ch);
                curl_close($ch);

                if ($curl_info['http_code'] == 200 || $curl_info['http_code'] == 201) {
                    if (preg_match('/Location:(.*?)\r/i', $response, $matches)) {
                        $job_object->steps_data[$job_object->current_step]['session_uri'] = trim($matches[1]);
                    }
                }

                if (empty($job_object->steps_data[$job_object->current_step]['session_uri'])) {
                    $job_object->buwd_logger->set_log(__('Resumable file transfer to Google Drive could not be established. Chunk transfer to Google Drive failed.', 'buwd'), E_USER_ERROR);
                    $job_object->update_progress();

                    return false;
                }
            }

            $job_object->buwd_logger->set_log(__('Upload to Google Drive has started. Please note, that this may take a while.', 'buwd'));
            $job_object->update_progress();

            $chunk_size = 5 * 1024 * 1024;

            if (!$job_object->steps_data[$job_object->current_step]['sub_step']) {
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 0;
            }

            if ($handle = fopen($job_object->backup_folder . $job_object->backup_file, 'rb')) {
                fseek($handle, $job_object->steps_data[$job_object->current_step]['sub_step']);
                while (!feof($handle)) {

                    $job_object->restart_if_needed();
                    $job_object->check_if_stopped();
                    $chunk_data = fread($handle, $chunk_size);

                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $job_object->steps_data[$job_object->current_step]['session_uri']);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $chunk_data);
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    if (Buwd::get_plugin_data('cacert')) {
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                        curl_setopt($ch, CURLOPT_CAINFO, Buwd::get_plugin_data('cacert'));
                        curl_setopt($ch, CURLOPT_CAPATH, dirname(Buwd::get_plugin_data('cacert')));
                    } else {
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    }
                    curl_setopt(
                        $ch, CURLOPT_HTTPHEADER, array(
                            'Authorization: Bearer ' . $access_token['access_token'],
                            'Content-Length: ' . strlen($chunk_data),
                            'Content-Type: application/json; charset=UTF-8',
                            'Content-Range: bytes ' . $job_object->steps_data[$job_object->current_step]['sub_step'] . '-' . ($job_object->steps_data[$job_object->current_step]['sub_step'] + strlen($chunk_data) - 1) . '/' . $job_object->backup_file_size,

                        )
                    );

                    $response = curl_exec($ch);
                    $curl_info = curl_getinfo($ch);
                    $curl_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    curl_close($ch);

                    if ($curl_info['http_code'] == 200 || $curl_info['http_code'] == 201 || $curl_info['http_code'] == 308) {
                        $json_data = mb_substr($response, $curl_header_size);
                        $uploaded_file = json_decode($json_data);
                        if ($curl_info['http_code'] == 308) {
                            $job_object->steps_data[$job_object->current_step]['sub_step'] = $job_object->steps_data[$job_object->current_step]['sub_step'] + $chunk_size;
                        }
                    } else {
                        $job_object->buwd_logger->set_log(__('An error occurred while trying to transfer file chunks to Google Drive.', 'buwd'), E_USER_WARNING);
                        $job_object->update_progress();

                        return false;
                    }
                }
                fclose($handle);

            } else {
                $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

            if (is_object($uploaded_file) && isset($uploaded_file->id)) {
                if ($uploaded_file->fileSize == $job_object->backup_file_size) {
                    $downloadUrl = str_replace('&gd=true', '', $uploaded_file->downloadUrl);
                    $last_file = array();
                    $UTC = new DateTimeZone("UTC");
                    $cd = new DateTime($uploaded_file->createdDate);
                    $last_file['file'] = $uploaded_file->title;
                    $last_file['folder'] = $job_object->job['gdrivefolder'];
                    $last_file['time'] = (int)$cd->setTimezone($UTC)->getTimestamp();
                    $last_file['size'] = $uploaded_file->fileSize;
                    $last_file['downloadurl'] = $downloadUrl;
                    $last_file['file_id'] = $uploaded_file->id;
                    $last_file['jid'] = $job_object->job_id;
                    $last_file['dest'] = 'gdrive';
                    $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                    $dest_files = get_site_option('buwd-dest-gdrive-' . $job_object->job_id, array());
                    $dest_files[] = $last_file;

                    update_site_option('buwd-dest-gdrive-' . $job_object->job_id, $dest_files);

                    $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to %s', 'buwd'), $uploaded_file->alternateLink));
                    $job_object->update_progress();
                } else {
                    $job_object->buwd_logger->set_log(__('Backup file size and uploaded file size do not match.', 'buwd'), E_USER_ERROR);
                    $job_object->update_progress();

                    return false;
                }
            } else {
                $job_object->buwd_logger->set_log(__('An error occurred while trying to transfer backup to Google Drive.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

            //delete files
            $backup_files = array();
            $files_list = $this->service->files->listFiles(array('fields' => 'files', 'q' => "explicitlyTrashed = false and '" . $job_object->steps_data[$job_object->current_step]['folder_id'] . "' in parents and mimeType = '" . Buwd_File::mime_content_type($job_object->backup_folder . $job_object->backup_file) . "'"));

            $deleted = 0;
            $files_to_delete = array();
            if ($files_list && $files_list->files) {
                foreach ($files_list->files as $file) {
                    $backup_files[strtotime($file->createdTime)] = $file->id;
                }

                if ((int)$job_object->job['gdrivefiledelete'] > 0 && count($backup_files) > (int)$job_object->job['gdrivefiledelete']) {
                    ksort($backup_files);
                    while (count($backup_files) > $job_object->job['gdrivefiledelete']) {
                        $file = array_shift($backup_files);
                        $this->service->files->delete($file);

                        $deleted++;
                        $files_to_delete[] = $file;
                    }
                }
            }

            if (!empty($files_to_delete)) {
                Buwd_Options::backup_bulk_delete('buwd-dest-gdrive-' . $job_object->job_id, $files_to_delete);
            }

            if ($deleted > 0) {
                $job_object->buwd_logger->set_log(sprintf(__('%d files were successfully deleted from Google Drive.', 'buwd'), $deleted));
                $job_object->update_progress();
            }
        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Google Drive API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine(), E_USER_ERROR);
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to Google Drive.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        try {
            $client = $this->GDrive();
            $gdrivetoken = $job_object->job['gdrivetoken'];
            $client->refreshToken($gdrivetoken['refresh_token']);

            $this->service = new Google_Service_Drive($client);

            $sync_folder_id = $this->get_folder_id($job_object->job['gdrivefolder']);
            $extra_files = array();
            if ($job_object->extra_files) {
                foreach ($job_object->extra_files as $extra_file) {
                    $extra_files[basename($extra_file)] = $extra_file;
                }
            }

            if (!isset($job_object->steps_data[$job_object->current_step]['folder_key'])) {
                $job_object->steps_data[$job_object->current_step]['folder_key'] = -1;
                $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
            }


            $files_folders = array();
            $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
            if (file_exists($files_folders_dir)) {
                $files_folders = unserialize(file_get_contents($files_folders_dir));
            }
            if (!isset($files_folders['files'])) {
                $files_folders['files'] = array();
            }

            $job_object->buwd_logger->set_log(__('Modified files were synced to Google Drive.', 'buwd'));
            $job_object->update_progress();


            //$drive_folder_id = $this->get_folder_id($sync_folder_id);
            $parameters = array('fields' => 'files', 'q' => "explicitlyTrashed = false and '" . $sync_folder_id . "' in parents");
            $pageToken = null;
            $drive_files = array();
            do {
                try {
                    if ($pageToken) {
                        $parameters['pageToken'] = $pageToken;
                    }
                    $files = $this->service->files->listFiles($parameters);
                    $drive_files[] = $files->getFiles();

                    $pageToken = $files->getNextPageToken();
                } catch (Exception $e) {
                    $pageToken = null;
                }
            } while ($pageToken);


            foreach ($files_folders['folders'] as $folder_key => $folder) {
                if ($job_object->steps_data[$job_object->current_step]['folder_key'] >= $folder_key) {
                    continue;
                }

                if (!isset($job_object->steps_data[$job_object->current_step][$folder_key])) {
                    $job_object->steps_data[$job_object->current_step][$folder_key] = array();
                    $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = -1;
                }

                $job_object->check_if_stopped();
                $job_object->restart_if_needed();
                $folder_dirname = $job_object->job['gdrivefolder'] . '/' . substr($folder, strlen($job_object->job['abs_path']));

                $drive_folder_id = $this->get_folder_id($folder_dirname);
                $parameters = array('fields' => 'files', 'q' => "explicitlyTrashed = false and '" . $drive_folder_id . "' in parents and mimeType != 'application/vnd.google-apps.folder'");
                $pageToken = null;
                $drive_files = array();
                do {
                    try {
                        if ($pageToken) {
                            $parameters['pageToken'] = $pageToken;
                        }
                        $files = $this->service->files->listFiles($parameters);

                        $drive_files[] = $files->getFiles();
                        $pageToken = $files->getNextPageToken();
                    } catch (Exception $e) {
                        $pageToken = null;
                    }
                } while ($pageToken);


                if ($sync_folder_id === $drive_folder_id && $drive_files) {
                    foreach ($drive_files as $drive_key => $drive_file) {
                        if (in_array((string)$drive_file->name, array_keys($extra_files))) {
                            unset($drive_files[$drive_key]);
                        }
                    }
                }

                $folder_files = isset($files_folders['files'][$folder]) ? $files_folders['files'][$folder] : array();
                foreach ($folder_files as $file_key => $f_file) {
                    if ($job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] >= $file_key) {
                        continue;
                    }

                    $job_object->check_if_stopped();
                    $job_object->restart_if_needed();
                    //TODO in future save file key for restart
                    foreach ($drive_files as $drive_key => $drive_file) {
                        if ($drive_file->name == basename($f_file)) {
                            if ($drive_file->md5Checksum != md5_file($f_file)) {

                                $updated_file = new Google_Service_Drive_DriveFile();

                                $this->service->files->update($drive_file->id, $updated_file, array(
                                    'data'       => file_get_contents($f_file),
                                    'uploadType' => 'multipart',
                                    'mimeType'   => Buwd_File::mime_content_type($f_file)
                                ));
                                $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);

                                $job_object->buwd_logger->set_log(sprintf(__('%s file was updated on Google Drive.', 'buwd'), $f_file));
                                $job_object->update_progress();
                            }
                            //remove existing file from gdrive files array
                            unset($drive_files[$drive_key]);
                            continue 2;
                        }
                    }

                    $new_file = new Google_Service_Drive_DriveFile();
                    $new_file->setName(basename($f_file));
                    $new_file->setMimeType(Buwd_File::mime_content_type($f_file));
                    //$parent = new Google_Service_Drive_ParentReference();
                    //$parent->setId($drive_folder_id);
                    $new_file->setParents(array($drive_folder_id));
                    $this->service->files->create($new_file, array(
                        'data'       => file_get_contents($f_file),
                        'uploadType' => 'multipart',
                        'mimeType'   => Buwd_File::mime_content_type($f_file)
                    ));
                    $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);

                    $file_dirname = $job_object->job['gdrivefolder'] . '/' . substr($f_file, strlen($job_object->job['abs_path']));

                    $job_object->buwd_logger->set_log(sprintf(__('%s file was uploaded to Google Drive.', 'buwd'), $file_dirname));
                    $job_object->update_progress();

                    $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = $file_key;
                }

                if (!$job_object->job['gdrivefiledeletesync'] && !empty($drive_files)) {
                    foreach ($drive_files as $drive_key => $drive_file) {
                        $job_object->restart_if_needed();

                        $this->service->files->delete($drive_file->id);

                        $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from Google Drive.', 'buwd'), $file_dirname . $drive_file->name));
                        $job_object->update_progress();
                    }
                }

                $job_object->steps_data[$job_object->current_step]['folder_key'] = $folder_key;
            }

            //add extra files
            if ($extra_files) {
                $drive_files = $this->service->files->listFiles(array('fields' => 'files', 'q' => "explicitlyTrashed = false and '" . $sync_folder_id . "' in parents and mimeType != 'application/vnd.google-apps.folder'"));
                $job_object->check_if_stopped();
                foreach ($extra_files as $extra_file_basename => $extra_file) {
                    $job_object->restart_if_needed();
                    foreach ($drive_files as $drive_file_key => $drive_file) {
                        if ($drive_file->name == $extra_file_basename) {
                            if ($drive_file->md5Checksum != md5_file($extra_file)) {
                                $this->service->files->delete($drive_file->id);

                                $new_file = new Google_Service_Drive_DriveFile();
                                $new_file->setName($extra_file_basename);
                                $new_file->setMimeType(Buwd_File::mime_content_type($extra_file));
                                $new_file->setParents(array($sync_folder_id));
                                $this->service->files->create($new_file, array(
                                    'data'       => file_get_contents($extra_file),
                                    'uploadType' => 'multipart',
                                    'mimeType'   => Buwd_File::mime_content_type($extra_file)
                                ));

                                /*  $update_extra_file = new Google_Service_Drive_DriveFile();
                                $this->service->files->update($drive_file->id, $update_extra_file, array(
                                     'data'       => file_get_contents($extra_file),
                                     'uploadType' => 'multipart',
                                     'mimeType'   => Buwd_File::mime_content_type($extra_file)
                                 ));*/

                                $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                            }

                            $job_object->buwd_logger->set_log(sprintf(__('%s extra file was updated on Google Drive.', 'buwd'), $extra_file));
                            $job_object->update_progress();
                            continue 2;
                        }
                    }
                    $file = new Google_Service_Drive_DriveFile();
                    $file->setName($extra_file_basename);
                    $file->setMimeType(Buwd_File::mime_content_type($extra_file));
                    // $parent = new Google_Service_Drive_ParentReference();
                    // $parent->setId($sync_folder_id);
                    $file->setParents(array($sync_folder_id));
                    $res = $this->service->files->create($file, array(
                        'data'       => file_get_contents($extra_file),
                        'uploadType' => 'multipart',
                        'mimeType'   => Buwd_File::mime_content_type($extra_file)
                    ));
                    $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                    if ($res->size == filesize($extra_file)) {
                        $job_object->buwd_logger->set_log(sprintf(__('%s extra file was uploaded to Google Drive.', 'buwd'), $job_object->job['gdrivefolder'] . '/' . $extra_file_basename));
                        $job_object->update_progress();
                    }
                }
            }

            $sync_file = get_site_option('buwd-dest-gdrive-sync-' . $job_object->job_id, array());
            $sync_file['file'] = 'Synchronized';
            $sync_file['folder'] = $job_object->job['gdrivefolder'];
            $sync_file['time'] = current_time('timestamp', true);
            $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
            $sync_file['jid'] = $job_object->job_id;
            $sync_file['dest'] = 'gdrive';
            $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
            $sync_file['sync'] = 1;

            update_site_option('buwd-dest-gdrive-sync-' . $job_object->job_id, $sync_file);


        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Google Drive API: %s', 'buwd'), $e->getMessage()), $e->getFile(), $e->getLine(), E_USER_ERROR);
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    private function GDrive($config = array(), $redirectUri = null)
    {
        $client = new Google_Client($config);
        $client->setApplicationName(Buwd::get_plugin_data('name'));
        $client->setClientId($this->gd_client_id);
        $client->setClientSecret($this->gd_client_secret);
        $client->setScopes(array('https://www.googleapis.com/auth/drive'));
        if ($redirectUri) {
            $client->setRedirectUri($redirectUri);
        }
        $client->setAccessType('offline');

        return $client;
    }

    /**
     * Create folder if not exist
     *
     * @param $path folder path
     *              return folder id
     */
    private function get_folder_id($path)
    {
        $folder_id = 'root';
        if ($path != '/') {
            $folders = explode('/', trim($path, '/'));
            $current_path = '';

            foreach ($folders as $folder) {
                $folder_exist = false;
                $current_path .= '/' . $folder;

                $sub_folders = $this->service->files->listFiles(array('fields' => 'files', 'q' => "explicitlyTrashed = false and '" . $folder_id . "' in parents and mimeType = 'application/vnd.google-apps.folder'"));

                if ($sub_folders) {
                    foreach ($sub_folders as $sub_folder) {
                        if ($sub_folder->name == $folder) {
                            $folder_id = $sub_folder->id;
                            $folder_exist = true;
                        }
                    }

                }

                if (!$folder_exist) {
                    $folder_id = $this->create_folder($folder, $folder_id);
                }
            }
        }

        return $folder_id;
    }

    /**
     * Create new folder
     *
     * @param $folder_name
     * @param $parent_id
     *
     * @return folder id
     */
    private function create_folder($folder_name, $parent_id)
    {
        $folder = new Google_Service_Drive_DriveFile();
        $folder->setName($folder_name);
        $folder->setMimeType('application/vnd.google-apps.folder');

        if ($parent_id != 'root') {
            //            $parent = new Google_Service_Drive_ParentReference();
            //            $parent->setId($parent_id);
            $folder->setParents(array($parent_id));
        }

        try {
            $created_folder = $this->service->files->create($folder, array('mimeType' => 'application/vnd.google-apps.folder'));
        } catch (Exception $e) {

            die();
        }

        return $created_folder->id;
    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if ($field_name == 'gdrivefolder') {
                $field_value = sanitize_text_field($field_value);
                $field_value = untrailingslashit(str_replace(array('//', '\\'), '/', trim($field_value)));
                if (substr($field_value, 0, 1) != '/') {
                    $field_value = '/' . ($field_value);
                }
            }

            if ($field_name == 'gdrivefiledelete') {
                $field_value = (int)
                $field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
    }

    public function delete_file($backup)
    {
        $file_id = $backup['file_id'];
        $job_id = $backup['jid'];

        try {
            $client = $this->GDrive();
            $gdrivetoken = Buwd_Options::get($job_id, 'gdrivetoken');
            $client->refreshToken($gdrivetoken['refresh_token']);

            $this->service = new Google_Service_Drive($client);
            $this->service->files->delete($file_id);

        } catch (Exception $e) {

            //set_site_transient( 'buwd_backups_error', $e->getMessage() );
            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $file_id = $backup['file_id'];
        $job_id = $backup['jid'];
        $size = $backup['size'];

        try {
            $client = $this->GDrive();
            $gdrivetoken = Buwd_Options::get($job_id, 'gdrivetoken');
            $client->refreshToken($gdrivetoken['refresh_token']);

            $this->service = new Google_Service_Drive($client);
            $response = $this->service->files->get($file_id, array("alt" => "media"));

            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . Buwd_File::mime_content_type($backup['file']));
            header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $size);
            echo $response->getBody()->getContents();
            die();

        } catch (Exception $e) {
            set_site_transient('buwd_backups_error', $e->getMessage());
            Buwd_Helper::redirect(array('page' => 'buwd_backups'));
        }

    }

    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_gdrive_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_gdrive_error');
        } else if ($updated = get_site_transient('buwd_gdrive_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_gdrive_updated');
        }
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['gdrivetoken'])) {
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

?>