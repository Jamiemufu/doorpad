<?php


namespace Whiskey\Bourbon\Helper;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\MissingDependencyException;


/**
 * Utils class
 * @package Whiskey\Bourbon\Helper
 */
class Utils
{


    /**
     * Convert a CSV file to a multidimensional array with column headers as
     * array keys
     * @param  string $csv CSV string
     * @return array       Multidimensional array
     */
    public function csvToArray($csv = '')
    {

        $result  = [];
        $pattern = '/[\r\n]{1,2}(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/';
        $csv     = preg_split($pattern, $csv, null, PREG_SPLIT_NO_EMPTY);
        $header  = str_getcsv(array_shift($csv));

        foreach ($csv as $csv_line)
        {
            $line     = str_getcsv($csv_line);
            $result[] = @array_combine($header, $line);
        }

        return $result;

    }


    /**
     * Convert an array to a CSV line
     * @param  array  $array     Single-dimension array
     * @param  string $delimiter Column delimiter
     * @param  string $quotes    Quote character
     * @return string            CSV string
     */
    protected function _strPutCsv(array $array = [], $delimiter = ',', $quotes = '"')
    {

        $temp_file = fopen('php://temp', 'r+');

        fputcsv($temp_file, $array, $delimiter, $quotes);
        rewind($temp_file);

        $csv_line = fread($temp_file, 1048576);

        fclose($temp_file);

        return rtrim($csv_line, "\n");

    }


    /**
     * Convert a multidimensional array to a CSV, taking the first row's keys as
     * the column names
     * @param  array  $array Input array
     * @return string        CSV string
     * @throws InvalidArgumentException if input is not a valid array
     */
    public function arrayToCsv(array $array = [])
    {

        if (!is_array($array))
        {
            throw new InvalidArgumentException('Input is not an array');
        }

        if (empty($array))
        {
            return '';
        }

        $header = reset($array);

        if (!is_array($header))
        {
            throw new InvalidArgumentException('Input array does not appear to be valid');
        }

        $header = array_keys($header);
        $result = $this->_strPutCsv($header);

        foreach ($array as $row)
        {

            $result .= "\n" . $this->_strPutCsv($row);

        }

        return $result;

    }


    /**
     * Convert a multidimensional array to a Bootstrap-compatible table, taking
     * the first row's keys as the column names
     * @param  array  $array Input array
     * @return string        Bootstrap-compatible HTML table string
     * @throws InvalidArgumentException if input is not a valid array
     */
    public function arrayToTable(array $array = [])
    {

        if (!is_array($array))
        {
            throw new InvalidArgumentException('Input is not an array');
        }

        if (empty($array))
        {
            return '';
        }

        $header = reset($array);

        if (!is_array($header))
        {
            throw new InvalidArgumentException('Input array does not appear to be valid');
        }

        $header = array_keys($header);
        $result = '<table class="table"><thead><tr>';

        foreach ($header as $column_name)
        {
            $result .= '<td>' . $column_name . '</td>';
        }

        $result .= '</tr></thead><tbody>';

        foreach ($array as $row)
        {

            $result .= '<tr>';

            foreach ($row as $column_value)
            {
                $result .= '<td>' . $column_value . '</td>';
            }

            $result .= '</tr>';

        }

        $result .= '</tbody></table>';

        return $result;

    }


    /**
     * Generate random data and return as a Base64-encoded string, falling back
     * to a less-random source (openssl_random_pseudo_bytes()) if no random
     * device can be found
     * @param  int    $size        Number of random bytes to sample
     * @param  bool   $true_random Whether or not to use a true random source
     * @return string              Base64-encoded version of random binary data
     */
    public function random($size = 64, $true_random = false)
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
            $result = openssl_random_pseudo_bytes($size);
        }

        return base64_encode($result);

    }


    /**
     * Convert a filesize in bytes to a human-readable format
     * @param  int    $filesize Filesize
     * @return string           Human-readable filesize
     */
    public function friendlyFileSize($filesize = 0)
    {

        $result = '1 B';

        $filesize_array =
            [
                '1'                         => 'B',
                '1024'                      => 'KiB',
                '1048576'                   => 'MiB',
                '1073741824'                => 'GiB',
                '1099511627776'             => 'TiB',
                '1125899906842624'          => 'PiB',
                '1152921504606846976'       => 'EiB',
                '1180591620717411303424'    => 'ZiB',
                '1208925819614629174706176' => 'YiB'
            ];

        $temp_filesize = 1;

        while ($filesize >= $temp_filesize)
        {

            $decimal_places = 0;

            if ($temp_filesize != 1)
            {
                $decimal_places = 2;
            }

            $result        = number_format(($filesize / $temp_filesize), $decimal_places) . ' ' . $filesize_array[$temp_filesize];
            $temp_filesize = ($temp_filesize * 1024);

        }

        if (!$filesize)
        {
            return '0 B';
        }

        return $result;

    }


    /**
     * Resize an image
     * @param  string $image_data       Image string
     * @param  string $dimension        Which side should be stretched ('w' or 'h')
     * @param  string $target_format    Format to save as (accepts: 'jpg', 'png', 'gif')
     * @param  int    $target_dimension Width or height to resize to
     * @param  int    $quality          Quality (0 - 100) for JPEG output
     * @return string                   String representation of resized image file
     * @throws MissingDependencyException if GD is missing
     */
    public function imageResize($image_data = null, $target_dimension = 640, $target_format = 'jpg', $dimension = 'w', $quality = 75)
    {

        if (!extension_loaded('gd'))
        {
            throw new MissingDependencyException('GD extension missing');
        }

        $image = imagecreatefromstring($image_data);

        $original_width  = imagesx($image);
        $original_height = imagesy($image);

        /*
         * Figure out what the new width and height should be
         */
        if (strtolower($dimension) == 'w')
        {
            $new_height = round(($original_height / $original_width) * $target_dimension);
            $new_width  = $target_dimension;
        }

        else
        {
            $new_width  = round(($original_width / $original_height) * $target_dimension);
            $new_height = $target_dimension;
        }


        $new = imagecreatetruecolor($new_width, $new_height);

        /*
         * Preserve the alpha channel
         */
        imagealphablending($new, false);
        imagesavealpha($new, true);
        $transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);

        /*
         * Copy and resize
         */
        imagefilledrectangle($new, 0, 0, $new_width, $new_height, $transparent);

        imagecopyresampled($new, $image,
                           0, 0, 0, 0,
                           $new_width, $new_height,
                           $original_width, $original_height);

        /*
         * Output the new file
         */
        ob_start();

        switch (strtolower($target_format))
        {

            case 'png':
                imagepng($new, null, 9);
                break;

            case 'gif':
                imagegif($new, null);
                break;

            default:
                imagejpeg($new, null, $quality);
                break;

        }

        $result = ob_get_clean();

        /*
         * Clean up
         */
        imagedestroy($new);
        imagedestroy($image);

        return $result;

    }


    /**
     * Trim a string to a certain length without truncating the last word
     * @param  string $string   String to trim
     * @param  int    $limit    Length to trim to
     * @param  bool   $ellipses Whether or not to append ellipses to trimmed strings
     * @return string           Trimmed string (or original string if shorter than 'length' argument)
     */
    public function textTrim($string = '', $limit = 100, $ellipses = true)
    {

        $length = mb_strlen($string);

        if ($length > $limit)
        {

            preg_match('/((.|\s){' . $limit . '}(.|\s)*?)(?:^|\s)/', $string, $matches);

            /*
             * Handle some unicode edge cases
             */
            if (!isset($matches[1]))
            {
                return $string;
            }

            $result = rtrim($matches[1]) . ($ellipses ? '...' : '');

        }

        else
        {
            $result = $string;
        }

        return $result;

    }


    /**
     * Generate lorem ipsum text up to a certain length
     * @param  int    $length Length of text required
     * @return string         Lorem ipsum string
     */
    public function loremIpsum($length = 445)
    {

        $lorem_ipsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        $result      = '';

        while (mb_strlen($result) < $length)
        {
            $result .= ' ' . $lorem_ipsum;
            $result  = trim($result);
        }

        $result = $this->textTrim($result, $length, false);
        $result = trim($result);

        return $result;

    }


    /**
     * Get a gravatar URL from an e-mail address
     * @param  string $email E-mail address
     * @param  int    $size  Size of image
     * @return string        Gravatar URL
     */
    public function getGravatar($email = '', $size = 256)
    {

        $email      = trim($email);
        $email      = strtolower($email);
        $email_hash = md5($email);
        $size       = (int)$size;
        $protocol   = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '');

        return $protocol . '://www.gravatar.com/avatar/' . $email_hash . '.jpg?s=' . $size . '&amp;d=mm';

    }


}