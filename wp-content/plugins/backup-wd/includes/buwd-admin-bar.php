<?php

/**
 *
 */
class Buwd_Admin_Bar
{
    private static $instance = null;

    private function __construct()
    {
        add_action('admin_bar_menu', array($this, "admin_bar"), 100);
    }

    public static function get_instance()
    {
        if (null === self::$instance && is_admin_bar_showing()) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function admin_bar()
    {
        global $wp_admin_bar;

        $wp_admin_bar->add_menu(array(
            'id'    => 'buwd',
            'title' => __('Backup WD', "buwd"),
            'href'  => network_admin_url("admin.php?page=buwd_dashboard"),
            'meta'  => array('title' => __('Backup WD', "buwd"))
        ));


        // jobs
        if (current_user_can("buwd_job")) {
            $wp_admin_bar->add_menu(array(
                'id'     => 'buwd_jobs',
                'parent' => 'buwd',
                'title'  => __('Jobs', "buwd"),
                'href'   => network_admin_url("admin.php?page=buwd_jobs"),
                'meta'   => array('title' => __('Jobs', "buwd"))
            ));
        }

        if (current_user_can("buwd_job_edit")) {
            $wp_admin_bar->add_menu(array(
                'id'     => 'buwd_add_job',
                'parent' => 'buwd_jobs',
                'title'  => __('Add new job', "buwd"),
                'href'   => network_admin_url("admin.php?page=buwd_editjob"),
                'meta'   => array('title' => __('Add new job', "buwd"))
            ));
        }

        $jobs = Buwd_Options::get_job_ids();

        foreach ($jobs as $job_id) {
            $job_name = Buwd_Options::get($job_id, 'name');
            if (current_user_can("buwd_job")) {
                $wp_admin_bar->add_menu(array(
                    'id'     => 'buwd_job' . $job_id,
                    'parent' => 'buwd_jobs',
                    'title'  => $job_name,
                    'href'   => network_admin_url("admin.php?page=buwd_editjob&job_id=" . $job_id),
                    'meta'   => array('title' => $job_name)
                ));
            }

            $url = wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=run&job_id=' . $job_id, 'job-run-' . $job_id);

            if (current_user_can("buwd_job_run")) {
                $wp_admin_bar->add_menu(array(
                    'id'     => 'buwd_job_run' . $job_id,
                    'parent' => 'buwd_job' . $job_id,
                    'title'  => __("Run Job", "buwd"),
                    'href'   => '#',
                    'meta'   => array('title' => __('Run Job', "buwd"), 'onclick' => 'buwd_run_action("' . $url . '")')
                ));
            }

        }


        // logs
        if (current_user_can("buwd_logs")) {
            $wp_admin_bar->add_menu(array(
                'id'     => 'buwd_logs',
                'parent' => 'buwd',
                'title'  => __('Logs', "buwd"),
                'href'   => network_admin_url("admin.php?page=buwd_logs"),
                'meta'   => array('title' => __('Logs', "buwd"))
            ));
        }

        // backups
        if (current_user_can("buwd_backups")) {
            $wp_admin_bar->add_menu(array(
                'id'     => 'buwd_backups',
                'parent' => 'buwd',
                'title'  => __('Backups', "buwd"),
                'href'   => network_admin_url("admin.php?page=buwd_backups"),
                'meta'   => array('title' => __('Backups', "buwd"))
            ));
        }

    }


}

?>