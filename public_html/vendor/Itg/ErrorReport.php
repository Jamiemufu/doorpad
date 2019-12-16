<?php


namespace Itg;


/**
 * ErrorReport class
 * @package Itg
 */
class ErrorReport
{


    protected static $_endpoint = 'https://api.itgproduction.com/api/issues/register';
    protected static $_salt     = 'AOuCUMqcaDie4bFBCLuamOCjdJAiNqoAVIVuy3RPr3i1HDkc8RXoRahCFOl4';
    protected static $_token    = null;


    /**
     * Initialise the error logger
     * @param string $token Project token
     */
    public static function init($token = '')
    {

        if ((string)$token !== '')
        {

            self::$_token = $token;

            set_error_handler('\Itg\ErrorReport::log');
            register_shutdown_function('\Itg\ErrorReport::shutdown');

        }

    }


    /**
     * Convert error number to equivalent PHP constant name
     * @param  int    $number Error number
     * @return string         PHP error constant name
     */
    protected static function _errorNumberConvert($number = 0)
    {

        $error_code = 'E_UNKNOWN';
        
        switch ($number)
        {
            case E_ERROR:             $error_code = 'E_ERROR'; break;
            case E_WARNING:           $error_code = 'E_WARNING'; break;
            case E_PARSE:             $error_code = 'E_PARSE'; break;
            case E_NOTICE:            $error_code = 'E_NOTICE'; break;
            case E_CORE_ERROR:        $error_code = 'E_CORE_ERROR'; break;
            case E_CORE_WARNING:      $error_code = 'E_CORE_WARNING'; break;
            case E_COMPILE_ERROR:     $error_code = 'E_COMPILE_ERROR'; break;
            case E_COMPILE_WARNING:   $error_code = 'E_COMPILE_WARNING'; break;
            case E_USER_ERROR:        $error_code = 'E_USER_ERROR'; break;
            case E_USER_WARNING:      $error_code = 'E_USER_WARNING'; break;
            case E_USER_NOTICE:       $error_code = 'E_USER_NOTICE'; break;
            case E_STRICT:            $error_code = 'E_STRICT'; break;
            case E_RECOVERABLE_ERROR: $error_code = 'E_RECOVERABLE_ERROR'; break;
            case E_DEPRECATED:        $error_code = 'E_DEPRECATED'; break;
            case E_USER_DEPRECATED:   $error_code = 'E_USER_DEPRECATED'; break;
            case E_ALL:               $error_code = 'E_ALL'; break;
        }

        return $error_code;

    }


    /**
     * Catch error messages
     * @param  int     $number  Error code/number
     * @param  string  $string  Error message
     * @param  string  $file    Name of file error occurred in
     * @param  int     $line    Line error occurred on
     * @param  array   $context Active variable array
     */
    public static function log($number = 0, $string = '', $file = '', $line = 0, array $context = [])
    {

        /*
         * Ignore suppressed errors
         */
        if (error_reporting())
        {

            $affected_line = '';

            if (is_readable($file))
            {
                $affected_line = file($file);
                $affected_line = $affected_line[(int)$line - 1];
            }
            
            $error =
                [
                    'code'          => self::_errorNumberConvert((int)$number),
                    'string'        => (string)$string,
                    'file'          => (string)$file,
                    'line'          => (int)$line,
                    'affected_line' => (string)$affected_line,
                    'environment'   => $_ENV['APP_ENVIRONMENT']
                ];

            self::_send($error);

        }

    }


    /**
     * Inspect for fatal errors on shutdown
     */
    public static function shutdown()
    {

        $last_error = error_get_last();
        $error_type = $last_error['type'];

        /*
         * These error types won't be caught by the error handler
         */
        if ($error_type == E_ERROR OR
            $error_type == E_PARSE OR
            $error_type == E_CORE_ERROR OR
            $error_type == E_CORE_WARNING OR
            $error_type == E_COMPILE_ERROR OR
            $error_type == E_COMPILE_WARNING)
        {
            self::log($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
        }

    }


    /**
     * Generate random data and return as a Base64-encoded string, falling back
     * to a less-random source (chr() output using mt_rand(1, 256)) if no random
     * device can be found
     * @param  int    $size        Number of random bytes to sample
     * @param  bool   $true_random Whether or not to use a true random source
     * @return string              Base64-encoded version of random binary data
     */
    public static function random($size = 64, $true_random = false)
    {

        $result = '';
        $file   = $true_random ? '/dev/random' : '/dev/urandom';

        if (is_readable($file))
        {
            $stream = fopen($file, 'r');
            $result = fread($stream, (int)$size);
            fclose($stream);
        }

        else
        {
            for ($i = 0; $i < (int)$size; $i++)
            {
                $result .= chr(mt_rand(1, 256));
            }
        }

        return base64_encode($result);

    }


    /**
     * Encrypt a string with the Rijndael 256 cipher
     * @param  string $string String to encrypt
     * @param  string $key    Encryption key
     * @return string         Encrypted string, Base64-encoded
     */
    public static function encrypt($string = '', $key = null)
    {

        if (!extension_loaded('mcrypt') OR is_null($key))
        {
            return false;
        }

        /*
         * Initialise the encryption descriptor and determine the required IV
         * size
         */
        $td      = mcrypt_module_open('rijndael-256', '', 'cbc', '');
        $iv_size = mcrypt_enc_get_iv_size($td);

        /*
         * Generate and trim the key
         */
        $key = hash('sha512', $key);
        $key = substr($key, 0, $iv_size);

        /*
         * Generate a random initialisation vector
         */
        $iv = self::random($iv_size);
        $iv = substr($iv, 0, $iv_size);

        /*
         * Encrypt the string
         */
        mcrypt_generic_init($td, $key, $iv);
        $result = base64_encode(mcrypt_generic($td, $string));

        /*
         * Tidy up
         */
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        /*
         * Return a package containing the encrypted string and the
         * initialisation vector
         */
        return base64_encode(json_encode(['result' => $result, 'iv' => $iv]));

    }


    /**
     * Send the error to the logger
     * @param  array  $error Array of error information
     * @return string        Error logger response
     */
    protected static function _send(array $error = [])
    {

        if (!extension_loaded('curl'))
        {
            return false;
        }

        if (!empty($error) AND self::$_token)
        {

            $endpoint       = self::$_endpoint;
            $token          = self::$_token;
            $encryption_key = self::$_salt . $token;
            $error          = json_encode($error);
            $error          = self::encrypt($error, $encryption_key);
            $params         = ['token' => $token, 'data' => $error];

            if ($error === false)
            {
                return false;
            }

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL,            $endpoint);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_AUTOREFERER,    true);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT,  true);
            curl_setopt($ch, CURLOPT_TIMEOUT,        1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_POST,           true);
            curl_setopt($ch, CURLOPT_POSTFIELDS,     $params);

            $result = curl_exec($ch);

            curl_close($ch);

            return $result;

        }

        return false;

    }


}