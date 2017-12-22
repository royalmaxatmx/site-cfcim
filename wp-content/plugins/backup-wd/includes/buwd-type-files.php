<?php

/**
 *
 */
class Buwd_Type_Files
{
    protected static $instance = null;
    public $info = array();
    public $errors = array();
    public $folders = array();
    public $files = array();

    public function __construct()
    {
        $this->info['title'] = __('Getting files and folders list', 'buwd');
        $this->info['name'] = __('Files Backup', 'buwd');
        $this->info['desc'] = __('', 'buwd');
    }

    public function defaults()
    {
        $log_folder = Buwd_File::get_absolute_path(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd::get_plugin_data('log_folder_dir')));
        $temp_folder = Buwd::get_plugin_data('temp_folder_dir');
        $job_ids = Buwd_Options::get_job_ids();
        $bup_uploads_exclude = array(basename($log_folder), basename($temp_folder));
        foreach ($job_ids as $j_id) {
            array_push($bup_uploads_exclude, basename(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd_Options::get($j_id, 'folderpath'))));
        }

        $defaults = array(
            'bup_root'             => '',
            'bup_content'          => '',
            'bup_content_exclude'  => array('cache', 'upgrade'),
            'bup_plugins'          => '1',
            'bup_plugins_exclude'  => array(Buwd::get_plugin_data('folder_name')),
            'bup_themes'           => '1',
            'bup_uploads'          => '1',
            'bup_extra_folders'    => '',
            'bup_uploads_exclude'  => $bup_uploads_exclude,
            'bup_include_specials' => '1',
            'exclude_types'        => '.tmp,.svn,.git,desktop.ini,.DS_Store,/node_modules/',
            'use_folder_as_wp'     => '0',
        );

        return $defaults;
    }

    public function get_paths($folder_above_as_abs = false)
    {
        $wp_root_path = Buwd::get_plugin_data('home_path');
        $wp_install_path = $wp_root_path;
        if ($folder_above_as_abs) {
            $wp_root_path = trailingslashit(dirname($wp_install_path));
        }

        $wp_content_path = trailingslashit(str_replace('\\', '/', WP_CONTENT_DIR));
        $wp_plugins_path = trailingslashit(str_replace('\\', '/', plugin_dir_path(BUWD_DIR)));
        $wp_themes_path = trailingslashit(str_replace('\\', '/', get_theme_root()));
        $wp_uploads_path = Buwd::get_upload_dir();

        $paths = array();
        $paths['bup_root_path'] = $wp_root_path;
        $paths['bup_content_path'] = $wp_content_path;
        $paths['bup_plugins_path'] = $wp_plugins_path;
        $paths['bup_themes_path'] = $wp_themes_path;
        $paths['bup_uploads_path'] = $wp_uploads_path;

        return $paths;
    }

    public function get_options($job_id)
    {
        $folder_above_as_abs = Buwd_Options::get($job_id, 'use_folder_as_wp');
        $show_foldier_size = Buwd_Options::getSetting('show_foldier_size') ? true : false;
        $folder_above_as_abs = !empty($folder_above_as_abs) ? true : false;
        $paths = $this->get_paths($folder_above_as_abs);

        $folders = array();
        $folders_size = array();

        foreach ($paths as $p_key => &$path) {
            $path_size = 0;
            foreach (scandir($path) as $install_dir) {
                if ($install_dir == '.' || $install_dir == '..') {
                    continue;
                }

                if (!$show_foldier_size) {
                    if (is_dir($path . $install_dir) && !in_array(trailingslashit($path . $install_dir), $paths)) {

                        if ($path . $install_dir == Buwd::get_plugin_data('temp_folder_dir')) {
                            continue;
                        }
                        $folders[$p_key][$install_dir] = $install_dir;
                    }
                } else {
                    if (is_dir($path . $install_dir) && !in_array(trailingslashit($path . $install_dir), $paths)) {
                        $folders_size[$p_key][$install_dir] = Buwd_File::get_folders_size($path . $install_dir);
                        $path_size += $folders_size[$p_key][$install_dir];
                        $folders[$p_key][$install_dir] = $install_dir . ' ( ' . Buwd_File::get_human_filesize($folders_size[$p_key][$install_dir]) . ' )';
                    } else {
                        $path_size += filesize($path . $install_dir);
                    }
                }
            }

            $base_directory = str_replace('_path', '', $p_key);
            $folders_size[$base_directory][1] = $path_size;
            if ($show_foldier_size) {
                $path = untrailingslashit($path) . ' <span>( ' . Buwd_File::get_human_filesize($path_size) . ' )</span>';
            } else {
                $path = untrailingslashit($path);
            }
        }

        $folder_class = $show_foldier_size ? 'bup_choices' : '';
        $options = array(
            'key'    => 'type-files',
            'title'  => '',
            'fields' => array(
                array(
                    'label'   => __('Backup installation folder', 'buwd'),
                    'header'  => __('Folders to backup', 'buwd'),
                    'id'      => 'bup_root',
                    'name'    => 'bup_root',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => $paths['bup_root_path']
                    ),
                    'class'   => array(
                        'bup_root'
                    ),
                    'value'   => Buwd_Options::get($job_id, 'bup_root'),
                    'attr'    => $show_foldier_size && isset($folders_size['bup_root']) ? array('data-size' => $folders_size['bup_root']) : array(),
                    /*'hint'  => array(
                        'html' => '<p class="description">Mark this option to backup main WordPress folders, <b>wp-admin</b>, <b>wp-includes</b> or <b>wp-snapshots</b>.</p>'
                    )*/
                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'bup_root_exclude',
                    'name'       => 'bup_root_exclude',
                    'type'       => 'checkbox',
                    'choices'    => isset($folders['bup_root_path']) ? $folders['bup_root_path'] : array(),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<b>Exclude</b><p class="description">Use this option to exclude selected folders from the backup.</p>',
                    ),
                    'class'      => array('bup_root_choices', $folder_class),
                    'value'      => Buwd_Options::get($job_id, 'bup_root_exclude'),
                    'attr'       => $show_foldier_size && isset($folders_size['bup_root_path']) ? array('data-size' => $folders_size['bup_root_path']) : array(),
                    'visibility' => Buwd_Options::get($job_id, 'bup_root') ? true : false,
                ),
                array(
                    'label'   => __('Backup content folder', 'buwd'),
                    'id'      => 'bup_content',
                    'name'    => 'bup_content',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => $paths['bup_content_path']
                    ),
                    'class'   => array(
                        'bup_content'
                    ),
                    'value'   => Buwd_Options::get($job_id, 'bup_content'),
                    'attr'    => $show_foldier_size ? array('data-size' => $folders_size['bup_content']) : array(),
                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'bup_content_exclude',
                    'name'       => 'bup_content_exclude',
                    'type'       => 'checkbox',
                    'choices'    => isset($folders['bup_content_path']) ? $folders['bup_content_path'] : array(),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<b>Exclude</b><p class="description">Use this option to exclude selected folders from the backup.</p>',
                    ),
                    'class'      => array('bup_content_choices', $folder_class),
                    'value'      => Buwd_Options::get($job_id, 'bup_content_exclude'),
                    'attr'       => $show_foldier_size && isset($folders_size['bup_content_path']) ? array('data-size' => $folders_size['bup_content_path']) : array(),
                    'visibility' => Buwd_Options::get($job_id, 'bup_content') ? true : false,
                ),
                array(
                    'label'   => __('Backup plugins', 'buwd'),
                    'id'      => 'bup_plugins',
                    'name'    => 'bup_plugins',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => $paths['bup_plugins_path']
                    ),
                    'class'   => array(
                        'bup_plugins'
                    ),
                    'value'   => Buwd_Options::get($job_id, 'bup_plugins'),
                    'attr'    => $show_foldier_size ? array('data-size' => $folders_size['bup_plugins']) : array(),

                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'bup_plugins_exclude',
                    'name'       => 'bup_plugins_exclude',
                    'type'       => 'checkbox',
                    'choices'    => isset($folders['bup_plugins_path']) ? $folders['bup_plugins_path'] : array(),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<b>Exclude</b><p class="description">Use this option to exclude selected folders from the backup.</p>',
                    ),
                    'class'      => array(
                        'bup_plugins_choices',
                        $folder_class
                    ),
                    'value'      => Buwd_Options::get($job_id, 'bup_plugins_exclude'),
                    'attr'       => $show_foldier_size && isset($folders_size['bup_plugins_path']) ? array('data-size' => $folders_size['bup_plugins_path']) : array(),
                    'visibility' => Buwd_Options::get($job_id, 'bup_plugins') ? true : false,
                ),
                array(
                    'label'   => __('Backup themes', 'buwd'),
                    'id'      => 'bup_themes',
                    'name'    => 'bup_themes',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => $paths['bup_themes_path']
                    ),
                    'class'   => array('bup_themes'),
                    'value'   => Buwd_Options::get($job_id, 'bup_themes'),
                    'attr'    => $show_foldier_size ? array('data-size' => $folders_size['bup_themes']) : array(),

                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'bup_themes_exclude',
                    'name'       => 'bup_themes_exclude',
                    'type'       => 'checkbox',
                    'choices'    => isset($folders['bup_themes_path']) ? $folders['bup_themes_path'] : array(),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<b>Exclude</b><p class="description">Use this option to exclude selected folders from the backup.</p>',
                    ),
                    'class'      => array('bup_themes_choices', $folder_class),
                    'value'      => Buwd_Options::get($job_id, 'bup_themes_exclude'),
                    'attr'       => $show_foldier_size && isset($folders_size['bup_themes_path']) ? array('data-size' => $folders_size['bup_themes_path']) : array(),
                    'visibility' => Buwd_Options::get($job_id, 'bup_themes') ? true : false,
                ),
                array(
                    'label'   => __('Backup uploads', 'buwd'),
                    'id'      => 'bup_uploads',
                    'name'    => 'bup_uploads',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => $paths['bup_uploads_path']
                    ),
                    'class'   => array('bup_uploads'),
                    'value'   => Buwd_Options::get($job_id, 'bup_uploads'),
                    'attr'    => $show_foldier_size ? array('data-size' => $folders_size['bup_uploads']) : array(),
                ),
                array(
                    'label'      => __('', 'buwd'),
                    'id'         => 'bup_uploads_exclude',
                    'name'       => 'bup_uploads_exclude',
                    'type'       => 'checkbox',
                    'choices'    => isset($folders['bup_uploads_path']) ? $folders['bup_uploads_path'] : array(),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<b>Exclude</b><p class="description">Use this option to exclude selected folders from the backup.</p>',
                    ),
                    'class'      => array('bup_uploads_choices', $folder_class),
                    'value'      => Buwd_Options::get($job_id, 'bup_uploads_exclude'),
                    'attr'       => $show_foldier_size && isset($folders_size['bup_uploads_path']) ? array('data-size' => $folders_size['bup_uploads_path']) : array(),
                    'visibility' => Buwd_Options::get($job_id, 'bup_uploads') ? true : false,
                ),
                array(
                    'label' => __('Extra folders to backup', 'buwd'),
                    'id'    => 'bup_extra_folders',
                    'name'  => 'bup_extra_folders',
                    'type'  => 'textarea',
                    'attr'  => array(
                        'rows' => '7',
                        'cols' => '60',
                    ),
                    'class' => array(),
                    'hint'  => array(
                        'html' => '<p class="description">Write the absolute path of the folders to backup. You can add multiple folders, however, make sure to separate them with a line break. For example:<br /><br />your_absolute_path/website_folder/subfolder/folder1</p>'
                    ),
                    'value' => Buwd_Options::get($job_id, 'bup_extra_folders'),
                ),
                array(
                    'label'   => __('Thumbnails in uploads', 'buwd'),
                    'header'  => 'Exclude from backup',
                    'id'      => 'bup_thumbs_exclude',
                    'name'    => 'bup_thumbs_exclude',
                    'type'    => 'checkbox',
                    'choices' => array('1' => "Don't backup thumbnails from the site's uploads folder."),
                    'class'   => array(
                        'buwd-large-text',
                        $folder_class
                    ),
                    'value'   => Buwd_Options::get($job_id, 'bup_thumbs_exclude'),
                    'hint'    => array(
                        'html' => '<p class="description">Select this option to exclude the backup of thumbnails from /wp-content/uploads folder.</p>'
                    ),
                ),
                array(
                    'label' => __('Exclude files/folders from backup', 'buwd'),
                    'id'    => 'exclude_types',
                    'name'  => 'exclude_types',
                    'type'  => 'textarea',
                    'attr'  => array(
                        'rows' => '7',
                        'cols' => '60',
                    ),
                    'class' => array(),
                    'hint'  => array(
                        'html' => '<p class="description">Use this option to write the files or paths to exclude from backup. Make sure to separate the entries with a line break or comma.</p>'
                    ),
                    'value' => Buwd_Options::get($job_id, 'exclude_types'),
                ),
                array(
                    'label'   => __('Include special files', 'buwd'),
                    'id'      => 'bup_include_specials',
                    'name'    => 'bup_include_specials',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => "Backup wp-config.php, robots.txt, nginx.conf, .htaccess, .htpasswd and favicon.ico from root if it is not included in backup."
                    ),
                    'header'  => 'Special options',
                    'value'   => Buwd_Options::get($job_id, 'bup_include_specials'),
                    /* 'hint'  => array(
                         'html' => '<p class="description">Mark this option as checked to backup <b>wp-config.php, robots.txt, nginx.conf, .htaccess, .htpasswd and favicon.ico</b> files, in case they are not included in backup.</p>'
                     ),*/
                ),
                array(
                    'label'   => __('Use one folder above as WordPress install directory', 'buwd'),
                    'id'      => 'use_folder_as_wp',
                    'name'    => 'use_folder_as_wp',
                    'type'    => 'checkbox',
                    'choices' => array(
                        '1' => "This option is recommended, in case you wish to backup files and folders, which are not in WordPress installation directory. Also, this can be helpful in case your WordPress installation is located under a separate folder. If you enable this, make sure to configure excludes again."
                    ),
                    'value'   => Buwd_Options::get($job_id, 'use_folder_as_wp'),
                ),
            )
        );

        return $options;
    }

    /**
     * @param Buwd_Job $job_object
     *
     * @return bool
     */
    public function run(Buwd_Job $job_object)
    {
        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to create a list of files to backup.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        $folder_above_as_abs = !empty($job_object->job['use_folder_as_wp']) ? true : false;

        $paths = $this->get_paths($folder_above_as_abs);
        $excludes = array();
        echo '<pre>';

        foreach ($paths as $p_key => $path) {
            if (in_array(trailingslashit(dirname($path)), $paths)) {
                $search_key = array_search(trailingslashit(dirname($path)), $paths);
                if (!isset($excludes[$search_key])) {
                    $excludes[$search_key] = array();
                }
                $excludes[$search_key][] = basename($path);
            }

            $exclude_path = str_replace('_path', '', $p_key);
            if (empty($job_object->job[$exclude_path])) {
                $excludes['paths'][] = $path;
                continue;
            }

            $exclude_item = str_replace('_path', '_exclude', $p_key);
            if (isset($job_object->job[$exclude_item]) && $job_object->job[$exclude_item]) {
                if (isset($excludes[$p_key])) {
                    $excludes[$p_key] = array_merge($excludes[$p_key], $job_object->job[$exclude_item]);
                } else {
                    $excludes[$p_key] = $job_object->job[$exclude_item];
                }
            }
        }

        foreach ($paths as $p_key => $path) {
            if (isset($excludes['paths']) && in_array($path, $excludes['paths'])) {
                continue;
            }
            $exclude = isset($excludes[$p_key]) ? $excludes[$p_key] : null;
            $this->get_files_list($job_object, $path, $exclude);
        }

        $this->add_additional_files($job_object);

        $handle = fopen(Buwd::get_plugin_data('temp_folder_dir') . 'job_files_folders.php', 'w');
        $content = serialize(array('folders' => $this->folders, 'files' => $this->files));
        fwrite($handle, $content);
        fclose($handle);


        if (!empty($this->files) || !empty($job_object->extra_files)) {
            $job_object->buwd_logger->set_log(sprintf(__('%1$d folders were added to backup.', 'buwd'), $job_object->folder_count));
            $job_object->update_progress();
        } else {
            $job_object->buwd_logger->set_log(__('No files or folders to backup.
', 'buwd'), E_USER_WARNING);
            $job_object->update_progress();

            return false;
        }
        $job_object->job['abs_path'] = $paths['bup_root_path'];
        $job_object->job['plugins_path'] = $paths['bup_plugins_path'];
        $job_object->job['themes_path'] = $paths['bup_themes_path'];
        $job_object->job['uploads_path'] = $paths['bup_uploads_path'];

        return true;
    }

    /**
     * Get files directories to backup
     *
     * @param        $job_object
     *
     * @param string $path
     *
     * @param array  $excludes
     *
     * @return bool
     */
    public function get_files_list(&$job_object, $path, $excludes = null, $sub_folder = false)
    {
        $bup_thumbs_exclude = !empty($job_object->job['bup_thumbs_exclude']) ? true : false;

        if (is_dir($path)) {
            if (!$this->check_is_allowed_dir($path, $job_object->files_exclude)) {
                return false;
            }

            $this->folders[] = untrailingslashit($path);

            $job_object->folder_count++;
            foreach (scandir($path) as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }

                if (isset($excludes) && in_array($file, $excludes) && !$sub_folder) {
                    continue;
                }

                if (is_link($path . $file)) {
                    continue;
                } else if (!is_readable($path . $file)) {
                    if (is_dir($path . $file)) {
                        $job_object->buwd_logger->set_log(sprintf(__('Folder %s is not readable.', 'buwd'), $path . $file), E_USER_WARNING);
                    } else {
                        $job_object->buwd_logger->set_log(sprintf(__('File %s is not readable.', 'buwd'), $path . $file), E_USER_WARNING);
                    }

                    $job_object->update_progress();
                    continue;
                } else {
                    $file_size = filesize($path . $file);
                    if (!is_int($file_size) || $file_size < 0 || $file_size > 2147483647) {
                        $job_object->buwd_logger->set_log(sprintf(__('Could not retrieve file size of "%s". The file might be too large. It will not be added to queue.
', 'buwd'), $path . $file . ' ' . $file_size), E_USER_WARNING);
                        $job_object->update_progress();
                        continue;
                    }
                }

                if ($bup_thumbs_exclude && strpos($path . $file, Buwd::get_upload_dir()) !== false && preg_match("/\-[0-9]{1,4}x[0-9]{1,4}.+\.(jpg|png|gif)$/i", $path . $file)) {
                    continue;
                }

                if (!$this->check_is_allowed_dir($path . $file, $job_object->files_exclude)) {
                    continue;
                }

                if (is_dir($path . $file)) {
                    $this->get_files_list($job_object, trailingslashit($path . $file), $excludes, true);
                } else {
                    if (!isset($this->files[utf8_encode(untrailingslashit($path))])) {
                        $this->files[utf8_encode(untrailingslashit($path))] = array();
                    }

                    $this->files[utf8_encode(untrailingslashit($path))][] = $path . $file;
                }

            }
        }
    }

    public function check_is_allowed_dir($path, $files_exclude)
    {
        $is_allowed_dir = true;
        if ($files_exclude) {
            foreach ($files_exclude as $file_exclude) {
                $file_exclude = trim($file_exclude);
                if ($file_exclude && stripos($path, $file_exclude) !== false) {
                    $is_allowed_dir = false;
                    break;
                }
            }
        }

        return $is_allowed_dir;
    }

    public function add_additional_files(&$job_object)
    {
        $extra_folders = isset($job_object->job['bup_extra_folders']) ? $job_object->job['bup_extra_folders'] : '';
        if ($extra_folders) {
            $extra_folders = preg_split('/\r\n|\r|\n/', $extra_folders);
            foreach ($extra_folders as $extra_folder) {
                if (is_dir($extra_folder)) {
                    $this->get_files_list($job_object, trailingslashit($extra_folder));
                }
            }
        }

        $abs_path = !empty($job_object->job['use_folder_as_wp']) ? dirname(realpath(ABSPATH)) : realpath(ABSPATH);
        $abs_path = trailingslashit(str_replace('\\', '/', $abs_path));

        if (!empty($job_object->job['bup_include_specials'])) {
            if (is_readable(ABSPATH . 'wp-config.php')) {
                $job_object->extra_files[] = str_replace('\\', '/', ABSPATH . 'wp-config.php');
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), 'wp-config.php'));
            } else if (Buwd_File::in_open_basedir(dirname(ABSPATH) . '/wp-config.php')) {
                if (is_readable(dirname(ABSPATH) . '/wp-config.php') && !is_readable(dirname(ABSPATH) . '/wp-settings.php')) {
                    $job_object->extra_files[] = str_replace('\\', '/', dirname(ABSPATH) . '/wp-config.php');
                    $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), 'wp-config.php'));
                }
            }
            if (is_readable($abs_path . '.htaccess') && empty($job_object->job['bup_root'])) {
                $job_object->extra_files[] = $abs_path . '.htaccess';
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), '.htaccess'));
            }
            if (is_readable($abs_path . 'nginx.conf') && empty($job_object->job['bup_root'])) {
                $job_object->extra_files[] = $abs_path . 'nginx.conf';
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), 'nginx.conf'));
            }
            if (is_readable($abs_path . '.htpasswd') && empty($job_object->job['bup_root'])) {
                $job_object->extra_files[] = $abs_path . '.htpasswd';
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), '.htpasswd'));
            }
            if (is_readable($abs_path . 'robots.txt') && empty($job_object->job['bup_root'])) {
                $job_object->extra_files[] = $abs_path . 'robots.txt';
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), 'robots.txt'));
            }
            if (is_readable($abs_path . 'favicon.ico') && empty($job_object->job['bup_root'])) {
                $job_object->extra_files[] = $abs_path . 'favicon.ico';
                $job_object->buwd_logger->set_log(sprintf(__('"%s" file was added to backup file list.', 'buwd'), 'favicon.ico'));
            }
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
