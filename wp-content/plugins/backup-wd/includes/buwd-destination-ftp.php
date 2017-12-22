<?php

/**
 *
 */
class Buwd_Destination_Ftp
{
    protected static $instance = null;
    public $errors = array();

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Transferring archive to FTP server', 'buwd');
        $this->info['name'] = __('FTP', 'buwd');
        $this->info['desc'] = __('', 'buwd');
        $this->info['sync'] = false;
    }

    public function defaults()
    {
        $defaults = array(
            'ftpserver'     => '',
            'ftpport'       => 21,
            'ftpuser'       => '',
            'ftppass'       => '',
            'ftpfolder'     => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
            'ftpfiledelete' => 15,
            'ftptimeout'    => 90,
            'ftpssl'        => '',
            'ftppmode'      => '1',
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        $options = array(
            'key'    => 'destination-folder',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('FTP server', 'buwd'),
                    'header' => __('FTP server and login', 'buwd'),
                    'id'     => 'ftpserver',
                    'name'   => 'ftpserver',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'ftpserver')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the FTP server where the backup files will be stored.</p>',
                    ),
                ),
                array(
                    'label' => __('Port', 'buwd'),
                    'id'    => 'ftpport',
                    'name'  => 'ftpport',
                    'type'  => 'number',
                    'class' => array(
                        'buwd-small-text',
                    ),
                    'value' => esc_html(Buwd_Options::get($job_id, 'ftpport')),
                    'hint'  => array(
                        'html' => '<p class="description">Specify the port for your FTP server. It is 21 by default, but it can be set to a different value on your web hosting.</p>',
                    ),
                ),
                array(
                    'label' => __('Username', 'buwd'),
                    'id'    => 'ftpuser',
                    'name'  => 'ftpuser',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => esc_html(Buwd_Options::get($job_id, 'ftpuser')),
                    'hint'  => array(
                        'html' => '<p class="description">Provide the username of your FTP account.</p>',
                    ),
                ),
                array(
                    'label' => __('Password', 'buwd'),
                    'id'    => 'ftppass',
                    'name'  => 'ftppass',
                    'type'  => 'password',
                    'class' => array(
                        'buwd-large-text',
                    ),
                    'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::get($job_id, 'ftppass'))),
                    'hint'  => array(
                        'html' => '<p class="description">Provide the password of your FTP account.</p>',
                    ),
                ),
                array(
                    'label'  => __('Folder to store', 'buwd'),
                    'header' => __('Backup settings', 'buwd'),
                    'id'     => 'ftpfolder',
                    'name'   => 'ftpfolder',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'ftpfolder')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the folder where the backup files will be uploaded.</p>',
                    ),
                ),
                array(
                    'label' => __('File deletion', 'buwd'),
                    'id'    => 'ftpfiledelete',
                    'name'  => 'ftpfiledelete',
                    'type'  => 'number', // to number
                    'class' => array(
                        'buwd-extra-small-text',
                    ),
                    'attr'  => array(
                        'min' => "0"
                    ),
                    'value' => esc_html(Buwd_Options::get($job_id, 'ftpfiledelete')),
                    'hint'  => array(
                        'html' => '<span>Number of files to keep in folder.</span><p class="description">Specify the maximum number of files in your FTP folder for backup. When the limit is reached, the oldest backup file will be deleted.</p>',
                    ),
                ),
                array(
                    'label'  => __('Timeout for FTP connection', 'buwd'),
                    'header' => __('FTP specific settings', 'buwd'),
                    'id'     => 'ftptimeout',
                    'name'   => 'ftptimeout',
                    'type'   => 'number',
                    'class'  => array(
                        'buwd-small-text',
                    ),
                    'value'  => esc_html(Buwd_Options::get($job_id, 'ftptimeout')),
                    'hint'   => array(
                        'html' => '<p class="description">Specify the timeout period for the FTP connection.</p>',
                    ),
                ),
                array(
                    'label'   => __('SSL-FTP connection', 'buwd'),
                    'id'      => 'ftpssl',
                    'name'    => 'ftpssl',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => 'Use explicit SSL-FTP connection.'
                    ),
                    'class'   => array(),
                    'value'   => Buwd_Options::get($job_id, 'ftpssl'),
                    'hint'    => array(
                        'html' => '<p class="description">Select this option in case your server uses explicit SSL-FTP connection.</p>',
                    ),
                ),
                array(
                    'label'   => __('FTP Passive Mode', 'buwd'),
                    'id'      => 'ftppmode',
                    'name'    => 'ftppmode',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => 'Use FTP Passive Mode.'
                    ),
                    'class'   => array(),
                    'value'   => Buwd_Options::get($job_id, 'ftppmode'),
                    'hint'    => array(
                        'html' => '<p class="description">Check this option to use FTP Passive mode. It can be helpful in case the FTP server fails to establish data channel connection. One of the reasons this happens is network firewalls.</p>',
                    ),
                ),

            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to send backup file to FTP server.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        if (!empty($job_object->job['ftpssl'])) {
            if (!function_exists('ftp_ssl_connect')) {
                $job_object->buwd_logger->set_log(__('PHP function to connect with explicit SSL-FTP to server does not exist.', 'buwd'), E_USER_ERROR);
                $job_object->update_progress();

                return true;
            }

            $ftp_connection = ftp_ssl_connect($job_object->job['ftpserver'], $job_object->job['ftpport'], $job_object->job['ftptimeout']);
            if (!$ftp_connection) {
                $job_object->buwd_logger->set_log(sprintf(__('Could not connect to %s server via explicit SSL-FTP.', 'buwd'), $job_object->job['ftpserver'] . ':' . $job_object->job['ftpport']), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

            $job_object->buwd_logger->set_log(sprintf(__('Connected to server via explicit SSL-FTP:', 'buwd'), $job_object->job['ftpserver'] . ':' . $job_object->job['ftpport']));
            $job_object->update_progress();

        } else {
            $ftp_connection = ftp_connect($job_object->job['ftpserver'], $job_object->job['ftpport'], $job_object->job['ftptimeout']);

            if (!$ftp_connection) {
                $job_object->buwd_logger->set_log(sprintf(__('Could not connect to FTP server: %s', 'buwd'), $job_object->job['ftpserver'] . ':' . $job_object->job['ftpport']), E_USER_ERROR);
                $job_object->update_progress();

                return false;
            }

            $job_object->buwd_logger->set_log(sprintf(__('Connected to FTP server: %s', 'buwd'), $job_object->job['ftpserver'] . ':' . $job_object->job['ftpport']));
            $job_object->update_progress();
        }

        $job_object->buwd_logger->set_log(sprintf(__('FTP client command: %s', 'buwd'), 'USER ' . $job_object->job['ftpuser']));
        $job_object->update_progress();

        $ftp_login = @ftp_login($ftp_connection, $job_object->job['ftpuser'], Buwd_Encrypt::decrypt($job_object->job['ftppass']));

        if ($ftp_login) {
            $job_object->buwd_logger->set_log(sprintf(__('FTP client response: %s logged in.', 'buwd'), 'USER ' . $job_object->job['ftpuser']));
            $job_object->update_progress();
        } else {
            $job_object->buwd_logger->set_log(sprintf(__('FTP client response: %s not connected.', 'buwd'), 'USER ' . $job_object->job['ftpuser']));
            $job_object->update_progress();

            return false;
        }

        if (!empty($job_object->job['ftpfolder'])) {
            $job_object->job['ftpfolder'] = trailingslashit(ftp_pwd($ftp_connection)) . trim($job_object->job['ftpfolder'], '/');
        } else {
            $job_object->job['ftpfolder'] = trailingslashit(ftp_pwd($ftp_connection));
        }

        if ($job_object->job['ftpfolder'] != '/') {
            $ftp_path = explode('/', trim($job_object->job['ftpfolder'], '/'));
            foreach ($ftp_path as $path) {
                $job_object->check_if_stopped();
                if (!@ftp_chdir($ftp_connection, $path)) {
                    if (!@ftp_mkdir($ftp_connection, $path)) {
                        $job_object->buwd_logger->set_log(sprintf(__('FTP Folder %s could not be created.', 'buwd'), $path), E_USER_ERROR);
                        $job_object->update_progress();

                        return false;
                    }

                    ftp_chdir($ftp_connection, $path);
                    $job_object->buwd_logger->set_log(sprintf(__('FTP Folder %s was created.', 'buwd'), $path));
                    $job_object->update_progress();
                }
            }
        }

        $current_path = ftp_pwd($ftp_connection);

        @clearstatcache();
        $job_object->buwd_logger->set_log(sprintf(__('FTP client command: %s', 'buwd'), 'PASV'));
        $job_object->update_progress();

        if (!empty($job_object->job['ftppmode'])) {
            if (ftp_pasv($ftp_connection, true)) {
                $job_object->buwd_logger->set_log(__('FTP server response: Entering passive mode', 'buwd'));
            } else {
                $job_object->buwd_logger->set_log(__('FTP server response: Can not enter passive mode', 'buwd'));
            }
            $job_object->update_progress();
        } else {
            if (ftp_pasv($ftp_connection, false)) {
                $job_object->buwd_logger->set_log(__('FTP server response: Entering normal mode', 'buwd'));
            } else {
                $job_object->buwd_logger->set_log(__('FTP server response: Can not enter normal mode', 'buwd'));
            }

            $job_object->update_progress();
        }

        ///////////////
        if ($job_object->steps_data[$job_object->current_step]['sub_step'] < $job_object->backup_file_size) {
            if ($fp = fopen($job_object->backup_folder . $job_object->backup_file, 'rb')) {

                fseek($fp, $job_object->steps_data[$job_object->current_step]['sub_step']);
                //async
                $ret = ftp_nb_fput($ftp_connection, $current_path . '/' . $job_object->backup_file, $fp, FTP_BINARY, $job_object->steps_data[$job_object->current_step]['sub_step']);
                while ($ret == FTP_MOREDATA) {
                    $job_object->check_if_stopped();
                    $job_object->steps_data[$job_object->current_step]['sub_step'] = ftell($fp);

                    $ret = ftp_nb_continue($ftp_connection);
                }
                if ($ret != FTP_FINISHED) {
                    $job_object->buwd_logger->set_log(__('Could not transfer backup to FTP server.', 'buwd'), E_USER_ERROR);
                    $job_object->update_progress();

                    return false;
                }

                $job_object->steps_data[$job_object->current_step]['sub_step'] = $job_object->backup_file_size + 1;

                $job_object->buwd_logger->set_log(sprintf(__('Backup was transferred to FTP server: %s', 'buwd'), $current_path . '/' . $job_object->backup_file));
                $job_object->update_progress();

                $last_file = array();
                $last_file['file'] = $job_object->backup_file;
                $last_file['folder'] = $current_path;
                $last_file['time'] = ftp_mdtm($ftp_connection, $job_object->backup_file);
                $last_file['size'] = ftp_size($ftp_connection, $job_object->backup_file);
                $last_file['jid'] = $job_object->job_id;
                $last_file['dest'] = 'ftp';
                $last_file['logfile'] = basename($job_object->buwd_logger->logfile);

                $dest_files = get_site_option('buwd-dest-ftp-' . $job_object->job_id, array());
                $dest_files[] = $last_file;

                update_site_option('buwd-dest-ftp-' . $job_object->job_id, $dest_files);

                fclose($fp);
            } else {
                $job_object->buwd_logger->set_log(__('Could not open source file for transfer.', 'buwd'));
                $job_object->update_progress();

                return false;
            }
        }

        $backup_files = array();
        if ($files_list = ftp_nlist($ftp_connection, ".")) {
            foreach ($files_list as $file) {
                if (basename($file) != '.' && basename($file) != '..') {
                    $fmdtm = ftp_mdtm($ftp_connection, $file);
                    if ($fmdtm) {
                        $backup_files[$fmdtm] = basename($file);
                    } else {
                        $backup_files[] = basename($file);
                    }
                }
            }

            $deleted = 0;
            $files_to_delete = array();
            if (!empty($job_object->job['ftpfiledelete']) && $job_object->job['ftpfiledelete'] > 0 && count($backup_files) > $job_object->job['ftpfiledelete']) {
                ksort($backup_files);
                while (count($backup_files) > $job_object->job['ftpfiledelete']) {
                    $file = array_shift($backup_files);
                    if (!ftp_delete($ftp_connection, $file)) {
                        $job_object->buwd_logger->set_log(sprintf(__('Could not delete %s file on FTP server.', 'buwd'), $current_path . $file), E_USER_ERROR);
                        $job_object->update_progress();
                    }
                    $deleted++;
                    $files_to_delete[] = basename($file);
                }
            }

            if (!empty($files_to_delete)) {
                Buwd_Options::backup_bulk_delete('buwd-dest-ftp-' . $job_object->job_id, $files_to_delete);
            }

            if ($deleted > 0) {
                $job_object->buwd_logger->set_log(sprintf(__('%d files were deleted from FTP server.', 'buwd'), $deleted));
                $job_object->update_progress();
            }
        }

        ftp_close($ftp_connection);

        return true;
    }

    public static function display_messages()
    {

    }

    public function save_form($job_id, $field_names)
    {
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if (in_array($field_name, array('ftpserver', 'ftpuser', 'ftppass', 'ftpfolder'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if ($field_name == 'ftppass') {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            if ($field_name == 'ftpserver' && strpos($field_value, 'ftp://') !== false) {
                $field_value = str_replace('ftp://', '', $field_value);
            }
            if ($field_name == 'ftpfolder') {
                $field_value = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($field_value))));
            }

            if (in_array($field_name, array('ftpport', 'ftptimeout'))) {
                $field_value = (int)$field_value;
            }

            Buwd_Options::update_job_option($job_id, $field_name, $field_value);
        }
    }

    public function delete_file($backup)
    {
        $job_id = $backup['jid'];
        $file = $backup['file'];
        $folder = $backup['folder'];
        //delete from folder

        $ftpssl = Buwd_Options::get($job_id, 'ftpssl');
        $ftpserver = Buwd_Options::get($job_id, 'ftpserver');
        $ftpport = Buwd_Options::get($job_id, 'ftpport');
        $ftptimeout = Buwd_Options::get($job_id, 'ftptimeout');

        $ftpuser = Buwd_Options::get($job_id, 'ftpuser');
        $ftppass = Buwd_Encrypt::decrypt(Buwd_Options::get($job_id, 'ftppass'));

        if (!empty($ftpssl) && function_exists('ftp_ssl_connect')) {
            $ftp_connection = ftp_ssl_connect($ftpserver, $ftpport, $ftptimeout);
        } else {
            $ftp_connection = ftp_connect($ftpserver, $ftpport, $ftptimeout);
        }

        if ($ftp_connection) {
            $ftp_login = @ftp_login($ftp_connection, $ftpuser, $ftppass);
            if ($ftp_login) {
                if (!ftp_delete($ftp_connection, $folder . '/' . $file)) {

                }
            }
        }

    }

    public function download_file($backup)
    {
        $job_id = $backup['jid'];
        $file = $backup['file'];
        $folder = $backup['folder'];

        //delete from folder

        $ftpssl = Buwd_Options::get($job_id, 'ftpssl');
        $ftpserver = Buwd_Options::get($job_id, 'ftpserver');
        $ftpport = Buwd_Options::get($job_id, 'ftpport');
        $ftptimeout = Buwd_Options::get($job_id, 'ftptimeout');

        $ftpuser = Buwd_Options::get($job_id, 'ftpuser');
        $ftppass = Buwd_Encrypt::decrypt(Buwd_Options::get($job_id, 'ftppass'));
        if (!empty($ftpssl) && function_exists('ftp_ssl_connect')) {
            $ftp_connection = ftp_ssl_connect($ftpserver, $ftpport, $ftptimeout);
        } else {
            $ftp_connection = ftp_connect($ftpserver, $ftpport, $ftptimeout);
        }


        if ($ftp_connection) {
            $ftp_login = @ftp_login($ftp_connection, $ftpuser, $ftppass);
            if ($ftp_login) {
                @set_time_limit(3000);
                nocache_headers();
                while (ob_get_level() > 0) {
                    @ob_end_clean();
                }
                header('Content-Description: File Transfer');
                header("Content-Type: application/octet-stream");
                header('Content-Disposition: attachment; filename="' . basename($backup['file']) . '"');

                $fp = fopen("php://output", 'w');
                if (!ftp_fget($ftp_connection, $fp, $folder . '/' . $file, FTP_BINARY)) {
                    //set error
                }
                fclose($fp);

                /*	header( 'Content-Transfer-Encoding: binary' );
                    */
                // header('Content-Length: ' . ob_get_length());
                die();
            }
        }

    }

    public function get_errors()
    {
        return $this->errors;
    }

    public function is_valid($job_options)
    {
        if (empty($job_options['ftpserver']) || empty($job_options['ftpuser']) || empty($job_options['ftppass'])) {
            return false;
        }

        if (!empty($job_options['ftpssl']) && function_exists('ftp_ssl_connect')) {
            $ftp_connection = ftp_ssl_connect($job_options['ftpserver'], $job_options['ftpport'], $job_options['ftptimeout']);
            $error_message = sprintf(__('Cannot connect via explicit SSL-FTP to server: %s', 'buwd'), $job_options['ftpserver'] . ':' . $job_options['ftpport']);
        } else {
            $ftp_connection = ftp_connect($job_options['ftpserver'], $job_options['ftpport'], $job_options['ftptimeout']);
            $error_message = sprintf(__('Cannot connect to FTP server: %s', 'buwd'), $job_options['ftpserver'] . ':' . $job_options['ftpport']);
        }

        if (!$ftp_connection) {
            $this->errors[] = $error_message;

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
