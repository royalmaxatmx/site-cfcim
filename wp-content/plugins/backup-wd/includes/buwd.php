<?php

class Buwd
{
    protected static $instance = null;
    protected static $job_types = array();
    protected static $destinations = array();
    protected static $plugin_data = array();
    private $regAutoloader = false;

    private function __construct()
    {
        $this->registerAutoloader();

        add_action('init', array($this, 'add_localization'), 1);
        add_action('wp_loaded', array('Buwd_Cron', 'run_cron'));


        //create temp, log folders if not exist
        $wp_upload_dir = self::get_upload_dir();
        $folders_to_create = array($wp_upload_dir, self::get_plugin_data('temp_folder_dir'), str_replace('{hash_key}', self::get_plugin_data('hash'), self::get_plugin_data('log_folder_dir')));
        foreach ($folders_to_create as $folder_to_create) {
            if (!is_dir($folder_to_create)) {
                mkdir($folder_to_create, 0777, true);
            }
        }
        /*
        if ( ! wp_next_scheduled( 'buwd_cron' ) ) {
                wp_schedule_event( time(), 'hourly', 'buwd_cron' );
            }

            add_action( 'buwd_cron', array( Buwd_Cron, 'run' ) );


        }*/

        $wpcron_job_ids = Buwd_Options::get_job_ids('schedule', 'wpcron');

        if (!empty($wpcron_job_ids)) {
            foreach ($wpcron_job_ids as $wpcron_job_id) {
                $cron_expression = Buwd_Options::get($wpcron_job_id, 'cron_expression');
                $args = array('id' => $wpcron_job_id);
                $cron_timestamp = Buwd_Cron::next_run($cron_expression, 'Y-m-d H:i:s');

                $cron_timestamp = strtotime($cron_timestamp);
                $offset = get_site_option('gmt_offset');

                $cron_timestamp -= (int)$offset * 3600;

                if (get_site_option('buwd_easycron_' . $wpcron_job_id)) {
                    Buwd_Cron::delete_easycron($wpcron_job_id);
                }

                //wp_clear_scheduled_hook( 'buwd_cron4',$args );
                if (!wp_next_scheduled('buwd_cron', $args)) {

                    wp_schedule_single_event($cron_timestamp, 'buwd_cron', $args);
                    if (!$cron_timestamp) {
                        wp_clear_scheduled_hook('buwd_cron', $args);
                    }
                }
            }
        }
        if (defined('DOING_CRON') && DOING_CRON) {
            add_action('buwd_cron', array('Buwd_Cron', 'run'));
        }

        $easycron_job_ids = Buwd_Options::get_job_ids('schedule', 'easycron');
        if (!empty($easycron_job_ids)) {
            foreach ($easycron_job_ids as $easycron_job_id) {
                $minute = Buwd_Options::get($easycron_job_id, 'scheduleminute');
                $hour = Buwd_Options::get($easycron_job_id, 'schedulehour');
                $days = Buwd_Options::get($easycron_job_id, 'scheduleday', array('*'));
                $months = Buwd_Options::get($easycron_job_id, 'schedulemonth', array('*'));
                $wday = Buwd_Options::get($easycron_job_id, 'scheduleweek', array('*'));

                $scheduletype = Buwd_Options::get($easycron_job_id, 'scheduletype');
                if ($scheduletype == 'weekly') {
                    $months = Buwd_Cron::create_num_array(12);
                }
                if ($scheduletype == 'dayly') {
                    $months = Buwd_Cron::create_num_array(12);
                    $wday = Buwd_Cron::create_num_array(6, 0);
                }

                if ($scheduletype == 'hourly') {
                    $months = Buwd_Cron::create_num_array(12);
                    $wday = Buwd_Cron::create_num_array(6, 0);
                    $days = Buwd_Cron::create_num_array(31);
                }


                $cron_expression = $minute . ' ' . $hour . ' ' . Buwd_Cron::generate_expression($days) . ' ' . Buwd_Cron::generate_expression($months) . ' ' . Buwd_Cron::generate_expression($wday);

                if (!get_site_option('buwd_easycron_' . $easycron_job_id)) {
                    $cronjob_id = Buwd_Cron::add_easycron($cron_expression, $easycron_job_id);
                    update_site_option('buwd_easycron_' . $easycron_job_id, $cronjob_id);
                }
            }
        }
    }

    public function registerAutoloader()
    {
        if ($this->regAutoloader) {
            return;
        }

        spl_autoload_register(array(__CLASS__, "autoload"));
        if (function_exists("__autoload")) {
            spl_autoload_register(array(__CLASS__, "autoloadProxy"));
        }

        $this->regAutoloader = true;
    }

    public static function autoload($class)
    {
        $class = str_replace("_", "-", strtolower($class));
        $include_file = BUWD_DIR . "/includes/" . $class . ".php";

        if (file_exists($include_file)) {
            include_once $include_file;
        }
    }

    public static function autoloadProxy($class)
    {
        __autoload($class);
    }

    /**
     * Add localization
     */
    public function add_localization()
    {
        $path = dirname(plugin_basename(__FILE__)) . '/languages/';
        $loaded = load_plugin_textdomain('buwd', false, $path);
        if (isset($_GET['page']) && $_GET['page'] == basename(__FILE__) && !$loaded) {
            echo '<div class="error">BackUp WD ' . __('Could not load the localization file: ' . $path, 'buwd') . '</div>';

            return;
        }
    }

    /**
     * Get job types
     */
    public static function get_job_types()
    {
        self::$job_types['db'] = Buwd_Type_DB::get_instance();
        self::$job_types['files'] = Buwd_Type_Files::get_instance();
        foreach (self::$job_types as $key => $job_type) {
            if (empty($job_type) || !is_object($job_type)) {
                unset(self::$job_types[$key]);
            }
        }

        return self::$job_types;
    }

    public static function get_job_type($key)
    {
        if (isset(self::$job_types[$key]) && is_object(self::$job_types[$key])) {
            return self::$job_types[$key];
        }

        $job_types = self::get_job_types();

        if (!empty($job_types[$key])) {
            self::$job_types[$key] = new $job_types[$key];
        } else {
            return null;
        }

        return self::$job_types[$key];
    }


    /**
     * Get destinations
     */
    public static function get_destinations()
    {
        // self::$destinations['lotus-cloud'] = Buwd_Destination_Lotus_Cloud::get_instance();
        self::$destinations['folder'] = Buwd_Destination_Folder::get_instance();
        self::$destinations['gdrive'] = Buwd_Destination_GDrive::get_instance();
        self::$destinations['amazon-s3'] = Buwd_Destination_Amazon_S3::get_instance();
        self::$destinations['dropbox'] = Buwd_Destination_Dropbox::get_instance();
        self::$destinations['azure'] = Buwd_Destination_Azure::get_instance();
        self::$destinations['rsc'] = Buwd_Destination_Rsc::get_instance();
        self::$destinations['ftp'] = Buwd_Destination_Ftp::get_instance();
        self::$destinations['sugarsync'] = Buwd_Destination_Sugarsync::get_instance();
        self::$destinations['amazon-glacier'] = Buwd_Destination_Amazon_Glacier::get_instance();
        // add other destinations

        foreach (self::$destinations as $key => $destination) {
            if (empty($destination) || !is_object($destination)) {
                unset(self::$destination[$key]);
            }
        }

        return self::$destinations;
    }

    public static function get_destination($key)
    {
        if (isset(self::$destinations[$key]) && is_object(self::$destinations[$key])) {
            return self::$destinations[$key];
        }

        $destinations = self::get_destinations();
        if (!empty($destinations[$key])) {
            self::$destinations[$key] = new $destinations[$key];
        } else {
            return null;
        }

        return self::$destinations[$key];
    }

    public static function get_plugin_data($key)
    {
        global $wp_version;
        if (!function_exists('get_home_path')) {
            require_once(ABSPATH.'/wp-admin/includes/file.php');
        }
        $wp_upload_dir = self::get_upload_dir();

        if (empty(self::$plugin_data)) {
            self::$plugin_data['name'] = 'Backup WD';
            self::$plugin_data['folder_name'] = plugin_basename(BUWD_DIR);
            self::$plugin_data['version'] = get_site_option('buwd_version');
            //self::$plugin_data['prefix'] = 'buwd';
            self::$plugin_data['hash'] = get_site_option('buwd_hash');
            if (!self::$plugin_data['hash']) {
                self::$plugin_data['hash'] = substr(md5(md5(__FILE__)), 10, 6);
                update_site_option('buwd_hash', self::$plugin_data['hash']);
            }

            self::$plugin_data['log_folder_dir'] = Buwd_Options::getSetting('log_folder');

            self::$plugin_data['temp_folder_dir'] = untrailingslashit($wp_upload_dir) . '/' . self::$plugin_data['folder_name'] . '-temp-' . self::$plugin_data['hash'] . '/';

            self::$plugin_data['temp_restore_dir'] = untrailingslashit($wp_upload_dir) . '/' . self::$plugin_data['folder_name'] . '-temp-restore-' . self::$plugin_data['hash'] . '/';

            self::$plugin_data['wp_version'] = $wp_version;
            self::$plugin_data['home_url'] = get_home_url();
            self::$plugin_data['home_path'] = get_home_path();
            self::$plugin_data['cacert'] = BUWD_DIR . '/assets/cacert-2016-11-02.pem';
        }

        if (isset(self::$plugin_data[$key])) {
            return self::$plugin_data[$key];
        }

        return '';
    }

    public static function get_upload_dir()
    {
        if (is_multisite()) {
            if (defined('UPLOADBLOGSDIR')) {
                return trailingslashit(str_replace('\\', '/', ABSPATH . UPLOADBLOGSDIR));
            } else if (is_dir(trailingslashit(WP_CONTENT_DIR) . 'uploads/sites')) {
                return str_replace('\\', '/', trailingslashit(WP_CONTENT_DIR) . 'uploads/sites/');
            } else if (is_dir(trailingslashit(WP_CONTENT_DIR) . 'uploads')) {
                return str_replace('\\', '/', trailingslashit(WP_CONTENT_DIR) . 'uploads/');
            } else {
                return trailingslashit(str_replace('\\', '/', WP_CONTENT_DIR));
            }
        } else {
            $upload_dir = wp_upload_dir(null, false, true);

            return trailingslashit(str_replace('\\', '/', $upload_dir['basedir']));
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

?>