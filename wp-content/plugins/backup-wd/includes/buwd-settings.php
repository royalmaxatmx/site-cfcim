<?php

/**
 *
 */
class Buwd_Settings
{
    protected static $instance = null;
    public $info = array();
    private $page_id = 'buwd_settings';

    public function __construct()
    {
        $this->info['tab'] = $this->get_tab();
    }

    public function tabs_options()
    {
        $wp_users = get_users(['fields' => ['ID', 'user_nicename']]);
        $wp_users_options = array();
        foreach ($wp_users as $wp_user) {
            $wp_users_options[$wp_user->ID] = $wp_user->user_nicename;
        }

        $options = array(
            'general' => array(
                'key'    => 'general',
                'title'  => '',
                'fields' => array(
                    array(
                        'label' => __('Hash key', 'buwd'),
                        'id'    => 'hash_key',
                        'name'  => 'hash_key',
                        'type'  => 'text',
                        'class' => array(
                            'buwd-small-text'
                        ),
                        'value' => Buwd_Options::getSetting('hash_key'),
                        'hint'  => array(
                            'html' => '<p class="description">Hash key is a unique identifier, which will be used to add hashes to backup folder and file names. It has to be at least 6 characters.</p>'
                        ),
                    ),
                    array(
                        'label'   => __('Admin bar', 'buwd'),
                        'id'      => 'show_on_bar',
                        'name'    => 'show_on_bar',
                        'type'    => 'checkbox',
                        'choices' => array(
                            '1' => 'Show Backup WD links in admin bar.'
                        ),
                        'value'   => Buwd_Options::getSetting('show_on_bar'),
                        'hint'    => array(
                            'html' => '<p class="description">If this option is enabled, the plugin adds Backup WD links to the top admin bar.</p>'
                        )
                    ),
                    array(
                        'label'   => __('Folder sizes', 'buwd'),
                        'id'      => 'show_foldier_size',
                        'name'    => 'show_foldier_size',
                        'type'    => 'checkbox',
                        'choices' => array(
                            '1' => 'Display folder sizes in the files tab when editing a job. (Might increase loading time of files tab.)'
                        ),
                        'value'   => Buwd_Options::getSetting('show_foldier_size'),
                        'hint'    => array(
                            'html' => '<p class="description">Enable this option to display folder sizes on Files Backup tab when adding or editing a job.</p>'
                        )
                    ),
                    array(
                        'label'   => __('Protect folders', 'buwd'),
                        'id'      => 'folder_protect',
                        'name'    => 'folder_protect',
                        'type'    => 'checkbox',
                        'choices' => array(
                            '1' => 'Protect Backup WD folders ( Temp, Log and Backups ) with .htaccess and index.php'
                        ),
                        'value'   => Buwd_Options::getSetting('folder_protect'),
                        'hint'    => array(
                            'html' => '<p class="description">Check this option to protect Backup WD folders, such as Temp, Log and Backups with .htaccess and index.php files.</p>'
                        )
                    )
                )
            ),
            'jobs'    => array(
                'key'    => 'jobs',
                'title'  => '',
                'fields' => array(
                    array(
                        'label' => __('Maximum number of retries for job steps', 'buwd'),
                        'id'    => 'job_step_max',
                        'name'  => 'job_step_max',
                        'type'  => 'number',
                        'value' => Buwd_Options::getSetting('job_step_max', 1, true),
                        'class' => array(
                            'buwd-small-text'
                        ),
                        'attr'  => array(
                            'min' => 1
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Use this option to set up a limit of retries for backup job steps.</p>'
                        )
                    ),
                    array(
                        'label' => __('Maximum script execution time', 'buwd'),
                        'id'    => 'max_exec_time',
                        'name'  => 'max_exec_time',
                        'type'  => 'number',
                        'value' => Buwd_Options::getSetting('max_exec_time', 300, true),
                        'class' => array(
                            'buwd-small-text'
                        ),
                        'hint'  => array(
                            'html' => 'sec.<p class="description">Set a maximum time limit for script executions during backup jobs.</p>'
                        )
                    ),
                    array(
                        'label' => __('Key to start jobs externally with a URL', 'buwd'),
                        'id'    => 'job_start_key',
                        'name'  => 'job_start_key',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('job_start_key'),
                        'class' => array(
                            'buwd-medium-text'
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Some servers might require keys to run the backup jobs externally. This key is provided by your web hosting. Provide it here and it will be automatically added to the start link of your backup jobs.</p>'
                        )
                    ),
                ),
            ),
            'logs'    => array(
                'key'    => 'logs',
                'title'  => '',
                'fields' => array(
                    array(
                        'label' => __('Log file folder', 'buwd'),
                        'id'    => 'log_folder',
                        'name'  => 'log_folder',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('log_folder'),
                        'class' => array(
                            'buwd-large-text'
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Specify the folder where the log file of the backups will be kept. Make sure to start it with root directory.</p>'
                        )
                    ),
                    array(
                        'label' => __('Maximum log files', 'buwd'),
                        'id'    => 'max_log_files',
                        'name'  => 'max_log_files',
                        'type'  => 'number',
                        'value' => Buwd_Options::getSetting('max_log_files', 20),
                        'class' => array(
                            'buwd-extra-small-text'
                        ),
                        'hint'  => array(
                            'html' => '<span>Number of files to keep in folder.</span><p class="description">Set the maximum limit of log files which are allowed to be kept in log file folder.</p>'
                        )
                    ),
                    array(
                        'label'   => __('Logging Level', 'buwd'),
                        'id'      => 'log_level',
                        'name'    => 'log_level',
                        'type'    => 'select',
                        'value'   => Buwd_Options::getSetting('log_level'),
                        'choices' => array(
                            'normal' => 'Normal',
                            'debug'  => 'Debug',
                        ),
                        'class'   => array(
                            'buwd-medium-text',
                        ),
                        'hint'    => array(
                            'html' => '<p class="description">Set the logging level, <b>Normal</b> or <b>Debug</b>.</p>'
                        )
                    ),
                )
            ),
            'email'   => array(
                'key'    => 'import',
                'title'  => '',
                'fields' => array(
                    array(
                        'label' => __('Send to', 'buwd'),
                        'id'    => 'recipient',
                        'name'  => 'recipient',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('recipient'),
                        'class' => array('buwd-large-text'),
                        'attr'  => array(),
                        'hint'  => array(
                            'html' => '<p class="description">Specify the email address to which the backup log will be sent. The plugin takes administrator user email address by default.</p>'
                        ),
                    ),
                    array(
                        'label' => __('From name', 'buwd'),
                        'id'    => 'from',
                        'name'  => 'from',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('from'),
                        'class' => array(
                            'buwd-large-text'
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Specify the name of the sender of the backup log email.</p>'
                        ),
                    ),
                    array(
                        'label' => __('Email from', 'buwd'),
                        'id'    => 'email_from',
                        'name'  => 'email_from',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('email_from'),
                        'class' => array(
                            'buwd-large-text'
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Specify the email address from which the backup log will be sent. The plugin takes administrator user email address by default.</p>'
                        ),
                    ),
                    array(
                        'label' => __('Subject', 'buwd'),
                        'id'    => 'subject',
                        'name'  => 'subject',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('subject'),
                        'class' => array(
                            'buwd-large-text'
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Set the subject of the backup log email.</p>'
                        ),
                    ),
                ),
            ),
            'info'    => array(
                'key'    => 'info',
                'title'  => '',
                'fields' => $this->get_info_fields(),
            ),
        );

        return $options;
    }

    public function get_tab()
    {
        return Buwd_Helper::get("tab") ? Buwd_Helper::get("tab") : "general";
    }

    public function get_user_guide($current_tab = null)
    {
        $user_guide = array(
            'general' => array(
                'title' => __('This section allows you to configure General tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/settings/general.html',
            ),
            'jobs'    => array(
                'title' => __('This section allows you to configure Jobs tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/settings/jobs.html',
            ),
            'logs'    => array(
                'title' => __('This section allows you to configure Logs tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/settings/logs.html',
            ),
            'email'   => array(
                'title' => __('This section allows you to configure Email Options tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/settings/email-options.html',
            ),
        );

        if ($current_tab && isset($user_guide[$current_tab])) {
            return $user_guide[$current_tab];
        }

        return $user_guide;
    }

    public static function load_action()
    {
        $current_action = Buwd_Helper::get("action") ? Buwd_Helper::get("action") : '';
        switch ($current_action) {
            case 'export':
                self::export();
                break;
            default:

                //	$this->display_page();
                break;
        }


    }

    /**
     * Display Current tab data
     */
    /*private function display_tab() {
        $current_tab = $this->info['tab'];
        $tab_data    = $this->render_tab( $current_tab );

        include_once( BUWD_DIR . '/views/settings-' . $current_tab . '.php' );
    }*/


    /**
     * Include styles
     */
    public static function admin_print_styles()
    {
        wp_enqueue_style(BUWD_PREFIX . 'buwd-settings', BUWD_URL . '/public/css/settings.css', array(), BUWD_VERSION);
    }

    /**
     * Include scripts
     */
    public static function admin_print_scripts()
    {
        wp_enqueue_script(BUWD_PREFIX . '-settings', BUWD_URL . '/public/js/settings.js', array(), BUWD_VERSION, true);
    }

    /**
     * @param $current_tab
     * Save Settings current tab data
     *
     * @return bool|void
     */
    public function save_form($current_tab)
    {
        $redirect_url = array();
        $redirect_url['page'] = $this->page_id;
        $redirect_url['tab'] = $current_tab;

        if (isset($_FILES['import']) && $_FILES['import']['tmp_name']) {
            $type = substr($_FILES['import']['name'], -4);
            if ($type != 'json') {
                Buwd_Helper::redirect($redirect_url);

                return true;
            }

            $file_contents = file_get_contents($_FILES['import']['tmp_name']);
            $file = json_decode($file_contents, true);

            if (isset($file['buwd_export'])) {
                return $this->import($file);
            }
        }

        $tabs = array_keys($this->get_tabs());
        $fileds = array();
        foreach ($tabs as $tab) {
            if ($tab == 'import' || $tab == 'info')
                continue;

            $tab_data = $this->get_tab_options($tab);
            $fileds = array_merge($fileds, $tab_data['fields']);
        }

        $options = array();
        $options['key'] = 'all';
        $options['title'] = '';
        $options['fields'] = $fileds;

        $group_class = new Buwd_Form_Group(array($options));
        $field_names = array_keys($group_class->get_fields());

        $old_settings = Buwd_Options::get_settings_options();
        $new_settings = array();
        foreach ($field_names as $field_name) {
            $field_value = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
            if (in_array($field_name, array('hash_key', 'job_start_key', 'log_folder', 'log_level', 'subject'))) {
                $field_value = sanitize_text_field($field_value);
            }

            if (in_array($field_name, array('recipient', 'email_from'))) {
                if ($field_name == 'recipient') {
                    $recipients = explode(',', $field_value);
                    array_walk($recipients, function (&$recipient) {
                        $recipient = sanitize_email($recipient);
                    });

                    $field_value = implode(',', $recipients);
                } else {
                    $field_value = sanitize_email($field_value);
                }
            }

            if (!is_array($field_value)) {
                $field_value = stripslashes($field_value);
            }

            if (in_array($field_name, array('job_step_max', 'max_exec_time', 'max_log_files'))) {
                $field_value = (int)$field_value;
            }

            if ($field_name == 'log_folder') {
                $field_value = ltrim($field_value, '/');
            }

            $new_settings[$field_name] = $field_value;
            if ($field_name == 'hash_key') {
                update_site_option('buwd_hash', $field_value);
            }

        }

        $new_settings = array_merge($old_settings, $new_settings);
        Buwd_Options::update_settings($new_settings);

        set_site_transient('buwd_settings_updated', __('All options have been saved successfully.
', 'buwd'));

        Buwd_Helper::redirect($redirect_url);
    }

    public function display_page()
    {
        $current_tab = $this->info['tab'];
        $tabs = $this->get_tabs();
        $user_guide = $this->get_user_guide();
        include_once(BUWD_DIR . '/views/settings.php');
    }


    public function display_messages()
    {
        if ($error = get_site_transient('buwd_settings_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_settings_error');
        } else if ($updated = get_site_transient('buwd_settings_updated')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_settings_updated');
        }
    }

    private function import($options)
    {
        $redirect_url = array();
        $redirect_url['page'] = $this->page_id;
        unset($options['buwd_export']);

        Buwd_Options::update_settings($options);

        Buwd_Helper::redirect($redirect_url);
    }

    private function get_tabs()
    {
        $tabs = array(
            'general' => array(
                'name'    => esc_html__('General', 'buwd'),
                //	'view'    => array( $this, 'display_tab' ),
                'display' => true
            ),
            'jobs'    => array(
                'name'    => __('Jobs', 'buwd'),
                //	'view'    => array( $this, 'display_tab' ),
                'display' => true
            ),
            'logs'    => array(
                'name'    => __('Logs', 'buwd'),
                //	'view'    => array( $this, 'display_tab' ),
                'display' => true
            ),
            'email'   => array(
                'name'    => __('Email Options', 'buwd'),
                //	'view'    => array( $this, 'display_tab' ),
                'display' => true
            ),
            'info'    => array(
                'name'    => __('Information', 'buwd'),
                //	'view'    => array( $this, 'display_tab' ),
                'display' => true
            ),
        );

        return $tabs;
    }

    private function display_tab($current_tab)
    {
        $tab_data = $this->render_tab($current_tab);

        return $tab_data;
    }

    private function render_tab($tab_id = 'general')
    {
        $options = $this->get_tab_options($tab_id);
        $group_class = new Buwd_Form_Group(array($options));
        $groups = $group_class->get_groups();
        $group_html = array();
        foreach ($groups as $g_name => $group) {
            $group_html['title'] = $group->title;
            $group_html['content'] = $group_class->render_group($g_name);
        }

        return $group_html;
    }

    private function get_tab_options($tab_id = 'general')
    {
        $tabs_options = $this->tabs_options();

        return $tabs_options[$tab_id];
    }

    private function get_info_fields()
    {
        global $wpdb;
        $sql_version = $wpdb->get_var("SELECT VERSION() AS version");
        $curl_version = curl_version();

        $now = localtime(time(), true);

        $info = array(
            array(
                "title" => __("WordPress version", 'buwd'),
                "value" => get_bloginfo("version"),
            ),
            array(
                "title" => __("Backup WD version", 'buwd'),
                "value" => BUWD_VERSION,
            ),
            array(
                "title" => __("PHP version", 'buwd'),
                "value" => PHP_VERSION,
            ),
            array(
                "title" => __("MySQL version", 'buwd'),
                "value" => $sql_version,
            ),
            array(
                "title" => __("cURL version", 'buwd'),
                "value" => $curl_version["version"],
            ),
            array(
                "title" => __("cURL SSL version", 'buwd'),
                "value" => $curl_version["ssl_version"],
            ),
            array(
                "title" => __("WP-cron url", 'buwd'),
                "value" => site_url('wp-cron.php'),
            ),
            array(
                "title" => __("Log folder", 'buwd'),
                "value" => str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd::get_plugin_data('log_folder_dir')),
            ),
            array(
                "title" => __("Temp folder", 'buwd'),
                "value" => Buwd::get_plugin_data('temp_folder_dir'),
            ),
            array(
                "title" => __("Server", 'buwd'),
                "value" => $_SERVER['SERVER_SOFTWARE'],
            ),
            array(
                "title" => __("Operating system", 'buwd'),
                "value" => PHP_OS . " (" . (PHP_INT_SIZE * 8) . ")",
            ),
            array(
                "title" => __("PHP SAPI", 'buwd'),
                "value" => PHP_SAPI,
            ),
            array(
                "title" => __("Current PHP user", 'buwd'),
                "value" => (function_exists('get_current_user') ? get_current_user() : __('Function disabled', 'buwd')),
            ),
            array(
                "title" => __("Maximum execution time", 'buwd'),
                "value" => ini_get('max_execution_time') . " " . __("seconds", 'buwd'),
            ),
            array(
                "title" => __("Alternative WP cron", 'buwd'),
                "value" => (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ? __("On", 'buwd') : __("Off", 'buwd')),
            ),
            array(
                "title" => __("Disabled WP cron", 'buwd'),
                "value" => (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON ? __("On", 'buwd') : __("Off", 'buwd')),
            ),
            array(
                "title" => __("CHMOD Dir", 'buwd'),
                "value" => defined('FS_CHMOD_DIR') ? FS_CHMOD_DIR : 0755,
            ),
            array(
                "title" => __("Server Time", 'buwd'),
                "value" => $now['tm_hour'] . ':' . $now['tm_min'],
            ),
            array(
                "title" => __("Blog Time", 'buwd'),
                "value" => date('H:i', current_time('timestamp')),
            ),
            array(
                "title" => __("Blog Timezone", 'buwd'),
                "value" => get_site_option('timezone_string'),
            ),
            array(
                "title" => __("Blog Time offset", 'buwd'),
                "value" => get_site_option('gmt_offset') . " " . __("hours", 'buwd'),
            ),
            array(
                "title" => __("Blog language", 'buwd'),
                "value" => get_bloginfo('language'),
            ),
            array(
                "title" => __("MySQL Client encoding", 'buwd'),
                "value" => defined('DB_CHARSET') ? DB_CHARSET : '',
            ),
            array(
                "title" => __("Blog charset", 'buwd'),
                "value" => get_bloginfo('charset'),
            ),
            array(
                "title" => __("PHP memory limit", 'buwd'),
                "value" => ini_get('memory_limit'),
            ),
            array(
                "title" => __("WP memory limit", 'buwd'),
                "value" => WP_MEMORY_LIMIT,
            ),
            array(
                "title" => __("WP maximum memory limit", 'buwd'),
                "value" => WP_MAX_MEMORY_LIMIT,
            ),
            array(
                "title" => __("Memory in use", 'buwd'),
                "value" => size_format(@memory_get_usage(true), 2),
            ),
            array(
                "title" => __("Loaded PHP extensions", 'buwd'),
                "value" => implode(', ', get_loaded_extensions()),
            ),
            array(
                "title" => __("Disabled PHP Functions", 'buwd'),
                "value" => ini_get('disable_functions'),
            ),

        );

        $info_fields = array();

        foreach ($info as $row) {
            $field = array(
                'label' => $row["title"],
                'name'  => $row["title"],
                'type'  => 'paragraph',
                'value' => $row["value"],
                'class' => array('buwd-info'),
            );
            $info_fields[] = $field;
        }

        return $info_fields;
    }

    private static function export()
    {
        $filter = array("drop_key",
            "drop_secret_key",
            "drop_sandbox_key",
            "drop_sandbox_secret",
            "gd_client_id",
            "gd_client_secret",
            "gd_redirect");

        $settings = Buwd_Options::get_settings_options($filter, false);

        $settings['buwd_export'] = '1';
        $settings_json = json_encode($settings);

        if ($fIn = fopen(Buwd::get_plugin_data('temp_folder_dir') . 'settings.json', 'w')) {
            fwrite($fIn, $settings_json);
        }

        fclose($fIn);

        @set_time_limit(300);
        nocache_headers();
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $file_name = Buwd::get_plugin_data('temp_folder_dir') . 'settings.json';

        header("Content-Description: File Transfer");
        header("Content-Type:  application/octet-stream");
        header("Content-disposition: attachment; filename=\"" . basename($file_name) . "\"");
        header("Content-Transfer-Encoding: Binary");
        header("Connection: Keep-Alive");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: public");
        header("Content-Length: " . filesize($file_name));
        readfile($file_name);
        die();


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