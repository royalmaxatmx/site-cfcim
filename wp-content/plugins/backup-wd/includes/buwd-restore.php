<?php

require_once BUWD_DIR . '/vendor/autoload.php';

/**
 *
 */
class Buwd_Restore
{
    protected static $instance = null;
    public $info = array();
    private $page_id = 'buwd_restore';
    private $error = null;
    private $warnings = array();

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
        return Buwd_Helper::get("tab") ? Buwd_Helper::get("tab") : "general";
    }

    /**
     * set tabs for settings view
     */
    private function get_tabs()
    {
        $tabs = array(
            'general' => array(
                'name'    => esc_html__('General', 'buwd'),
                'view'    => array($this, 'display_tab'),
                'display' => true
            ),
        );

        return $tabs;
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
    private function render_tab($tab_id = 'general')
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
    private function get_tab_options($tab_id = 'general')
    {
        $tabs_options = $this->tabs_options();

        return $tabs_options[$tab_id];
    }

    /**
     * set settings elements
     * field types (text,number,radio,checkbox,select,file,textarea,hidden)
     */
    private function tabs_options()
    {
        $options = array(
            'general' => array(
                'key'    => 'general',
                'title'  => '',
                'desc'   => 'After downloading the <b>database backup file</b>, you are able to easily restore it by uploading the package through Backup WD plugin. Simply press Choose File and browse the backup file you wish to restore, then click Restore.
<br/><br/>The feature to restore <b>website files</b> will also be available in Backup WD plugin soon.',
                'fields' => array(
                    array(
                        'label' => __('Upload files to backup', 'buwd'),
                        'id'    => 'upload_backup',
                        'name'  => 'upload_backup',
                        'type'  => 'file',
                        'value' => Buwd_Options::getSetting('upload_backup'),
                        'attr'  => array(
                            'placeholder' => ''
                        ),
                    ),
                )
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
        @set_time_limit('300');
        $restore_temp_dir = Buwd::get_plugin_data('temp_restore_dir');
        if (!is_dir($restore_temp_dir)) {
            mkdir($restore_temp_dir);
        }

        $redirect_url = array();
        $redirect_url['page'] = $this->page_id;
        $redirect_url['tab'] = $current_tab;

        if (isset($_FILES['upload_backup']) && $_FILES['upload_backup']['tmp_name']) {
            $filename = $_FILES['upload_backup']['name'];
            $tempfile = $_FILES['upload_backup']['tmp_name'];
            if (strtolower(substr($filename, -4)) == '.zip') {
                $ziparchive = new ZipArchive();
                $ziparchive_open = $ziparchive->open($tempfile);
                if ($ziparchive_open === true) {
                    $ziparchive->extractTo($restore_temp_dir);
                    $ziparchive->close();
                    unlink($tempfile);
                }
            } else {
                if (strtolower(substr($filename, -7)) == '.tar.gz') {
                    $tar = new Buwd_Archive_Tar($tempfile, 'gz');
                } else if (strtolower(substr($filename, -8)) == '.tar.bz2') {
                    $tar = new Buwd_Archive_Tar($tempfile, 'bz2');
                } else if (strtolower(substr($filename, -4)) == '.tar') {
                    $tar = new Buwd_Archive_Tar($tempfile);
                }
                $tar->extract($restore_temp_dir, false);
            }

            if ($config_file = file_get_contents($restore_temp_dir . 'buwd_config.json')) {
                $configs = json_decode($config_file, true);
                $job_object = $configs['job'];

                if (in_array('db', $job_object['type'])) {
                    if (!$this->restore_db($job_object)) {
                        //error on db restore proccess
                        $this->warnings[] = __('Database restore faild', 'buwd');
                    }
                }

                /*if (in_array('files', $job_object['type'])) {
                    if (!$this->restore_files($job_object)) {
                        //error on files restore proccess
                        $this->warnings[] = __('Files restore faild', 'buwd');
                    }
                }*/

                if (is_dir($restore_temp_dir)) {
                    $this->delete_folder($restore_temp_dir);
                }

                if (count($this->warnings) > 1) {
                    $this->error = __('Restore failed', 'buwd');
                } else {
                    set_site_transient('buwd_restore_done', 'Database restore done.');
                }

            } else {
                $this->error = __('Config file not found.', 'buwd');
            }

        } else {
            $this->error = __('File not found.', 'buwd');
        }

        if ($this->error) {
            set_site_transient('buwd_restore_error', $this->error);
        }

        if (!empty($this->warnings)) {
            set_site_transient('buwd_restore_warnings', $this->warnings);
        }

        Buwd_Helper::redirect($redirect_url);
    }

    private function restore_db($job_object)
    {
        global $wpdb;

        $restore_temp_dir = Buwd::get_plugin_data('temp_restore_dir');
        $dbfilename = $job_object['dbfilename'] . '.sql';

        if ($job_object['dbfilecomp'] == 'gzip' && file_exists($restore_temp_dir . $dbfilename . '.gz')) {
            if (!$this->extract_gzip($restore_temp_dir . $dbfilename . '.gz')) {
                //error

            }
        }

        if (file_exists($restore_temp_dir . $dbfilename)) {
            if (isset($job_object['db_encrypt']) && is_array($job_object['db_encrypt']))
                Buwd_Encrypt::decrypt_file($restore_temp_dir . $dbfilename);

            // restore db file
            $dbhandle = fopen($restore_temp_dir . $dbfilename, 'r');
            if (!$dbhandle) {

                return false;
            }

            $dbfilecont = fread($dbhandle, filesize($restore_temp_dir . $dbfilename));

            $sql_querys = $this->split_sqlfile($dbfilecont);
            foreach ($sql_querys as $sql_query) {
                $req = $wpdb->query(trim($sql_query));
                if (!$req && substr(trim($sql_query), 0, 3) != "SET") {
                    // set errors
                    break;
                }
            }

            fclose($dbhandle);

            delete_site_option('buwd_job_running');
            delete_site_option('buwd_progress');
        } else {
            //db file does not exist

            return false;
        }

        return true;
    }

    private function extract_gzip($filename)
    {
        $buffer_size = 4 * 1024; // read 1mb at a time
        $out_filename = str_replace('.gz', '', $filename);

        // Open our files (in binary mode)
        $file = gzopen($filename, 'rb');
        $out_file = fopen($out_filename, 'wb');

        // Keep repeating until the end of the input file
        while (!gzeof($file)) {
            // Read buffer-size bytes
            // Both fwrite and gzread and binary-safe
            fwrite($out_file, gzread($file, $buffer_size));
        }

        // Files are done, close files
        fclose($out_file);
        gzclose($file);

        return true;
    }

    private function split_sqlfile($sql)
    {
        $tokens = explode(';' . PHP_EOL, $sql);
        // try to save mem.
        $sql = "";
        $output = array();

        // we don't actually care about the matches preg gives us.
        $matches = array();

        // this is faster than calling count($oktens) every time thru the loop.
        $token_count = count($tokens);
        for ($i = 0; $i < $token_count; $i++) {
            // Don't wanna add an empty string as the last thing in the array.
            if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
                // This is the total number of single quotes in the token.
                $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);

                // Counts single quotes that are preceded by an odd number of backslashes,
                // which means they're escaped quotes.
                $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

                $unescaped_quotes = $total_quotes - $escaped_quotes;

                // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                if (($unescaped_quotes % 2) == 0) {
                    // It's a complete sql statement.
                    $output[] = $tokens[$i];
                    // save memory.
                    $tokens[$i] = "";
                } else {
                    // incomplete sql statement. keep adding tokens until we have a complete one.
                    // $temp will hold what we have so far.
                    $temp = $tokens[$i] . ';' . PHP_EOL;
                    // save memory..
                    $tokens[$i] = "";

                    // Do we have a complete statement yet?
                    $complete_stmt = false;

                    for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++) {
                        // This is the total number of single quotes in the token.
                        $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
                        // Counts single quotes that are preceded by an odd number of backslashes,
                        // which means they're escaped quotes.
                        $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

                        $unescaped_quotes = $total_quotes - $escaped_quotes;

                        if (($unescaped_quotes % 2) == 1) {
                            // odd number of unescaped quotes. In combination with the previous incomplete
                            // statement(s), we now have a complete statement. (2 odds always make an even)
                            $output[] = $temp . $tokens[$j];

                            // save memory.
                            $tokens[$j] = "";
                            $temp = "";

                            // exit the loop.
                            $complete_stmt = true;
                            // make sure the outer loop continues at the right point.
                            $i = $j;
                        } else {
                            // even number of unescaped quotes. We still don't have a complete statement.
                            // (1 odd and 1 even always make an odd)
                            $temp .= $tokens[$j] . ';' . PHP_EOL;
                            // save memory.
                            $tokens[$j] = "";
                        }

                    } // for..
                } // else
            }
        }

        return $output;
    }

    private function restore_files($job_object)
    {
        $restore_temp_dir = Buwd::get_plugin_data('temp_restore_dir');
        if ($content_dir = $this->get_content_dir($restore_temp_dir)) {
            $content_dirs = array(
                'plugins' => $content_dir . '/plugins',
                'themes'  => $content_dir . '/themes',
                'uploads' => $content_dir . '/uploads',
            );

            $folders_to_restore = array(
                'plugins' => utf8_encode(untrailingslashit($job_object['plugins_path'])),
                'themes'  => utf8_encode(untrailingslashit($job_object['themes_path'])),
                'uploads' => utf8_encode(untrailingslashit($job_object['uploads_path'])),
            );
            foreach ($folders_to_restore as $folder => $folder_path) {
                if (!empty($job_object['bup_' . $folder])) {
                    if (!is_dir($folder_path . '-temp')) {
                        mkdir(utf8_decode($folder_path . '-temp'));
                    }
                }
            }

            foreach ($content_dirs as $dir_basename => $dir) {
                if (is_dir($dir)) {
                    if ($this->copy_folders($dir, $folders_to_restore[$dir_basename] . '-temp')) {
                        if ($this->delete_folder($folders_to_restore[$dir_basename])) {
                            rename($folders_to_restore[$dir_basename] . '-temp', $folders_to_restore[$dir_basename]);
                        } else {
                            //cant delete folder

                        }
                    }
                }
            }
        }

        return true;
    }

    private function get_content_dir($temp_dir)
    {
        foreach (scandir($temp_dir) as $_dir) {
            if ($_dir == '.' || $_dir == '..') {
                continue;
            }

            if (is_dir($temp_dir . '/' . $_dir)) {
                if ($_dir == 'wp-content') {
                    return $temp_dir . '/' . $_dir;
                }

                $this->get_content_dir($temp_dir . '/' . $_dir);
            }
        }

        return false;
    }

    private function copy_folders($upload_dir, $folder_to_restore)
    {
        foreach (scandir($upload_dir) as $dir) {
            if ($dir == '.' || $dir == '..') {
                continue;
            }

            if (is_dir($upload_dir . '/' . $dir)) {
                if (!is_dir($folder_to_restore . '/' . $dir)) {
                    mkdir($folder_to_restore . '/' . $dir);
                }

                $this->copy_folders($upload_dir . '/' . $dir, $folder_to_restore . '/' . $dir);
            } else {
                copy($upload_dir . '/' . $dir, $folder_to_restore . '/' . $dir);
            }
        }

        return true;
    }

    /**
     * @param $dir
     */
    private function delete_folder($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir($dir . "/" . $file)) {
                $this->delete_folder($dir . "/" . $file);
            } else {
                unlink($dir . "/" . $file);
            }
        }

        rmdir($dir);

        return true;
    }

    /**
     * display settings view
     */
    public function display_page()
    {
        $current_tab = $this->info['tab'];
        $tabs = $this->get_tabs();

        include_once(BUWD_DIR . '/views/restore.php');
    }

    public function display_messages()
    {
        if ($error = get_site_transient('buwd_restore_error')) {
            echo Buwd_Helper::message($error, 'error');
            delete_site_transient('buwd_restore_error');
        } else if ($updated = get_site_transient('buwd_restore_done')) {
            echo Buwd_Helper::message($updated, 'success');
            delete_site_transient('buwd_restore_done');
        }

        if ($warnings = get_site_transient('buwd_restore_warnings')) {
            foreach ($warnings as $warning) {
                echo Buwd_Helper::message($warning, 'warning');
            }
            delete_site_transient('buwd_restore_warnings');
        }
    }

    /**
     * Include styles
     */
    public static function admin_print_styles()
    {
    }

    /**
     * Include scripts
     */
    public static function admin_print_scripts()
    {
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