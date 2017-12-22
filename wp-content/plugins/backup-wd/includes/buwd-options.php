<?php

/**
 * Class for options
 */
class Buwd_Options
{
    /**
     *
     * set default options
     *
     */

    public static function set_default_options()
    {
        add_site_option('buwd_version', '1.0.0');
        add_site_option('buwd_hash', substr(md5(md5(__FILE__)), 10, 6));

        $default_settings = self::settings_defaults();
        self::update_settings($default_settings);

        $wp_upload_dir = Buwd::get_upload_dir();
        $wp_home_path = Buwd::get_plugin_data('home_path');

        $job_max_id = (int)get_site_option('buwd_job_maxid');
        if (!$job_max_id) {
            $log_folder = str_replace($wp_home_path, '', $wp_upload_dir) . str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd_Options::getSetting('log_folder'));
            $temp_folder = Buwd::get_plugin_data('temp_folder_dir');
            $dest_folder = Buwd::get_plugin_data('folder_name') . '-' . Buwd::get_plugin_data('hash'); //TODO

            global $wpdb;
            $archive_format = 'zip';
            if(extension_loaded('zip') === false) {
                $archive_format = 'tar';
            }
            $tables = $wpdb->get_results('SHOW TABLES FROM `' . DB_NAME . '`', ARRAY_N);
            $_tables = array();
            if ($tables) {
                foreach ($tables as $table) {
                    if (substr($table[0], 0, strlen($wpdb->prefix)) === $wpdb->prefix) {
                        $_tables[$table[0]] = $table[0];
                    }
                }
            }

            $default_jobs = array(
                1 => array(
                    'name'                 => 'Database Backup',
                    'type'                 =>
                        array(
                            0 => 'db'
                        ),
                    'destination'          =>
                        array(
                            0 => 'folder'
                        ),
                    'backup_type'          => 'archive',
                    'archive_name'         => 'backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s',
                    'archive_format'       => $archive_format,
                    'schedule'             => 'manually',
                    'scheduletype'         => 'basic',
                    'schedulemonth'        =>
                        array(
                            0  => 'any',
                            1  => '1',
                            2  => '2',
                            3  => '3',
                            4  => '4',
                            5  => '5',
                            6  => '6',
                            7  => '7',
                            8  => '8',
                            9  => '9',
                            10 => '10',
                            11 => '11',
                            12 => '12',
                        ),
                    'scheduleweek'         =>
                        array(
                            0 => '1',
                        ),
                    'dbtables_all'         => array(),
                    'dbtables'             => $_tables,
                    'dbfilename'           => sanitize_file_name(DB_NAME),
                    'use_wp_connection'    => '1',
                    'dbfilecomp'           => 'none',
                    'db_host'              => '',
                    'db_user'              => '',
                    'db_password'          => '',
                    'db_name'              => '',
                    'bup_root'             => '',
                    'bup_content'          => '',
                    'bup_content_exclude'  => '',
                    'bup_plugins'          => '1',
                    'bup_plugins_exclude'  =>
                        array(
                            0 => plugin_basename(BUWD_DIR),
                        ),
                    'bup_themes'           => '1',
                    'bup_uploads'          => '1',
                    'bup_extra_folders'    => '',
                    'bup_uploads_exclude'  =>
                        array(
                            0 => basename($log_folder),
                            1 => basename($temp_folder),
                            2 => $dest_folder,
                        ),
                    'bup_include_specials' => '1',
                    'exclude_types'        => '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/',
                    'use_folder_as_wp'     => '0',
                    'folderpath'           => str_replace($wp_home_path, '', $wp_upload_dir ) . Buwd::get_plugin_data('folder_name') . '-db-{hash_key}/',
                    'folderdelete'         => 15,
                    'folderdeletesync'     => '1',
                    'gdrivefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'gdrivefiledelete'     => 15,
                    'gdrivefiledeletesync' => '1',
                    's3service'            => 'us-east-1',
                    's3accesskey'          => '',
                    's3privatekey'         => '',
                    's3bucket'             => '',
                    's3newbucket'          => '',
                    's3bucketfolder'       => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    's3filedelete'         => 15,
                    's3filedeletesync'     => '1',
                    's3multiupload'        => '1',
                    's3storageclass'       => 'standard',
                    's3serverencryption'   => '1',
                    'dboxtoken'            => '',
                    'dboxtype'             => 'sandbox',
                    'dboxfolder'           => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'dboxfiledelete'       => 15,
                    'dboxfiledeletesync'   => '1',
                    'azurename'            => '',
                    'azurekey'             => '',
                    'azurenewcont'         => '',
                    'azurefolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'azurefiledelete'      => 15,
                    'azurefiledeletesync'  => '1',
                    'rscuser'              => '',
                    'rsckey'               => '',
                    'rscregion'            => 'DFW',
                    'rsccontainer'         => '',
                    'rscnewcont'           => '',
                    'rscfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'rscfiledelete'        => 15,
                    'rscfiledeletesync'    => '1',
                    'ftpserver'            => '',
                    'ftpport'              => 21,
                    'ftpuser'              => '',
                    'ftppass'              => '',
                    'ftpfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'ftpfiledelete'        => 15,
                    'ftptimeout'           => 90,
                    'ftpssl'               => '',
                    'ftppmode'             => '1',
                    'sugartoken'           => '',
                    'sugaruser'            => '',
                    'sugarsyncfolder'      => '',
                    'sugarfolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'sugarfiledelete'      => 15,
                    'glacier_region'       => 'us-east-1',
                    'glacier_accesskey'    => '',
                    'glacier_privatekey'   => '',
                    'glacier_newvault'     => '',
                    'glacier_filedelete'   => 15,
                    'send_email'           => '',
                    'job_id'               => 6,
                    'cron_expression'      => '0 0 * * *',
                    'schedulelink'         => site_url('wp-cron.php') . '?jobid=1&amp;buwd_cron=1&amp;type=run&amp;_wpnonce=' . self::getSetting('job_start_key'),
                    'day'                  => '',
                    'scheduleday'          => '',
                    'schedulehour'         => '00',
                    'scheduleminute'       => '00',
                ),
                2 => array(
                    'name'                 => 'Files Backup',
                    'type'                 =>
                        array(
                            0 => 'files'
                        ),
                    'destination'          =>
                        array(
                            0 => 'folder'
                        ),
                    'backup_type'          => 'archive',
                    'archive_name'         => 'backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s',
                    'archive_format'       => $archive_format,
                    'schedule'             => 'manually',
                    'scheduletype'         => 'basic',
                    'schedulemonth'        =>
                        array(
                            0  => 'any',
                            1  => '1',
                            2  => '2',
                            3  => '3',
                            4  => '4',
                            5  => '5',
                            6  => '6',
                            7  => '7',
                            8  => '8',
                            9  => '9',
                            10 => '10',
                            11 => '11',
                            12 => '12',
                        ),
                    'scheduleweek'         =>
                        array(
                            0 => '1',
                        ),
                    'dbtables_all'         => array(),
                    'dbtables'             => $_tables,
                    'dbfilename'           => sanitize_file_name(DB_NAME),
                    'use_wp_connection'    => '1',
                    'dbfilecomp'           => 'none',
                    'db_host'              => '',
                    'db_user'              => '',
                    'db_password'          => '',
                    'db_name'              => '',
                    'bup_root'             => array(
                        0 => '1'
                    ),
                    'bup_content'          => array(
                        0 => '1'
                    ),
                    'bup_content_exclude'  => '',
                    'bup_plugins'          => array(
                        0 => '1'
                    ),
                    'bup_plugins_exclude'  =>
                        array(
                            0 => plugin_basename(BUWD_DIR),
                        ),
                    'bup_themes'           => '1',
                    'bup_uploads'          => '1',
                    'bup_extra_folders'    => '',
                    'bup_uploads_exclude'  =>
                        array(
                            0 => basename($log_folder),
                            1 => basename($temp_folder),
                            2 => $dest_folder,
                        ),
                    'bup_include_specials' => '1',
                    'exclude_types'        => '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/',
                    'use_folder_as_wp'     => '0',
                    'folderpath'           => str_replace($wp_home_path, '', $wp_upload_dir ) . Buwd::get_plugin_data('folder_name') . '-files-{hash_key}/',
                    'folderdelete'         => 15,
                    'folderdeletesync'     => '1',
                    'gdrivefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'gdrivefiledelete'     => 15,
                    'gdrivefiledeletesync' => '1',
                    's3service'            => 'us-east-1',
                    's3accesskey'          => '',
                    's3privatekey'         => '',
                    's3bucket'             => '',
                    's3newbucket'          => '',
                    's3bucketfolder'       => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    's3filedelete'         => 15,
                    's3filedeletesync'     => '1',
                    's3multiupload'        => '1',
                    's3storageclass'       => 'standard',
                    's3serverencryption'   => '1',
                    'dboxtoken'            => '',
                    'dboxtype'             => 'sandbox',
                    'dboxfolder'           => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'dboxfiledelete'       => 15,
                    'dboxfiledeletesync'   => '1',
                    'azurename'            => '',
                    'azurekey'             => '',
                    'azurenewcont'         => '',
                    'azurefolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'azurefiledelete'      => 15,
                    'azurefiledeletesync'  => '1',
                    'rscuser'              => '',
                    'rsckey'               => '',
                    'rscregion'            => 'DFW',
                    'rsccontainer'         => '',
                    'rscnewcont'           => '',
                    'rscfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'rscfiledelete'        => 15,
                    'rscfiledeletesync'    => '1',
                    'ftpserver'            => '',
                    'ftpport'              => 21,
                    'ftpuser'              => '',
                    'ftppass'              => '',
                    'ftpfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'ftpfiledelete'        => 15,
                    'ftptimeout'           => 90,
                    'ftpssl'               => '',
                    'ftppmode'             => '1',
                    'sugartoken'           => '',
                    'sugaruser'            => '',
                    'sugarsyncfolder'      => '',
                    'sugarfolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'sugarfiledelete'      => 15,
                    'glacier_region'       => 'us-east-1',
                    'glacier_accesskey'    => '',
                    'glacier_privatekey'   => '',
                    'glacier_newvault'     => '',
                    'glacier_filedelete'   => 15,
                    'send_email'           => '',
                    'job_id'               => 6,
                    'cron_expression'      => '0 0 * * *',
                    'schedulelink'         => site_url('wp-cron.php') . '?jobid=2&amp;buwd_cron=1&amp;type=run&amp;_wpnonce=' . self::getSetting('job_start_key'),
                    'day'                  => '',
                    'scheduleday'          => '',
                    'schedulehour'         => '00',
                    'scheduleminute'       => '00',
                ),
                3 => array(
                    'name'                 => 'Plugins and Themes Backup',
                    'type'                 =>
                        array(
                            0 => 'files'
                        ),
                    'destination'          =>
                        array(
                            0 => 'folder'
                        ),
                    'backup_type'          => 'sync',
                    'archive_name'         => 'backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s',
                    'archive_format'       => $archive_format,
                    'schedule'             => 'manually',
                    'scheduletype'         => 'basic',
                    'schedulemonth'        =>
                        array(
                            0  => 'any',
                            1  => '1',
                            2  => '2',
                            3  => '3',
                            4  => '4',
                            5  => '5',
                            6  => '6',
                            7  => '7',
                            8  => '8',
                            9  => '9',
                            10 => '10',
                            11 => '11',
                            12 => '12',
                        ),
                    'scheduleweek'         =>
                        array(
                            0 => '1',
                        ),
                    'dbtables_all'         => array(),
                    'dbtables'             => $_tables,
                    'dbfilename'           => sanitize_file_name(DB_NAME),
                    'use_wp_connection'    => '1',
                    'dbfilecomp'           => 'none',
                    'db_host'              => '',
                    'db_user'              => '',
                    'db_password'          => '',
                    'db_name'              => '',
                    'bup_root'             => '',
                    'bup_content'          => '',
                    'bup_content_exclude'  => '',
                    'bup_plugins'          => array(
                        0 => '1'
                    ),
                    'bup_plugins_exclude'  =>
                        array(
                            0 => plugin_basename(BUWD_DIR),
                        ),
                    'bup_themes'           => '1',
                    'bup_uploads'          => '',
                    'bup_extra_folders'    => '',
                    'bup_uploads_exclude'  =>
                        array(
                            0 => basename($log_folder),
                            1 => basename($temp_folder),
                            2 => $dest_folder,
                        ),
                    'bup_include_specials' => '1',
                    'exclude_types'        => '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/',
                    'use_folder_as_wp'     => '0',
                    'folderpath'           => str_replace($wp_home_path, '', $wp_upload_dir ) . Buwd::get_plugin_data('folder_name') . '-plugins-themes-{hash_key}/',
                    'folderdelete'         => 15,
                    'folderdeletesync'     => '1',
                    'gdrivefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'gdrivefiledelete'     => 15,
                    'gdrivefiledeletesync' => '1',
                    's3service'            => 'us-east-1',
                    's3accesskey'          => '',
                    's3privatekey'         => '',
                    's3bucket'             => '',
                    's3newbucket'          => '',
                    's3bucketfolder'       => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    's3filedelete'         => 15,
                    's3filedeletesync'     => '1',
                    's3multiupload'        => '1',
                    's3storageclass'       => 'standard',
                    's3serverencryption'   => '1',
                    'dboxtoken'            => '',
                    'dboxtype'             => 'sandbox',
                    'dboxfolder'           => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'dboxfiledelete'       => 15,
                    'dboxfiledeletesync'   => '1',
                    'azurename'            => '',
                    'azurekey'             => '',
                    'azurenewcont'         => '',
                    'azurefolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'azurefiledelete'      => 15,
                    'azurefiledeletesync'  => '1',
                    'rscuser'              => '',
                    'rsckey'               => '',
                    'rscregion'            => 'DFW',
                    'rsccontainer'         => '',
                    'rscnewcont'           => '',
                    'rscfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'rscfiledelete'        => 15,
                    'rscfiledeletesync'    => '1',
                    'ftpserver'            => '',
                    'ftpport'              => 21,
                    'ftpuser'              => '',
                    'ftppass'              => '',
                    'ftpfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'ftpfiledelete'        => 15,
                    'ftptimeout'           => 90,
                    'ftpssl'               => '',
                    'ftppmode'             => '1',
                    'sugartoken'           => '',
                    'sugaruser'            => '',
                    'sugarsyncfolder'      => '',
                    'sugarfolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'sugarfiledelete'      => 15,
                    'glacier_region'       => 'us-east-1',
                    'glacier_accesskey'    => '',
                    'glacier_privatekey'   => '',
                    'glacier_newvault'     => '',
                    'glacier_filedelete'   => 15,
                    'send_email'           => '',
                    'job_id'               => 6,
                    'cron_expression'      => '0 0 * * *',
                    'schedulelink'         => site_url('wp-cron.php') . '?jobid=3&amp;buwd_cron=1&amp;type=run&amp;_wpnonce=' . self::getSetting('job_start_key'),
                    'day'                  => '',
                    'scheduleday'          => '',
                    'schedulehour'         => '00',
                    'scheduleminute'       => '00',
                ),
                4 => array(
                    'name'                 => 'Files and Database Backup',
                    'type'                 =>
                        array(
                            0 => 'db',
                            1 => 'files'
                        ),
                    'destination'          =>
                        array(
                            0 => 'folder'
                        ),
                    'backup_type'          => 'sync',
                    'archive_name'         => 'backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s',
                    'archive_format'       => $archive_format,
                    'schedule'             => 'manually',
                    'scheduletype'         => 'basic',
                    'schedulemonth'        =>
                        array(
                            0  => 'any',
                            1  => '1',
                            2  => '2',
                            3  => '3',
                            4  => '4',
                            5  => '5',
                            6  => '6',
                            7  => '7',
                            8  => '8',
                            9  => '9',
                            10 => '10',
                            11 => '11',
                            12 => '12',
                        ),
                    'scheduleweek'         =>
                        array(
                            0 => '1',
                        ),
                    'dbtables_all'         => array(),
                    'dbtables'             => $_tables,
                    'dbfilename'           => sanitize_file_name(DB_NAME),
                    'use_wp_connection'    => '1',
                    'dbfilecomp'           => 'none',
                    'db_host'              => '',
                    'db_user'              => '',
                    'db_password'          => '',
                    'db_name'              => '',
                    'bup_root'             => '',
                    'bup_content'          => '1',
                    'bup_content_exclude'  => '',
                    'bup_plugins'          => array(
                        0 => '1'
                    ),
                    'bup_plugins_exclude'  =>
                        array(
                            0 => plugin_basename(BUWD_DIR),
                        ),
                    'bup_themes'           => '1',
                    'bup_uploads'          => '',
                    'bup_extra_folders'    => '',
                    'bup_uploads_exclude'  =>
                        array(
                            0 => basename($log_folder),
                            1 => basename($temp_folder),
                            2 => $dest_folder,
                        ),
                    'bup_include_specials' => '1',
                    'exclude_types'        => '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/',
                    'use_folder_as_wp'     => '0',
                    'folderpath'           => str_replace($wp_home_path, '', $wp_upload_dir ) . Buwd::get_plugin_data('folder_name') . '-files-db-{hash_key}/',
                    'folderdelete'         => 15,
                    'folderdeletesync'     => '1',
                    'gdrivefolder'         => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'gdrivefiledelete'     => 15,
                    'gdrivefiledeletesync' => '1',
                    's3service'            => 'us-east-1',
                    's3accesskey'          => '',
                    's3privatekey'         => '',
                    's3bucket'             => '',
                    's3newbucket'          => '',
                    's3bucketfolder'       => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    's3filedelete'         => 15,
                    's3filedeletesync'     => '1',
                    's3multiupload'        => '1',
                    's3storageclass'       => 'standard',
                    's3serverencryption'   => '1',
                    'dboxtoken'            => '',
                    'dboxtype'             => 'sandbox',
                    'dboxfolder'           => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'dboxfiledelete'       => 15,
                    'dboxfiledeletesync'   => '1',
                    'azurename'            => '',
                    'azurekey'             => '',
                    'azurenewcont'         => '',
                    'azurefolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'azurefiledelete'      => 15,
                    'azurefiledeletesync'  => '1',
                    'rscuser'              => '',
                    'rsckey'               => '',
                    'rscregion'            => 'DFW',
                    'rsccontainer'         => '',
                    'rscnewcont'           => '',
                    'rscfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'rscfiledelete'        => 15,
                    'rscfiledeletesync'    => '1',
                    'ftpserver'            => '',
                    'ftpport'              => 21,
                    'ftpuser'              => '',
                    'ftppass'              => '',
                    'ftpfolder'            => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'ftpfiledelete'        => 15,
                    'ftptimeout'           => 90,
                    'ftpssl'               => '',
                    'ftppmode'             => '1',
                    'sugartoken'           => '',
                    'sugaruser'            => '',
                    'sugarsyncfolder'      => '',
                    'sugarfolder'          => trailingslashit(sanitize_file_name(get_bloginfo('name'))),
                    'sugarfiledelete'      => 15,
                    'glacier_region'       => 'us-east-1',
                    'glacier_accesskey'    => '',
                    'glacier_privatekey'   => '',
                    'glacier_newvault'     => '',
                    'glacier_filedelete'   => 15,
                    'send_email'           => '',
                    'job_id'               => 6,
                    'cron_expression'      => '0 0 * * *',
                    'schedulelink'         => site_url('wp-cron.php') . '?jobid=4&amp;buwd_cron=1&amp;type=run&amp;_wpnonce=' . self::getSetting('job_start_key'),
                    'day'                  => '',
                    'scheduleday'          => '',
                    'schedulehour'         => '00',
                    'scheduleminute'       => '00',
                ),
            );

            add_site_option('buwd_jobs', $default_jobs);
            add_site_option('buwd_job_maxid', count(get_site_option('buwd_jobs')));
        }


        //add options
        //add options
        //add options

    }

    public static function remove_default_options()
    {

    }

    public static function get_job_ids($option = '', $value = '')
    {
        $job_ids = array();
        $jobs_options = self::get_job_options();

        if ($jobs_options) {
            if (!empty ($option) && !empty ($value)) {
                foreach ($jobs_options as $job_id => $jobs_option) {
                    if (!isset($jobs_option[$option]) || $jobs_option[$option] != $value) {
                        unset($jobs_options[$job_id]);
                    }
                }
            }

            $job_ids = array_keys($jobs_options);
        }

        return $job_ids;
    }

    public static function get_job($job_id)
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }
        $jobs_options = self::get_job_options();

        return wp_parse_args($jobs_options[$job_id], self::job_defaults());
    }

    public static function get_job_maxid()
    {
        $job_maxid = (int)get_site_option('buwd_job_maxid');
        if ($job_maxid) {
            return $job_maxid;
        } else {
            // get all job and return max id
        }

    }

    public static function update_job_maxid($job_id)
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }

        $job_maxid = get_site_option('buwd_job_maxid');
        if ($job_maxid < $job_id) {
            $job_maxid = $job_id;
        }

        return update_site_option('buwd_job_maxid', (int)$job_maxid);
    }

    public static function delete_job($job_id)
    {
        $job_id = (int)$job_id;
        if (!$job_id) {
            return false;
        }

        $jobs_options = self::get_job_options(false);
        unset($jobs_options[$job_id]);

        return self::update_job_options($jobs_options);
    }

    public static function get($job_id, $option, $default = null, $if_empty = false)
    {
        $job_id = (int)$job_id;
        $option = sanitize_key(trim($option));

        if (!$job_id || !$option) {
            return false;
        }

        $jobs_options = self::get_job_options();

        if (isset($jobs_options[$job_id][$option])) {
            if ($if_empty && !$jobs_options[$job_id][$option]) {
                if (isset($default)) {
                    return $default;
                }
            } else {
                return $jobs_options[$job_id][$option];
            }
        } else if (isset($default)) {
            return $default;
        }
    }

    public static function update_job_option($job_id, $option, $value)
    {
        $job_id = (int)$job_id;
        $option = sanitize_key(trim($option));

        if (!$job_id || !$option) {
            return false;
        }

        $jobs_options = self::get_job_options();
        $jobs_options[$job_id][$option] = $value;

        return self::update_job_options($jobs_options);
    }

    public static function delete_job_option($job_id, $option)
    {
        $job_id = (int)$job_id;
        $option = sanitize_key(trim($option));

        if (!$job_id || !$option) {
            return false;
        }

        $jobs_options = self::get_job_options();
        unset($jobs_options[$job_id][$option]);

        return self::update_job_options($jobs_options);
    }

    public static function getSetting($option, $default = null, $if_empty = false)
    {
        $option = sanitize_key(trim($option));
        if (!$option) {
            return false;
        }

        $settings_options = self::get_settings_options();
        if (isset($settings_options[$option])) {
            if ($if_empty && !$settings_options[$option]) {
                if (isset($default)) {
                    return $default;
                }
            } else {
                return $settings_options[$option];
            }
        } else if (isset($default)) {
            return $default;
        } else {
            return self::settings_defaults($option);
        }
    }

    public static function job_defaults($key = '')
    {
        $key = sanitize_key(trim($key));

        //set defaults
        $default['name'] = 'New Job';
        $default['type'] = array('db', 'files');
        $default['destination'] = array('folder');
        $default['backup_type'] = 'archive';
        $default['archive_name'] = 'backupwd_{hash_key}_%Y-%m-%d_%H-%i-%s';
        $default['archive_format'] = 'zip';
        if(extension_loaded('zip') === false){
            $default['archive_format'] = 'tar';
        }
        $default['schedule'] = 'manually';
        $default['scheduletype'] = 'basic';
        $default['schedulemonth'] = array('any', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12',);
        $default['scheduleweek'] = array('1');
        //add defaults
        //add defaults
        //add defaults
        //add defaults

        $job_types = Buwd::get_job_types();
        if ($job_types) {
            foreach ($job_types as $job_type_info) {
                $default = array_merge($default, $job_type_info->defaults());
            }
        }

        $destinations = Buwd::get_destinations();
        if ($destinations) {
            foreach ($destinations as $destination) {
                $default = array_merge($default, $destination->defaults());
            }
        }

        if (empty($key)) {
            return $default;
        }

        if (isset($default[$key])) {
            return $default[$key];
        } else {
            return false;
        }
    }

    public static function settings_defaults($key = '')
    {
        $wp_upload_dir = Buwd::get_upload_dir();
        $wp_home_path = Buwd::get_plugin_data('home_path');

        //TODO set deafults
        $key = sanitize_key(trim($key));

        $default['show_on_bar'] = array('1');
        $default['log_folder'] = str_replace($wp_home_path, '', $wp_upload_dir) . Buwd::get_plugin_data('folder_name') . '-logs-{hash_key}';

        $default['job_step_max'] = 3;
        $default['auth_method'] = 'none';
        $default['max_exec_time'] = 300;
        $default['job_start_key'] = substr(md5(Buwd::get_plugin_data('hash')), 9, 7);
        $default['red_server_load'] = 'disabled';
        $default['max_log_files'] = 15;
        $default['log_level'] = 'normal_translated';
        $default['hash_key'] = Buwd::get_plugin_data('hash');
        $default['recipient'] = get_bloginfo('admin_email');
        $default['email_from'] = get_bloginfo('admin_email');
        $default['from'] = sanitize_file_name(get_bloginfo('name'));
        $default['subject'] = __('Backup log', 'buwd');
        if (empty($key)) {
            return $default;
        }

        if (isset($default[$key])) {
            return $default[$key];
        } else {
            return false;
        }
    }

    private static function get_job_options()
    {
        return get_site_option('buwd_jobs', array());
    }

    private static function update_job_options($jobs_options)
    {
        return update_site_option('buwd_jobs', $jobs_options);
    }

    public static function get_settings_options($filter = array(), $filter_include = true)
    {
        $settings = get_site_option('buwd_settings', array());

        if (!empty($filter)) {
            if (!$filter_include) {
                $keys = array_keys($settings);
                $settings = array_filter($settings, function ($key) use ($filter, $keys) {
                    return (!in_array($key, $filter));
                }, ARRAY_FILTER_USE_KEY);
            } else {
                $settings = array_filter($settings, function ($key) use ($filter) {
                    return (!in_array($key, $filter));
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        return $settings;
    }

    public static function update_settings($options)
    {
        return update_site_option('buwd_settings', $options);
    }

    public static function update_settings_option($option, $value)
    {
        $option = sanitize_key(trim($option));

        if (!$option) {
            return false;
        }

        $settings_options = self::get_settings_options();
        $settings_options[$option] = $value;

        return update_site_option('buwd_settings', $settings_options);
    }

    public static function backup_bulk_delete($option, $files)
    {
        $dest_options = get_site_option($option, array());
        if (!empty($dest_options)) {
            $dest_options = Buwd_Helper::search_in_array_diff($dest_options, 'file', $files);

            $dest_options = array_values($dest_options);
            update_site_option($option, $dest_options);
        }
    }

    public static function backup_delete($option, $file)
    {
        $dest_options = get_site_option($option, array());
        foreach ($dest_options as $key => $dest_option) {
            if ($dest_option['file'] == $file) {
                unset($dest_options[$key]);
                $dest_options = array_values($dest_options);
                update_site_option($option, $dest_options);
                break;
            }
        }
    }


}	
	