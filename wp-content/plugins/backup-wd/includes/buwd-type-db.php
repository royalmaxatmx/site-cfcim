<?php

/**
 *
 */
class Buwd_Type_DB
{
    protected static $instance = null;
    private $db_user = DB_USER;
    private $db_password = DB_PASSWORD;
    private $db_name = DB_NAME;
    private $db_host = DB_HOST;

    /**
     *
     */
    public function __construct()
    {
        $this->info['title'] = __('Creating database file', 'buwd');
        $this->info['name'] = __('DB Backup', 'buwd');
        $this->info['desc'] = __('', 'buwd');
    }

    public function defaults()
    {
        //$wpdb = new wpdb($this->db_user, $this->db_password, $this->db_name, $this->db_host);
        global $wpdb;
        $tables = $wpdb->get_results('SHOW TABLES FROM ' . $this->backquote($this->db_name), ARRAY_N);
        $_tables = array();
        if ($tables) {
            foreach ($tables as $table) {
                if (substr($table[0], 0, strlen($wpdb->prefix)) === $wpdb->prefix) {
                    $_tables[$table[0]] = $table[0];
                }
            }
        }

        $defaults = array(
            'dbtables'          => $_tables,
            'dbfilename'        => sanitize_file_name(DB_NAME),
            'use_wp_connection' => '1',
            'dbfilecomp'        => 'none',
            'db_host'           => '',
            'db_user'           => '',
            'db_password'       => '',
            'db_name'           => DB_NAME,
        );

        return $defaults;
    }

    public function get_options($job_id)
    {
        global $wpdb;

        $_tables = $this->get_tables($job_id);
        $db_names = $this->get_db_names($job_id);
        /*$prefix = $wpdb->prefix;

        if(empty(Buwd_Options::get($job_id, 'dbtables')) && $job_id == (Buwd_Options::get_job_maxid() + 1) ){
                $tables_to_save = array_filter($_tables, function ($_table) use ($prefix) {
                        return substr( $_table, 0, strlen($prefix) ) == $prefix;
                });

                Buwd_Options::update_job_option($job_id, 'dbtables', $tables_to_save);
        }*/
        $wp_connection = Buwd_Options::get($job_id, 'use_wp_connection');
        $options = array(
            'key'    => 'type-db',
            'title'  => '',
            'fields' => array(
                array(
                    'label'   => __('Database connection', 'buwd'),
                    'header'  => __('Database Options', 'buwd'),
                    'id'      => 'use_wp_connection',
                    'name'    => 'use_wp_connection',
                    'type'    => 'checkbox',
                    'choices' => array(
                        1 => 'Use Wordpress Conection'),
                    'class'   => array(
                        'wp_connection',
                    ),
                    'value'   => $wp_connection,
                    'hint'    => array(
                        'html' => '<p class="description">Select to use WordPress connection to the website database, or uncheck the option to connect manually</p>',
                    ),
                ),
                array(
                    'label'      => __('Host', 'buwd'),
                    'id'         => 'db_host',
                    'name'       => 'db_host',
                    'type'       => 'text',
                    'class'      => array(
                        'buwd_db_settings',
                        'buwd-medium-text',
                    ),
                    'visibility' => empty($wp_connection) ? 1 : 0,
                    'value'      => esc_attr(Buwd_Options::get($job_id, 'db_host')),
                    'hint'       => array(
                        'html' => '<p class="description">Provide the host of your website database</p>',
                    ),
                ),
                array(
                    'label'      => __('User', 'buwd'),
                    'id'         => 'db_user',
                    'name'       => 'db_user',
                    'type'       => 'text',
                    'class'      => array(
                        'buwd_db_settings',
                        'buwd-medium-text',
                    ),
                    'visibility' => empty($wp_connection) ? 1 : 0,
                    'value'      => esc_attr(Buwd_Options::get($job_id, 'db_user')),
                    'hint'       => array(
                        'html' => '<p class="description">Write the username for your database login.</p>',
                    ),
                ),
                array(
                    'label'      => __('Password', 'buwd'),
                    'id'         => 'db_password',
                    'name'       => 'db_password',
                    'type'       => 'password',
                    'class'      => array(
                        'buwd_db_settings',
                        'buwd-medium-text',
                    ),
                    'visibility' => empty($wp_connection) ? 1 : 0,
                    'value'      => esc_attr(Buwd_Options::get($job_id, 'db_password')),
                    'hint'       => array(
                        'html' => '<p class="description">Provide the password of your database user.</p>',
                    ),
                ),
                array(
                    'label'      => __('Database', 'buwd'),
                    'id'         => 'db_name',
                    'name'       => 'db_name',
                    'type'       => 'select',
                    'class'      => array(
                        'buwd_db_settings',
                        'buwd-medium-text',
                    ),
                    'visibility' => empty($wp_connection) ? 1 : 0,
                    'choices'    => $db_names,
                    'value'      => Buwd_Options::get($job_id, 'db_name'),
                    'hint'       => array(
                        'html' => '<p class="description">Select the database to connect to.</p>',
                    ),
                ),
                array(
                    'label'   => __('Backup all tables', 'buwd'),
                    'id'      => 'dbtables_all',
                    'name'    => 'dbtables_all',
                    'type'    => 'checkbox',
                    'class'   => array(
                        'buwd-tables-all'
                    ),
                    'choices' => array('all' => ''),
                    'value'   => Buwd_Options::get($job_id, 'dbtables_all'),
                ),
                array(
                    'label'      => __('Tables to backup', 'buwd'),
                    'id'         => 'dbtables',
                    'name'       => 'dbtables',
                    'type'       => 'checkbox',
                    'class'      => array(
                        'type-db-tables',
                    ),
                    'choices'    => $_tables,
                    'value'      => Buwd_Options::get($job_id, 'dbtables'),
                    'hint'       => array(
                        'pos'  => 'before',
                        'html' => '<input type="button" class="buwd-button db-table-check" id="dball" value="check all" /> <input type="button" class="buwd-button db-table-check" id="dbnone" value="uncheck all" /> <input type="button" class="buwd-button db-table-check" id="dbprefix" value="check ' . $wpdb->prefix . '" />'
                    ),
                    'visibility' => is_array(Buwd_Options::get($job_id, 'dbtables_all')) && in_array('all', Buwd_Options::get($job_id, 'dbtables_all')) ? false : true,
                    'column'     => 3
                ),
                array(
                    'label' => __('Backup file name', 'buwd'),
                    'id'    => 'dbfilename',
                    'name'  => 'dbfilename',
                    'type'  => 'text',
                    'class' => array(
                        'buwd-medium-text',
                    ),
                    'value' => esc_attr(Buwd_Options::get($job_id, 'dbfilename')),
                    'hint'  => array(
                        'html' => '.sql<p class="description">Provide a filename for database backup file.</p>'
                    )
                ),
                array(
                    'label'   => __('Backup file compression', 'buwd'),
                    'id'      => 'dbfilecomp',
                    'name'    => 'dbfilecomp',
                    'type'    => 'radio',
                    'class'   => array(
                        ''
                    ),
                    'choices' => array(
                        'none' => 'None',
                        'gzip' => 'GZip',
                    ),
                    'value'   => Buwd_Options::get($job_id, 'dbfilecomp'),
                    'column'  => 5,
                    'hint'    => array(
                        'html' => '<p class="description">Select the compression type for database backup file.</p>'
                    )
                ),
                array(
                    'label'   => __('Encrypt DB file', 'buwd'),
                    'id'      => 'db_encrypt',
                    'name'    => 'db_encrypt',
                    'type'    => 'checkbox',
                    'class'   => array(
                        '',
                    ),
                    'choices' => array(1 => ''),
                    'value'   => Buwd_Options::get($job_id, 'db_encrypt'),
                    'hint'    => array(
                        'html' => '<p class="description">Mark this option as checked, in case you would like to encrypt the database backup file for data protection.</p>'
                    )
                ),
            ),
        );

        return $options;
    }

    public function run(Buwd_Job $job_object)
    {
        $dbtables = is_array($job_object->job['dbtables_all']) && in_array('all', $job_object->job['dbtables_all']) ? $this->get_tables($job_object->job_id) : $job_object->job['dbtables'];
        $dbfilename = $job_object->job['dbfilename'];
        $dbfilecomp = $job_object->job['dbfilecomp'];

        $job_object->buwd_logger->set_log(sprintf(__('%d. Attempted to backup database.', 'buwd'), $job_object->steps_data[$job_object->current_step]['step']));
        $job_object->update_progress();

        return $this->create_db_export_file($job_object, $dbtables, $dbfilename, $dbfilecomp, Buwd::get_plugin_data('temp_folder_dir'));

    }

    private function get_db_names($job_id)
    {
        $wpdb = $this->db_connect($job_id);
        $res = $wpdb->get_results('SHOW DATABASES');
        $db_names = array();
        foreach ($res as $db) {
            $db_names[$db->Database] = $db->Database;
        }

        return $db_names;
    }

    private function db_connect($job_id)
    {
        if (isset($_POST['use_wp_connection'])) {
            $use_wp_connection = (int)$_POST['use_wp_connection'] == 0 ? true : false;
        } else {
            $wp_connection = Buwd_Options::get($job_id, 'use_wp_connection');
            $use_wp_connection = empty($wp_connection) ? true : false;
        }

        $db_name = isset($_POST['db_name']) ? sanitize_text_field($_POST['db_name']) : esc_attr(Buwd_Options::get($job_id, 'db_name', DB_NAME, true));
        $db_user = isset($_POST['db_user']) ? sanitize_text_field($_POST['db_user']) : esc_attr(Buwd_Options::get($job_id, 'db_user', DB_USER, true));
        $db_password = isset($_POST['db_password']) ? sanitize_text_field($_POST['db_password']) : esc_attr(Buwd_Options::get($job_id, 'db_password', DB_PASSWORD, true));
        $db_host = isset($_POST['db_host']) ? sanitize_text_field($_POST['db_host']) : esc_attr(Buwd_Options::get($job_id, 'db_host', DB_HOST, true));

        if ($use_wp_connection) {
            $this->db_user = $db_user;
            $this->db_password = $db_password;
            $this->db_name = $db_name;
            $this->db_host = $db_host;
        }

        $wpdb = new wpdb($this->db_user, $this->db_password, $this->db_name, $this->db_host);

        return $wpdb;
    }

    public function run_ajax()
    {
        $html = $this->render_tab();
        echo $html;
        wp_die();
    }


    private function get_tables($job_id = 0)
    {
        $wpdb = $this->db_connect($job_id);
        $tables = $wpdb->get_results('SHOW TABLES FROM ' . $this->backquote($this->db_name), ARRAY_N);
        $_tables = array();
        if ($tables) {
            foreach ($tables as $table) {
                $_tables[$table[0]] = $table[0];
            }
        }

        return $_tables;
    }


    /**
     * @param $tables
     * @param $file_name
     * @param $compression_type
     * @param $temp_dir
     * Create Export File For DataBase
     */
    private function create_db_export_file($job_object, $tables, $file_name, $compression_type, $temp_dir)
    {
        $wpdb = $this->db_connect($job_object->job_id);
        $db_file_name = $temp_dir . $file_name . '.sql';
        if (($db_file = fopen($db_file_name, 'w+')) !== false) {
            $sql_header_string = "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";" . PHP_EOL . "SET time_zone = \"+00:00\";" . PHP_EOL . "SET foreign_key_checks = 0;" . PHP_EOL;
            fwrite($db_file, $sql_header_string . PHP_EOL);

            $search = array("\x00", "\x0a", "\x0d", "\x1a");
            $replace = array('\0', '\n', '\r', '\Z');

            if (!empty($tables)) {
                foreach ($tables as $table) {
                    $table_structure = $wpdb->get_results("DESCRIBE " . $this->backquote(stripslashes($table)));
                    if (!$table_structure) {
                        return false;
                    }

                    $defaults = array();
                    $int_fields = array();
                    foreach ($table_structure as $t_struct) {
                        if ((0 === strpos($t_struct->Type, 'tinyint')) || (0 === strpos(strtolower($t_struct->Type), 'smallint')) ||
                            (0 === strpos(strtolower($t_struct->Type), 'mediumint')) || (0 === strpos(strtolower($t_struct->Type), 'int')) || (0 === strpos(strtolower($t_struct->Type), 'bigint'))
                        ) {
                            $defaults[strtolower($t_struct->Field)] = (null === $t_struct->Default) ? 'NULL' : $t_struct->Default;
                            $int_fields[strtolower($t_struct->Field)] = 1;
                        }
                    }


                    $create_table_syntax = $wpdb->get_row('SHOW CREATE TABLE ' . $this->backquote(stripslashes($table)), ARRAY_N);
                    $table_data = $wpdb->get_results('SELECT * FROM ' . $this->backquote(stripslashes($table)), ARRAY_A);

                    $insert_data = "INSERT INTO " . $this->backquote(stripslashes($table)) . " VALUES ";
                    $insert_string = "";
                    if ($table_data) {
                        $entry = "";
                        foreach ($table_data as $table_row) {
                            $items = array();
                            foreach ($table_row as $key => $item) {
                                if (isset($int_fields[strtolower($key)])) {
                                    $item = ($item === null || $item === '') ? $defaults[strtolower($key)] : $item;
                                    $items[] = ($item === '') ? "''" : $item;
                                } else {
                                    $items[] = ($item === null) ? 'NULL' : "'" . str_replace($search, $replace, str_replace('\'', '\\\'', str_replace('\\', '\\\\', $item))) . "'";
                                }

                                /*$last_key = key(array_slice($table_row, -1, 1, true));
                                $item = addslashes($item);
                                $insert_string .= '"' . $item . '"';
                                if ($key != $last_key) {
                                    $insert_string .= ',';
                                }*/
                            }
                            if ($entry) {
                                $entry .= ",\n ";
                            }

                            $entry .= '(' . implode(', ', $items) . ')';
                        }


                        if ($entry) {
                            $insert_string = " \n" . $insert_data . $entry . ';' . PHP_EOL;
                        }
                    }

                    $drop_string = "DROP TABLE IF EXISTS " . $this->backquote(stripslashes($table)) . ";";
                    //	$comment_drop   = '/**' . PHP_EOL . 'DROP TABLE ' . $table . PHP_EOL . '**/';
                    //	$comment_create = '/**' . PHP_EOL . 'CREATE TABLE ' . $table . PHP_EOL . '**/';
                    //	$comment_insert = '/**' . PHP_EOL . 'INSERT ' . $table . ' DATA' . PHP_EOL . '**/';
                    //	fwrite( $db_file, $comment_drop . PHP_EOL );
                    fwrite($db_file, $drop_string . PHP_EOL);
                    //	fwrite( $db_file, $comment_create . PHP_EOL );
                    fwrite($db_file, $create_table_syntax[1] . ';' . PHP_EOL . PHP_EOL);
                    //	fwrite( $db_file, $comment_insert . PHP_EOL );
                    fwrite($db_file, $insert_string . PHP_EOL . PHP_EOL);

                    if ($job_object->isdebug) {
                        $job_object->buwd_logger->set_log(sprintf(__('Backup database table "%s" with "%d" records was created.', 'buwd'), $table, count($table_data)));
                    }
                }

                if ($job_object->job['dbfilecomp'] == 'gzip') {
                    $archive_class = new Buwd_Archive($db_file_name . '.gz', 'gzip');
                    if ($archive_class->add_file($db_file_name, $db_file_name)) {
                        @unlink($db_file_name);
                        $db_file_name .= '.gz';
                    } else {

                    }
                    unset($archive_class);
                }

                $db_file_size = filesize($db_file_name);
                $job_object->extra_files[] = $db_file_name;
                $job_object->buwd_logger->set_log(sprintf(__('Database file "%1$s" with %2$s was added to backup file list.', 'buwd'), $db_file_name, size_format($db_file_size, 2)));
                $job_object->buwd_logger->set_log(__('Database backup was completed.', 'buwd'));
            } else {
                $job_object->buwd_logger->set_log(__('There are no database tables to backup.', 'buwd'), E_USER_WARNING);
            }

            fclose($db_file);
        } else {
            $job_object->buwd_logger->set_log(__('Database backup file was not created.', 'buwd'), E_USER_ERROR);

            return false;
        }

        $db_encrypt = Buwd_Options::get($job_object->job_id, 'db_encrypt');
        if (!empty($db_encrypt)) {
            Buwd_Encrypt::encrypt_file($db_file_name);
        }

        return true;
    }

    private function render_tab()
    {
        $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $options = $this->get_options($job_id);
        $group_class = new Buwd_Form_Group(array($options));
        $groups = $group_class->get_groups();
        $group_html = array();
        foreach ($groups as $g_name => $group) {
            $group_html['title'] = $group->title;
            $group_html['content'] = $group_class->render_group($g_name);
        }

        return '<table>' . $group_html['content'] . '</table>';
    }

    private function backquote($t_name)
    {
        if (!empty($t_name) && $t_name != '*') {
            if (is_array($t_name)) {
                $result = array();
                reset($t_name);
                while (list($key, $val) = each($t_name))
                    $result[$key] = '`' . $val . '`';

                return $result;
            } else {
                return '`' . $t_name . '`';
            }
        } else {
            return $t_name;
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