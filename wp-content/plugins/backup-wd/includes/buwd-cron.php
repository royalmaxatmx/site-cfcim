<?php

use Cron\CronExpression;


class Buwd_Cron
{
    /**
     * Calculate next run time for CRON Expression
     *
     * @param        $cron_expression Cron expression to parse
     * @param string $format          Datetime string format
     *
     * @return string Next run date in given format
     */
    public static function next_run($cron_expression, $format = 'M d, Y \a\t h:i:sa')
    {
        $cron = CronExpression::factory($cron_expression);

        $offset = get_site_option('gmt_offset');
        $offset = "GMT" . ($offset < 0 ? $offset : "+" . $offset);

        $next_run = $cron->getNextRunDate('now', 0, false, $offset);

        return $next_run->format($format);
        /*
        $current_time = current_time('timestamp');
        if (!$timestamp['sch_month'] || !isset($timestamp['sch_month'])) {
            $timestamp['sch_month'] = self::create_num_array(12);
        }

        if (!$timestamp['sch_wday'] || !isset($timestamp['sch_wday'])) {
            $timestamp['sch_wday'] = self::create_num_array(7);
        }

        if (!$timestamp['sch_day'] || !isset($timestamp['sch_day'])) {
            $timestamp['sch_day'] = self::create_num_array(31);
        }


        if ($timestamp['sch_month']) {
            if ($timestamp['sch_month'][0] == 'any') {
                unset($timestamp['sch_month'][0]);
            }

            if ($timestamp['sch_wday'][0] == 'any') {
                unset($timestamp['sch_wday'][0]);
            }

            if ($timestamp['sch_day'][0] == 'any') {
                unset($timestamp['sch_day'][0]);
            }

            foreach ($timestamp['sch_month'] as $month) {
                foreach ($timestamp['sch_day'] as $day) {
                    $time_by_settings = mktime($timestamp['sch_hour'], $timestamp['sch_minute'], 0, $month, $day, '2017');
                    $month_niddle = date('n', $time_by_settings);
                    $day_niddle = date('j', $time_by_settings);
                    $week_niddle = date('w', $time_by_settings);
                    $hour_niddle = date('H', $time_by_settings);
                    $minute_niddle = date('i', $time_by_settings);

                    if (
                        in_array($month_niddle, $timestamp['sch_month'])
                        && in_array($day_niddle, $timestamp['sch_day'])
                        && in_array($week_niddle, $timestamp['sch_wday'])
                        && $hour_niddle == $timestamp['sch_hour']
                        && $minute_niddle == $timestamp['sch_minute']
                        && $time_by_settings > $current_time
                    ) {
                        //					echo $month . ' ' . $day . ' ' . $timestamp['sch_hour'] . ' ' . $timestamp['sch_minute'] . '<br>';
                        //					echo $time_by_settings . ' <br>';
                        return $time_by_settings;
                    }
                }
            }
        }

        return false;
    */

    }


    public static function create_num_array($end, $start = 1)
    {
        $array = array();
        if ($start == 0) {
            for ($i = 0; $i < $end; $i++) {
                $array[] = $i;
            }
        } else {
            for ($i = 1; $i <= $end; $i++) {
                $array[] = $i;
            }
        }

        return $array;

    }

    public static function generate_expression($data)
    {
        $temp_array = array();
        $expression_array = array();

        if (empty($data) || $data == '') {
            return '*';
        }

        if (in_array('any', $data))
            return '*';

        /*foreach ( $data as $key => $value ) {
            if ( isset( $data[ $key + 1 ] ) && $data[ $key + 1 ] == $value + 1 ) {
                $temp_array[] = $value;
            } else {
                $temp_array[] = $value;
                if ( $temp_array[0] == end( $temp_array ) ) {
                    $expression_array[] = ltrim($temp_array[0],'0');
                } else {
                    $expression_array[] =  ltrim($temp_array[0],'0') . '-' . ltrim(end( $temp_array ),'0');
                }

                $temp_array = array();
            }
        }*/

        return implode(',', $data);
    }

    public static function add_easycron($expression, $job_id)
    {
        $token = Buwd_Options::getSetting('easy_cron_key');
        //$url = wp_nonce_url(site_url('wp-cron.php?buwd_cron=1&jobid=' . $job_id), 'buwd-' . $job_id);
        $url = Buwd_Job::get_job_run_url("run", $job_id);

        $easy_cron_url = 'https://www.easycron.com/rest/add?token=' . $token . '&cron_expression=' . $expression . '&url=' . urlencode($url) . '&email_me=0&log_output_length=10240&via_tor=0';


        //home_url('wp-cron.php?') . 'buwd_cron=1&jobid=' . $this->info['job_id'] . '&_wpnonce=' . md5(Buwd_Options::getSetting('job_start_key'))
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $easy_cron_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $output = curl_exec($ch);

        if ($output === false) {
            echo "Error Number:" . curl_errno($ch) . "<br>";
            echo "Error String:" . curl_error($ch);

            return;
        }
        $output = json_decode($output);
        if ($output->status == 'error') {
            /*	echo "Error Code: " . $output->error->code . "<br>";
                echo "Error: " . $output->error->message;*/

            return;
        }


        curl_close($ch);

        return $output->cron_job_id;
    }

    public static function edit_easycron($job_id)
    {
        $url = Buwd_Job::get_job_run_url("run", $job_id);

        $token = Buwd_Options::getSetting('easy_cron_key');

        $minute = Buwd_Options::get($job_id, 'scheduleminute');
        $hour = Buwd_Options::get($job_id, 'schedulehour');
        $day = Buwd_Options::get($job_id, 'scheduleday', array('*'));
        $wday = Buwd_Options::get($job_id, 'scheduleweek', array('*'));
        $month = Buwd_Options::get($job_id, 'schedulemonth', array('*'));
        $scheduletype = Buwd_Options::get($job_id, 'scheduletype');
        if ($scheduletype == 'weekly') {
            $month = Buwd_Cron::create_num_array(12);
        }
        if ($scheduletype == 'dayly') {
            $month = Buwd_Cron::create_num_array(12);
            $wday = Buwd_Cron::create_num_array(6, 0);
        }

        if ($scheduletype == 'hourly') {
            $month = Buwd_Cron::create_num_array(12);
            $wday = Buwd_Cron::create_num_array(6, 0);
            $day = Buwd_Cron::create_num_array(31);
        }

        $expression = $minute . ' ' . $hour . ' ' . self::generate_expression($day) . ' ' . self::generate_expression($month) . ' ' . self::generate_expression($wday);
        $cron_job_id = get_site_option('buwd_easycron_' . $job_id);

        $easy_cron_url = 'https://www.easycron.com/rest/edit?token=' . $token . '&id=' . $cron_job_id . '&cron_expression=' . $expression . '&url=' . urlencode($url) . '&email_me=0&log_output_length=10240&via_tor=0';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $easy_cron_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $output = curl_exec($ch);
        $output = json_decode($output);
        if ($output === false) {
            echo "Error Number:" . curl_errno($ch) . "<br>";
            echo "Error String:" . curl_error($ch);
        }
        curl_close($ch);
    }

    public static function delete_easycron($job_id)
    {
        $token = Buwd_Options::getSetting('easy_cron_key');
        $cron_job_id = get_site_option('buwd_easycron_' . $job_id);

        $easy_cron_url = 'https://www.easycron.com/rest/delete?token=' . $token . '&id=' . $cron_job_id;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $easy_cron_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $output = curl_exec($ch);
        if ($output === false) {
            echo "Error Number:" . curl_errno($ch) . "<br>";
            echo "Error String:" . curl_error($ch);
        }
        curl_close($ch);

        delete_site_option('buwd_easycron_' . $job_id);
    }

    public static function run_cron()
    {

       /* $job_id = isset($_GET['jobid']) ? (int)$_GET['jobid'] : 0;
        $hash = Buwd_Options::getSetting('job_start_key');
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : null;
        if (!isset($_GET['buwd_cron']) || $nonce != md5($hash)) {
            return;
        }

        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'run';
        Buwd_Job::setup($type, $job_id);*/
    }

    public static function run($id)
    {
        $job_id = (int)$id;
        if (get_site_option('buwd_job_running') != 1) {
            update_site_option('buwd_job_running', '1');
            Buwd_Job::setup('run', $job_id);
        }
        die();
    }
}

?>