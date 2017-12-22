<?php

/**
 *
 */
class Buwd_Backups extends WP_List_Table
{
    protected static $instance = null;
    protected $orderby = null;
    protected $order = null;
    protected $per_page = null;
    protected $filter_jobid = null;
    protected $filter_dest = null;
    public static $deleted = null;
    protected static $backup_files = array();

    public function __construct()
    {
        parent::__construct(array(
            'plural'   => 'backups',
            'singular' => 'backup',
            'ajax'     => true
        ));

        $this->orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'time';
        $this->order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        $per_page = false !== get_site_transient('backups_per_page') ? get_site_transient('backups_per_page') : 20;
        $this->per_page = !empty($_POST['per_page']) ? (int)$_POST['per_page'] : $per_page;
        set_site_transient('backups_per_page', $this->per_page);

        $this->filter_jobid = Buwd_Helper::get("j_id") ? (int)Buwd_Helper::get("j_id") : 0;
        $this->filter_dest = Buwd_Helper::get("j_dest") ? Buwd_Helper::get("j_dest") : '';
    }

    public function get_columns()
    {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'time'        => __('Date', 'buwd'),
            'file'        => __('File', 'buwd'),
            'destination' => __('Destination', 'buwd'),
            'folder'      => __('Folder', 'buwd'),
            'size'        => __('Size', 'buwd'),
        );

        return $columns;
    }

    public function get_sortable_columns()
    {
        return array(
            'time'        => array('time', false),
            'file'        => array('file', false),
            'destination' => array('dest', false),
            'folder'      => array('folder', false),
            'size'        => array('size', false),
        );
    }

    public function get_backups_count()
    {
        $ckeck = array();
        if ($this->filter_jobid != '') {
            $ckeck[] = 'filter_jobid';
        }

        if ($this->filter_dest != '') {
            $ckeck[] = 'filter_dest';
        }

        $backups = self::$backup_files;
        $backup_count = count($backups);
        if (count($ckeck) > 0) {
            if (in_array('filter_jobid', $ckeck)) {
                $backups = Buwd_Helper::search_in_array($backups, 'jid', $this->filter_jobid);
                $backup_count = count($backups);
            }

            if (in_array('filter_dest', $ckeck)) {
                $backups = Buwd_Helper::search_in_array($backups, 'dest', $this->filter_dest);
                $backup_count = count($backups);
            }
        }

        return $backup_count;
    }

    public function get_rows($current_page = 1)
    {
        //	$c_keys = array_keys( $this->get_columns() );
        $ckeck = array();
        if ($this->filter_jobid != '') {
            $ckeck[] = 'filter_jobid';
        }

        if ($this->filter_dest != '') {
            $ckeck[] = 'filter_dest';
        }

        $backups = self::$backup_files;
        if (count($ckeck) > 0) {
            if (in_array('filter_jobid', $ckeck)) {
                $backups = Buwd_Helper::search_in_array($backups, 'jid', $this->filter_jobid);
            }

            if (in_array('filter_dest', $ckeck)) {
                $backups = Buwd_Helper::search_in_array($backups, 'dest', $this->filter_dest);
            }
        }

        $rows = array();
        if (!empty($backups)) {
            foreach ($backups as $key => $backup) {
                foreach ($backup as $_key => $_backup) {
                    $rows[$key][$_key] = $_backup;
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
        $destinations = Buwd::get_destinations();
        if ($which == 'top') {
            if ($this->has_items() && current_user_can('buwd_backups_delete')) {
                ?>
                <div class="alignleft actions">
                    <select name="action" id="bulk_action">
                        <option value="">Bulk Actions</option>
                        <option value="delete_backups">Delete</option>
                    </select>
                    <button class="buwd-button bulk-action-button" onclick="buwd_bulk_action('backups'); return false;">
                        Apply
                    </button>

                </div>
                <?php
            }
            ?>
            <div class="alignleft actions">
                <label for="filter-by-jid" class="screen-reader-text">Filter by job id</label>
                <select name="j_id" id="filter-by-jid">
                    <option value="" <?php echo($this->filter_jobid == "" ? 'selected="selected"' : '') ?>>Select Job
                    </option>
                    <?php
                    foreach ($job_ids as $key => $job_id) {
                        //$name     = Buwd_Options::get( $job_id, 'name' );
                        $selected = $this->filter_jobid == $job_id ? 'selected="selected"' : '';
                        echo '<option value="' . $job_id . '" ' . $selected . '>' . Buwd_Options::get($job_id, 'name') . '</option>';
                    }
                    ?>
                </select>
                <label for="filter-by-dest" class="screen-reader-text">Filter by destination</label>
                <select name="j_dest" id="filter-by-dest">
                    <option value="" <?php echo($this->filter_dest == "" ? 'selected="selected"' : '') ?>>Select
                        Destination
                    </option>
                    <?php
                    foreach ($destinations as $key => $dest) {
                        $selected = $this->filter_dest == $key ? 'selected="selected"' : '';
                        echo '<option value="' . $key . '" ' . $selected . '>' . $dest->info['name'] . '</option>';
                    }
                    ?>
                </select>
                <input type="submit" name="filter_action" id="filter-submit" class="buwd-button filter-button"
                       value="Filter">
                <button class="buwd-button reset-button"
                        onclick="window.location.href = window.location.href; return false; ">
                    Reset
                </button>
            </div>
            <div class="tablenav-pages">
                <input type="number" step="1" min="1" max="999" class="" name="per_page" id="pagination_per_page"
                       maxlength="3" value="<?php echo $this->per_page; ?>" onchange="this.form.submit();"/>
            </div>
            <?php

        }
    }

    public function no_items()
    {
        _e('No backups found.');
    }

    public function column_cb($item)
    {
        if (!isset($item['sync'])) {
            return '<input type="checkbox" class="backups-cb" name="backups[]" value="' . esc_attr($item['file']) . '@*@dest@*@' . $item['dest'] . '" />';
        }
    }

    public function column_time($item)
    {
        return Buwd_Helper::get_user_time($item['time'])->format('M d, Y \a\t h:i:sa');

    }

    public function column_file($item)
    {
        $actions = array();
        if (!isset($item['sync'])) {
            if (current_user_can('buwd_backups_delete')) {
                $actions['delete'] = '<a onclick="if (confirm(\'Do you want to delete selected item(s)?\')) { buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_backups&action=delete&backup=' . $item['file'] . '&job_id=' . $item['jid'] . '&dest=' . $item['dest'], 'bulk-backups') . '\'); } return false;" href=\'\'>' . esc_html__("Delete", "buwd") . '</a>';
            }
        }

        if (!isset($item['sync'])) {
            if (current_user_can('buwd_backups_download')) {
                $actions['download'] = '<a href=\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_backups&action=download&backup=' . $item['file'] . '&jid=' . $item['jid'] . '&dest=' . $item['dest'], 'backup-download-' . $item['file']) . '\'>' . esc_html__("Download", "buwd") . '</a>';
            }
        }

        return sprintf('%1$s %2$s', $item['file'], $this->row_actions($actions));
    }

    public function column_folder($item)
    {
        return isset($item['container']) ? $item['container'] . '/' . $item['folder'] : $item['folder'];
    }

    public function column_destination($item)
    {
        $destination = Buwd::get_destination($item['dest']);

        return isset($item['dest']) ? $destination->info['name'] : '';
    }

    public function column_size($item)
    {
        return $item['size'] ? Buwd_File::get_human_filesize($item['size']) : '';
    }

    public function prepare_items()
    {
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $columns = $this->get_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = $this->get_backups_count();

        $this->set_pagination_args(array(
            'total_items'  => $total_items,
            'per_page'     => $this->per_page,
            'orderby'      => $this->orderby,
            'order'        => $this->order,
            'filter_jobid' => $this->filter_jobid,
            'filter_dest'  => $this->filter_dest,
        ));

        $this->items = $this->get_rows($current_page);
    }

    public function display_page()
    {
        $this->prepare_items();

        include_once(BUWD_DIR . '/views/backups.php');
    }

    public function display_messages()
    {
        $current_action = Buwd_Helper::get('action');
        if ($error = get_site_transient('buwd_backups_error')) {
            if (!$current_action) {
                echo Buwd_Helper::message($error, 'error');
                delete_site_transient('buwd_backups_error');
            }
        } else if ($updated = get_site_transient('buwd_backups_updated')) {
            if (!$current_action) {
                echo Buwd_Helper::message($updated, 'success');
                delete_site_transient('buwd_backups_updated');
            }
        }
    }

    public static function load_action()
    {
        self::get_backup_files();
        $current_action = sanitize_text_field(Buwd_Helper::get('action'), 'get');

        $destinations = Buwd::get_destinations();
        self::$deleted = 0;
        switch ($current_action) {
            case 'delete' :
                if (!current_user_can('buwd_backups_delete')) {
                    return false;
                }
                check_admin_referer('bulk-backups');

                $file = Buwd_Helper::get('backup') ? sanitize_text_field(Buwd_Helper::get('backup')) : '';
                $dest = Buwd_Helper::get('dest') ? sanitize_text_field(Buwd_Helper::get('dest')) : '';

                foreach (self::$backup_files as $backup_file) {
                    if ($backup_file['file'] == $file && $backup_file['dest'] == $dest) {
                        if ($destinations[$dest]->delete_file($backup_file)) {
                            self::$deleted++;
                            Buwd_Options::backup_delete('buwd-dest-' . $dest . '-' . $backup_file['jid'], $file);
                        }

                        break;
                    }
                }

                if (self::$deleted > 0) {
                    set_site_transient('buwd_backups_updated', __('Selected backup have been deleted successfully.', 'buwd'));
                } else {
                    set_site_transient('buwd_backups_error', __('An error occurred while trying to delete backup.', 'buwd'));
                }

                break;
            case 'delete_backups' :
                if (!current_user_can('buwd_backups_delete')) {
                    return false;
                }
                check_ajax_referer(BUWD_PREFIX . '_ajax_nonce');

                if (!isset($_REQUEST['backups'])) {
                    set_site_transient('buwd_backups_error', __('Please select at least one item to delete.', 'buwd'));
                } else {
                    $elements = Buwd_Helper::get('backups') ? ( array )Buwd_Helper::get('backups') : array();

                    foreach ($elements as $key => $element) {
                        $element = explode('@*@dest@*@', $element);
                        $file = sanitize_text_field($element[0]);
                        $dest = sanitize_text_field($element[1]);

                        foreach (self::$backup_files as $backup_file) {
                            if ($backup_file['file'] == $file && $backup_file['dest'] == $dest) {
                                if ($destinations[$dest]->delete_file($backup_file)) {
                                    self::$deleted++;
                                    Buwd_Options::backup_delete('buwd-dest-' . $dest . '-' . $backup_file['jid'], $file);
                                }

                                break;
                            }
                        }
                    }

                    if (self::$deleted > 0) {
                        set_site_transient('buwd_backups_updated', sprintf(__('%d backups have been deleted successfully.', 'buwd'), self::$deleted));
                    } else {
                        set_site_transient('buwd_backups_error', __('An error occurred while trying to delete backups.', 'buwd'));
                    }

                    //self::get_backup_files();
                }

                break;
            case 'download' : {
                if (!current_user_can('buwd_backups_download')) {
                    return false;
                }

                $backup = Buwd_Helper::get("backup") ? Buwd_Helper::get("backup") : '';
                check_admin_referer('backup-download-' . $backup);

                if (!empty($backup)) {
                    self::action_download($backup);
                }
                break;
            }

            default: {

                break;
            }
        }

    }

    public static function get_backup_files()
    {
        $dests = array_keys(Buwd::get_destinations());
        $job_ids = Buwd_Options::get_job_ids();
        foreach ($job_ids as $key => $job_id) {
            foreach ($dests as $d_key => $dest) {
                $backup = get_site_option('buwd-dest-' . $dest . '-' . $job_id, array());

                //	delete_site_option( 'buwd-dest-' . $dest . '-' . $job_id );
                if (!empty($backup)) {
                    self::$backup_files = array_merge(self::$backup_files, $backup);
                }

                $backup_sync = get_site_option('buwd-dest-' . $dest . '-sync-' . $job_id, array());
                if (!empty($backup_sync)) {
                    self::$backup_files[] = $backup_sync;
                }
            }
        }
    }

    public static function admin_print_scripts()
    {
        wp_enqueue_style('buwd-table', BUWD_URL . '/public/js/list-table.js', array(), BUWD_VERSION);
    }

    public static function admin_print_styles()
    {
        wp_enqueue_style('buwd-table', BUWD_URL . '/public/css/list-table.css', array(), BUWD_VERSION);
    }

    protected function pagination($which)
    {
        if (empty($this->_pagination_args)) {
            return;
        }

        $total_items = $this->_pagination_args['total_items'];
        $total_pages = $this->_pagination_args['total_pages'];
        $filter_jobid = $this->_pagination_args['filter_jobid'];
        $filter_dest = $this->_pagination_args['filter_dest'];
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

        if ($filter_dest != '') {
            $current_url = add_query_arg('j_dest', $filter_dest, $current_url);
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

    private static function action_download($backup)
    {
        @ini_set('max_execution_time', Buwd_Options::getSetting('max_exec_time'));

        $destinations = Buwd::get_destinations();

        $dest = Buwd_Helper::get('dest') ? Buwd_Helper::get('dest') : '';
        foreach (self::$backup_files as $backup_file) {
            if ($backup_file['file'] == $backup && $backup_file['dest'] == $dest) {
                $memory_limit = ini_get('memory_limit');
                if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                    if ($matches[2] == 'M') {
                        $memory_limit = $matches[1] * 1024 * 1024;
                    } else if ($matches[2] == 'K') {
                        $memory_limit = $matches[1] * 1024;
                    }
                }

                if ($memory_limit < $backup_file['size']) {
                    $memory_limit = (int)($backup_file['size'] / (1024 * 1024));

                    if ($memory_limit >= 256) {
                        ini_set('memory_limit', -1);
                    } else {
                        $memory_limit = $memory_limit + 50;
                        ini_set('memory_limit', $memory_limit . 'M');
                    }
                }

                $destinations[$dest]->download_file($backup_file);
                die();
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

?>