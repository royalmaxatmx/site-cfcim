<?php

/**
 * Class Buwd_Encrypt
 * If exist uses openssl_encrypt/decrypt
 * If not, uses XOR algorithm
 */
class Buwd_Encrypt
{


    /**
     * @param $str
     *
     * @return mixed
     *
     * Encrypting string
     */


    public static function encrypt($str)
    {
        if (strpos($str, '$buwd_encrypt$') !== false) {
            return $str;
        }

        if (!function_exists('openssl_decrypt')) {
            return self::encrypt_xor($str);
        }

        if ($str == '') {
            return $str;
        }

        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);

        $encrypted_str = openssl_encrypt($str,
            "AES-128-ECB",
            $key);

        return $encrypted_str . '$buwd_encrypt$';
    }


    /**
     * @param $str
     *
     * @return string
     *
     * Encrypt string using xor algorithm
     *
     * Url https://en.wikipedia.org/wiki/XOR_cipher
     */

    public static function encrypt_xor($str)
    {
        if (strpos($str, '$buwd_encrypt$') !== false) {
            return $str;
        }

        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);
        $encrypted_str = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            $char_key = substr($key, ($i % strlen($key)) - 1, 1);
            $char = $char ^ $char_key;
            $encrypted_str .= $char;
        }

        return base64_encode($encrypted_str) . '$buwd_encrypt$';
    }

    /**
     * @param $str
     *
     * @return mixed|string
     *
     * Decrypt string using xor algorithm
     *
     * Url https://en.wikipedia.org/wiki/XOR_cipher
     */

    public static function decrypt_xor($str)
    {
        if (strpos($str, '$buwd_encrypt$') === false) {
            return $str;
        }

        $str = str_replace('$buwd_encrypt$', '', $str);
        $str = base64_decode($str);
        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);
        $decrypted_str = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $char = $str[$i];
            $char_key = substr($key, ($i % strlen($key)) - 1, 1);
            $char = $char ^ $char_key;
            $decrypted_str .= $char;
        }

        return $decrypted_str;
    }


    /**
     * @param $str
     *
     * @return mixed
     *
     * Decrypting string
     */
    public static function decrypt($str)
    {
        if (strpos($str, '$buwd_encrypt$') === false) {
            return $str;
        }

        if (!function_exists('openssl_decrypt')) {
            return self::decrypt_xor($str);
        }

        $str = str_replace('$buwd_encrypt$', '', $str);
        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);

        $decrypted_str = openssl_decrypt($str,
            "AES-128-ECB",
            $key);

        if (!$decrypted_str) {
            return $str;
        }

        return $decrypted_str;
    }

    /**
     * @param $source
     *
     * @return bool
     *
     * Using Openssl_encrypt PHP 5.3>=
     */

    public static function encrypt_file($source)
    {
        $dest = Buwd::get_plugin_data('temp_folder_dir') . 'tmp.txt';

        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);
        //initialization vector
        $iv = openssl_random_pseudo_bytes(16);

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            fwrite($fpOut, $iv);
            if ($fpIn = fopen($source, 'rb')) {
                while (!feof($fpIn)) {
                    $plaintext = fread($fpIn, 16 * 1000);
                    $ciphertext = openssl_encrypt($plaintext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $ciphertext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }

        unlink($source);
        rename($dest, $source);

        return $error ? false : true;
    }


    /**
     * @param $source
     *
     * @return bool
     *
     * Using openssl_decrypt PHP 5.3>=
     */
    public static function decrypt_file($source)
    {
        $dest = Buwd::get_plugin_data('temp_folder_dir') . 'tmp.txt';

        $key = md5(DB_HOST . DB_NAME . DB_PASSWORD);

        $error = false;
        if ($fpOut = fopen($dest, 'w')) {
            if ($fpIn = fopen($source, 'rb')) {
                //initialization vector
                $iv = fread($fpIn, 16);
                while (!feof($fpIn)) {
                    $ciphertext = fread($fpIn, 16 * (1000 + 1));
                    $plaintext = openssl_decrypt($ciphertext, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
                    $iv = substr($ciphertext, 0, 16);
                    fwrite($fpOut, $plaintext);
                }
                fclose($fpIn);
            } else {
                $error = true;
            }
            fclose($fpOut);
        } else {
            $error = true;
        }

        if (filesize($dest) != 0) {
            unlink($source);
            rename($dest, $source);
        } else {
            unlink($dest);
        }

        return $error ? false : true;
    }
}