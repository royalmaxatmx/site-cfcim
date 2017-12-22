<?php

/**
 *
 */
class Buwd_Logs extends WP_List_Table
{
    protected static $instance = null;
    protected $orderby = null;
    protected $order = null;
    protected $per_page = null;
    protected $filter_type = null;
    protected $filter_jobid = null;
    private static $log_folder = null;
    private static $log_files = array();

    public function __construct()
    {
        parent::__construct(array(
            'plural'   => 'logs',
            'singular' => 'log',
            'ajax'     => true
        ));

        $this->orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'time';
        $this->order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        $per_page = false !== get_site_transient('logs_per_page') ? (int)get_site_transient('logs_per_page') : 20;
        $this->per_page = !empty($_POST['per_page']) ? (int)$_POST['per_page'] : $per_page;
        set_site_transient('logs_per_page', $this->per_page);

        $this->filter_type = Buwd_Helper::get("j_type") ? Buwd_Helper::get("j_type") : 'both';
        $this->filter_jobid = Buwd_Helper::get("j_id") ? (int)Buwd_Helper::get("j_id") : 0;


    }

    public function get_columns()
    {
        $columns = array(
            'cb'      => '<input type="checkbox" />',
            'time'    => __('Date', 'buwd'),
            'job'     => __('Job', 'buwd'),
            'type'    => __('Job Type', 'buwd'),
            'status'  => __('Status', 'buwd'),
            'size'    => __('Backup Size', 'buwd'),
            'runtime' => __('Runtime', 'buwd'),
        );

        return $columns;
    }

    public function get_sortable_columns()
    {
        return array(
            'time' => array('time', false),
        );
    }

    public function get_log_count()
    {
        $ckeck = array();
        if ($this->filter_type != 'both') {
            $ckeck[] = 'filter_type';
        }

        if ($this->filter_jobid != '') {
            $ckeck[] = 'filter_jobid';
        }

        if (count($ckeck) > 0) {
            foreach (self::$log_files as $key => $log_file) {
                $log_data = $this->get_log_header_data(self::$log_folder . '/' . $log_file);
                $type = explode(',', $log_data['type']);
                if (in_array('filter_type', $ckeck)) {
                    if (count($type) > 1 || !in_array($this->filter_type, $type)) {
                        unset(self::$log_files[$key]);
                    }
                }

                if (in_array('filter_jobid', $ckeck)) {
                    if ($this->filter_jobid != $log_data['job_id']) {
                        unset(self::$log_files[$key]);
                    }
                }
            }
        }

        return count(self::$log_files);
    }

    public function get_rows($current_page = 1)
    {
        $c_keys = array_keys($this->get_columns());
        //$sortable_c_keys = array_keys( $this->get_sortable_columns() );
        array_push($c_keys, 'log_summary');
        $rows = array();
        foreach (self::$log_files as $key => $log_file) {
            $log_data = $this->get_log_header_data(self::$log_folder . '/' . $log_file);
            foreach ($c_keys as $c_key) {
                if ($c_key == 'cb') {
                    continue;
                }
                $rows[$key]['file'] = $log_file;
                if ($c_key == 'job') {
                    $rows[$key]['job_id'] = $log_data['job_id'];
                    $rows[$key]['job_name'] = $log_data['job_name'];
                } else {
                    if ($log_data[$c_key]) {
                        $rows[$key][$c_key] = $log_data[$c_key];
                    } else {
                        $rows[$key][$c_key] = '';
                    }
                }
            }
        }

        $this->sort_array($rows);

        $start = ($current_page - 1) * $this->per_page;
        $rows = array_slice($rows, $start, $this->per_page);

        return $rows;
    }

    public function extra_tablenav($which)
    {
        $job_ids = Buwd_Options::get_job_ids();
        $job_types = Buwd::get_job_types();
        if ($which == 'top') {
            if ($this->has_items() && current_user_can('buwd_log_delete')) {
                ?>
                <div class="alignleft actions">
                    <div class="alignleft actions">
                        <select name="bulk_action" id="bulk_action">
                            <option value="">Bulk Actions</option>
                            <option value="delete_logs">Delete</option>
                        </select>
                        <button class="buwd-button bulk-action-button"
                                onclick="buwd_bulk_action('logs'); return false;">Apply
                        </button>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="alignleft actions">
                <label for="filter-by-jid" class="screen-reader-text">Filter by id</label>
                <select name="j_id" id="filter-by-jid">
                    <option value="" <?php echo($this->filter_jobid == "" ? 'selected="selected"' : '') ?>>Select Job
                    </option>
                    <?php
                    foreach ($job_ids as $key => $job_id) {
                        $name = Buwd_Options::get($job_id, 'name');
                        $selected = $this->filter_jobid == $job_id ? 'selected="selected"' : '';
                        echo '<option value="' . $job_id . '" ' . $selected . '>' . $name . '</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="alignleft actions">
                <label for="filter-by-type" class="screen-reader-text">Filter by type</label>
                <select name="j_type" id="filter-by-type">
                    <option value="both" <?php echo($this->filter_type == "both" ? 'selected="selected"' : '') ?>>Both
                    </option>
                    <?php
                    foreach ($job_types as $key => $job_type) {
                        $selected = $this->filter_type == $key ? 'selected="selected"' : '';
                        echo '<option value="' . $key . '" ' . $selected . '>' . $job_types[$key]->info['name'] . '</option>';
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="filter-submit" class="buwd-button filter-button"
                       value="Filter">
                <button class="buwd-button reset-button"
                        onclick="window.location.href = window.location.href; return false; ">Reset
                </button>
            </div>

            <div class="tablenav-pages">
                <input type="number" step="1" min="1" max="999" class="" name="per_page" id="pagination_per_page"
                       maxlength="3" value="<?php echo $this->per_page; ?>" onchange="this.form.submit();"/>
            </div>
            <?php
        }
        if ($which == 'bottom') {
            if ($this->has_items() && current_user_can('buwd_log_delete')) {
                ?>
                <div class="alignleft actions">
                    <div class="alignleft actions">
                        <select name="bulk_action" id="bulk_action">
                            <option value="">Bulk Actions</option>
                            <option value="delete_logs">Delete</option>
                        </select>
                        <button class="buwd-button bulk-action-button"
                                onclick="buwd_bulk_action('logs'); return false;">Apply
                        </button>
                    </div>
                </div>
                <?php
            }
        }

    }

    public function no_items()
    {
        _e('No logs found.');
    }

    public function column_cb($item)
    {
        return '<input type="checkbox" name="logs[]" class="logs-cb" value="' . esc_attr($item['file']) . '" />';
    }

    public function column_time($item)
    {
        return Buwd_Helper::get_user_time($item['time'])->format('M d, Y \a\t h:i:sa');
    }

    public function column_job($item)
    {
        $actions = array();
        $view_ajax_nonce = wp_create_nonce("log-view-" . $item['file']);

        if (current_user_can('buwd_log_view')) {
            $actions['view'] = '<a href=\'' . network_admin_url("admin-ajax.php") . '?action=view_log&log=' . $item['file'] . '&_ajax_nonce=' . $view_ajax_nonce . '&width=900&height=500&TB_iframe=1\' class="thickbox thickbox-view" title="' . $item['file'] . '" onclick="return false;">' . esc_html__("View", "buwd") . '</a>';
        }

        if (current_user_can('buwd_log_delete')) {
            $actions['delete'] = '<a onclick="if (confirm(\'Do you want to delete selected item(s)?\')) { buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_logs&action=delete&log=' . $item['file'], 'bulk-logs') . '\'); }return false;" href=\'\'>' . esc_html__("Delete", "buwd") . '</a>';
        }

        if (current_user_can('buwd_log_download')) {
            $actions['download'] = '<a href=\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_logs&action=download&log=' . $item['file'], 'log-download-' . $item['file']) . '\'>' . esc_html__("Download", "buwd") . '</a>';
        }

        return sprintf('%1$s %2$s', $item['job_name'] . ' ID: ' . $item['job_id'], $this->row_actions($actions));
    }

    public function column_type($item)
    {
        $job_types = Buwd::get_job_types();
        $types = explode(',', $item['type']);
        $type_names = array_map(function ($value) use ($job_types) {
            return $job_types[$value]->info['name'];
        }, $types);

        return implode('</br>', $type_names);
    }

    public function column_status($item)
    {
        switch ($item['status']) {
            case 'OK':
                $class = 'buwd-status-success';
                break;
            case 'Failed':
                $class = 'buwd-status-failed';
                break;
            case 'Stopped':
                $class = 'buwd-status-stopped';
                break;
            default:
                $class = '';
                break;
        }

        return '<span class="' . $class . '">' . $item['status'] . '</span>' . '<br>' . $item['log_summary'];
    }

    public function column_size($item)
    {
        return $item['size'] ? Buwd_File::get_human_filesize($item['size']) : '';
    }

    public function column_runtime($item)
    {
        return gmdate("H:i:s", (int)$item['runtime']);
    }

    public function prepare_items()
    {
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $columns = $this->get_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = $this->get_log_count();

        $this->set_pagination_args(array(
            'total_items'  => $total_items,
            'per_page'     => $this->per_page,
            'orderby'      => $this->orderby,
            'order'        => $this->order,
            'filter_type'  => $this->filter_type,
            'filter_jobid' => $this->filter_jobid,
        ));

        $this->items = $this->get_rows($current_page);

    }

    public function display_page()
    {
        $this->prepare_items();

        include_once(BUWD_DIR . '/views/logs.php');
    }

    public static function load_action()
    {
        self::get_log_files();

        $current_action = sanitize_text_field(Buwd_Helper::get('action'), 'get');
        if ($current_action) {
            switch ($current_action) {
                case 'delete' :
                    if (!current_user_can('buwd_log_delete')) {
                        return false;
                    }
                    check_admin_referer('bulk-logs');

                    if (!self::action_delete('log')) {
                        set_site_transient('buwd_logs_error', __('An error occurred while trying to delete logs.', 'buwd'));
                    } else {
                        set_site_transient('buwd_logs_updated', __('Selected logs have been deleted successfully.', 'buwd'));

                    }

                    break;

                case 'delete_logs' :
                    if (!current_user_can('buwd_log_delete')) {
                        return false;
                    }

                    check_ajax_referer(BUWD_PREFIX . '_ajax_nonce');

                    if (!isset($_REQUEST['logs'])) {
                        set_site_transient('buwd_logs_error', __('Please select items to delete.', 'buwd'));
                        die();
                    } else {
                        if (!self::action_delete('logs')) {
                            set_site_transient('buwd_logs_error', __('An error occurred while trying to delete logs.', 'buwd'));
                        } else {
                            set_site_transient('buwd_logs_updated', __('Selected logs have been deleted successfully.', 'buwd'));

                        }
                    }

                    break;

                case 'download' : {
                    if (!current_user_can('buwd_log_download')) {
                        return false;
                    }

                    $log = Buwd_Helper::get("log") ? Buwd_Helper::get("log") : '';
                    check_admin_referer('log-download-' . $log);

                    self::action_download($log);
                    break;
                }

                default: {

                    break;
                }
            }
        }
    }

    public static function action_view_log()
    {
        $log = Buwd_Helper::get("log") ? Buwd_Helper::get("log") : '';
        if (!current_user_can('buwd_log_view') || !$log || strstr($log, 'backupwd_log_') === false) {

            die();
        }

        check_ajax_referer('log-view-' . $log);

        $log_folder = Buwd_File::get_absolute_path(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd::get_plugin_data('log_folder_dir')));

        $log_file = $log_folder . '/' . $log;
        echo '<link rel="stylesheet"  href="' . BUWD_URL . '/public/css/log.css" type="text/css" media="all">';

        echo '<div class="buwd_log">';
        if (file_exists($log_file) && is_readable($log_file)) {
            //echo file_get_contents( $log_file, false );
            $log = fopen($log_file, 'r');

            while (($line = fgets($log)) !== false) {
                echo '<div>' . $line . '</div>';
            }

        } else {
            die(__('Log file not found!', 'buwd'));
        }

        echo '<div>';

        die();
    }

    public static function get_log_files()
    {
        self::$log_folder = Buwd_File::get_absolute_path(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd::get_plugin_data('log_folder_dir')));

        if (is_readable(self::$log_folder) && $dir = opendir(self::$log_folder)) {
            while (($file = readdir($dir)) !== false) {
                $log_file = self::$log_folder . '/' . $file;
                if (is_file($log_file) && is_readable($log_file) && false !== strpos($file, 'backupwd_log_') && false !== strpos($file, '.html')) {
                    self::$log_files[] = $file;
                }
            }
            closedir($dir);
        }
    }

    protected function pagination($which)
    {
        if (empty($this->_pagination_args)) {
            return;
        }

        $total_items = $this->_pagination_args['total_items'];
        $total_pages = $this->_pagination_args['total_pages'];
        $filter_type = $this->_pagination_args['filter_type'];
        $filter_jobid = $this->_pagination_args['filter_jobid'];
        $per_page = $this->_pagination_args['per_page'];
        $infinite_scroll = false;
        if (isset($this->_pagination_args['infinite_scroll'])) {
            $infinite_scroll = $this->_pagination_args['infinite_scroll'];
        }

        if ('top' === $which && $total_pages > 1) {
            $this->screen->render_screen_reader_content('heading_pagination');
        }

        $output = '<span class="displaying-num">' . sprintf(_n('%s item', '%s items', $total_items), number_format_i18n($total_items)) . '</span>';

        $current = $this->get_pagenum();
        $removable_query_args = wp_removable_query_args();

        $current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        $current_url = remove_query_arg($removable_query_args, $current_url);

        $current_url = add_query_arg('per_page', $per_page, $current_url);

        if ($filter_jobid != '') {
            $current_url = add_query_arg('j_id', $filter_jobid, $current_url);
        }

        if ($filter_type != 'both') {
            $current_url = add_query_arg('j_type', $filter_type, $current_url);
        }

        $page_links = array();
        $total_pages_before = '<span class="paging-input">';
        $total_pages_after = '</span></span>';

        $disable_first = $disable_last = $disable_prev = $disable_next = false;

        if ($current == 1) {
            $disable_first = true;
            $disable_prev = true;
        }
        if ($current == 2) {
            $disable_first = true;
        }
        if ($current == $total_pages) {
            $disable_last = true;
            $disable_next = true;
        }
        if ($current == $total_pages - 1) {
            $disable_last = true;
        }

        if ($disable_first) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
        } else {
            $page_links[] = sprintf("<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url(remove_query_arg('paged', $current_url)),
                __('First page'),
                '&laquo;'
            );
        }

        if ($disable_prev) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
        } else {
            $page_links[] = sprintf("<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url(add_query_arg('paged', max(1, $current - 1), $current_url)),
                __('Previous page'),
                '&lsaquo;'
            );
        }

        if ('bottom' === $which) {
            $html_current_page = $current;
            $total_pages_before = '<span class="screen-reader-text">' . __('Current Page') . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
        } else {
            $html_current_page = sprintf("%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
                '<label for="current-page-selector" class="screen-reader-text">' . __('Current Page') . '</label>',
                $current,
                strlen($total_pages)
            );
        }
        $html_total_pages = sprintf("<span class='total-pages'>%s</span>", number_format_i18n($total_pages));
        $page_links[] = $total_pages_before . sprintf(_x('%1$s of %2$s', 'paging'), $html_current_page, $html_total_pages) . $total_pages_after;

        if ($disable_next) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
        } else {
            $page_links[] = sprintf("<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url(add_query_arg('paged', min($total_pages, $current + 1), $current_url)),
                __('Next page'),
                '&rsaquo;'
            );
        }

        if ($disable_last) {
            $page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
        } else {
            $page_links[] = sprintf("<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
                esc_url(add_query_arg('paged', $total_pages, $current_url)),
                __('Last page'),
                '&raquo;'
            );
        }

        $pagination_links_class = 'pagination-links';
        if (!empty($infinite_scroll)) {
            $pagination_links_class = ' hide-if-js';
        }
        $output .= "\n<span class='$pagination_links_class'>" . join("\n", $page_links) . '</span>';

        if ($total_pages) {
            $page_class = $total_pages < 2 ? ' one-page' : '';
        } else {
            $page_class = ' no-pages';
        }
        $this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

        echo $this->_pagination;

    }

    private static function action_delete($option)
    {
        $elements = Buwd_Helper::get($option) ? ( array )Buwd_Helper::get($option) : array();

        if (empty($elements)) {
            return false;
        }

        foreach ($elements as $element) {
            if (in_array($element, self::$log_files)) {

                $file_key = array_search($element, self::$log_files);

                unset(self::$log_files[$file_key]);
                unlink(self::$log_folder . '/' . $element);
            }
        }

        return true;
    }

    private static function action_download($log)
    {
        if (empty($log)) {
            return false;
        }

        $log_file = self::$log_folder . '/' . $log;
        if (is_readable($log_file) && !is_link($log_file)) {
            @set_time_limit(3000);
            nocache_headers();
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }
            header('Content-Description: File Transfer');
            header('Content-Type: ' . Buwd_File::mime_content_type($log_file));
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . $log . "\"");
            readfile($log_file);
            die();
        } else {
            header('HTTP/1.0 404 Not Found');
            die();
        }
    }

    private function get_log_header_data($log_file)
    {
        $metadata = get_meta_tags($log_file);
        $columns = array(
            'date'            => 'time',
            'job_id'          => 'job_id',
            'job_name'        => 'job_name',
            'job_type'        => 'type',
            'backup_filesize' => 'size',
            'job_runtime'     => 'runtime',
            'status'          => 'status',
            'log_summary'     => 'log_summary',
        );

        $data = array();
        foreach ($columns as $key => $column) {
            if (isset($metadata[$key])) {
                if ($key == 'date') {
                    $data[$column] = $metadata['date'];
                } else {
                    $data[$column] = $metadata[$key];
                }
            } else {
                $data[$column] = '';
            }
        }

        return $data;
    }

    private function sort_array(&$array_to_sort)
    {
        $result = array();
        foreach ($array_to_sort as &$ma) {
            $result[] = &$ma[$this->orderby];
        }

        if ($this->order == 'asc') {
            array_multisort($result, SORT_ASC, $array_to_sort);
        } else {
            array_multisort($result, SORT_DESC, $array_to_sort);
        }
    }

    public static function admin_print_scripts()
    {
        wp_enqueue_script('buwd-table', BUWD_URL . '/public/js/list-table.js', array(), BUWD_VERSION);
    }

    public static function admin_print_styles()
    {
        wp_enqueue_style('buwd-table', BUWD_URL . '/public/css/list-table.css', array(), BUWD_VERSION);
    }

    public function display_messages()
    {
        $current_action = Buwd_Helper::get('action');
        if ($error = get_site_transient('buwd_logs_error')) {
            if (!$current_action) {
                echo Buwd_Helper::message($error, 'error');
                delete_site_transient('buwd_logs_error');
            }
        } else if ($updated = get_site_transient('buwd_logs_updated')) {
            if (!$current_action) {
                echo Buwd_Helper::message($updated, 'success');
                delete_site_transient('buwd_logs_updated');
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