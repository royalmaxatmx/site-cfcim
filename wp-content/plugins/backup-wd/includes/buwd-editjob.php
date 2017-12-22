<?php

/**
 *
 */
class Buwd_Editjob
{
    protected static $instance = null;
    public $info = array();

    public function __construct()
    {
        $this->info['job_id'] = $this->get_jobid();
        $this->info['tab'] = $this->get_tab();
        $this->save_dafaults();
    }

    /**
     *
     *
     * @return int
     */

    public function save_dafaults()
    {
        $job_maxid = Buwd_Options::get_job_maxid();
        if ($this->info['job_id'] == ($job_maxid + 1)) {
            $default_options = Buwd_Options::job_defaults();
            foreach ($default_options as $option_key => $default_option) {
                Buwd_Options::update_job_option($this->info['job_id'], $option_key, $default_option);
            }
        }
    }

    public function get_jobid()
    {
        $job_id = Buwd_Helper::get("job_id") ? (int)Buwd_Helper::get("job_id") : 0;

        if (!$job_id) {
            $job_id = Buwd_Options::get_job_maxid() + 1;
        }

        return (int)$job_id;
    }

    public function get_tab()
    {
        return Buwd_Helper::get("tab") ? Buwd_Helper::get("tab") : "general";
    }

    public function get_tabs()
    {
        $tabs = array(
            'general'  => array(
                'name'    => esc_html__('General', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            ),
            'schedule' => array(
                'name'    => __('Schedule', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            ),
        );

        // add job type tabs
        $job_type = Buwd_Options::get($this->info['job_id'], 'type');

        $job_types = Buwd::get_job_types();
        if ($job_types) {
            foreach ($job_types as $type_id => $job_type_info) {
                $tabs['type-' . $type_id]['name'] = $job_type_info->info['name'];
                $tabs['type-' . $type_id]['view'] = array($this, 'display_tab');
                $tabs['type-' . $type_id]['display'] = true;
                if (!in_array($type_id, (array)$job_type, true)) {
                    $tabs['type-' . $type_id]['display'] = false;
                }
            }
        }

        // add job type tabs
        $job_destination = Buwd_Options::get($this->info['job_id'], 'destination');

        $destinations = Buwd::get_destinations();
        if ($destinations) {
            foreach ($destinations as $dest_id => $destination) {
                $tabs['destination-' . $dest_id]['name'] = $destination->info['name'];
                $tabs['destination-' . $dest_id]['view'] = array($this, 'display_tab');
                $tabs['destination-' . $dest_id]['messages'] = array($destination, 'display_messages');
                $tabs['destination-' . $dest_id]['display'] = true;
                if (!in_array($dest_id, (array)$job_destination, true)) {
                    $tabs['destination-' . $dest_id]['display'] = false;
                }
            }
        }

        return $tabs;
    }

    public function get_user_guide($current_tab = 'general')
    {
        $user_guide = array(
            'general'                    => array(
                'title' => __('This section allows you to configure General tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/creating-new-job/general.html',
            ),
            'schedule'                   => array(
                'title' => __('This section allows you to configure Schedule tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/creating-new-job/schedule.html',
            ),
            'type-db'                    => array(
                'title' => __('This section allows you to configure DB Backup tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/creating-new-job/db-backup.html',
            ),
            'type-files'                 => array(
                'title' => __('This section allows you to configure Files Backup tab options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/creating-new-job/files-backup.html',
            ),
            'destination-folder'         => array(
                'title' => 'This section allows you to configure Folder destination options.',
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/folder.html',
            ),
            'destination-gdrive'         => array(
                'title' => __('This section allows you to configure Google Drive destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/google-drive.html',
            ),
            'destination-amazon-s3'      => array(
                'title' => __('This section allows you to configure Amazon S3 destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/amazon-s3.html',
            ),
            'destination-dropbox'        => array(
                'title' => __('This section allows you to configure Dropbox destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/dropbox.html',
            ),
            'destination-azure'          => array(
                'title' => __('This section allows you to configure Microsoft Azure destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/microsoft-azure.html',
            ),
            'destination-rsc'            => array(
                'title' => __('This section allows you to configure RackSpace Cloud destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/rackspace-cloud.html',
            ),
            'destination-ftp'            => array(
                'title' => __('This section allows you to configure FTP destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/ftp.html',
            ),
            'destination-sugarsync'      => array(
                'title' => __('This section allows you to configure SugarSync destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/sugarsync.html',
            ),
            'destination-amazon-glacier' => array(
                'title' => __('This section allows you to configure Amazon Glacier destination options.'),
                'url'   => 'https://web-dorado.com/wordpress-backupwd-guide/backup-destinations/amazon-glacier.html',
            ),
        );

        return $user_guide[$current_tab];
    }

    public function general_tab_options()
    {
        $backup_type = Buwd_Options::get($this->info['job_id'], 'backup_type');
        $job_types = array_map(function (&$v) {
            return $v->info['name'];
        }, Buwd::get_job_types());

        $destinations = array_map(function (&$v) {
            return $v->info['name'];
        }, Buwd::get_destinations());

        $dest_visibility = array();
        if ($backup_type == 'sync') {
            $dests_exclude = array('ftp', 'sugarsync', 'amazon-glacier', 'gdrive');
            foreach ($destinations as $key => $dest) {
                if (in_array($key, $dests_exclude)) {
                    $dest_visibility[$key] = 'hidden';
                    continue;
                }
            }
        }

        $date_formats = Buwd_Helper::date_formats();
        $archive_name_replacement_html = '<div class="buwd-filename-replacement buwd-hide">';
        foreach ($date_formats as $date_symbol => $date_format) {
            $archive_name_replacement_html .= '<a class="date-symbol" data-value="' . $date_symbol . '">' . $date_symbol . ' - ' . $date_format . '</a>';
        }
        $archive_name_replacement_html .= '</div>';

        return array(
            'key'    => 'general',
            'title'  => '',
            'fields' => array(
                array(
                    'label'  => __('Job Name', 'buwd'),
                    'header' => __('Job Options', 'buwd'),
                    'id'     => 'name',
                    'name'   => 'name',
                    'type'   => 'text',
                    'class'  => array(
                        'buwd-large-text',
                    ),
                    'hint'   => array(
                        'html' => '<p class="description">Write a name for this backup job.</p>',
                    ),
                    'value'  => esc_attr(Buwd_Options::get($this->info['job_id'], 'name')),
                ),
                array(
                    'label'   => __('Job Type', 'buwd'),
                    'id'      => 'type',
                    'name'    => 'type',
                    'type'    => 'checkbox',
                    'class'   => array(
                        'job-type',
                        'toggle-tab',
                    ),
                    'choices' => $job_types,
                    'value'   => Buwd_Options::get($this->info['job_id'], 'type'),
                    'column'  => 3,
                    'attr'    => array(
                        'icon'    => true,
                        'reverse' => true,
                    ),
                    'hint'    => array(
                        'html' => '<p class="description">Select the type of this backup job, website database, files or both.</p>',
                    ),
                ),
                array(
                    'label'   => __('Backup file type', 'buwd'),
                    'header'  => __('Backup File Creation', 'buwd'),
                    'id'      => 'backup_type',
                    'name'    => 'backup_type',
                    'type'    => 'radio',
                    'class'   => array(
                        'buwd-type',
                    ),
                    'choices' => array(
                        'archive' => 'Archive',
                        'sync'    => 'Sync',
                    ),
                    'value'   => $backup_type,
                    'column'  => 2,
                    'hint'    => array(
                        'html' => '<p class="description">Choose the type of the backup file. <b>Archive</b> creates a compressed file with the backup, whereas <b>Sync</b> synchronizes the updated files on the previous backup.</p>',
                    ),
                ),
                array(
                    'label'   => __('Backup to', 'buwd'),
                    'header'  => __('Destination', 'buwd'),
                    'id'      => 'destination',
                    'name'    => 'destination',
                    'type'    => 'checkbox',
                    'class'   => array(
                        'job-destination',
                        'toggle-tab',
                    ),
                    'choices' => $destinations,
                    'value'   => Buwd_Options::get($this->info['job_id'], 'destination'),
                    'column'  => 3,
                    'attr'    => array(
                        'icon'       => true,
                        'reverse'    => true,
                        'visibility' => $dest_visibility,
                        'disabled'   => extension_loaded('ftp') === false ? array('ftp' => 'disabled') : '',
                        'title'      => extension_loaded('ftp') === false ? array('ftp' => 'PHP FTP extension not loaded') : '',
                    ),
                    'hint'    => array(
                        'html' => '<p class="description">Choose the destination of the backup file. It can be saved on a cloud hosting service account, or to your website directory.</p>',
                    ),
                ),
                array(
                    'label'      => __('Backup archive name', 'buwd'),
                    'id'         => 'archive_name',
                    'name'       => 'archive_name',
                    'type'       => 'text',
                    'class'      => array(
                        'buwd-extra-large-text',
                        'buwd-format-name',
                    ),
                    'value'      => esc_attr(Buwd_Options::get($this->info['job_id'], 'archive_name')),
                    'hint'       => array(
                        'html' => '<div class="buwd-filename-preview">Preview: <span class="buwd-filename-preview-text">' . Buwd_Job::gen_file_name(Buwd_Options::get($this->info['job_id'], 'archive_name')) . '</span><span class=""></span></div><div class="buwd-extra-large-text">' . $archive_name_replacement_html . '</div><p class="description">Provide a name for your backup archive file. You can edit it as necessary, or click on the input field to use available suggestions. However, we do not recommend removing {hash_key} component. See {hash_key} description on Backup WD > Settings ><a href="' . admin_url("admin.php?page=buwd_settings&tab=general") . '" target="_blank">Global Options tab</a>. Preview option will show the final output of the backup archive name.
</p>',
                    ),
                    'visibility' => Buwd_Options::get($this->info['job_id'], 'backup_type') == 'archive' ? true : false,
                ),
                array(
                    'label'      => __('Backup archive format', 'buwd'),
                    'id'         => 'archive_format',
                    'name'       => 'archive_format',
                    'type'       => 'radio',
                    'class'      => array(
                        'archive-format',
                    ),
                    'choices'    => array(
                        'zip'       => 'Zip',
                        'tar'       => 'Tar',
                        'tar_gzip'  => 'Tar GZip',
                        'tar_bzip2' => 'Tar BZip2',
                    ),
                    'value'      => Buwd_Options::get($this->info['job_id'], 'archive_format'),
                    'visibility' => Buwd_Options::get($this->info['job_id'], 'backup_type') == 'archive' ? true : false,
                    'column'     => 2,
                    'attr'    => array(
                        'visibility' => $dest_visibility,
                        'disabled'   => extension_loaded('zip') === false ? array('zip' => 'disabled') : '',
                        'title'      => extension_loaded('zip') === false ? array('zip' => 'PHP ZIP extension not loaded') : '',
                    ),
                    'hint'       => array(
                        'html' => '<p class="description">Select the format of the backup archive file.</p>',
                    ),
                ),
                array(
                    'label'   => __('Send Email', 'buwd'),
                    'header'  => __('Email Options', 'buwd'),
                    'id'      => 'send_email',
                    'name'    => 'send_email',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => 'Send email containing the log of this backup job.'
                    ),
                    'value'   => Buwd_Options::get($this->info['job_id'], 'send_email'),
                    'hint'    => array(
                        'html' => '<p class="description">You can edit email options from Backup WD > Settings > <a href="' . admin_url("admin.php?page=buwd_settings&tab=email") . '" target="_blank">Email Options tab</a>.</p>',
                    ),
                ),
            ),
        );
    }

    public function schedule_tab_options()
    {
        $month_options = Buwd_Helper::month_options();
        $week_options = Buwd_Helper::week_options();
        //$day_options = Buwd_Helper::day_options();
        $hour_options = Buwd_Helper::hour_options();
        $minute_options = Buwd_Helper::minute_options();
        $url = Buwd_Job::get_job_run_url("run", $this->info['job_id']);

        $cron_choices = array(
            'manually' => 'Manually',
            'wpcron'   => 'Wordpress Cron',
            'link'     => 'Link',
        );


        if (isset($_GET['devmode']) && (int)$_GET['devmode'] == 1) {
            $cron_choices = array_merge($cron_choices, array(
                'easycron' => 'EasyCron <a href="' . network_admin_url('admin.php?page=buwd_api_keys&tab=easy-cron&devmode=1') . '" target="_blank">Setup API key</a>',
            ));
        }

        return array(
            'key'    => 'schedule',
            'title'  => '',
            'fields' => array(
                array(
                    'label'   => __('Start job with', 'buwd'),
                    'header'  => __('Schedule', 'buwd'),
                    'id'      => 'schedule',
                    'name'    => 'schedule',
                    'type'    => 'radio',
                    'class'   => array(
                        'buwd-schedule',
                    ),
                    'choices' => $cron_choices,
                    'value'   => Buwd_Options::get($this->info['job_id'], 'schedule'),
                    'column'  => 2,
                    'attr'    => array(),
                    'hint'    => array(
                        'html' => '<p class="description">Choose a method which will be used to run this backup job. With <b>Manually</b> method, the backup job only runs when you manually click Run from Backup WD > Jobs page.  With <b>Link</b> method backup will only occur in case you run the link below.</p>',
                    ),
                ),

                array(
                    'label'   => __('Schedule type', 'buwd'),
                    'id'      => 'scheduletype',
                    'name'    => 'scheduletype',
                    'type'    => 'radio',
                    'class'   => array(
                        'buwd-schedule-type'
                    ),
                    'choices' => array(
                        'basic'    => 'Basic',
                        'advanced' => 'Advanced',

                    ),
                    'column'  => 2,
                    'value'   => Buwd_Options::get($this->info['job_id'], 'scheduletype'),
                    'hint'    => array(
                        'html' => '<p class="description">Choose the setup type to schedule automatic backup, <b>Basic</b> or <b>Advanced</b>. They let you schedule specific date when the backup will be done automatically.</p>',
                    ),

                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) ? true : false,
                ),
                array(
                    'label' => __('Scheduler', 'buwd'),
                    'id'    => 'cron_expression',
                    'name'  => 'cron_expression',
                    'type'  => 'hidden',
                    'class' => array(
                        'cron_expression'
                    ),
                    'hint'  => array(
                        'pos'  => 'after',
                        'html' => ' <div id="buwd_cron_expression_select"></div> ',
                    ),

                    'value'      => Buwd_Options::get($this->info['job_id'], 'cron_expression'),
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'basic' ? true : false,
                ),
                array(
                    'label'      => '',
                    'id'         => 'schedulelink',
                    'name'       => 'schedulelink',
                    'type'       => 'text',
                    'class'      => array(
                        'buwd-schedule-link',
                        'buwd-extra-large-text',
                    ),
                    'value'      => $url,
                    'attr'       => array(
                        'readonly' => 'readonly',
                        'onclick'  => 'jQuery(this).select(); return false;',
                    ),
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'link',
                    )) ? true : false,
                ),
                array(
                    'label'      => __('Month', 'buwd'),
                    'id'         => 'schedulemonth',
                    'name'       => 'schedulemonth',
                    'type'       => 'checkbox',
                    'class'      => array(
                        'schedulemonth',
                        'scheduleadvanced',
                    ),
                    'choices'    => $month_options,
                    'value'      => Buwd_Options::get($this->info['job_id'], 'schedulemonth'),
                    'column'     => 2,
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false,
                ),
                array(
                    'label'      => __('Week', 'buwd'),
                    'id'         => 'scheduleweek',
                    'name'       => 'scheduleweek',
                    'type'       => 'checkbox',
                    'class'      => array(
                        'scheduleweek',
                        'scheduleadvanced',
                    ),
                    'choices'    => $week_options,
                    'value'      => Buwd_Options::get($this->info['job_id'], 'scheduleweek'),
                    'column'     => 2,
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false,
                ),
                /*               array(
                                    'label'      => __('Day', 'buwd'),
                                    'id'         => 'scheduleday',
                                    'name'       => 'scheduleday',
                                    'type'       => 'select',
                                    'class'      => array(
                                        'scheduleday',
                                        'scheduleadvanced',
                                        'buwd-small-select',
                                    ),
                                    'choices'    => $day_options,
                                    'value'      => Buwd_Options::get($this->info['job_id'], 'scheduleday'),
                                    'multiple'   => true,
                                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                                        'wpcron',
                                        'easycron',
                                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false,
                                ),*/
                array(
                    'label' => __('Day', 'buwd'),
                    'id'    => 'day',
                    'name'  => 'day',
                    'type'  => 'day',
                    'class' => array(
                        'scheduleday',
                        'scheduleadvanced',
                    ),
                    'value' => Buwd_Options::get($this->info['job_id'], 'scheduleday'),

                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false
                ),
                array(
                    'label' => '',
                    'id'    => 'scheduleday',
                    'name'  => 'scheduleday',
                    'type'  => 'hidden',
                    'class' => array(),
                    'value' => Buwd_Options::get($this->info['job_id'], 'scheduleday'),
                ),

                array(
                    'label'      => __('Time', 'buwd'),
                    'id'         => 'schedulehour',
                    'name'       => 'schedulehour',
                    'type'       => 'time',
                    'class'      => array(
                        'schedulehour',
                        'scheduleadvanced',
                    ),
                    'choices'    => $hour_options,
                    'value'      => Buwd_Options::get($this->info['job_id'], 'schedulehour'),
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false,
                    'hint'       => array(
                        'pos'  => 'after',
                        'html' => ' - ',
                    ),
                    'child'      => 1,
                ),
                array(
                    'label'      => '',
                    'id'         => 'scheduleminute',
                    'name'       => 'scheduleminute',
                    'type'       => 'time',
                    'class'      => array(),
                    'choices'    => $minute_options,
                    'value'      => Buwd_Options::get($this->info['job_id'], 'scheduleminute'),
                    'visibility' => in_array(Buwd_Options::get($this->info['job_id'], 'schedule'), array(
                        'wpcron',
                        'easycron',
                    )) && Buwd_Options::get($this->info['job_id'], 'scheduletype') == 'advanced' ? true : false,
                    'hint'       => array(
                        'pos'  => 'after',
                        'html' => '<div><span class="time-size">' . __('Hour', 'buwd') . '</span> - <span class="time-size">' . __('Minute', 'buwd') . '</span></div>',
                    ),
                    'child'      => 2
                ),
            ),
        );
    }

    public function get_tab_options($tab_id = 'general')
    {
        if (strpos($tab_id, 'type-') !== false) {
            $key = str_replace('type-', '', $tab_id);

            return Buwd::get_job_type($key)->get_options($this->info['job_id']);
        }

        if (strpos($tab_id, 'destination-') !== false) {
            $key = str_replace('destination-', '', $tab_id);

            return Buwd::get_destination($key)->get_options($this->info['job_id']);
        }

        if (method_exists($this, $tab_id . '_tab_options')) {
            $method_name = $tab_id . '_tab_options';

            return $this->$method_name();
        } else {
            return $this->general_tab_options();
        }
    }

    public function get_footer($tab_id = 'general')
    {
        if (in_array($tab_id, array('destination-gdrive', 'destination-dropbox', 'destination-sugarsync'))) {
            $key = str_replace('destination-', '', $tab_id);

            return Buwd::get_destination($key)->get_footer();
        }

        return '<button class="buwd-button button-save" onclick="if(jQuery(this.form).valid()){this.form.submit()}"><span></span>Save</button>';
    }

    public function render_tab($tab_id = 'general')
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
        $group_html['footer'] = $this->get_footer($tab_id);

        return $group_html;
    }

    public static function admin_print_scripts()
    {
        global $wpdb;
        wp_enqueue_script(BUWD_PREFIX . '-editjob', BUWD_URL . '/public/js/editjob.js', array(), BUWD_VERSION, true);
        wp_localize_script(BUWD_PREFIX . '-editjob', 'buwd', array(
            'db_prefix' => $wpdb->prefix,
            'ajaxurl'   => admin_url('admin-ajax.php'),
            'hash_key'  => Buwd::get_plugin_data('hash'),
        ));
        wp_enqueue_script('jquery-ui');
        wp_enqueue_script('jquery-ui-tooltip');
    }

    public static function admin_print_styles()
    {
        wp_enqueue_style('buwd-editjob', BUWD_URL . '/public/css/editjob.css', array(), BUWD_VERSION);
    }

    public function display_page()
    {
        /*	if ( ! isset( $_GET['nonce_buwd'] ) || ! wp_verify_nonce( $_GET['nonce_buwd'], 'nonce_buwd' ) ) {
                die( 'verify nonce' );
            }*/

        $job_id = $this->info['job_id'];
        $current_tab = $this->get_tab();
        $tabs = $this->get_tabs();
        $job_title = Buwd_Options::get($job_id, 'name');

        $user_guide = $this->get_user_guide($current_tab);

        include_once(BUWD_DIR . '/views/editjob.php');
    }

    public function display_tab()
    {
        $current_tab = $this->get_tab();
        $tab_data = $this->render_tab($current_tab);

        include_once(BUWD_DIR . '/views/editjob-' . $current_tab . '.php');
    }

    public function display_messages()
    {
        $current_tab = $this->get_tab();
        if ($errors = get_site_transient('buwd_editjob_errors')) {
            $options = $this->get_tab_options($current_tab);
            $group_class = new Buwd_Form_Group(array($options));

            foreach ($errors as $f_key => $error) {
                foreach ($error as $err) {
                    $element = $group_class->get_element($f_key);
                    $label = $element->get_label();
                    echo Buwd_Helper::message($label . ': ' . $err, 'error');
                    delete_site_transient('buwd_editjob_errors');
                }
            }
        } else if ($updated = get_site_transient('buwd_editjob_updated')) {
            $job_id = $this->info['job_id'];
            $joblist_url = '<a href="' . admin_url('admin.php?page=buwd_jobs') . '">' . esc_html__("Jobs List", "buwd") . '</a>';
            $run_url = '<a onclick="buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=run&job_id=' . $job_id, 'job-run-' . $job_id) . '\');return false" href="">' . esc_html__("Run now", "buwd") . '</a>';
            echo Buwd_Helper::message($updated . ' ' . $joblist_url . ' | ' . $run_url, 'success');
            delete_site_transient('buwd_editjob_updated');
        }

        Buwd_Jobs::display_messages();
    }

    public function save_form($job_id, $current_tab)
    {
        $job_id = (int)$job_id;
        Buwd_Options::update_job_maxid($this->info['job_id']);
        $options = $this->get_tab_options($current_tab);
        $group_class = new Buwd_Form_Group(array($options));
        $fields = $group_class->get_fields();
        $field_names = array_keys($fields);
        $destinations = Buwd::get_destinations();

        $redirect_tab = Buwd_Helper::get('tab') ? Buwd_Helper::get('tab') : $current_tab;
        $redirect_url = array();
        $redirect_url['page'] = 'buwd_editjob';
        $redirect_url['job_id'] = $job_id;
        $field_values = array();
        foreach ($field_names as $field_name) {
            $field_values[$field_name] = Buwd_Helper::get($field_name) ? Buwd_Helper::get($field_name) : '';
        }

        $is_valid = $group_class->is_valid($field_values);
        if (!$is_valid) {
            set_site_transient('buwd_editjob_errors', $group_class->get_errors());
            $redirect_url['tab'] = sanitize_text_field($current_tab);
        } else {
            $redirect_url['tab'] = sanitize_text_field($redirect_tab);
            if (strpos($current_tab, 'destination-') !== false) {
                $destinations[str_replace('destination-', '', $current_tab)]->save_form($job_id, $field_names);
            } else {
                foreach ($field_names as $field_name) {
                    $field_value = $field_values[$field_name];
                    if (in_array($field_name, array('name', 'day', 'scheduleday', 'schedulehour', 'scheduleminute'))) {
                        $field_value = sanitize_text_field($field_value);
                    }

                    if ($field_name == 'archive_name') {
                        $field_value = Buwd_File::sanitize_filename($field_value);
                    }

                    if ($field_name == 'schedulelink') {
                        $field_value = esc_url_raw($field_value);
                    }


                    if (in_array($field_name, array('bup_extra_folders', 'exclude_types'))) {

                        $textarea_field = explode(',', sanitize_text_field(stripslashes(str_replace(array("\r\n", "\r"), ',', $field_value))));
                        foreach ($textarea_field as $key => $value) {
                            $textarea_field[$key] = trailingslashit(str_replace('//', '/', str_replace('\\', '/', trim($value))));
                            if ($textarea_field[$key] == '/' || empty($textarea_field[$key]) || !is_dir($textarea_field[$key])) {

                                unset($textarea_field[$key]);
                            }
                        }
                        sort($textarea_field);
                        $field_value = implode(',', $textarea_field);
                    }
                    Buwd_Options::update_job_option($job_id, $field_name, $field_value);
                }
            }
        }
        Buwd_Options::update_job_option($job_id, 'job_id', $job_id);

        if (Buwd_Options::get($job_id, 'scheduletype') == 'advanced') {
            $minute = Buwd_Options::get($job_id, 'scheduleminute');
            $hour = Buwd_Options::get($job_id, 'schedulehour');
            $days = Buwd_Options::get($job_id, 'scheduleday');
            $wday = Buwd_Options::get($job_id, 'scheduleweek');
            $months = Buwd_Options::get($job_id, 'schedulemonth');

            $cron_expression = $minute
                . ' ' . $hour
                . ' ' . Buwd_Cron::generate_expression(array_filter(explode(',', $days)))
                . ' ' . Buwd_Cron::generate_expression($months)
                . ' ' . Buwd_Cron::generate_expression($wday);
            Buwd_Options::update_job_option($job_id, 'cron_expression', $cron_expression);
        }

        wp_clear_scheduled_hook('buwd_cron', array('id' => $job_id));

        if (get_site_option('buwd_easycron_' . $job_id)) {
            Buwd_Cron::edit_easycron($job_id);
        }


        set_site_transient('buwd_editjob_updated', 'Successfully Updated');
        Buwd_Helper::redirect($redirect_url);
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