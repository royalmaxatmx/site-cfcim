<?php

class Buwd_Helper
{

    public static function get($key, $method = 'post', $default_value = null)
    {
        if ($method == 'get') {
            if (isset($_GET[$key])) {
                return $_GET[$key];
            }
        }

        if (isset($_POST[$key])) {
            return $_POST[$key];
        } else if (isset($_GET[$key])) {
            return $_GET[$key];
        } else if (isset($default_value)) {
            return $default_value;
        }

        return false;
    }

    public static function message($message, $type)
    {
        return '<div class="buwd-' . $type . '">
				<p>' . $message . '</p>
			</div>';
    }

    public static function redirect($url)
    {
        //network_admin_url check if multisite
        if (is_array($url)) {
            $url = add_query_arg($url, network_admin_url('admin.php'));
        }
        ?>
        <script>
            window.location = "<?php echo $url; ?>";
        </script>
        <?php
        exit();
    }

    public static function date_formats()
    {
        $date_formats = array(
            '%d' => 'Day of the month, 2 digits with leading zeros (01 to 31)',
            '%j' => 'Day of the month without leading zeros (1 to 31)',
            '%m' => 'Numeric representation of a month, with leading zeros (01 to 12)',
            '%n' => 'Numeric representation of a month, without leading zeros	(01 to 12)',
            '%y' => 'Two digit representation of a year	(98 or 16)',
            '%Y' => 'Four digit representation of the year (1998 or 2016)',
            '%a' => 'Lowercase Ante meridiem and Post meridiem (am / pm)',
            '%A' => 'Uppercase ante meridiem and post meridiem (AM / PM)',
            '%g' => '12-hour format of an hour without leading zeros (1 to 12)',
            '%G' => '24-hour format of an hour without leading zeros (0 to 23)',
            '%h' => '12-hour format of an hour with leading zeros (01 to 12)',
            '%H' => '24-hour format of an hour with leading zeros (00 to 23)',
            '%i' => 'Minutes with leading zeros (00 to 59)',
            '%s' => 'Seconds, with leading zeros (00 to 59)',
        );

        return $date_formats;
    }

    public static function month_options()
    {
        $month_options = array();
        $month_options['any'] = 'Any';
        for ($m = 1; $m <= 12; $m++) {
            $month_options[$m] = date('F', mktime(0, 0, 0, $m));
        }

        return $month_options;
    }

    public static function week_options()
    {
        $week_options = array(
            'any' => 'Any',
            '0'   => 'Sunday',
            '1'   => 'Monday',
            '2'   => 'Tuesday',
            '3'   => 'Wednesday',
            '4'   => 'Thursday',
            '5'   => 'Friday',
            '6'   => 'Saturday',
        );

        return $week_options;
    }

    public static function day_options()
    {
        $day_options = array();
        $day_options['any'] = 'Any';
        for ($d = 1; $d <= 31; $d++) {
            $day_options[$d] = $d < 10 ? '0' . $d : $d;
        }

        return $day_options;
    }

    public static function hour_options()
    {
        $hour_options = array();
        for ($h = 0; $h < 24; $h++) {
            $hour_options[$h] = $h < 10 ? '0' . $h : $h;
        }

        return $hour_options;
    }

    public static function minute_options()
    {
        $minute_options = array();
        for ($m = 0; $m < 60; $m++) {
            $minute_options[$m] = $m < 10 ? '0' . $m : $m;
        }

        return $minute_options;
    }

    public static function ucwords_specific($string, $delimiters = '', $encoding = null)
    {
        if ($encoding === null) {
            $encoding = mb_internal_encoding();
        }

        if (is_string($delimiters)) {
            $delimiters = str_split(str_replace(' ', '', $delimiters));
        }

        $delimiters_pattern1 = array();
        $delimiters_replace1 = array();
        $delimiters_pattern2 = array();
        $delimiters_replace2 = array();
        foreach ($delimiters as $delimiter) {
            $uniqid = uniqid();
            $delimiters_pattern1[] = '/' . preg_quote($delimiter) . '/';
            $delimiters_replace1[] = $delimiter . $uniqid . ' ';
            $delimiters_pattern2[] = '/' . preg_quote($delimiter . $uniqid . ' ') . '/';
            $delimiters_replace2[] = $delimiter;
        }

        // $return_string = mb_strtolower($string, $encoding);
        $return_string = $string;
        $return_string = preg_replace($delimiters_pattern1, $delimiters_replace1, $return_string);

        $words = explode(' ', $return_string);

        foreach ($words as $index => $word) {
            $words[$index] = mb_strtoupper(mb_substr($word, 0, 1, $encoding), $encoding) . mb_substr($word, 1, mb_strlen($word, $encoding), $encoding);
        }

        $return_string = implode(' ', $words);

        $return_string = preg_replace($delimiters_pattern2, $delimiters_replace2, $return_string);

        return $return_string;
    }

    public static function search_in_array($arr, $key, $value)
    {
        return array_filter($arr, function ($el) use ($key, $value) {
            if (isset($el[$key])) {
                if (is_array($value)) {
                    if (in_array($el[$key], $value)) {
                        return true;
                    }
                } else {
                    if ($el[$key] == $value) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    public static function search_in_array_diff($arr, $key, $value)
    {
        return array_filter($arr, function ($el) use ($key, $value) {
            if (isset($el[$key])) {
                if (is_array($value)) {
                    if (!in_array($el[$key], $value)) {
                        return true;
                    }
                } else {
                    if ($el[$key] != $value) {
                        return true;
                    }
                }
            }

            return false;
        });
    }

    /**
     * @param $time integer|DateTime|string Timestamp of string time
     *
     * @return DateTime
     */
    public static function get_user_time($time)
    {
        if (!($time instanceof DateTime)) {
            if (self::is_timestamp($time) == false) {
                $time = strtotime($time);
            }
            $time = new DateTime('@' . $time);
        }

        $user_timezone = get_site_option('gmt_offset') * 3600;
        $myInterval = DateInterval::createFromDateString((string)$user_timezone . 'seconds');
        $time->add($myInterval);

        return $time;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function is_timestamp($string)
    {
        try {
            new DateTime('@' . $string);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

}