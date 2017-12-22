<?php

class Buwd_Logger
{
    protected static $instance = null;
    public $log_file_name;
    public $logfile;
    public $log_data = array();
    public $isdebug = false;
    public $stopped = false;
    public $error = false;
    public $error_count = 0;
    public $warning_count = 0;
    public $notice_count = 0;
    public $warning = false;
    protected $fp;
    public $log_summary = '';

    public function __construct()
    {
        $loglavel_labels = array(
            'normal' => __('Normal'),
            'debug'  => __('Debug')
        );

        $log_folder = Buwd_File::get_absolute_path(str_replace('{hash_key}', Buwd::get_plugin_data('hash'), Buwd::get_plugin_data('log_folder_dir')));
        $folder_protect = Buwd_Options::getSetting('folder_protect');
        if (!empty($folder_protect)) {
            Buwd_File::protect_folder($log_folder);
        }

        $this->log_file_name = 'backupwd_log_' . Buwd::get_plugin_data('hash') . '_' . Buwd_Helper::get_user_time(current_time('timestamp', true))->format('Y-m-d_H-i-s');

        $this->logfile = $log_folder . '/' . $this->log_file_name . '.html';

        $this->loglevel = in_array(Buwd_Options::getSetting('log_level'), array_keys($loglavel_labels)) ? Buwd_Options::getSetting('log_level') : 'normal';
        $this->isdebug = strpos($this->loglevel, 'debug') !== false ? true : false;

        //$this->open();
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

    public function open()
    {
        $this->fp = fopen($this->logfile, 'a+');
        if (!$this->fp) {
            $this->logfile = '';
            $this->set_log(__('Could not write log file', 'buwd'), E_USER_ERROR);
        }
    }

    public function set_log($message, $type = E_USER_NOTICE, $file = null, $line = null)
    {
        if (error_reporting() == 0) {
            return true;
        }

        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }

        $log_message = '[' . Buwd_Helper::get_user_time(current_time('timestamp', true))->format('d-M-Y H:i:s') . '] ';

        switch ($type) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $log_message .= '<span class="logline">';
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $this->warning = true;
                $this->warning_count++;
                $log_message .= '<span class="logline warning">';
                $log_message .= __('[WARNING]', 'buwd');
                break;
            case E_ERROR:
            case E_PARSE:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $this->error = true;
                $this->error_count++;
                $log_message .= '<span class="logline error">';
                $log_message .= __('[ERROR]', 'buwd');
                break;
            case E_STRICT:
                $this->notice_count++;
                $log_message .= __('[STRICT NOTICE]', 'buwd');
                break;
            case E_RECOVERABLE_ERROR:
                $this->error = true;
                $this->error_count++;
                $log_message .= '<span class="logline error">';
                $log_message .= __('[RECOVERABLE ERROR]', 'buwd');
                break;
            case 'SHUTDOWN_ERROR':
                $this->error = true;
                $this->error_count++;
                $log_message .= '<span class="logline error">';
                $log_message .= __('[SHUTDOWN ERROR]', 'buwd');
                break;
            case 'SUMMARY_ERROR':
                $log_message .= '<span class="logline summary_error">';
                break;
            case 'SUMMARY_WARNING':
                $log_message .= '<span class="logline summary_warning">';
                break;
            default:
                $log_message .= '<span class="logline">';
                $log_message .= $type;
                break;
        }

        $log_message .= $message;
        if (isset($file) && isset($line)) {
            $log_message .= /*. $type.': '*/
                ' in ' . $file . ' on line ' . $line;
            // $log_message = $message. ' |File:' .$file. ' |Line:' .$line;
        }
        $log_message .= '</span>';


        if (!$this->stopped) {
            $this->log_data[] = $log_message;
        }

        if ($this->logfile && !$this->stopped) {
            if (!file_put_contents($this->logfile, $log_message . '<br/>' . PHP_EOL, FILE_APPEND)) {
                $this->logfile = '';
                restore_error_handler();
                trigger_error(esc_html($log_message), $type);
            }
        }

    }

    public function put($log_data)
    {
        if (!file_put_contents($this->logfile, $log_data, FILE_APPEND)) {
            $this->logfile = '';
            $this->set_log(__('Could not write log file', 'buwd'), E_USER_ERROR);
        }
    }

    public function render_html($data)
    {
        $log_data = "<!DOCTYPE html>" . PHP_EOL;
        $log_data .= "<html lang=\"" . str_replace('_', '-', get_locale()) . "\">" . PHP_EOL;
        $log_data .= "<head>" . PHP_EOL;

        if (isset($data['title'])) {
            $log_data .= "<title>" . $data['title'] . "</title>" . PHP_EOL;
        }

        if (isset($data['charset'])) {
            $log_data .= '<meta charset="' . $data['charset'] . '" />' . PHP_EOL;
        }

        foreach ($data['metadata'] as $meta_key => $meta) {
            if (in_array($meta_key, $data['dynamic_metas'])) {
                $log_data .= str_pad('<meta name="' . $meta_key . '" content="' . $meta . '" />', 120) . PHP_EOL;
            } else {
                $log_data .= '<meta name="' . $meta_key . '" content="' . $meta . '" />' . PHP_EOL;
            }
        }

        $log_data .= "<meta http-equiv=\"cache-control\" content=\"no-cache\" />" . PHP_EOL;
        $log_data .= "<meta http-equiv=\"pragma\" content=\"no-cache\" />" . PHP_EOL;
        $log_data .= '</head>' . PHP_EOL;

        $log_data .= '<body class="log-body dark">';
        foreach ($data['info'] as $info) {
            $log_data .= '<span class="logline info">' . __('[INFO]', 'buwd') . ' ' . $info . '<br/>' . PHP_EOL . '</span>';
        }
        $log_data .= '</body>';

        return $log_data;

    }

    public function log_summary()
    {
        $str = '';
        $type = '';
        if ($this->notice_count != 0) {
            $str .= $this->notice_count . ' Notice(s) ';
        }

        if ($this->warning_count != 0) {
            $str .= $this->warning_count . ' Warning(s) ';
            $type = 'SUMMARY_WARNING';
        }

        if ($this->error_count != 0) {
            $str .= $this->error_count . ' Error(s) ';
            $type = 'SUMMARY_ERROR';
        }

        if ($str != '') {
            $this->set_log(__('Run Ended With ' . $str, 'buwd'), $type);
            $this->log_summary = $str;
        }
    }
}
