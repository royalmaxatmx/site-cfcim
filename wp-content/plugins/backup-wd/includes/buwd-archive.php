<?php

/**
 *
 */
class Buwd_Archive
{
    private $file = '';
    public $method = '';
    public $file_count = 0;
    public $ziparchive = null;
    private $filehandle = null;

    public function __construct($file, $archive_type)
    {
        $this->file = $file;

        if (!is_dir(dirname($this->file)) || !is_writable(dirname($this->file))) {
            throw new Buwd_Archive_Exception(sprintf(__('Folder %s for archive not found or not writable', 'buwd'), dirname($this->file)));
        }

        $method_params = array(
            'zip'       => array(
                'method' => 'ZipArchive',
                'class'  => 'ZipArchive',
            ),
            'gzip'      => array(
                'method'   => 'gz',
                'function' => 'gzencode',
            ),
            'bzip2'     => array(
                'method'   => 'bz2',
                'function' => 'bzcompress',
            ),
            'tar'       => array(
                'method' => 'Tar',
            ),
            'tar_gzip'  => array(
                'method'   => 'TarGz',
                'function' => 'gzencode',
            ),
            'tar_bzip2' => array(
                'method'   => 'TarBz2',
                'function' => 'bzcompress',
            ),
        );

        if (isset($method_params[$archive_type])) {
            if (isset($method_params[$archive_type]['class']) && !class_exists($method_params[$archive_type]['class'])) {
                throw new Buwd_Archive_Exception(sprintf(__('Class for %s compression not available', 'buwd'), $method_params[$archive_type]['method']));
            }
            if (isset($method_params[$archive_type]['function']) && !function_exists($method_params[$archive_type]['function'])) {
                throw new Buwd_Archive_Exception(sprintf(__('Functions for %s compression not available', 'buwd'), $method_params[$archive_type]['method']));
            }
            $this->method = $method_params[$archive_type]['method'];

            if ($this->method == 'ZipArchive') {
                $this->ziparchive = new ZipArchive();

                $res = $this->ziparchive->open($this->file, ZipArchive::CREATE);
                if ($res !== true) {
                    throw new Buwd_Archive_Exception(sprintf(__('Cannot create zip archive: %d', 'buwd'), $res));
                }
            } else if ($this->method == 'gz') {
                $this->filehandle = fopen('compress.zlib://' . $this->file, 'wb');
            } else {
                $this->filehandle = fopen($this->file, 'ab');
            }

            if (isset($this->filehandle) && !$this->filehandle) {
                throw new Buwd_Archive_Exception(__('Cannot open archive file', 'buwd'));
            }

        } else {
            throw new Buwd_Archive_Exception(sprintf(__('Method to archive file %s not detected', 'buwd'), dirname($this->file)));
        }
    }

    public function __destruct()
    {
        $method = $this->get_method();
        if ($method == 'ZipArchive') {
            if (is_object($this->ziparchive)) {
                if (!$this->ziparchive->close()) {
                    trigger_error(__('ZIP archive cannot be closed correctly.', 'buwd'), E_USER_ERROR);
                    sleep(1);
                }
                $this->ziparchive = null;
            }
        }
        // close file if open
        if (is_resource($this->filehandle)) {
            fclose($this->filehandle);
        }
    }

    public function archive_reload()
    {
        $method = $this->get_method();

        if ($method == 'ZipArchive') {
            if (is_object($this->ziparchive)) {
                $this->ziparchive->close();
                $this->ziparchive = new ZipArchive();
                $this->ziparchive->open($this->file, ZipArchive::CREATE);
            }
        }

    }

    public function get_method()
    {
        return $this->method;
    }

    public function get_file()
    {
        return $this->file;
    }

    public function get_file_count()
    {
        return $this->file_count;
    }

    public function add_file($file, $dir)
    {
        if (!is_readable($file)) {
            trigger_error(sprintf(__('File %s does not exist or is not readable', 'buwd'), $file), E_USER_WARNING);

            return true;
        }
        $method = $this->get_method();
        if ($method == 'ZipArchive') {
            if (!$this->ziparchive->addFile($file, $dir)) {
                trigger_error(sprintf(__('Cannot add "%s" to zip archive!', 'buwd'), $file), E_USER_ERROR);

                return false;
            } else {
                $this->file_count++;
            }
        } else if ($method == 'gz') {
            if (!($fd = fopen($file, 'rb'))) {
                trigger_error(sprintf(__('Cannot open source file %s to archive', 'buwd'), $file), E_USER_WARNING);

                return false;
            }
            while (!feof($fd)) {
                fwrite($this->filehandle, fread($fd, 8192));
            }
            fclose($fd);
            $this->file_count++;
        } else {
            //tar
            if (!$this->buwd_create_tar($file, $dir)) {
                //error
                return false;
            } else {
                $this->file_count++;
            }
        }

        return true;
    }

    public function add_empty_folder($dir)
    {
        if (!is_dir($dir)) {

            return true;
        }

        $method = $this->get_method();
        if ($method == 'ZipArchive') {
            if (!$this->ziparchive->addEmptyDir($dir)) {
                trigger_error(sprintf(__('Cannot add "%s" to zip archive!', 'buwd'), $dir), E_USER_WARNING);

                return false;
            }
        }

        return true;
    }


    public function buwd_create_tar($file_name, $name_in_archive)
    {
        $chunk_size = 1024 * 1024 * 4;
        $filename = $name_in_archive;
        $filename_prefix = "";

        //get file stat
        $file_stat = stat($file_name);
        if (!$file_stat) {
            return true;
        }
        $file_stat['size'] = abs((int)$file_stat['size']);
        //open file
        if ($file_stat['size'] > 0) {
            if (!($fd = fopen($file_name, 'rb'))) {
                trigger_error(sprintf(__('Cannot open source file %s for archiving', 'buwd'), $file_name), E_USER_WARNING);

                return true;
            }
        }
        //Set file user/group name if linux
        $fileowner = "Unknown";
        $filegroup = "Unknown";
        if (function_exists('posix_getpwuid')) {
            $info = posix_getpwuid($file_stat['uid']);
            $fileowner = $info['name'];
            $info = posix_getgrgid($file_stat['gid']);
            $filegroup = $info['name'];
        }
        // Generate the TAR header for this file
        $chunk = pack("a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155a12",
            $filename, //name of file  100
            sprintf("%07o", $file_stat['mode']), //file mode  8
            sprintf("%07o", $file_stat['uid']), //owner user ID  8
            sprintf("%07o", $file_stat['gid']), //owner group ID  8
            sprintf("%011o", $file_stat['size']), //length of file in bytes  12
            sprintf("%011o", $file_stat['mtime']), //modify time of file  12
            "        ", //checksum for header  8
            0, //type of file  0 or null = File, 5=Dir
            "", //name of linked file  100
            "ustar", //USTAR indicator  6
            "00", //USTAR version  2
            $fileowner, //owner user name 32
            $filegroup, //owner group name 32
            "", //device major number 8
            "", //device minor number 8
            $filename_prefix, //prefix for file name 155
            ""); //fill block 12

        // Computes the unsigned Checksum of a file's header
        $checksum = 0;
        for ($i = 0; $i < 512; $i++) {
            $checksum += ord(substr($chunk, $i, 1));
        }

        $checksum = pack("a8", sprintf("%07o", $checksum));
        $chunk = substr_replace($chunk, $checksum, 148, 8);

        if (isset($fd) && is_resource($fd)) {
            // read/write files in 512 bite Blocks
            while (($content = fread($fd, 512)) != '') {
                $chunk .= pack("a512", $content);
                if (strlen($chunk) >= $chunk_size) {
                    if ($this->method == 'TarGz') {
                        $chunk = gzencode($chunk);
                    }
                    if ($this->method == 'TarBz2') {
                        $chunk = bzcompress($chunk);
                    }
                    fwrite($this->filehandle, $chunk);
                    $chunk = '';
                }

            }
            fclose($fd);
        }

        if (!empty($chunk)) {
            if ($this->method == 'TarGz') {
                $chunk = gzencode($chunk);
            }
            if ($this->method == 'TarBz2') {
                $chunk = bzcompress($chunk);
            }
            fwrite($this->filehandle, $chunk);
        }

        return true;
    }

}

/**
 * Exception Handler
 */
class Buwd_Archive_Exception extends Exception
{
}