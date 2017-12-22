<?php

class Buwd_Admin
{
    protected static $instance = null;
    protected $updates = array();
    protected $notices = null;
    protected static $page = null;
    public $buwd_page = array();

    private function __construct()
    {
        /* 	$this->notices = new Buwd_Notices(); */
        self::$page = Buwd_Helper::get("page") ? esc_html(Buwd_Helper::get("page")) : "";

        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));

        add_action('admin_menu', array($this, 'add_admin_menu'), 10);

        add_action('admin_post_buwd_save', array($this, 'save_form'));
        add_action('admin_post_nopriv_buwd_save', array($this, 'save_form'));

        add_action('admin_init', array($this, 'admin_ajax_actions'));
        add_action('admin_init', array($this, 'admin_actions'));

        //Register session if there is no active session
        add_action('admin_init', array($this, 'register_session'));

        add_action('admin_notices', array($this, 'buwd_admin_notices'));
        add_filter('admin_footer_text', array($this, 'admin_footer_text'));

        //admin bar
        $show_on_bar = Buwd_Options::getSetting('show_on_bar');
        if (isset($show_on_bar[0]) && $show_on_bar[0] == 1) {
            add_action('init', array('Buwd_Admin_Bar', 'get_instance'));
        }

    }

    /**
     * use current_user_can('capability');
     *
     */
    public static function add_roles()
    {
        add_role('buwd_admin', __('Backup WD Admin', 'buwd'),
            array(
                'read'                  => true,
                'buwd_edit'             => true,
                'buwd_job'              => true,
                'buwd_job_edit'         => true,
                'buwd_job_delete'       => true,
                'buwd_job_run'          => true,
                'buwd_api_keys'         => true,
                'buwd_logs'             => true,
                'buwd_settings'         => true,
                'buwd_settings_import'  => true,
                'buwd_settings_export'  => true,
                'buwd_log_view'         => true,
                'buwd_log_delete'       => true,
                'buwd_log_download'     => true,
                'buwd_backups'          => true,
                'buwd_backups_delete'   => true,
                'buwd_backups_download' => true,
            )
        );

        add_role('buwd_helper', __('Backup WD Helper', 'buwd'),
            array(
                'read'                  => true,
                'buwd_edit'             => true,
                'buwd_job'              => true,
                'buwd_job_edit'         => false,
                'buwd_job_delete'       => false,
                'buwd_job_run'          => true,
                'buwd_api_keys'         => false,
                'buwd_settings'         => false,
                'buwd_settings_import'  => false,
                'buwd_settings_export'  => false,
                'buwd_logs'             => true,
                'buwd_log_view'         => true,
                'buwd_log_delete'       => false,
                'buwd_log_download'     => true,
                'buwd_backups'          => true,
                'buwd_backups_delete'   => false,
                'buwd_backups_download' => true,
            )
        );

        add_role('buwd_checker', __('Backup WD Checker', 'buwd'),
            array(
                'read'                  => true,
                'buwd_edit'             => true,
                'buwd_job'              => true,
                'buwd_job_edit'         => false,
                'buwd_job_delete'       => false,
                'buwd_job_run'          => false,
                'buwd_api_keys'         => false,
                'buwd_settings'         => false,
                'buwd_settings_import'  => false,
                'buwd_settings_export'  => false,
                'buwd_logs'             => true,
                'buwd_log_view'         => true,
                'buwd_log_delete'       => false,
                'buwd_log_download'     => false,
                'buwd_backups'          => true,
                'buwd_backups_delete'   => false,
                'buwd_backups_download' => false,
            )
        );

        $admin_role = get_role('administrator');

        $admin_role->add_cap('buwd_edit');
        $admin_role->add_cap('buwd_job');
        $admin_role->add_cap('buwd_job_edit');
        $admin_role->add_cap('buwd_job_delete');
        $admin_role->add_cap('buwd_job_run');
        $admin_role->add_cap('buwd_api_keys');
        $admin_role->add_cap('buwd_settings');
        $admin_role->add_cap('buwd_settings_import');
        $admin_role->add_cap('buwd_settings_export');
        $admin_role->add_cap('buwd_logs');
        $admin_role->add_cap('buwd_log_view');
        $admin_role->add_cap('buwd_log_delete');
        $admin_role->add_cap('buwd_log_download');
        $admin_role->add_cap('buwd_backups');
        $admin_role->add_cap('buwd_backups_delete');
        $admin_role->add_cap('buwd_backups_download');
    }

    public static function activate()
    {
        // set default options
        Buwd_Options::set_default_options();
        update_site_option('buwd_version', BUWD_VERSION);

        self::add_roles();
    }


    public static function deactivate()
    {
        global $buwd_plugin_options;
        if (!class_exists("DoradoWebConfig")) {
            include_once(BUWD_DIR . "/wd/config.php");
        }

        if (!class_exists("DoradoWebDeactivate")) {
            include_once(BUWD_DIR . "/wd/includes/deactivate.php");
        }

        $config = new DoradoWebConfig();
        $config->set_options($buwd_plugin_options);
        $deactivate_reasons = new DoradoWebDeactivate($config);
        //$deactivate_reasons->add_deactivation_feedback_dialog_box();
        $deactivate_reasons->submit_and_deactivate();

        Buwd_Options::remove_default_options();
    }


    /**
     * Check user is on plugin page
     * @return  bool
     */
    public static function is_buwd_page()
    {
        $page = self::get_page();
        if ($page == BUWD_PREFIX . '_dashboard' || $page == BUWD_PREFIX . '_jobs' || $page == BUWD_PREFIX . '_editjob' || $page == BUWD_PREFIX . '_api_keys' || $page == BUWD_PREFIX . '_logs' || $page == BUWD_PREFIX . '_backups' || $page == BUWD_PREFIX . '_settings' || $page == BUWD_PREFIX . '_restore') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add admin menu
     *
     */
    public function add_admin_menu()
    {
        $parent_slug = null;
        if (get_option("buwd_subscribe_done") == 1) {
            $parent_slug = "buwd_jobs";

            add_menu_page(__('Backup WD', 'buwd'), __('Backup WD', 'buwd'), 'buwd_edit', 'buwd_jobs', array(
                $this,
                'display_page'
            ), BUWD_URL . '/public/images/menu_logo.png');
        }

        // jobs
        $this->buwd_page['jobs'] = add_submenu_page($parent_slug, __('Jobs', 'buwd'), __('Jobs', 'buwd'), BUWD_PREFIX . '_edit', BUWD_PREFIX . '_jobs', array(
            $this,
            'display_page'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['jobs'], array(
            ucfirst(BUWD_PREFIX) . '_Jobs',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['jobs'], array(
            ucfirst(BUWD_PREFIX) . '_Jobs',
            'admin_print_styles'
        ));

        // edit job
        $this->buwd_page['editjob'] = add_submenu_page($parent_slug, __('Add new job', 'buwd'), __('Add new job', 'buwd'), BUWD_PREFIX . '_job_edit', BUWD_PREFIX . '_editjob', array(
            $this,
            'display_page'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['editjob'], array(
            ucfirst(BUWD_PREFIX) . '_Editjob',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['editjob'], array(
            ucfirst(BUWD_PREFIX) . '_Editjob',
            'admin_print_styles'
        ));

        // Logs
        $this->buwd_page['logs'] = add_submenu_page($parent_slug, __('Logs', 'buwd'), __('Logs', 'buwd'), BUWD_PREFIX . '_logs', BUWD_PREFIX . '_logs', array($this, 'display_page'));
        add_action('load-' . $this->buwd_page['logs'], array(ucfirst(BUWD_PREFIX) . '_Logs', 'load_action'));
        add_action('admin_print_scripts-' . $this->buwd_page['logs'], array(
            ucfirst(BUWD_PREFIX) . '_Logs',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['logs'], array(
            ucfirst(BUWD_PREFIX) . '_Logs',
            'admin_print_styles'
        ));

        // Backups
        $this->buwd_page['backups'] = add_submenu_page($parent_slug, __('Backups', 'buwd'), __('Backups', 'buwd'), BUWD_PREFIX . '_backups', BUWD_PREFIX . '_backups', array(
            $this,
            'display_page'
        ));
        add_action('load-' . $this->buwd_page['backups'], array(
            ucfirst(BUWD_PREFIX) . '_Backups',
            'load_action'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['backups'], array(
            ucfirst(BUWD_PREFIX) . '_Backups',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['backups'], array(
            ucfirst(BUWD_PREFIX) . '_Backups',
            'admin_print_styles'
        ));

        //API keys
        $this->buwd_page['api_keys'] = add_submenu_page($parent_slug, __('API Keys', 'buwd'), __('API Keys', 'buwd'), BUWD_PREFIX . '_api_keys', BUWD_PREFIX . '_api_keys', array(
            $this,
            'display_page'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['api_keys'], array(
            ucfirst(BUWD_PREFIX) . '_api_keys',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['api_keys'], array(
            ucfirst(BUWD_PREFIX) . '_api_keys',
            'admin_print_styles'
        ));


        //settings
        $this->buwd_page['settings'] = add_submenu_page($parent_slug, __('Settings', 'buwd'), __('Settings', 'buwd'), BUWD_PREFIX . '_settings', BUWD_PREFIX . '_settings', array(
            $this,
            'display_page'
        ));
        add_action('load-' . $this->buwd_page['settings'], array(
            ucfirst(BUWD_PREFIX) . '_Settings',
            'load_action'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['settings'], array(
            ucfirst(BUWD_PREFIX) . '_Settings',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['settings'], array(
            ucfirst(BUWD_PREFIX) . '_Settings',
            'admin_print_styles'
        ));

        //restore
        $this->buwd_page['restore'] = add_submenu_page($parent_slug, __('Restore', 'buwd'), __('Restore', 'buwd'), BUWD_PREFIX . '_edit', BUWD_PREFIX . '_restore', array(
            $this,
            'display_page'
        ));
        add_action('admin_print_scripts-' . $this->buwd_page['restore'], array(
            ucfirst(BUWD_PREFIX) . '_Restore',
            'admin_print_scripts'
        ));
        add_action('admin_print_styles-' . $this->buwd_page['restore'], array(
            ucfirst(BUWD_PREFIX) . '_Restore',
            'admin_print_styles'
        ));
    }

    public function admin_actions()
    {
        add_thickbox();


    }

    public function admin_ajax_actions()
    {
        //if ( defined( 'DOING_AJAX' ) && DOING_AJAX && defined( 'WP_ADMIN' ) && WP_ADMIN ) {
        if ($destinations = Buwd::get_destinations()) {
            foreach ($destinations as $dest_id => $destination) {
                add_action('wp_ajax_buwd-' . strtolower($dest_id), array(Buwd::get_destination($dest_id), 'run_ajax'));
            }
        }
        add_action('wp_ajax_buwd-type-db', array(Buwd_Type_DB::get_instance(), 'run_ajax'));
        add_action('wp_ajax_buwd-run_job', array('Buwd_Job', 'run_job'));
        add_action('wp_ajax_buwd_progress', array('Buwd_Job', 'get_progress'));
        add_action('wp_ajax_buwd_success_message', array('Buwd_Job', 'do_not_show_message'));
        add_action('wp_ajax_view_log', array('Buwd_Logs', 'action_view_log'));
        add_action('wp_ajax_buwd_login', array('Buwd_Dashboard', 'login'));
        add_action('wp_ajax_delete_backups', array('Buwd_Backups', 'load_action'));
        add_action('wp_ajax_delete_logs', array('Buwd_Logs', 'load_action'));
        //	}

    }

    /**
     * Display menu page
     */
    public function display_page()
    {
        if (!self::is_buwd_page()) {
            die(__('Page not found!', 'buwd'));
        }

        $classname = implode('_', array_map('ucfirst', explode('_', self::$page)));
        $class = $classname::get_instance();

        include_once(BUWD_DIR . '/views/progressbar.php');
        $class->display_page();

    }

    /**
     * Load public scripts
     */
    public function enqueue_admin_scripts()
    {
        /* global $wpdb;
        wp_enqueue_script( BUWD_PREFIX ); */
        if (!self::is_buwd_page()) {
            return false;
        }
        wp_enqueue_script(BUWD_PREFIX . '-admin', BUWD_URL . '/public/js/admin.js', array(), BUWD_VERSION, true);
        wp_enqueue_script(BUWD_PREFIX . '-datetimepicker', BUWD_URL . '/public/js/jquery.datetimepicker.js', array(), BUWD_VERSION, true);
        wp_enqueue_script(BUWD_PREFIX . '-jquery.validate', BUWD_URL . '/public/js/jquery.validate.js', array(), BUWD_VERSION, true);
        wp_enqueue_script(BUWD_PREFIX . '-additional-methods', BUWD_URL . '/public/js/additional-methods.js', array(), BUWD_VERSION, true);
        wp_enqueue_script(BUWD_PREFIX . '-jquery-cron', BUWD_URL . '/public/js/jquery-cron-min.js', array(), BUWD_VERSION, true);
        wp_enqueue_script(BUWD_PREFIX . '-progressbar', BUWD_URL . '/public/js/progressbar-manager.js', array(), BUWD_VERSION, true);
        wp_localize_script(BUWD_PREFIX . '-admin', 'buwd', array(
            'ajaxurl'    => admin_url('admin-ajax.php'),
            'ajaxnonce'  => wp_create_nonce(BUWD_PREFIX . '_ajax_nonce'),
            'plugin_url' => BUWD_URL,
            //		'user_data_url' => Buwd::get_plugin_data('service_url').'api/auth',
        ));

    }

    /**
     * Load public styles
     */
    public function enqueue_admin_styles()
    {
        if (!self::is_buwd_page()) {
            return false;
        }
        wp_enqueue_style('buwd-style', BUWD_URL . '/public/css/style.css', array(), BUWD_VERSION);
        wp_enqueue_style('buwd-animations-style', BUWD_URL . '/public/css/animate.css', array(), BUWD_VERSION);
        wp_enqueue_style('buwd-progressbar', BUWD_URL . '/public/css/progressbar.css', array(), BUWD_VERSION);
        wp_enqueue_style('buwd-log-style', BUWD_URL . '/public/css/log.css', array(), BUWD_VERSION);
    }


    /**
     * Save form data
     */
    public static function save_form()
    {
        if (!self::is_buwd_page()) {
            die(__('Page not found', 'buwd'));
        }

        check_admin_referer('nonce_buwd', 'nonce_buwd');

        $current_tab = Buwd_Helper::get('current_tab') ? Buwd_Helper::get('current_tab') : 'general';
        if (self::$page == 'buwd_editjob') {
            $job_id = Buwd_Helper::get('job_id') ? (int)Buwd_Helper::get('job_id') : null;
            $class = Buwd_Editjob::get_instance();
            $class->save_form($job_id, $current_tab);
        }

        if (self::$page == 'buwd_settings' || self::$page == 'buwd_api_keys' || self::$page == 'buwd_restore') {
            $class_name = Buwd_Helper::ucwords_specific(self::$page, '_');
            $class = $class_name::get_instance();
            $class->save_form($current_tab);
        }
    }


    /**
     * BUWD notices
     */

    function buwd_admin_notices()
    {
        if (self::is_buwd_page()) {
            if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
                echo '<div class="buwd-messages">' . Buwd_Helper::message('WordPress Cron is disabled on your website. Please enable it, so Backup WD can run backup jobs. <br/>Edit <b>wp-config.php</b> file of your website and add <b>define(\'DISABLE_WP_CRON\', false);</b> to it. If the line is there, but it\'s set to true, simply <b>change</b> it to <b>false</b>.', 'error') . '</div>';
            }
        }
    }


    public function admin_footer_text($footer_text) {
        if (self::is_buwd_page()) {
            $footer_text = sprintf(
                __( 'Enjoyed <strong>BackUp WD</strong>? Please leave us a %s rating. We really appreciate your support!', 'buwd' ),
                '<a href="https://wordpress.org/support/plugin/backup-wd/reviews/?filter=5#new-post" target="_blank">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
            );
        }

        return $footer_text;
    }

    /**
     * Ignore function that gets ran at admin init to ensure any messages that were dismissed get marked
     */
    public function admin_notice_ignore()
    {

    }


    /**
     *
     *
     */
    public static function get_page()
    {
        return self::$page;
    }

    /**
     * Register session if there is no active session
     */
    function register_session()
    {
        if (!session_id()) {
            session_start();
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