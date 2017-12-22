<?php

/**
 *
 */
class Buwd_Api_Keys
{
    protected static $instance = null;
    public $info = array();
    private $page_id = 'buwd_api_keys';

    public function __construct()
    {
        $this->info['tab'] = $this->get_tab();
    }

    /**
     * get current tab
     * @return bool|null|string
     */
    private function get_tab()
    {
        return Buwd_Helper::get("tab") ? Buwd_Helper::get("tab") : "gdrive";
    }

    /**
     * set tabs for settings view
     */
    private function get_tabs()
    {
        $tabs = array(
            'gdrive'     => array(
                'name'    => esc_html__('Google Drive', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            ),
            'dropbox'    => array(
                'name'    => __('Dropbox', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            ),
            'sugar-sync' => array(
                'name'    => __('SugarSync', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            )

        );

        if (isset($_GET['devmode']) && $_GET['devmode'] == 1) {
            $tabs = array_merge($tabs, array(
                'easy-cron' => array(
                    'name'    => __('EasyCron', 'buwd'),
                    'view'    => array($this, 'display_tab'),
                    'display' => true
                ),
            ));
        }

        return $tabs;
    }

    public function get_user_guide($current_tab = null)
    {
        $user_guide = array(
            'gdrive'     => array(
                'title' => __('This section allows you to configure Google Drive tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/api-keys/google-drive.html',
            ),
            'dropbox'    => array(
                'title' => __('This section allows you to configure Drobpox tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/api-keys/dropbox.html',
            ),
            'sugar-sync' => array(
                'title' => __('This section allows you to configure SugarSync tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/api-keys/sugarsync.html',
            ),
        );

        if ($current_tab && isset($user_guide[$current_tab])) {
            return $user_guide[$current_tab];
        }

        return $user_guide;
    }

    /**
     * Display Current tab data
     */
    private function display_tab($current_tab)
    {
        $tab_data = $this->render_tab($current_tab);

        return $tab_data;
    }

    /**
     * @param string $tab_id
     *
     * @return array
     * Generate HTML for current tab
     */
    private function render_tab($tab_id = 'gdrive')
    {
        $options = $this->get_tab_options($tab_id);
        $group_class = new Buwd_Form_Group(array($options));
        $groups = $group_class->get_groups();
        $group_html = array();
        foreach ($groups as $g_name => $group) {
            $group_html['title'] = $group->title;
            $group_html['desc'] = $group->desc;
            $group_html['content'] = $group_class->render_group($g_name);
        }

        return $group_html;
    }

    /**
     * @param $tab_id
     * get current tab elements
     *
     * @return array
     */
    private function get_tab_options($tab_id = 'gdrive')
    {
        $tabs_options = $this->tabs_options();

        return $tabs_options[$tab_id];
    }

    /**
     * set api keys elements
     * field types ( text, number, radio, checkbox, select, file, textarea, hidden )
     */
    private function tabs_options()
    {
        $options = array(
            'gdrive'     => array(
                'key'    => 'gdrive',
                'title'  => '',
                'desc'   => 'Go to <a href="https://console.developers.google.com" target="_blank">Google APIs Manager</a> page to create a new <b>OAuth Client ID</b>. Login with your Google account first. Note, that in case you have multiple Google accounts, you will need to log in with the one where you wish to keep the backup files.<br><br>Firstly, go to <b>Dashboard</b> tab after logging in and press <b>Enable APIs and Services</b> button. Click on <b>Drive API</b> link, then hit <b>Enable</b> button at the top.<br/><br>Navigate to <b>Credentials</b> tab, then to <b>OAuth Consent Screen</b> page. Use <b>Product name shown to users</b> option to set a product name, then press <b>Save.</b><br/><br>Afterwards, go back to <b>Credentials</b> tab and press <b>Create Credentials</b> button. Choose <b>OAuth Client ID</b> option. Select <b>Web application</b> option for <b>Application type.</b><br><br>Provide your website URL as the value of <b>Authorized JavaScript origins</b> input. As for <b>Authorized redirect URIs</b> option, you will need to provide the link in Redirect URIs option below exactly as it is. ',
                'fields' => array(
                    array(
                        'label' => 'Client ID',
                        'id'    => 'gd_client_id',
                        'name'  => 'gd_client_id',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('gd_client_id'),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">As soon as you are done creating the OAuth client, provide the Client ID of it on Google Drive tab of the plugin’s API Keys page.</p>'
                        ),
                    ),
                    array(
                        'label' => 'Client secret',
                        'id'    => 'gd_client_secret',
                        'name'  => 'gd_client_secret',
                        'type'  => 'password',
                        'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::getSetting('gd_client_secret'))),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Also copy Client Secret from your Google OAuth Client and paste it in this input.</p>'
                        ),
                    ),
                    array(
                        'label' => 'Redirect URIs',
                        'id'    => 'gd_redirect',
                        'name'  => 'gd_redirect',
                        'type'  => 'text',
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'value' => admin_url('admin-ajax.php') . '?action=buwd-gdrive',
                        'attr'  => array(
                            'readonly' => 'readonly',
                            'onclick'  => 'jQuery(this).select(); return false;',
                        ),
                        'hint'  => array(
                            'html' => '<p class="description">Use the URI provided by this option while creating your Google OAuth Client ID.</p>',
                        ),
                    ),
                ),
            ),
            'dropbox'    => array(
                'key'    => 'dropbox',
                'title'  => '',
                'desc'   => 'Use the settings below to add Dropbox API keys of your Dropbox account. You can find your Dropbox API keys from <a href="https://www.dropbox.com/developers/apps" target="_blank">Dropbox Apps page</a>.<br/><br/>Note, that Full Dropbox App and Sandbox App have different API keys. Make sure to add yours to the correct options.',
                'fields' => array(
                    array(
                        'label' => 'Full Dropbox App key',
                        'id'    => 'drop_key',
                        'name'  => 'drop_key',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('drop_key'),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label' => 'Full Dropbox App secret',
                        'id'    => 'drop_secret_key',
                        'name'  => 'drop_secret_key',
                        'type'  => 'password',
                        'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::getSetting('drop_secret_key'))),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label' => 'Sandbox App key',
                        'id'    => 'drop_sandbox_key',
                        'name'  => 'drop_sandbox_key',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('drop_sandbox_key'),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label' => 'Sandbox App secret',
                        'id'    => 'drop_sandbox_secret',
                        'name'  => 'drop_sandbox_secret',
                        'type'  => 'password',
                        'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::getSetting('drop_sandbox_secret'))),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'hint'  => array(),
                    ),
                ),
            ),
            'sugar-sync' => array(
                'key'    => 'sugar-sync',
                'title'  => '',
                'desc'   => 'Use the settings below to add Access Key ID, Private Access Key	and App ID from your SugarSync account.',
                'fields' => array(
                    array(
                        'label' => 'Access Key ID',
                        'id'    => 'sugar_key',
                        'name'  => 'sugar_key',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('sugar_key'),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label' => 'Private Access Key',
                        'id'    => 'sugar_secret_key',
                        'name'  => 'sugar_secret_key',
                        'type'  => 'password',
                        'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::getSetting('sugar_secret_key'))),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label' => 'App ID',
                        'id'    => 'sugar_app_id',
                        'name'  => 'sugar_app_id',
                        'type'  => 'text',
                        'value' => Buwd_Options::getSetting('sugar_app_id'),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                ),
            ),
            'easy-cron'  => array(
                'key'    => 'easy-cron',
                'title'  => '',
                'desc'   => 'Here you can setup your <a href="https://www.easycron.com/user/token?ref=36673" title="Affiliate Link!" target="_blank">EasyCron.com API key</a> to use this service.',
                'fields' => array(
                    array(
                        'label' => 'Api key',
                        'id'    => 'easy_cron_key',
                        'name'  => 'easy_cron_key',
                        'type'  => 'password',
                        'value' => Buwd_Encrypt::decrypt(esc_html(Buwd_Options::getSetting('easy_cron_key'))),
                        'class' => array(
                            'buwd-large-text',
                        ),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                        'hint'  => array(),
                    ),
                    array(
                        'label'   => 'Trigger WordPress Cron',
                        'id'      => 'easy_cron_trigger_wp',
                        'name'    => 'easy_cron_trigger_wp',
                        'type'    => 'checkbox',
                        'choices' => array(
                            '1' => 'If you check this box, a cron job will be created on EasyCron that all 5 Minutes calls the WordPress cron.'
                        ),
                        'value'   => Buwd_Options::getSetting('easy_cron_trigger_wp'),
                        'class'   => array(
                            'buwd-large-text',
                        ),
                        'hint'    => array(),
                    ),
                ),
            ),
        );

        return $options;
    }

    /**
     * @param $current_tab
     * Save Settings current tab data
     */
    public function save_form($current_tab)
    {
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
            $field_name = sanitize_text_field($field_name);

            if (in_array($field_name, array(
                'gd_client_secret',
                'drop_secret_key',
                'drop_sandbox_secret',
                'sugar_secret_key',
                'easy_cron_key'
            ))) {
                $field_value = Buwd_Encrypt::encrypt($field_value);
            }

            $new_settings[$field_name] = $field_value;
        }
        $new_settings = array_merge($old_settings, $new_settings);
        Buwd_Options::update_settings($new_settings);

        $redirect_url = array();
        $redirect_url['page'] = $this->page_id;
        $redirect_url['tab'] = $current_tab;

        if ($current_tab == 'easy-cron') {
            $redirect_url['devmode'] = 1;
        }

        set_site_transient('buwd_settings_updated', __('All options have been saved successfully.
', 'buwd'));
        Buwd_Helper::redirect($redirect_url);
    }

    /**
     * display settings view
     */
    public function display_page()
    {
        $current_tab = $this->get_tab();
        $tabs = $this->get_tabs();
        $user_guide = $this->get_user_guide();
        include_once(BUWD_DIR . '/views/api-keys.php');
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

    /**
     * Include styles
     */
    public static function admin_print_styles()
    {
        wp_enqueue_style(BUWD_PREFIX . 'buwd-editjob', BUWD_URL . '/public/css/editjob.css', array(), BUWD_VERSION);
    }

    /**
     * Include scripts
     */
    public static function admin_print_scripts()
    {
        wp_enqueue_script(BUWD_PREFIX . '-settings', BUWD_URL . '/public/js/settings.js', array(), BUWD_VERSION, true);
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