<?php

/**
 *
 */
class Buwd_Job
{
    /**
     * @var
     */
    public $job_id = 0;
    /**
     * @var array of the job options
     */
    public $job = array();

    public $job_start_time = null;

    /**
     * @var array of the job run steps
     */
    public $steps = array();

    public $steps_data = array();

    public $max_step_try = null;

    public $current_step = null;

    public $buwd_logger = null;

    public $done_steps = array();

    public $folders = array();

    public $files = array();

    public $extra_files = array();

    public $files_exclude = array();

    public $backup_folder = '';

    public $backup_file = '';

    public $backup_file_size = 0;

    public $loglevel = '';

    public $isdebug = false;

    public $folder_count = 0;

    public $file_count = 0;

    public $log_file_name = '';

    public $error = false;

    public $stopped = false;

    private $log_data = array();

    public static function setup($run_type = 'run', $job_id)
    {
        if ($run_type == 'run') {
            $buwd_job = new self();
            $buwd_job->create($job_id);
            $buwd_job->run();
        }
        if ($run_type == 'restart') {
            //$buwd = get_site_option( 'buwd_restart' );
            $buwd = self::get_job_object_file_content();
            $buwd->run();
        }
    }

    private function create($job_id = 0)
    {
        if (!$job_id) {
            return false;
        }

        $this->job_id = $job_id;
        $this->job_start_time = current_time('timestamp', true);
        $this->job = Buwd_Options::get_job($this->job_id);
        $this->max_step_try = Buwd_Options::getSetting('job_step_max');

        //add job types
        $job_types = Buwd::get_job_types();
        if ($job_types) {
            foreach ($job_types as $id => $job_type) {
                if (is_array($this->job['type']) && in_array($id, $this->job['type'], true)) {
                    $this->steps[] = 'job_' . $id;
                    $this->steps_data['job_' . $id]['title'] = $job_type->info['title'];
                    $this->steps_data['job_' . $id]['step'] = 0;
                }
            }
        }


        $this->steps[] = 'create_config';
        $this->steps_data['create_config']['name'] = __('Generating config file', 'buwd');
        $this->steps_data['create_config']['step'] = 0;

        if ($this->job['backup_type'] == 'archive') {
            if (is_array($this->job['destination']) && in_array('folder', $this->job['destination'], true)) {
                $this->backup_folder = str_replace('{hash_key}', Buwd::get_plugin_data('hash'), $this->job['folderpath']);
                //check backup folder

                if (!empty($this->backup_folder)) {
                    $this->backup_folder = Buwd_File::get_absolute_path($this->backup_folder);
                    $this->job['folderpath'] = $this->backup_folder;
                }

            }
            //set temp folder to backup folder if not set
            if (!$this->backup_folder || $this->backup_folder == '/') {
                $this->backup_folder = Buwd::get_plugin_data('temp_folder_dir');
            }

            //Create backup archive file name
            $this->backup_file = self::gen_file_name(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), $this->job['archive_name']), $this->job['archive_format']);

            //add archive create
            $this->steps[] = 'create_archive';
            $this->steps_data['create_archive']['title'] = __('Creating archive', 'buwd');
            $this->steps_data['create_archive']['step'] = 0;
            $this->steps_data['create_archive']['sub_step'] = 0;
        }

        if ($destinations = Buwd::get_destinations()) {
            if (isset($destinations['folder'])) {
                $dest_folder = $destinations['folder'];
                unset($destinations['folder']);
                $destinations = array('folder' => $dest_folder) + $destinations;
            }

            foreach ($destinations as $id => $dest) {
                if (!is_array($this->job['destination']) || !in_array($id, $this->job['destination'], true)) {
                    continue;
                }
                $destination = Buwd::get_destination($id);
                if ($destination->is_valid($this->job)) {
                    if ($this->job['backup_type'] == 'archive') {
                        $this->steps[] = 'dest_' . $id;
                        $this->steps_data['dest_' . $id]['title'] = $dest->info['title'];
                        $this->steps_data['dest_' . $id]['step'] = 0;
                        $this->steps_data['dest_' . $id]['sub_step'] = 0;
                        $this->steps_data['dest_' . $id]['step_done'] = 0;
                    } else {
                        if ($dest->info['sync']) {
                            $this->steps[] = 'dest_sync_' . $id;
                            $this->steps_data['dest_sync_' . $id]['title'] = $dest->info['sync_title'];
                            $this->steps_data['dest_sync_' . $id]['step'] = 0;
                            $this->steps_data['dest_sync_' . $id]['sub_step'] = 0;
                            $this->steps_data['dest_sync_' . $id]['step_done'] = 0;
                        }
                    }
                } else {

                }
            }
        }

        $this->steps[] = 'end_run';
        $this->steps_data['end_run']['title'] = __('Run has finished', 'buwd');
        $this->steps_data['end_run']['step'] = 0;


        $this->files_exclude = explode(',', $this->job['exclude_types']);
        $this->files_exclude = array_unique($this->files_exclude);
        $this->files_exclude[] = str_replace('{hash_key}', Buwd::get_plugin_data('hash'), $this->job['folderpath']);

        Buwd_Options::update_job_option($this->job_id, 'lastrun', $this->job_start_time);

        //protect temp folder
        $folder_protect = Buwd_Options::getSetting('folder_protect');
        if (!empty($folder_protect)) {
            Buwd_File::protect_folder(Buwd::get_plugin_data('temp_folder_dir'));
        }

        //create log file
        $this->create_log();
        $this->write_job_object_file();
        $this->done_steps[] = 'create';
    }

    public function create_log()
    {
        $this->buwd_logger = new Buwd_Logger();

        Buwd_Options::update_job_option($this->job_id, 'last_log_file', $this->buwd_logger->log_file_name . '.html');

        $data = array(
            'title'         => sprintf(__('Backup WD log for "%1$s" job from %2$s at %3$s', 'buwd'), $this->job['name'], date_i18n(get_site_option('date_format')), date_i18n(get_site_option('time_format'))),
            'charset'       => get_bloginfo('charset'),
            'metadata'      => array(
                'plugin'          => "BackupWD " . Buwd::get_plugin_data('version'),
                'date'            => date('c', current_time('timestamp', true)),
                'job_id'          => $this->job_id,
                'job_name'        => esc_attr($this->job['name']),
                'job_type'        => implode(',', $this->job['type']),
                'backup_filesize' => '0',
                'job_runtime'     => '0',
                'status'          => '',
                'log_summary'     => '',
            ),
            'dynamic_metas' => array(
                'backup_filesize',
                'job_runtime',
                'status',
                'log_summary',
            ),
            'info'          => array(
                sprintf(__('%1$s %2$s', 'buwd'), Buwd::get_plugin_data('name'), Buwd::get_plugin_data('version')),
                sprintf(__('PHP version: %1$s, PHP SAPI: %2$s, OS: %3$s (%4$d bit)', 'buwd'), PHP_VERSION, PHP_SAPI, PHP_OS, (PHP_INT_SIZE * 8)),
                sprintf(__('Job Name: %1$s, ID: %2$s', 'buwd'), $this->job['name'], $this->job_id),
                sprintf(__('Log Level: %1$s', 'buwd'), $this->buwd_logger->loglevel)
            ),
        );

        $log_info = array();
        if ($this->buwd_logger->isdebug) {
            $log_info[] = sprintf(__('Log file is: %s', 'buwd'), $this->buwd_logger->logfile);
            if ($this->job['backup_type'] == 'archive') {
                $log_info[] = sprintf(__('Backup file is: %s', 'buwd'), $this->backup_folder . $this->backup_file);
            }

            if ($this->job['schedule'] == 'lotus' || $this->job['schedule'] == 'wpcron' || $this->job['schedule'] == 'easycron') {
                $cron_labels = array(
                    'lotus'    => 'Lotus Cron',
                    'wpcron'   => 'Wordpress Cron',
                    'easycron' => 'EasyCron',
                );
                $log_info[] = sprintf(__('Job started from %s', 'buwd'), $cron_labels[$this->job['schedule']]);

                $cron_expression = $this->job['cron_expression'];
                $next_run = Buwd_Cron::next_run($cron_expression);

                $log_info[] = sprintf(__('Next run time %s', 'buwd'), $next_run);
            } else if ($this->job['schedule'] == 'manually') {
                $log_info[] = __('Job started manually', 'buwd');
            } else if ($this->job['schedule'] == 'link') {
                $log_info[] = __('Job started from external url', 'buwd');
            }


        } else {
            $log_info[] = sprintf(__('Log file is: %s', 'buwd'), basename($this->buwd_logger->logfile));

            if ($this->job['backup_type'] == 'archive') {
                $log_info[] = sprintf(__('Backup file is: %s', 'buwd'), $this->backup_file);
            }
        }

        $data['info'] = array_merge($data['info'], $log_info);
        $log_data = $this->buwd_logger->render_html($data);
        $this->buwd_logger->put($log_data);
    }

    public function add_actions()
    {
        add_action('buwd_cron', array('Buwd_Cron', 'run'));
        add_action('shutdown', array($this, 'buwd_shutdown'));
    }

    public function set_runtime_configs()
    {
        @ini_set('display_errors', '0');
        @ini_set('log_errors', '1');
        @ini_set('html_errors', '0');
        @ini_set('zlib.output_compression', '0');
        @ini_set('memory_limit', apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT));
        $memory_limit = (int)apply_filters('admin_memory_limit', WP_MAX_MEMORY_LIMIT);
        if ($memory_limit < 1204) {
            @ini_set('memory_limit', '5000M');
        }
        @ini_set('max_execution_time', Buwd_Options::getSetting('max_exec_time'));

        if ($this->buwd_logger->isdebug) {
            @ini_set('error_log', $this->buwd_logger->logfile);
            error_reporting(-1);
        }

        if (!empty($this->buwd_logger->logfile)) {
            if ($this->buwd_logger->isdebug) {
                set_error_handler(array($this->buwd_logger, 'set_log'));
            } else {
                set_error_handler(array($this->buwd_logger, 'set_log'), E_ALL ^ E_NOTICE);
            }
        }
        set_exception_handler(array($this, 'exception_handler'));
    }

    public function run()
    {
        $this->set_runtime_configs();

        $this->add_actions();

        $job_types = Buwd::get_job_types();
        $destinations = Buwd::get_destinations();

        $files_folders = array();
        $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
        if (file_exists($files_folders_dir)) {
            $files_folders = unserialize(file_get_contents($files_folders_dir));
        }
        $step_done = false;
        if ($this->steps) {
            $this->steps_data['job_start_time'] = microtime(true);//current_time('timestamp');

            foreach ($this->steps as $this->current_step) {
                if (in_array($this->current_step, $this->done_steps)) {
                    continue;
                }

                while (true) {
                    if ($this->steps_data[$this->current_step]['step'] >= $this->max_step_try) {
                        $this->done_steps[] = $this->current_step;
                        $this->buwd_logger->set_log(__('Maximum number of retries has been exceeded. Step was aborted.', 'buwd'), E_USER_ERROR);
                        break;
                    }

                    $this->update_progress();

                    $this->steps_data[$this->current_step]['step']++;
                    if (strpos($this->current_step, 'end_run') !== false) {
                        $step_done = $this->end_run();
                        break 2;
                    } else if (strpos($this->current_step, 'job_') !== false) {
                        $step_done = $job_types[str_replace('job_', '', $this->current_step)]->run($this);
                    } else if ($this->current_step == 'create_config') {
                        $step_done = $this->create_config_file();
                    } else if ($this->current_step == 'create_archive') {
                        if (!isset($files_folders['files']) || !empty($this->extra_files)) {
                            $step_done = $this->create_archive();
                        } else {
                            //$step_done = false;
                            // log
                        }
                    } else if (strpos($this->current_step, 'dest_sync_') !== false) {
                        $step_done = $destinations[str_replace('dest_sync_', '', $this->current_step)]->run_sync($this);
                    } else if (strpos($this->current_step, 'dest_') !== false) {
                        $step_done = $destinations[str_replace('dest_', '', $this->current_step)]->run($this);
                    }

                    if ($step_done) {
                        update_site_option('buwd_step_done', $this->current_step);
                        $this->done_steps[] = $this->current_step;
                        break;
                    }
                }
            }
        }
    }

    public function update_progress()
    {
        $done_count = count($this->done_steps);
        $total_steps = count($this->steps);
        $log = end($this->buwd_logger->log_data);
        $error = $this->buwd_logger->error;

        $progress['progress'] = round(($done_count * 100) / $total_steps);
        //$progress['current_step'] = $this->job_steps_messages[$this->current_step];
        $progress['current_step'] = isset($this->steps_data[$this->current_step]['title']) ? $this->steps_data[$this->current_step]['title'] : '';
        $progress['log'] = $log;
        $progress['error'] = $error;
        $progress['stop'] = $this->stopped;
        $progress['end'] = false;
        if ($this->current_step == 'end_run') {
            $progress['end'] = true;
        }


        update_site_option('buwd_progress', $progress);
    }

    public function end_run()
    {
        //unlink(Buwd::get_plugin_data('temp_folder_dir') . 'job_object.php');
        remove_action('shutdown', array($this, 'buwd_shutdown'));
        $this->delete_folder(Buwd::get_plugin_data('temp_folder_dir'));
        delete_site_option('buwd_job_running');
        delete_site_option('buwd_progress');


        if (get_site_transient('buwd_message_show') != 1 && !$this->stopped) {
            set_site_transient('buwd_jobs_updated', array(__('Job is completed', 'buwd')));
        }
        delete_site_transient('buwd_message_show');


        /*if((current_time('timestamp', true) - $this->job_start_time)<3) {
            set_site_transient('buwd_jobs_updated', array(__('Job Completed', 'buwd')));
        }*/
        if (!$this->buwd_logger->error && !$this->stopped) {
            $this->buwd_logger->set_log(sprintf(__('Job was done in %s seconds.', 'buwd'), current_time('timestamp', true) - $this->job_start_time));
            $this->update_progress();
        }

        $this->buwd_logger->log_summary();
        $this->update_progress();
        $log_summary = $this->buwd_logger->log_summary;
        $status = !$this->buwd_logger->error ? __('OK', 'buwd') : __('Failed', 'buwd');
        $status = $this->buwd_logger->stopped ? __('Stopped', 'buwd') : $status;

        if (!empty($this->buwd_logger->logfile)) {
            $replaced = 0;
            if ($fd = fopen($this->buwd_logger->logfile, 'r+')) {
                $file_pos = ftell($fd);
                while (!feof($fd)) {
                    $line = fgets($fd);
                    if (strpos($line, '<meta name="backup_filesize"') !== false) {
                        $line = str_pad('<meta name="backup_filesize" content="' . $this->backup_file_size . '" />', 120) . PHP_EOL;
                        fseek($fd, $file_pos);
                        fwrite($fd, $line);
                        $replaced++;
                    }


                    $job_run_time = ceil(microtime(true) - $this->job_start_time);

                    if (strpos($line, '<meta name="job_runtime"') !== false) {
                        $line = str_pad('<meta name="job_runtime" content="' . $job_run_time . '" />', 120) . PHP_EOL;
                        fseek($fd, $file_pos);
                        fwrite($fd, $line);
                        $replaced++;
                    }


                    if (strpos($line, '<meta name="status"') !== false) {
                        $line = str_pad('<meta name="status" content="' . $status . '" />', 120) . PHP_EOL;
                        fseek($fd, $file_pos);
                        fwrite($fd, $line);
                        $replaced++;
                    }

                    if (strpos($line, '<meta name="log_summary"') !== false) {
                        $line = str_pad('<meta name="log_summary" content="' . $log_summary . '" />', 120) . PHP_EOL;
                        fseek($fd, $file_pos);
                        fwrite($fd, $line);
                        $replaced++;
                    }


                    if ($replaced > 3) {
                        break;
                    }

                    $file_pos = ftell($fd);
                }

                fclose($fd);
            }

            $max_log_files = (int)Buwd_Options::getSetting('max_log_files');
            $log_dir_path = dirname($this->buwd_logger->logfile);

            foreach (new DirectoryIterator($log_dir_path) as $fileInfo) {
                if ($fileInfo->isDot() || $fileInfo->isDir())
                    continue;

                $log_files[$fileInfo->getATime()] = $fileInfo->getBasename();
            }

            ksort($log_files);

            foreach ($log_files as $key => $log_file) {
                if (count($log_files) <= $max_log_files) {
                    break;
                }
                unset($log_files[$key]);
                unlink($log_dir_path . '/' . $log_file);
            }

        }

        if (!empty($this->job['send_email'])) {
            $recipient = explode(',', Buwd_Options::getSetting('recipient'));
            $from = Buwd_Options::getSetting('from');
            $email_from = Buwd_Options::getSetting('email_from');
            $subject = Buwd_Options::getSetting('subject');
            $params = array(
                'recipient'  => $recipient,
                'from'       => $from,
                'email_from' => $email_from,
                'subject'    => $subject,
                'body'       => __('Please see attached file.', 'buwd'),
                'attachment' => $this->buwd_logger->logfile,
            );

            Buwd_Email::send($params);
        }

    }

    public static function do_not_show_message()
    {
        set_site_transient('buwd_message_show', 1);

    }

    public static function gen_file_name($file_name, $file_format = '')
    {
        $file_name = str_replace('{hash_key}', Buwd::get_plugin_data('hash'), $file_name);
        $local_time = current_time('timestamp', true);
        $date_formats = array_keys(Buwd_Helper::date_formats());
        $date_values = array();
        foreach ($date_formats as $date_format) {
            $date_values[] = date(str_replace('%', '', $date_format), Buwd_Helper::get_user_time($local_time)->getTimestamp());
        }

        if ($file_format == 'tar_gzip') {
            $file_format = 'tar.gz';
        }

        if ($file_format == 'tar_bzip2') {
            $file_format = 'tar.bz2';
        }
        if ($file_format == '') {
            $file_name = str_replace($date_formats, $date_values, Buwd_File::sanitize_filename($file_name));
        } else {
            $file_name = str_replace($date_formats, $date_values, Buwd_File::sanitize_filename($file_name)) . '.' . $file_format;
        }

        return $file_name;
    }


    private function delete_folder($dir)
    {
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if ('.' === $file || '..' === $file) {
                    continue;
                }
                if (is_dir($dir . $file)) {
                    $this->delete_folder($dir . $file);
                } else {
                    if (is_file($dir . $file)) {
                        @unlink($dir . $file);
                    }
                }
            }

            @rmdir($dir);
        }

        return true;
    }


    private function create_config_file()
    {
        $configs = array();
        $configs['job'] = $this->job;

        if (!file_put_contents(Buwd::get_plugin_data('temp_folder_dir') . 'buwd_config.json', json_encode($configs))) {
            return false;
        }

        $this->extra_files[] = Buwd::get_plugin_data('temp_folder_dir') . 'buwd_config.json';

        return true;
    }

    private function create_archive()
    {
        $this->check_if_stopped();
        $this->buwd_logger->set_log(sprintf(__('%d. Attempted to create backup archive.', 'buwd'), $this->steps_data[$this->current_step]['step']));
        $this->update_progress();

        if (!isset($this->steps_data[$this->current_step]['folder_key'])) {
            $this->steps_data[$this->current_step]['folder_key'] = -1;
            $this->steps_data[$this->current_step]['file_key'] = -1;
        }


        if ($this->steps_data[$this->current_step]['folder_key'] == -1 && $this->steps_data[$this->current_step]['file_key'] == -1 && is_file(Buwd::get_plugin_data('temp_folder_dir') . $this->backup_file)) {
            unlink(Buwd::get_plugin_data('temp_folder_dir') . $this->backup_file);
        }

        $files_folders = array();
        $files_folders_dir = Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php';
        if (file_exists($files_folders_dir)) {
            $files_folders = unserialize(file_get_contents($files_folders_dir));
        }
        if (!isset($files_folders['files'])) {
            $files_folders['files'] = array();
        }

        try {
            $archive_class = new Buwd_Archive(Buwd::get_plugin_data('temp_folder_dir') . $this->backup_file, $this->job['archive_format']);
            $archive_method = $archive_class->get_method();
            if (!isset($this->job['abs_path'])) {
                $this->job['abs_path'] = Buwd::get_plugin_data('home_path');
            }

            if ($this->extra_files) {
                if ($this->steps_data[$this->current_step]['sub_step'] == 1
                ) {
                    $this->buwd_logger->set_log(__('Extra files have been added to backup archive.', 'buwd'));
                    $this->update_progress();
                }

                foreach ($this->extra_files as $extra_file) {
                    $this->restart_if_needed();

                    if (strpos($extra_file, Buwd::get_plugin_data('temp_folder_dir')) !== false) {
                        $f_dirname = substr($extra_file, strlen(Buwd::get_plugin_data('temp_folder_dir')));
                    } else {
                        $f_dirname = substr($extra_file, strlen($this->job['abs_path']));
                    }

                    if (!is_dir($extra_file)) {
                        if ($archive_class->add_file($extra_file, $f_dirname)) {
                            $this->check_if_stopped();
                        } else {
                            unset($archive_class);
                            $this->buwd_logger->set_log(__('Could not create the backup archive correctly.', 'buwd'), E_USER_ERROR);
                            $this->update_progress();

                            return false;
                        }
                    }
                }
                $this->steps_data[$this->current_step]['sub_step']++;
            }
            $files_count = 1;
            if (isset($files_folders['folders'])) {
                foreach ($files_folders['folders'] as $folder_key => $folder) {
                    if ($this->steps_data[$this->current_step]['folder_key'] >= $folder_key) {
                        continue;
                    }

                    $this->restart_if_needed();

                    if (!isset($this->steps_data[$this->current_step][$folder_key])) {
                        $this->steps_data[$this->current_step][$folder_key] = array();
                        $this->steps_data[$this->current_step][$folder_key]['file_key'] = -1;
                    }

                    $this->buwd_logger->set_log(sprintf(__('%s folder has been compressed as an archive.', 'buwd'), $folder));
                    $this->update_progress();

                    if ($archive_class->add_empty_folder(substr($folder, strlen($this->job['abs_path'])))) {

                    }

                    $files = isset($files_folders['files'][$folder]) ? $files_folders['files'][$folder] : array();

                    foreach ($files as $file_key => $file) {
                        $files_count++;
                        if ($this->steps_data[$this->current_step][$folder_key]['file_key'] >= $file_key
                        ) {
                            continue;
                        }

                        $this->restart_if_needed();

                        if ($archive_class->add_file($file, substr($file, strlen($this->job['abs_path'])))) {
                            $this->check_if_stopped();
                            //TODO log if needed
                        } else {
                            unset($archive_class);
                            $this->steps_data[$this->current_step]['sub_step'] = 0;
                            $this->steps_data[$this->current_step]['folder_key'] = -1;
                            $this->steps_data[$this->current_step]['file_key'] = -1;
                            $this->buwd_logger->set_log(__('Cannot create backup archive correctly.', 'buwd'), E_USER_ERROR);

                            return false;
                        }

                        if ($files_count % 700 == 0) {
                            $archive_class->archive_reload();
                        }

                        $this->steps_data[$this->current_step][$folder_key]['file_key'] = $file_key;
                    }

                    $this->steps_data[$this->current_step]['folder_key'] = $folder_key;
                    $this->steps_data[$this->current_step]['sub_step'] = 1;
                }
            }
            $this->file_count = $archive_class->get_file_count();

            $this->buwd_logger->set_log(sprintf(__('Files were compressed as %s.', 'buwd'), $archive_method));
            $this->update_progress();

            unset($archive_class);

            $this->buwd_logger->set_log(__('Backup archive was created.', 'buwd'));
            $this->update_progress();
        } catch (Exception $e) {
            unset($archive_class);

            $this->buwd_logger->set_log($e->getMessage(), E_USER_ERROR, $e->getFile(), $e->getLine());
            $this->update_progress();

            return false;
        }

        $this->backup_file_size = filesize(Buwd::get_plugin_data('temp_folder_dir') . basename($this->backup_file));

        if ($this->backup_file_size === false) {
            $this->backup_file_size = PHP_INT_MAX;
        }

        if ($this->backup_file_size >= PHP_INT_MAX) {
            $this->buwd_logger->set_log(__('The backup archive is too large for operations with PHP Version of your website. Please try splitting the backup job to multiple jobs with less files in each.', 'buwd'), E_USER_ERROR);
            $this->update_progress();
            die();
        }

        $this->buwd_logger->set_log(sprintf(__('%1$d files with %2$s are added to backup archive.', 'buwd'), $this->file_count, size_format($this->backup_file_size, 2)));

        $this->update_progress();

        return true;
    }


    public function restart_job()
    {
        $this->buwd_logger->set_log(sprintf(__('Max_execution_time directive is about to exceed. The job has restarted.', 'buwd')));
        $this->update_progress();
        $this->write_job_object_file();

        remove_action('shutdown', array($this, 'buwd_shutdown'));
        self::run_job('restart');
        die();
    }

    public function write_job_object_file()
    {
        $handle = fopen(Buwd::get_plugin_data('temp_folder_dir') . 'job_object.php', 'w');

        $content = serialize($this);
        fwrite($handle, $content);
        fclose($handle);
    }

    public static function run_job($type)
    {
        //wp_clear_scheduled_hook('buwd_cron',array('id'=>$job_id));
        //wp_schedule_single_event(time()+1,'buwd_cron',array('id'=>$job_id));

        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        $hash = md5(Buwd_Options::getSetting('job_start_key'));

        $url = self::get_job_run_url($type, $job_id);
        $result = wp_remote_post($url, array(
            'timeout'   => 0.01,
            'blocking'  => false,
            'sslverify' => false,
            'body'=> array(
                'type'      => $type,
                'buwd_hash'  => $hash,
            )
        ));

        //Buwd_Job::setup( 'run', 1 );

    }

    public static function get_job_run_url($type, $job_id)
    {
        $auth_method = Buwd_Options::getSetting('auth_method');
        $query_arg = Buwd_Options::getSetting('query_arg');
        $hash = md5(Buwd_Options::getSetting('job_start_key'));

        $url = site_url('?rest_route=/buwd/v1/job/'.$job_id.'/run');


        /*$args = array(
            'jobid'     => $job_id,
            'buwd_cron' => '1',
            'type'      => $type,
            '_wpnonce'  => $hash,
        );

        if ($auth_method == 'query' && empty($query_arg) === false) {
            $url .= '?' . $query_arg;
        }
        $url = add_query_arg($args, $url);*/

        return $url;
    }

    public static function get_job_object_file_content()
    {
        $content = file_get_contents(Buwd::get_plugin_data('temp_folder_dir') . 'job_object.php');

        return unserialize($content);
    }

    public static function get_progress()
    {
        $buwd_progress = get_site_option('buwd_progress');
        $buwd_progress = json_encode($buwd_progress);
        echo $buwd_progress;
        die();
    }

    public function exception_handler($exception)
    {
        $this->buwd_logger->set_log(sprintf(__('Exception caught in %1$s: %2$s', 'buwd'), get_class($exception), $exception->getMessage()), E_USER_ERROR, $exception->getFile(), $exception->getLine());
    }

    public function buwd_shutdown()
    {
        remove_action('shutdown', array($this, 'buwd_shutdown'));

        if (!$this->stopped) {
            $last_error = error_get_last();
            if ($last_error['type'] === E_ERROR || $last_error['type'] === E_PARSE || $last_error['type'] === E_CORE_ERROR || $last_error['type'] === E_CORE_WARNING || $last_error['type'] === E_COMPILE_ERROR || $last_error['type'] === E_COMPILE_WARNING) {

                $this->buwd_logger->set_log($last_error['message'], $last_error['type'], $last_error['file'], $last_error['line']);
            } else {
                $this->buwd_logger->set_log(__('ERROR', 'buwd'), 'SHUTDOWN_ERROR');
            }


        }

        $this->update_progress();
        $this->end_run();
        //die();
        //$this->restart_if_needed();
    }

    public function restart_if_needed()
    {
        $max_exec_time_server = ini_get('max_execution_time');
        $max_exec_time = Buwd_Options::getSetting('max_exec_time');

        $max_exec_time = $max_exec_time_server < $max_exec_time ? $max_exec_time_server : $max_exec_time;

        $loop_exec_time = microtime(true) - $this->steps_data['job_start_time'];
        if ($loop_exec_time >= ((int)$max_exec_time - 5)) {
            $this->steps_data[$this->current_step]['step'] = $this->steps_data[$this->current_step]['step'] - 1;
            $this->restart_job();

            return false;
        }
    }

    public static function abort_run()
    {
        $job_object = self::get_job_object_file_content();
        $job_object->stopped = true;
        $job_object->buwd_logger->stopped = true;

        // $job_object->update_progress();
        set_site_transient('buwd_jobs_updated', array(__('Job has stopped.', 'buwd')));
        unlink(Buwd::get_plugin_data('temp_folder_dir') . 'job_object.php');
        $job_object->end_run();
        //$job_object->check_if_stopped();

        //
        //$this->end_run();
    }


    public function check_if_stopped()
    {
        if (!file_exists(Buwd::get_plugin_data('temp_folder_dir') . 'job_object.php')) {
            $this->buwd_logger->set_log(sprintf(__('Job has stopped.', 'buwd')));
            $this->stopped = true;
            $this->buwd_logger->stopped = true;

            exit;
        }

        //return true;
    }


}