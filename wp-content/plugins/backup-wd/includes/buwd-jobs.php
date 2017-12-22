<?php

/**
 *
 */
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}


class Buwd_Jobs extends WP_List_Table
{
    protected static $instance = null;
    protected $orderby = null;
    protected $order = null;
    protected $per_page = null;
    protected $search = null;
    protected $filter_type = null;
    protected $filter_dest = null;

    public function __construct()
    {
        parent::__construct(array(
            'plural'   => 'jobs',
            'singular' => 'job',
            'ajax'     => true
        ));

        $this->orderby = !empty($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'id';
        $this->order = !empty($_GET['order']) ? sanitize_text_field($_GET['order']) : 'asc';

        $per_page = false !== get_site_transient('jobs_per_page') ? (int)get_site_transient('jobs_per_page') : 20;
        $this->per_page = !empty($_POST['per_page']) ? (int)$_POST['per_page'] : $per_page;
        set_site_transient('jobs_per_page', $this->per_page);

        $this->filter_type = Buwd_Helper::get("j_type") ? Buwd_Helper::get("j_type") : 'both';
        $this->filter_dest = Buwd_Helper::get("j_dest") ? Buwd_Helper::get("j_dest") : 'all';
        $this->search = isset($_REQUEST['s']) ? strtolower($_REQUEST['s']) : null;

        $this->load_action();
    }

    public function get_columns()
    {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'id'          => __('ID', 'buwd'),
            'name'        => __('Name', 'buwd'),
            'type'        => __('Type', 'buwd'),
            'destination' => __('Destination', 'buwd'),
            'next'        => __('Next Run', 'buwd'),
            'lastrun'     => __('Last Run', 'buwd'),
        );

        return $columns;
    }

    public function get_sortable_columns()
    {
        return array(
            'id'          => array('id', false),
            'name'        => array('name', false),
            'type'        => array('type', false),
            'destination' => array('destination', false),
            'next'        => array('next', false),
            'lastrun'     => array('lastrun', false),
        );
    }

    protected function get_bulk_actions()
    {
        if (!$this->has_items()) {
            return array();
        }
        $actions = array();
        if (current_user_can('buwd_job_delete')) {
            $actions = array(
                'delete' => __('Delete', 'buwd')
            );
        }

        return $actions;
    }

    public function get_rows($current_page = 1)
    {
        $job_ids = Buwd_Options::get_job_ids();

        $c_keys = array_keys($this->get_columns());
        $sortable_c_keys = array_keys($this->get_sortable_columns());

        $rows = array();
        foreach ($job_ids as $key => $id) {
            foreach ($c_keys as $c_key) {
                if ($c_key == 'cb') {
                    continue;
                }

                if ($c_key == 'id') {
                    $rows[$key][$c_key] = $id;
                } else {
                    $value = Buwd_Options::get($id, $c_key);
                    if ($c_key == 'name') {
                        if (isset($this->search) && $this->search != '' && strpos(strtolower($value), $this->search) === false) {
                            if (isset($rows[$key])) {
                                unset($rows[$key]);
                            }
                            continue 2;
                        }
                    } else if ($c_key == 'type' && $this->filter_type != 'both') {
                        if (count($value) > 1 || !in_array($this->filter_type, $value)) {
                            if (isset($rows[$key])) {
                                unset($rows[$key]);
                            }
                            continue 2;
                        }
                    } else if ($c_key == 'destination' && $this->filter_dest != 'all') {
                        if (count($value) > 1 || !in_array($this->filter_dest, $value)) {
                            if (isset($rows[$key])) {
                                unset($rows[$key]);
                            }
                            continue 2;
                        }
                    }

                    $rows[$key][$c_key] = $value;
                }

                if (is_array($rows[$key][$c_key]) && in_array($c_key, $sortable_c_keys)) {
                    if ($this->order == 'asc') {
                        sort($rows[$key][$c_key]);
                    } else {
                        rsort($rows[$key][$c_key]);
                    }

                }
            }
        }

        $this->sort_array($rows);

        $start = ($current_page - 1) * $this->per_page;
        $rows = array_slice($rows, $start, $this->per_page);

        return $rows;
    }

    public function get_job_count()
    {
        $job_ids = Buwd_Options::get_job_ids();
        $ckeck = array();
        if (isset($this->search) && $this->search != '') {
            $ckeck[] = 'serach';
        }

        if ($this->filter_type != 'both') {
            $ckeck[] = 'filter_type';
        }

        if ($this->filter_dest != 'all') {
            $ckeck[] = 'filter_dest';
        }

        if (count($ckeck) > 0) {
            foreach ($job_ids as $key => $job_id) {
                $name = Buwd_Options::get($job_id, 'name');
                $type = (array)Buwd_Options::get($job_id, 'type');
                $destination = (array)Buwd_Options::get($job_id, 'destination');

                if (in_array('serach', $ckeck) && strpos(strtolower($name), $this->search) === false) {
                    unset($job_ids[$key]);
                }

                if (in_array('filter_type', $ckeck)) {
                    if (count($type) > 1 || !in_array($this->filter_type, $type)) {
                        unset($job_ids[$key]);
                    }
                }

                if (in_array('filter_dest', $ckeck)) {
                    if (count($destination) > 1 || !in_array($this->filter_dest, $destination)) {
                        unset($job_ids[$key]);
                    }
                }
            }
        }

        return count($job_ids);
    }

    public function extra_tablenav($which)
    {
        $job_types = Buwd::get_job_types();
        $destinations = Buwd::get_destinations();
        if ($which == 'top') {
            //if ( $this->has_items() ) {
            ?>
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
            </div>

            <div class="alignleft actions">
                <label for="filter-by-dest" class="screen-reader-text">Filter by destination</label>
                <select name="j_dest" id="filter-by-dest">
                    <option value="all" <?php echo($this->filter_dest == "all" ? 'selected="selected"' : '') ?>>All
                    </option>
                    <?php
                    foreach ($destinations as $key => $dest) {
                        $selected = $this->filter_dest == $key ? 'selected="selected"' : '';
                        echo '<option value="' . $key . '" ' . $selected . '>' . $destinations[$key]->info['name'] . '</option>';
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
            //}
        }

    }

    public function no_items()
    {
        _e('No jobs found.');
    }

    public function column_cb($item)
    {
        return '<input type="checkbox" name="jobs[]" value="' . esc_attr($item['id']) . '" />';
    }

    public function column_id($item)
    {
        return $item['id'];
    }

    public function column_name($item)
    {
        $actions = array();
        if (current_user_can('buwd_job_edit')) {
            $actions['edit'] = '<a href=\'' . network_admin_url("admin.php") . '?page=buwd_editjob&job_id=' . $item['id'] . '\'>' . esc_html__("Edit", "buwd") . '</a>';
            $actions['copy'] = '<a onclick="buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=copy&job_id=' . $item['id'], 'job-copy-' . $item['id']) . '\');return false" href=\'\'>' . esc_html__("Copy", "buwd") . '</a>';
        }

        if (current_user_can('buwd_job_delete')) {
            $actions['delete'] = '<a onclick="if (confirm(\'Do you want to delete selected item(s)?\')) { buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=delete&job_id=' . $item['id'], 'bulk-jobs') . '\'); } return false; " href="">' . esc_html__("Delete", "buwd") . '</a>';
        }

        if (current_user_can('buwd_job_run')) {
            $wp_cron = (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) ? 1 : 0;
            $actions['run'] = '<a onclick="if('.$wp_cron.') {alert(\'WordPress Cron is disabled on your website. Please enable it, so Backup WD can run backup jobs.\') }else{ buwd_run_action(\'' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=run&job_id=' . $item['id'], 'job-run-' . $item['id']) . '\');} return false;" href="" >' . esc_html__("Run now", "buwd") . '</a>';
            //$actions['run'] = '<a href="' . wp_nonce_url(network_admin_url("admin.php") . '?page=buwd_jobs&action=run&job_id=' . $item['id'], 'job-run-' . $item['id']) . '"  >' . esc_html__("Run now", "buwd") . '</a>';
        }

        return sprintf('%1$s %2$s', '<strong>' . $item['name'] . '</strong>', $this->row_actions($actions));
    }

    public function column_type($item)
    {
        $types = array();
        $job_types = Buwd::get_job_types();
        if ($current_job_types = Buwd_Options::get($item['id'], 'type')) {
            foreach ($current_job_types as $current_job_type) {
                $types[] = $job_types[$current_job_type]->info['name'];
            }
        }

        return implode('</br>', $types);
    }

    public function column_destination($item)
    {
        $dests = array();
        $destinations = Buwd::get_destinations();
        if ($job_destinations = Buwd_Options::get($item['id'], 'destination')) {
            foreach ($job_destinations as $job_destination) {
                $dests[] = $destinations[$job_destination]->info['name'];
            }

            if ($this->order == 'asc') {
                sort($dests);
            } else {
                rsort($dests);
            }

            $dests = implode('</br>', $dests);
        } else {
            $dests = 'No destination set';
        }


        return $dests;
    }

    public function column_next($item)
    {
        $schedule_type = Buwd_Options::get($item['id'], 'schedule');
        if (in_array($schedule_type, array('manually', 'link')) || empty($schedule_type)) {
            return __('The job is not scheduled.', 'buwd');
        }
        /*
                $schedule_time_array = array(
                    'sch_minute' => Buwd_Options::get($item['id'], 'scheduleminute'),
                    'sch_hour'   => Buwd_Options::get($item['id'], 'schedulehour'),
                    'sch_day'    => Buwd_Options::get($item['id'], 'scheduleday'),
                    'sch_wday'   => Buwd_Options::get($item['id'], 'scheduleweek'),
                    'sch_month'  => Buwd_Options::get($item['id'], 'schedulemonth')
                );*/
        $cron_expression = Buwd_Options::get($item['id'], 'cron_expression');
        $next_run = Buwd_Cron::next_run($cron_expression);

        return $next_run;
        //return date('M d, Y', $cron_timestamp) . ' at ' . date('h:i:sa', $cron_timestamp);
    }

    public function column_lastrun($item)
    {
        $last_log = Buwd_Options::get($item['id'], 'last_log_file');
        $view_log = '';
        if ($last_log != '') {
            $view_log = '<br><span><a class="thickbox thickbox-view" href="' . add_query_arg(array('log' => $last_log, '_ajax_nonce' => wp_create_nonce('log-view-' . $last_log)), admin_url('admin-ajax.php') . '?action=view_log') . '&width=900&height=500&TB_iframe=1" title="' . basename($last_log) . '">View Log</a></span>';
        }

        $last_run = ($item['lastrun'] ? (Buwd_Helper::get_user_time($item['lastrun'])->format('M d, Y \a\t h:i:sa')) : 'not run yet');

        return $last_run . $view_log;
    }

    public function prepare_items()
    {
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $columns = $this->get_columns();

        $this->_column_headers = array($columns, $hidden, $sortable);

        $current_page = $this->get_pagenum();
        $total_items = $this->get_job_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page'    => $this->per_page,
            'orderby'     => $this->orderby,
            'order'       => $this->order,
            's'           => $this->search,
            'filter_type' => $this->filter_type,
            'filter_dest' => $this->filter_dest,
        ));

        $this->items = $this->get_rows($current_page);
    }

    public function display_page()
    {
        $this->prepare_items();
        include_once(BUWD_DIR . '/views/jobs.php');
    }

    public function load_action()
    {
        $job_id = (int)Buwd_Helper::get("job_id");
        $current_action = $this->current_action();
        $errors = get_site_transient('buwd_jobs_error') ? get_site_transient('buwd_jobs_error') : array();
        $messages = get_site_transient('buwd_jobs_updated') ? get_site_transient('buwd_jobs_updated') : array();

        if ($current_action) {
            switch ($current_action) {
                case 'delete' : {
                    if (!current_user_can('buwd_job_delete')) {
                        return false;
                    }

                    check_admin_referer('bulk-jobs');
                    $option = isset($_REQUEST['jobs']) ? 'jobs' : 'job_id';

                    if (!isset($_REQUEST['jobs']) && !isset($_REQUEST['job_id'])) {
                        $_POST['action'] = '';
                        $errors[] = __('Please select item(s) to delete.', 'buwd');
                        set_site_transient('buwd_jobs_error', $errors);

                    } else {
                        if (!$this->action_delete($option)) {
                            $errors[] = __('An error occurred while trying to delete jobs', 'buwd');
                            set_site_transient('buwd_jobs_error', $errors);
                        } else {
                            if (isset($_REQUEST['jobs'])) {
                                $_POST['action'] = '';
                            }

                            $messages[] = __('Selected jobs have been deleted successfully.', 'buwd');
                            set_site_transient('buwd_jobs_updated', $messages);

                        }
                    }

                    break;
                }
                case 'copy' : {
                    if (!current_user_can('buwd_job_edit')) {
                        return false;
                    }

                    check_admin_referer('job-copy-' . $job_id);
                    if (!$this->action_copy($job_id)) {
                        $errors[] = __('An error occurred while trying to copy the jobs.', 'buwd');
                        set_site_transient('buwd_jobs_error', $errors);
                    } else {
                        $messages[] = __('Selected jobs have been copied successfully.', 'buwd');
                        set_site_transient('buwd_jobs_updated', $messages);
                    }
                    break;
                }
                case 'run' : {
                    if (!current_user_can('buwd_job_run')) {
                        return false;
                    }
                    if (!$job_id) {
                        $errors[] = __('Please select job to run', 'buwd');
                        set_site_transient('buwd_jobs_error', $errors);
                        die();
                    }

                    check_admin_referer('job-run-' . $job_id);

                    $this->action_run($job_id);
                    break;
                }
                case 'abort_job':
                    Buwd_Job::abort_run();
                    break;
                default: {
                    break;
                }
            }
        }
    }

    protected function pagination($which)
    {
        if (empty($this->_pagination_args)) {
            return;
        }

        $total_items = $this->_pagination_args['total_items'];
        $total_pages = $this->_pagination_args['total_pages'];
        $s = $this->_pagination_args['s'];
        $filter_type = $this->_pagination_args['filter_type'];
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
        if ($s) {
            $current_url = add_query_arg('s', $s, $current_url);
        }

        if ($filter_type != 'both') {
            $current_url = add_query_arg('j_type', $filter_type, $current_url);
        }

        if ($filter_dest != 'all') {
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

    private function action_delete($option)
    {
        $elements = Buwd_Helper::get($option) ? ( array )Buwd_Helper::get($option) : array();

        if (empty($elements)) {
            return false;
        }

        foreach ($elements as $element) {
            wp_clear_scheduled_hook('buwd_cron', array('id' => $element));

            if (!Buwd_Options::delete_job($element)) {

            }
        }


        return true;
    }


    private function action_copy($job_id)
    {
        if (!$job_id) {
            return false;
        }

        $job_ids = Buwd_Options::get_job_ids();
        $new_job_id = max($job_ids) + 1;
        $job_options = Buwd_Options::get_job($job_id);
        foreach ($job_options as $key => $job_option) {
            if ($key == "last_log_file" || $key == "lastrun" || $key == 'backup_service_id') {
                continue;
            }
            if ($key == 'job_id') {
                $job_option = $new_job_id;
            } else if ($key == 'name') {
                $job_option = $job_option . ' copy';
            }
            Buwd_Options::update_job_option($new_job_id, $key, $job_option);
        }
        Buwd_Options::update_job_maxid($new_job_id);

        return true;
    }

    private function action_run($job_id)
    {
        $errors = get_site_transient('buwd_jobs_error') ? get_site_transient('buwd_jobs_error') : array();
        //check wp cron
        if(defined('DISABLE_WP_CRON') && DISABLE_WP_CRON){
            /* $errors[] = __('', 'buwd');
             set_site_transient('buwd_jobs_error', $errors);*/

            return false;
        }

        if(extension_loaded('zip') === false && Buwd_Options::get($job_id, 'archive_format') == 'zip') {
            $errors[] = __('PHP ZIP extension is disabled on your website. Please ask your hosting provider to enable it, or select a different option for Backup archive format setting.', 'buwd');
            set_site_transient('buwd_jobs_error', $errors);

            return false;
        }
        //job types
        $job_types = Buwd::get_job_types();
        $job_type = (array)Buwd_Options::get($job_id, 'type');

        $valid_job = false;
        if ($job_types) {
            foreach ($job_types as $id => $_type) {
                if (in_array($id, $job_type, true)) {
                    $valid_job = true;
                    break;
                }
            }
        }

        if (!$valid_job) {
            $errors[] = sprintf(__('Please choose one or more type to run "%s" job.', 'buwd'), esc_attr(Buwd_Options::get($job_id, 'name')));
            set_site_transient('buwd_jobs_error', $errors);

            return false;
        }

        //job destinations
        $destinations = Buwd::get_destinations();
        $job_destination = Buwd_Options::get($job_id, 'destination', array(), true);
        $valid_dest = true;
        if (!$job_destination) {
            $errors[] = sprintf(__('Please choose one or more destinations to run "%s" job.', 'buwd'), esc_attr(Buwd_Options::get($job_id, 'name')));
            set_site_transient('buwd_jobs_error', $errors);

            return false;
        }

        foreach ($destinations as $id => $dest) {
            if (!in_array($id, $job_destination, true)) {
                continue;
            }
            $dest_class = Buwd::get_destination($id);
            $job_options = Buwd_Options::get_job($job_id);

            if (!$dest_class->is_valid($job_options)) {
                $errors[] = sprintf(__('Make sure to properly configure "%2$s" destination in "%1$s" job.', 'buwd'), esc_attr(Buwd_Options::get($job_id, 'name')), $id);


                if (method_exists($dest_class, 'get_errors')) {
                    $dest_errors = $dest_class->get_errors();
                    if (!empty($dest_errors)) {
                        foreach ($dest_errors as $dest_error) {
                            $errors[] = $dest_error;
                        }
                    }
                }

                $valid_dest = false;
                set_site_transient('buwd_jobs_error', $errors);
            }

        }


        if ($valid_dest && get_site_option('buwd_job_running') != 1) {

            update_site_option('buwd_job_running', 1);
            Buwd_Job::run_job('run'); //right
            //Buwd_Job::setup('run', $job_id);
        }

    }

    private function sort_array(&$array_to_sort)
    {
        $result = array();
        foreach ($array_to_sort as &$ma) {
            if (is_array($ma[$this->orderby])) {
                $result[] = &$ma[$this->orderby][0];
            } else {
                $result[] = &$ma[$this->orderby];
            }

        }
        if ($this->order == 'asc') {
            array_multisort($result, SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE, $array_to_sort);
        } else {
            array_multisort($result, SORT_DESC, SORT_NATURAL | SORT_FLAG_CASE, $array_to_sort);
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

    public static function display_messages()
    {
        $current_action = Buwd_Helper::get('action');
        if (get_site_transient('buwd_jobs_error')) {
            $buwd_jobs_error = get_site_transient('buwd_jobs_error');
            if (!empty($buwd_jobs_error) && !$current_action) {
                foreach (get_site_transient('buwd_jobs_error') as $error) {
                    echo Buwd_Helper::message($error, 'error');
                }

                delete_site_transient('buwd_jobs_error');
            }
        } else if (get_site_transient('buwd_jobs_updated')) {
            $buwd_jobs_updated = get_site_transient('buwd_jobs_updated');
            if (!empty($buwd_jobs_updated) && !$current_action) {
                foreach (get_site_transient('buwd_jobs_updated') as $updated) {
                    echo Buwd_Helper::message($updated, 'success');
                }

                delete_site_transient('buwd_jobs_updated');
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