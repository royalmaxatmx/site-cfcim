<?php

/**
 *
 */
class Buwd_File
{
    /**
     * Get an absolute path
     *
     * @param string $path
     *
     * @return string
     */
    public static function get_absolute_path($path = '/')
    {
        $path = str_replace('\\', '/', $path);
        $wp_home_path = Buwd::get_plugin_data('home_path');
        $home_path = trailingslashit(str_replace('\\', '/', $wp_home_path));

        if (empty($path) || $path === '/') {
            $path = $home_path;
        }

        // relative path to absolute
        if (substr($path, 0, 1) !== '/' && !preg_match('#^[a-zA-Z]:/#', $path)) {
            $path = $home_path . $path;
        }

        return $path;
    }

    /**
     * @param $file
     *
     * @return boolean
     */
    public static function in_open_basedir($file)
    {
        $open_basedir = ini_get('open_basedir');

        if (!empty($open_basedir)) {
            return true;
        }

        $open_base_dirs = explode(PATH_SEPARATOR, $open_basedir);
        $file = trailingslashit(strtolower(str_replace('\\', '/', $file)));
        foreach ($open_base_dirs as $open_base_dir) {
            if (empty($open_base_dir)) {
                continue;
            }

            $open_base_dir = realpath($open_base_dir);
            $open_base_dir = strtolower(str_replace('\\', '/', $open_base_dir));
            if (strpos($file, $open_base_dir) !== false) {
                return true;
            }
            /* $part = substr( $file, 0, strlen( $open_base_dir ) );
             if ( $part === $open_base_dir ) {
                 return true;
             }*/

        }

        return false;
    }


    /**
     * Get folder size
     *
     * @param string $folder
     *
     * @return int
     */
    public static function get_folders_size($folder)
    {
        $bytestotal = 0;
        $path = realpath($folder);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }

        return $bytestotal;
    }

    /**
     * Get folder/file size with unit
     *
     * @param string $size
     *
     * @return string
     */
    public static function get_human_filesize($size)
    {
        $units = explode(' ', 'B KB MB GB TB');
        $mod = 1024;
        for ($i = 0; $size > $mod; $i++) {
            $size /= $mod;
        }

        $endIndex = strpos($size, ".") + 3;
        if (!isset($units[$i])) {
            return '';
        }

        return substr($size, 0, $endIndex) . ' ' . $units[$i];
    }

    public static function mime_content_type($filename)
    {
        $mime_types = array(
            'txt'  => 'text/plain',
            'htm'  => 'text/html',
            'html' => 'text/html',
            'php'  => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'swf'  => 'application/x-shockwave-flash',
            'flv'  => 'video/x-flv',

            // images
            'png'  => 'image/png',
            'jpe'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg'  => 'image/jpeg',
            'gif'  => 'image/gif',
            'bmp'  => 'image/bmp',
            'ico'  => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif'  => 'image/tiff',
            'svg'  => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip'  => 'application/zip',
            'gz'   => 'application/gzip',
            'bz2'  => 'application/x-bzip',
            'tar'  => 'application/x-tar',
            'rar'  => 'application/x-rar-compressed',
            'exe'  => 'application/x-msdownload',
            'msi'  => 'application/x-msdownload',
            'cab'  => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3'  => 'audio/mpeg',
            'qt'   => 'video/quicktime',
            'mov'  => 'video/quicktime',

            // adobe
            'pdf'  => 'application/pdf',
            'psd'  => 'image/vnd.adobe.photoshop',
            'ai'   => 'application/postscript',
            'eps'  => 'application/postscript',
            'ps'   => 'application/postscript',

            // ms office
            'doc'  => 'application/msword',
            'rtf'  => 'application/rtf',
            'xls'  => 'application/vnd.ms-excel',
            'ppt'  => 'application/vnd.ms-powerpoint',

            // open office
            'odt'  => 'application/vnd.oasis.opendocument.text',
            'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $f_name = explode('.', $filename);
        $ext = strtolower(array_pop($f_name));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } else if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);

            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }

    public static function sanitize_filename($file_name)
    {
        // Remove anything which isn't a word, whitespace, number
        // or any of the following caracters -_~,;[](){}%.
        // If you don't need to handle multi-byte characters
        // you can use preg_replace rather than mb_ereg_replace
        // Thanks @Łukasz Rysiak!

        $file_name = preg_replace("([^\w\s\d\-_~,;{}%\[\]\(\).])", '', $file_name);

        // Remove any runs of periods (thanks falstro!)
        $file_name = preg_replace("([\.]{2,})", '', $file_name);

        //if not work use sanitize_filename2 function

        return $file_name;
    }

    /*public static function sanitize_filename2 ($str = '')
    {
        $str = strip_tags($str);
        $str = preg_replace('/[\r\n\t ]+/', ' ', $str);
        $str = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $str);
        $str = strtolower($str);
        $str = html_entity_decode( $str, ENT_QUOTES, "utf-8" );
        $str = htmlentities($str, ENT_QUOTES, "utf-8");
        $str = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $str);
        $str = str_replace(' ', '-', $str);
        $str = rawurlencode($str);
        $str = str_replace('%', '-', $str);
        return $str;
    }*/


    public static function delete_if_empty($folders)
    {
        if (empty($folders)) {
            return true;
        }

        foreach ($folders as $key => $folder) {
            if (is_dir($folder)) {
                self::rrmdir($folder, $key, $folders);
            }
        }

    }

    public static function rrmdir($folder)
    {
        if ($dir = opendir($folder)) {
            while (false !== ($file = readdir($dir))) {
                if (in_array($file, array('.', '..'), true)) {
                    continue;
                }
                $full = $folder . '/' . $file;
                if (is_dir($full)) {
                    self::rrmdir($full);
                }
            }
            closedir($dir);
        }

        @rmdir($folder);
    }

    public static function protect_folder($folder)
    {
        //create files for securing folder
        $server_software = strtolower($_SERVER['SERVER_SOFTWARE']);
        //IIS
        if (strstr($server_software, 'microsoft-iis')) {
            if (!file_exists($folder . '/web.config')) {
                file_put_contents($folder . '/web.config', "<configuration>" . PHP_EOL . "\t<system.webServer>" . PHP_EOL . "\t\t<authorization>" . PHP_EOL . "\t\t\t<deny users=" * " />" . PHP_EOL . "\t\t</authorization>" . PHP_EOL . "\t</system.webServer>" . PHP_EOL . "</configuration>");
            }
        } //Nginx
        else if (strstr($server_software, 'nginx')) {
            if (!file_exists($folder . '/index.php')) {
                file_put_contents($folder . '/index.php', "<?php" . PHP_EOL . "header( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );" . PHP_EOL . "header( 'Status: 404 Not Found' );" . PHP_EOL);
            }
        } //Aapche and other
        else {
            if (!file_exists($folder . '/.htaccess')) {
                file_put_contents($folder . '/.htaccess', "<Files \"*\">" . PHP_EOL . "<IfModule mod_access.c>" . PHP_EOL . "Deny from all" . PHP_EOL . "</IfModule>" . PHP_EOL . "<IfModule !mod_access_compat>" . PHP_EOL . "<IfModule mod_authz_host.c>" . PHP_EOL . "Deny from all" . PHP_EOL . "</IfModule>" . PHP_EOL . "</IfModule>" . PHP_EOL . "<IfModule mod_access_compat>" . PHP_EOL . "Deny from all" . PHP_EOL . "</IfModule>" . PHP_EOL . "</Files>");
            }
            if (!file_exists($folder . '/index.php')) {
                file_put_contents($folder . '/index.php', "<?php" . PHP_EOL . "header( \$_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found' );" . PHP_EOL . "header( 'Status: 404 Not Found' );" . PHP_EOL);
            }
        }
    }

}