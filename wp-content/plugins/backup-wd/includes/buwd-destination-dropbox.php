<?php
require_once BUWD_DIR . '/vendor/autoload.php';

use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

class Buwd_Destination_Dropbox
{
    protected static $instance = null;
    public static $drop_job_object = null;
    private $sandbox_app_key = null;
    private $sandbox_secret_key = null;
    private $dropbox_app_key = null;
    private $dropbox_secret_key = null;
    private $dropbox = null;

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to Dropbox', 'buwd');
        $this->info['sync_title'] = __('Syncing files to Dropbox', 'buwd');
        $this->info['name'] = __('Dropbox', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = true;
        $this->info['tab_name'] = 'destination-dropbox';
    }

    public function defaults()
    {
        return array(
            'dboxtoken'          => array(),
            'dboxtype'           => 'sandbox',
            'dboxfolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'dboxfiledelete'     => 15,
            'dboxfiledeletesync' => '1',
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $this->set_keys();
        $disabled = '';
        $readonly = false;


        /*      if (!$this->is_keys_setted() && Buwd_Helper::get('tab') == $this->info['tab_name']) {
                  echo '<div class="buwd-messages">' . Buwd_Helper::message(sprintf(__('Dropbox API: Please configure Dropbox API keys on %s page of Backup WD plugin.', 'buwd'), '<a href="' . admin_url("admin.php?page=buwd_api_keys&tab=dropbox") . '" target="_blank">API Keys</a>'), 'error') . '</div>';

                  $disabled = 'buwd-disabled';
                  $readonly = 'readonly';
              }*/

        $dboxtoken = Buwd_Options::get($job_id, 'dboxtoken');
        $dboxtype = Buwd_Options::get($job_id, 'dboxtype');

        try {
            $sandbox_app = new DropboxApp($this->sandbox_app_key, $this->sandbox_secret_key, $dboxtoken);
            $dropbox_app = new DropboxApp($this->dropbox_app_key, $this->dropbox_secret_key, $dboxtoken);

            if ($dboxtoken && isset($_GET['auth_delete']) && (int)$_GET['auth_delete'] == 1) {
                if ($dboxtype == 'sandbox') {
                    $dropbox = new Dropbox($sandbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
                } else {
                    $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
                }

                $dropbox_auth_helper = $dropbox->getAuthHelper();
                $dropbox_auth_helper->revokeAccessToken();

                Buwd_Options::update_job_option($job_id, 'dboxtoken', array());
                Buwd_Options::update_job_option($job_id, 'dboxsandbox', '');
                Buwd_Options::update_job_option($job_id, 'dboxdropbox', '');
                Buwd_Options::update_job_option($job_id, 'dboxtype', 'sandbox');
            }

            $sandbox = new Dropbox($sandbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
            $sandbox_auth_helper = $sandbox->getAuthHelper();
            $sandbox_auth_url = $sandbox_auth_helper->getAuthUrl();

            $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
            $dropbox_auth_helper = $dropbox->getAuthHelper();
            $dropbox_auth_url = $dropbox_auth_helper->getAuthUrl();

        } catch (DropboxClientException $e) {
            set_site_transient('buwd_dbox_error', $e->getMessage());
            Buwd_Helper::redirect(array('page' => 'buwd_editjob', 'job_id' => $job_id, 'tab' => 'destination-dropbox'));
        }


        $dbox_sandbox_type = $dbox_dropbox_type = 'hidden_row';
        $dbox_dropbox_sep_text = '';
        if ($dboxtoken = Buwd_Options::get($job_id, 'dboxtoken')) {
            $auth_visibility = false;
            $auth_delation_href = add_query_arg(array(
                'job_id'      => $job_id,
                'auth_delete' => 1
            ), admin_url('admin.php?page=buwd_editjob&tab=destination-dropbox'));
            $auth_button = '<span class="auth">Authenticated</span><a class="dropbox-auth buwd-button" href="' . $auth_delation_href . '">Delete ' . ucfirst($dboxtype) . ' Authentication</a>';
            $dboxauth = 1;
        } else {
            if ($this->sandbox_app_key && $this->sandbox_secret_key) {
                $dbox_sandbox_type = 'text';
            }

            if ($this->dropbox_app_key && $this->dropbox_secret_key) {
                $dbox_dropbox_type = 'text';
                $dbox_dropbox_sep_text = '<p class="seperator">-OR-</p>';
            }

            $auth_visibility = true;
            $auth_button = '<span class="not-auth">Not Authenticated</span>';
            $dboxauth = 0;
        }

        $options = array(
            'key'    => 'destination-dbox',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Authenticate', 'buwd'),
                    'header' => __('Login', 'buwd'),
                    'id'     => 'dboxauth',
                    'name'   => 'dboxauth',
                    'type'   => 'hidden',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => $dboxauth,
                    'hint'   => array(
                        'html' => $auth_button . '<p class="description">Please create a <a href="https://www.dropbox.com/referrals/NTQ1MDE2MzQ4OQ?src=referrals_twitter9" target="_blank">Dropbox account</a>, in case you don’t have one.</p>'
                    ),
                ),
                array(
                    'label'      => __('App Access to Dropbox', 'buwd'),
                    'id'         => 'dboxsandbox',
                    'name'       => 'dboxsandbox',
                    'type'       => $dbox_sandbox_type,
                    'class'      => array(
                        'buwd-large-text',
                    ),
                    'attr'       => array(
                        'readonly' => $readonly
                    ),
                    'value'      => '',
                    'hint'       => array(
                        'html' => '<a class="buwd-button" href="' . $sandbox_auth_url . '" target="_blank">Get Dropbox App auth code</a><p class="description">Press <b>Get Dropbox App auth code</b> to let the plugin create a dedicated folder titled BackupWD in Apps folder of your Dropbox account. The plugin will have permissions to read and write files into this folder only. You can also specify a subfolder as backup destination, using <b>Folder in Dropbox</b> setting.</p>' . $dbox_dropbox_sep_text
                    ),
                    'visibility' => $auth_visibility
                ),
                array(
                    'label'      => __('Full Access to Dropbox', 'buwd'),
                    'id'         => 'dboxdropbox',
                    'name'       => 'dboxdropbox',
                    'type'       => $dbox_dropbox_type,
                    'class'      => array(
                        'buwd-large-text',
                    ),
                    'attr'       => array(
                        'readonly' => $readonly
                    ),
                    'value'      => '',
                    'hint'       => array(
                        'html' => '<a class="buwd-button" href="' . $dropbox_auth_url . '" target="_blank">Get Dropbox App auth code</a><p class="description">Press <b>Get Dropbox App auth code</b> to let the plugin create a dedicated folder titled BackupWD in Apps folder of your Dropbox account. The plugin will have permissions to read and write files into this folder only. You can also specify a subfolder as backup destination, using <b>Folder in Dropbox</b> setting.</p>'
                    ),
                    'visibility' => $auth_visibility
                ),
                array(
                    'label'  => __('Folder in Dropbox', 'buwd'),
                    'header' => __('Dropbox Backup settings', 'buwd'),
                    'id'     => 'dboxfolder',
                    'name'   => 'dboxfolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'attr'   => array(
                        'readonly' => $readonly
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'dboxfolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Set the title of your Dropbox folder, where the backup files will be uploaded.</p>',
                    ),
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'dboxfiledelete',
                    'name'       => 'dboxfiledelete',
                    'type'       => 'number', // to number
                    'class'      => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'       => array(
                        'min'      => "0",
                        'readonly' => $readonly
                    ),
                    'value'      => esc_html(Buwd_Options::get($job_id, 'dboxfiledelete')),
                    'hint'       => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in Dropbox folder for backup. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') == 'archive' ? true : false,
                ),
                array(
                    'label'      => __('File deletion', 'buwd'),
                    'id'         => 'dboxfiledeletesync',
                    'name'       => 'dboxfiledeletesync',
                    'type'       => 'checkbox', // to number
                    'class'      => array(),
                    'choices'    => array(
                        '1' => 'Keep deleted files from previous backup sync.'
                    ),
                    'attr'       => array(
                        str_replace('buwd-', '', $disabled) => str_replace('buwd-', '', $disabled)
                    ),
                    'value'      => Buwd_Options::get($job_id, 'dboxfiledeletesync'),
                    'visibility' => Buwd_Options::get($job_id, 'backup_type') != 'archive' ? true : false,
                    'hint'       => array(
                        'html' => '<p class="description">Uncheck the option to remove the deleted files.</p><span class="buwd-error">Please note that if you uncheck this option, you will loose all previous files in backup storage folder</span>'
                    ),
                ),
            ),
        );

        return $options;
    }

    public function run_ajax()
    {
        $auth_url = '';
        if (isset($_POST['type'])) {
            $this->set_keys();
            if (sanitize_text_field($_POST['type']) == 'sandbox') {
                $sandbox_app = new DropboxApp($this->sandbox_app_key, $this->sandbox_secret_key);
                $sandbox = new Dropbox($sandbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
                $sandbox_auth_helper = $sandbox->getAuthHelper();
                $auth_url = $sandbox_auth_helper->getAuthUrl();
            } else {
                $dropbox_app = new DropboxApp($this->dropbox_app_key, $this->dropbox_secret_key);
                $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
                $dropbox_auth_helper = $dropbox->getAuthHelper();
                $auth_url = $dropbox_auth_helper->getAuthUrl();
            }
        }

        echo $auth_url;
        die();
    }

    public function set_keys()
    {
        $this->sandbox_app_key = Buwd_Options::getSetting('drop_sandbox_key', 'bwqs2k8vx5l5uz1', true);
        $this->sandbox_secret_key = Buwd_Encrypt::decrypt(Buwd_Options::getSetting('drop_sandbox_secret', 'mpa3gaiiyat48p5', true));
        $this->dropbox_app_key = Buwd_Options::getSetting('drop_key');
        $this->dropbox_secret_key = Buwd_Encrypt::decrypt(Buwd_Options::getSetting('drop_secret_key'));
    }

    public function is_keys_setted()
    {
        if ((!empty($this->sandbox_app_key) && !empty($this->sandbox_secret_key)) || (!empty($this->dropbox_app_key) && !empty($this->dropbox_secret_key))) {
            return true;
        }

        return false;
    }

    public function get_footer()
    {
        $disabled = '';
        if (!$this->is_keys_setted()) {
            $disabled = 'buwd-disabled';
        }

        return '<button class="buwd-button button-save ' . $disabled . '" onclick="if(jQuery(this.form).valid()){this.form.submit()};"><span></span>Save</button>';
    }

    public function run(Buwd_Job $job_object)
    {
        $this->set_keys();
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to Dropbox.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();
        $job_object->check_if_stopped();

        $dboxtype = $job_object->job['dboxtype'];
        if ($dboxtype == 'sandbox') {
            $app_key = $this->sandbox_app_key;
            $secret_key = $this->sandbox_secret_key;
        } else {
            $app_key = $this->dropbox_app_key;
            $secret_key = $this->dropbox_secret_key;
        }

        $dboxtoken = Buwd_Options::get($job_object->job_id, 'dboxtoken');

        $job_object->update_progress();

        try {
            $dropbox_app = new DropboxApp($app_key, $secret_key, $dboxtoken);
            $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));

            $account = $dropbox->getCurrentAccount();
            $account_id = $account->getAccountId();

            if (!empty($account_id)) {
                $account_display_name = $account->getDisplayName();
                if ($job_object->buwd_logger->isdebug) {
                    $account_email = $account->getEmail();
                    $job_object->buwd_logger->set_log(sprintf(__('Dropbox user %s was authenticated.', 'buwd'), $account_display_name . ' (' . $account_email . ')'));
                    $job_object->update_progress();
                    //free spaces

                    if ($job_object->buwd_logger->isdebug) {
                        $space_usage = $dropbox->getSpaceUsage();
                        if (!empty($space_usage)) {
                            $dbox_free_spase = $space_usage['allocation']['allocated'] - $space_usage['used'];

                            if ($job_object->backup_file_size <= $dbox_free_spase) {
                                $job_object->buwd_logger->set_log(sprintf(__('There is %d storage space available on your Dropbox.', 'buwd'), size_format($dbox_free_spase, 2)));
                            } else {
                                $job_object->buwd_logger->set_log(sprintf(__('There is not enough storage space available on Dropbox. Available: %s.', 'buwd'), size_format($dbox_free_spase, 2)), E_USER_ERROR);

                                return true;
                            }
                        }
                    }
                } else {
                    $job_object->buwd_logger->set_log(sprintf(__('Dropbox user %s was authenticated.', 'buwd'), $account_display_name));
                    $job_object->update_progress();
                }
            } else {
                $job_object->buwd_logger->set_log(__('Dropbox is not authenticated.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }
            $job_object->buwd_logger->set_log(__('Upload to Dropbox has started.', 'buwd'));
            $job_object->update_progress();

            self::$drop_job_object = &$job_object;

            if ($job_object->steps_data[$job_object->current_step]['step_done'] < $job_object->backup_file_size) {
                $job_object->check_if_stopped();
                if ($uploaded_file = $dropbox->upload($job_object->backup_folder . $job_object->backup_file, '/' . $job_object->job['dboxfolder'] . $job_object->backup_file)) {
                    if ($uploaded_file->getSize() == $job_object->backup_file_size) {
                        $job_object->steps_data[$job_object->current_step]['step_done'] = $job_object->backup_file_size + 1;
                        $job_object->check_if_stopped();

                        $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to %s.', 'buwd'), $uploaded_file->getPathDisplay()));
                        $job_object->update_progress();

                        $last_file = array();
                        $UTC = new DateTimeZone("UTC");
                        $sm = new DateTime($uploaded_file->getServerModified());
                        $last_file['file'] = $uploaded_file->getName();
                        $last_file['folder'] = $job_object->job['dboxfolder'];
                        $last_file['time'] = (int)$sm->setTimezone($UTC)->getTimestamp();
                        $last_file['size'] = $uploaded_file->getSize();
                        $last_file['dboxtype'] = $dboxtype;
                        $last_file['dboxtoken'] = $job_object->job['dboxtoken'];
                        $last_file['jid'] = $job_object->job_id;
                        $last_file['dest'] = 'dropbox';
                        $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                        $dest_files = get_site_option('buwd-dest-dropbox-' . $job_object->job_id, array());
                        $dest_files[] = $last_file;

                        update_site_option('buwd-dest-dropbox-' . $job_object->job_id, $dest_files);

                    } else {
                        $job_object->buwd_logger->set_log(__('Uploaded file size and local file size do not match.', 'buwd'), E_USER_ERROR);
                        $job_object->update_progress();

                        return false;
                    }
                } else {
                    $job_object->buwd_logger->set_log(__('An error occurred while trying to transfer backup to Dropbox.', 'buwd'), E_USER_ERROR);
                    $job_object->update_progress();

                    return false;
                }
            }

            $backup_files = array();
            $folder_contents = $dropbox->listFolder('/' . untrailingslashit($job_object->job['dboxfolder']));

            $files = $folder_contents->getData();
            $deleted = 0;
            $files_to_delete = array();
            if (!empty($files['entries'])) {
                foreach ($files['entries'] as $key => $data) {
                    if ($data['.tag'] != 'folder') {
                        $backup_files[strtotime($data['server_modified'])] = $data['path_display'];
                    }
                }

                if (!empty($job_object->job['dboxfiledelete']) && $job_object->job['dboxfiledelete'] > 0 && count($backup_files) > $job_object->job['dboxfiledelete']) {
                    ksort($backup_files);
                    while (count($backup_files) > $job_object->job['dboxfiledelete']) {
                        $file = array_shift($backup_files);
                        $dropbox->delete($file);
                        $deleted++;
                        $files_to_delete[] = basename($file);
                    }
                }
            }

            if (!empty($files_to_delete)) {
                Buwd_Options::backup_bulk_delete('buwd-dest-dropbox-' . $job_object->job_id, $files_to_delete);
            }

            if ($deleted > 0) {
                $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from Dropbox.', 'buwd'), $deleted));
                $job_object->update_progress();
            }
        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Dropbox API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR);
            $job_object->update_progress();

            return false;
        }

        return true;
    }

    public function run_sync(Buwd_Job $job_object)
    {
        $this->set_keys();
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to sync files to Dropbox.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $dboxtype = $job_object->job['dboxtype'];
        if ($dboxtype == 'sandbox') {
            $app_key = $this->sandbox_app_key;
            $secret_key = $this->sandbox_secret_key;
        } else {
            $app_key = $this->dropbox_app_key;
            $secret_key = $this->dropbox_secret_key;
        }

        $dboxtoken = Buwd_Options::get($job_object->job_id, 'dboxtoken');
        try {
            $dropbox_app = new DropboxApp($app_key, $secret_key, $dboxtoken);
            $this->dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));

            $account = $this->dropbox->getCurrentAccount();
            $account_id = $account->getAccountId();

            if (!empty($account_id)) {
                $account_display_name = $account->getDisplayName();
                if ($job_object->buwd_logger->isdebug) {
                    $account_email = $account->getEmail();
                    $job_object->buwd_logger->set_log(sprintf(__('Dropbox user %s was authenticated.', 'buwd'), $account_display_name . ' (' . $account_email . ')'));
                    //free spaces

                    if ($job_object->buwd_logger->isdebug) {
                        $space_usage = $this->dropbox->getSpaceUsage();
                        if (!empty($space_usage)) {
                            $dbox_free_spase = $space_usage['allocation']['allocated'] - $space_usage['used'];
                            if ($job_object->backup_file_size <= $dbox_free_spase) {
                                $job_object->buwd_logger->set_log(sprintf(__('There is %s storage space available on your Dropbox.', 'buwd'), size_format($dbox_free_spase, 2)));
                            } else {
                                $job_object->buwd_logger->set_log(sprintf(__('There is not enough storage space available on Dropbox. Available: %s.', 'buwd'), size_format($dbox_free_spase, 2)), E_USER_ERROR);

                                return true;
                            }
                        }
                    }
                } else {
                    $job_object->buwd_logger->set_log(sprintf(__('Dropbox user %s was authenticated.', 'buwd'), $account_display_name));
                }
            } else {
                $job_object->buwd_logger->set_log(__('Dropbox is not authenticated.', 'buwd'), E_USER_ERROR);

                return false;
            }

            if (!isset($job_object->steps_data[$job_object->current_step]['files'])) {
                $job_object->steps_data[$job_object->current_step]['files'] = array();
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'drop_files';
                $job_object->steps_data[$job_object->current_step]['folder_key'] = -1;
                $job_object->steps_data[$job_object->current_step]['folder_size'] = 0;
            }

            $job_object->buwd_logger->set_log(__('Modified files were synced to Dropbox.', 'buwd'));

            $extra_files = array();
            if ($job_object->extra_files) {
                foreach ($job_object->extra_files as $extra_file) {
                    $extra_files[basename($extra_file)] = $extra_file;
                }
            }

            $files_folders = array();
            $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
            if (file_exists($files_folders_dir)) {
                $files_folders = unserialize(file_get_contents($files_folders_dir));
            }
            if (!isset($files_folders['files'])) {
                $files_folders['files'] = array();
            }

            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'drop_files') {
                try {
                    $drop_abs_folder = '/' . untrailingslashit($job_object->job['dboxfolder']) . '/';
                    $folder_contents = $this->dropbox->listFolder($drop_abs_folder, array('recursive' => true));
                    $dbox_folder_files = $folder_contents->getData();
                    $dbox_files = $dbox_folder_files['entries'];

                    if (!empty($dbox_files)) {
                        foreach ($dbox_files as $dbox_file) {
                            if ($dbox_file['.tag'] == 'file') {
                                $job_object->steps_data[$job_object->current_step]['files'][$dbox_file['path_display']] = $dbox_file;
                            }


                        }
                    }
                    while ($dbox_folder_files['has_more']) {
                        $folder_contents = $this->dropbox->listFolderContinue($dbox_folder_files['cursor']);
                        $dbox_folder_files = $folder_contents->getData();
                        $dbox_files = $dbox_folder_files['entries'];
                        if (!empty($dbox_files)) {
                            foreach ($dbox_files as $dbox_file) {
                                if ($dbox_file['.tag'] == 'file') {
                                    $job_object->steps_data[$job_object->current_step]['files'][$dbox_file['path_display']] = $dbox_file;
                                }
                            }
                        }
                    }

                } catch (Exception $e) {
                };
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_files';
            }


            // self::$drop_job_object = &$job_object;
            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_files') {
                if (isset($files_folders['folders'])) {
                    foreach ($files_folders['folders'] as $folder_key => $folder) {
                        if ($job_object->steps_data[$job_object->current_step]['folder_key'] >= $folder_key) {
                            continue;
                        }

                        if (!isset($job_object->steps_data[$job_object->current_step][$folder_key])) {
                            $job_object->steps_data[$job_object->current_step][$folder_key] = array();
                            $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = -1;
                        }

                        $folder_files = isset($files_folders['files'][$folder]) ? $files_folders['files'][$folder] : array();
                        foreach ($folder_files as $file_key => $f_file) {
                            if ($job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] >= $file_key) {
                                continue;
                            }

                            $job_object->check_if_stopped();
                            $job_object->restart_if_needed();
                            if (strpos($f_file, $job_object->job['abs_path']) !== false) {
                                $file_dirname = untrailingslashit('/' . $job_object->job['dboxfolder']) . '/' . substr($f_file, strlen($job_object->job['abs_path']));
                            } else {
                                $file_dirname = untrailingslashit('/' . $job_object->job['dboxfolder']) . '/' . substr($f_file, strlen(dirname($job_object->job['abs_path']) . '/'));
                            }

                            if (isset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname])) {

                                $dbox_file = $job_object->steps_data[$job_object->current_step]['files'][$file_dirname];
                                if ($dbox_file['content_hash'] !== $this->generateCheckSum($f_file)) {
                                    $uploaded_file = $this->dropbox->simpleUpload($f_file, $file_dirname, array('mode' => 'overwrite'));

                                    if ($uploaded_file->getSize() === filesize($f_file)) {
                                        $job_object->buwd_logger->set_log(sprintf(__('%s file was updated on Dropbox.', 'buwd'), $file_dirname));
                                        $job_object->update_progress();
                                    }
                                }
                            } else {

                                $uploaded_file = $this->dropbox->simpleUpload($f_file, $file_dirname, array('mode' => 'overwrite'));
                                if ($uploaded_file->getSize() === filesize($f_file)) {
                                    $job_object->buwd_logger->set_log(sprintf(__('%s file was uploaded to Dropbox.', 'buwd'), $file_dirname));
                                    $job_object->update_progress();
                                }
                            }

                            $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($f_file);
                            unset($job_object->steps_data[$job_object->current_step]['files'][$file_dirname]);

                            $job_object->steps_data[$job_object->current_step][$folder_key]['file_key'] = $file_key;
                        }

                        $job_object->steps_data[$job_object->current_step]['folder_key'] = $folder_key;
                    }
                }

                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'sync_extra_files';
            }


            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'sync_extra_files') {
                //add extra files
                if ($extra_files) {
                    foreach ($extra_files as $extra_file_basename => $extra_file) {
                        $extra_file_basename = untrailingslashit('/' . $job_object->job['dboxfolder']) . '/' . $extra_file_basename;
                        if (isset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_basename])) {
                            $dbox_file = $job_object->steps_data[$job_object->current_step]['files'][$extra_file_basename];
                            if ($dbox_file['content_hash'] !== $this->generateCheckSum($extra_file)) {
                                $uploaded_file = $this->dropbox->simpleUpload($extra_file, $extra_file_basename, array('mode' => 'overwrite'));

                                if ($uploaded_file->getSize() === filesize($extra_file)) {
                                    $job_object->buwd_logger->set_log(sprintf(__('%s extra file was updated on Dropbox.', 'buwd'), $extra_file_basename));
                                    $job_object->restart_if_needed();
                                    $job_object->update_progress();
                                }
                            }
                        } else {

                            $uploaded_file = $this->dropbox->simpleUpload($extra_file, $extra_file_basename, array('mode' => 'overwrite'));
                            if ($uploaded_file->getSize() === filesize($extra_file)) {
                                $job_object->buwd_logger->set_log(sprintf(__('%s extra file was uploaded to Dropbox.', 'buwd'), $extra_file_basename));
                                $job_object->restart_if_needed();
                                $job_object->update_progress();
                            }
                        }

                        $job_object->steps_data[$job_object->current_step]['folder_size'] += filesize($extra_file);
                        unset($job_object->steps_data[$job_object->current_step]['files'][$extra_file_basename]);
                    }
                }
                $job_object->steps_data[$job_object->current_step]['sub_step'] = 'delete_files';
            }

            if ($job_object->steps_data[$job_object->current_step]['sub_step'] == 'delete_files') {
                if (!$job_object->job['dboxfiledeletesync'] && !empty($job_object->steps_data[$job_object->current_step]['files'])) {
                    $job_object->buwd_logger->set_log(__('Non-existent files will be deleted from Dropbox.', 'buwd'));
                    $job_object->update_progress();
                    foreach ($job_object->steps_data[$job_object->current_step]['files'] as $dbox_key => $dbox_file) {
                        $job_object->check_if_stopped();
                        $job_object->restart_if_needed();

                        $this->dropbox->delete($dbox_file['path_display']);
                        unset($job_object->steps_data[$job_object->current_step]['files'][$dbox_key]);
                        $job_object->buwd_logger->set_log(sprintf(__('%s file was deleted from Dropbox.', 'buwd'), $dbox_file['path_display']));
                        $job_object->update_progress();
                    }
                }
            }

            $sync_file = get_site_option('buwd-dest-dropbox-sync-' . $job_object->job_id, array());
            $sync_file['file'] = 'Synchronized';
            $sync_file['folder'] = $job_object->job['dboxfolder'];
            $sync_file['size'] = $job_object->steps_data[$job_object->current_step]['folder_size'];
            $sync_file['dboxtype'] = $dboxtype;
            $sync_file['time'] = current_time('timestamp', true);
            $sync_file['jid'] = $job_object->job_id;
            $sync_file['dest'] = 'dropbox';
            $sync_file['logfile'] = basename($job_object->buwd_logger->logfile);
            $sync_file['sync'] = 1;
            update_site_option('buwd-dest-dropbox-sync-' . $job_object->job_id, $sync_file);

        } catch (Exception $e) {
            $job_object->buwd_logger->set_log(sprintf(__('Dropbox API: %s', 'buwd'), $e->getMessage()), E_USER_ERROR);

            return false;
        }

        return true;
    }


    /**
     * @param $file
     *
     * @return string
     *
     * generating content hash of file fro DropBox
     * @link https://www.dropbox.com/developers/reference/content-hash DropBox documentation
     */
    private function generateCheckSum($file)
    {
        $handle = fopen($file, 'r');
        $content_bin = '';
        while (!feof($handle)) {
            $chunk = fread($handle, 4 * 1024 * 1024);
            $chunk_hash = hash('SHA256', $chunk);
            $chunk_bin = hex2bin($chunk_hash);
            $content_bin .= $chunk_bin;
        }

        return hash('SHA256', $content_bin);
    }

    public static function display_messages()
    {
        if ($error = get_site_transient('buwd_dbox_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_dbox_error');
        } else if ($updated = get_site_transient('buwd_dbox_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_dbox_updated');
        }

    }

    /**
     * @param $job_id
     * @param $field_names
     */
    public function save_form($job_id, $field_names)
    {
        $this->set_keys();

        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';


            if ($field_name == 'dboxauth' || $field_name == 'dboxfiledelete') {
                $field_value = (int)$field_value;
            }

            if ($field_name == 'dboxsandbox' || $field_name == 'dboxdropbox') {
                $field_value = sanitize_text_field($field_value);
                if ($field_value != '') {
                    try {
                        if ($field_name == 'dboxsandbox') {
                            $dropbox_app = new DropboxApp($this->sandbox_app_key, $this->sandbox_secret_key);
                            Buwd_Options::update_job_option($job_id, 'dboxtype', 'sandbox');
                        } else {
                            $dropbox_app = new DropboxApp($this->dropbox_app_key, $this->dropbox_secret_key);
                            Buwd_Options::update_job_option($job_id, 'dboxtype', 'dropbox');
                        }

                        $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));
                        $dropbox_auth_helper = $dropbox->getAuthHelper();

                        $accesstoken = $dropbox_auth_helper->getAccessToken($field_value);
                        $dropboxtoken = $accesstoken->getToken();


                        Buwd_Options::update_job_option($job_id, 'dboxtoken', $dropboxtoken);
                    } catch (exception $e) {
                        set_site_transient('buwd_dbox_error', __('Dropbox:', 'buwd') . $e->getMessage());

                        return false;
                    }
                }


            }

            if ($field_name == 'dboxfolder') {
                $field_value = sanitize_text_field($field_value);
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($field_value))));
                if ($field_value === '/') {
                    $field_value = '';
                } else {
                    if (substr($field_value, 0, 1) == '/') {
                        $field_value = substr($field_value, 1);
                    }
                }
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }

    }

    public function delete_file($backup)
    {
        $this->set_keys();
        $file = $backup['file'];
        $folder = $backup['folder'];
        $dboxtype = $backup['dboxtype'];
        $dboxtoken = $backup['dboxtoken'];

        if ($dboxtype == 'sandbox') {
            $app_key = $this->sandbox_app_key;
            $secret_key = $this->sandbox_secret_key;
        } else {
            $app_key = $this->dropbox_app_key;
            $secret_key = $this->dropbox_secret_key;
        }

        //delete from folder
        try {
            $dropbox_app = new DropboxApp($app_key, $secret_key, $dboxtoken);
            $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));

            if (!$dropbox->delete('/' . $folder . $file)) {
                return false;
            }
        } catch (Exception $e) {

            return false;
        }

        return true;
    }

    public function download_file($backup)
    {
        $this->set_keys();
        $file = $backup['file'];
        $folder = $backup['folder'];
        $size = $backup['size'];
        $dboxtype = $backup['dboxtype'];
        $dboxtoken = $backup['dboxtoken'];

        if ($dboxtype == 'sandbox') {
            $app_key = $this->sandbox_app_key;
            $secret_key = $this->sandbox_secret_key;
        } else {
            $app_key = $this->dropbox_app_key;
            $secret_key = $this->dropbox_secret_key;
        }

        try {
            $dropbox_app = new DropboxApp($app_key, $secret_key, $dboxtoken);
            $dropbox = new Dropbox($dropbox_app, array('cacert' => Buwd::get_plugin_data('cacert')));

            $file_download = $dropbox->download('/' . $folder . $file);
            $contents = $file_download->getContents();


            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            // Buwd_Helper::redirect(array('page'=>'buwd_backups'));

            header('Content-Description: File Transfer');
            header("Content-Type: " . Buwd_File::mime_content_type($backup['file']));
            header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');
            header('Content-Transfer-Encoding: binary');
            header('Content-Length: ' . $size);
            echo $contents;
            die();

        } catch (Exception $e) {
            echo $e->getMessage();

            return false;
        }

    }

    public function is_valid($job_options)
    {
        if (empty($job_options['dboxtoken'])) {
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