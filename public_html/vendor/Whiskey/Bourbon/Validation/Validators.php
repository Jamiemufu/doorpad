<?php


namespace Whiskey\Bourbon\Validation;


use DateTime;
use finfo;


/**
 * Validators class
 * @package Whiskey\Bourbon\Validation
 */
class Validators
{


    /**
     * Instantiate and return a Builder object
     * @param  Handler $handler Handler object
     * @param  array   $array   Array to validate (will use $_POST if not provided)
     * @return Handler          Handler object
     */
    public function build(Handler $handler, $array = null)
    {

        $array_to_pass = is_null($array) ? $_POST : $array;

        $handler->setInputArray($array_to_pass);

        return $handler;

    }


    /**
     * Determine if a string is valid, based upon whether or not it is required
     * to not be blank
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes 'required' validation
     */
    public function validateRequiredCheck($input = '', $required = true)
    {

        $input = (string)$input;

        /*
         * If required and we have something, return true
         */
        if ($required AND $input !== '')
        {
            return true;
        }
        
        /*
         * If not required, return true
         */
        else if (!$required)
        {
            return true;
        }

        /*
         * In all other circumstances (i.e., required, but blank), return false
         */
        return false;

    }


    /**
     * Check whether a string is valid
     * @param  string $input         Input string
     * @param  bool   $required      Whether the input is required to not be blank
     * @param  bool   $filter_result Result of type-specific validation
     * @return bool                  Whether the input passes validation
     */
    public function validateCheck($input = '', $required = true, $filter_result = false)
    {

        $input = (string)$input;

        if ($this->validateRequiredCheck($input, $required))
        {

            /*
             * Check to see if the input is blank or passed validation
             */
            if ($input === '' OR $filter_result)
            {
                return true;
            }

            return false;

        }

        return false;

    }


    /**
     * Check whether a string is strictly numeric
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateNum($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = ctype_digit($input);

        return $this->validateCheck($input, $required, $filter_result);

    }

    /**
     * Check whether a string is strictly alphabetic (without spaces)
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateAlpha($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = ctype_alpha($input);

        return $this->validateCheck($input, $required, $filter_result);
        
    }
    
    
    /**
     * Check whether a string is strictly alphabetic (with spaces)
     * @author David Allak
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateAlphaSpaces($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = !!preg_match('/^[a-zA-Z\s]+$/', $input);

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is strictly alphanumeric (without spaces)
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateAlphaNum($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = ctype_alnum($input);

        return $this->validateCheck($input, $required, $filter_result);

    }
    
    
    /**
     * Check whether a string is strictly alphanumeric (with spaces)
     * @author David Allak
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateAlphaNumSpaces($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = !!preg_match('/^[a-zA-Z0-9\s]+$/', $input);

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is a valid e-mail address
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateEmail($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = (filter_var($input, FILTER_VALIDATE_EMAIL) !== false);

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is a valid URL with DNS records
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateUrl($input = '', $required = true)
    {

        $original_input = $input;

        $input = (string)$input;
        $input = (string)parse_url($input, PHP_URL_HOST);

        $filter_result = false;
        
        if ($input !== '')
        {
            $filter_result = (checkdnsrr($input) !== false);
        }

        return $this->validateCheck($original_input, $required, $filter_result);

    }


    /**
     * Check whether a string is truthy
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateTrue($input = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = (filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === true) ? true : false;

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is falsy
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateFalse($input = '', $required = true)
    {

        $original_input = $input;
        $input          = (string)$input;
        $filter_result  = (filter_var($input, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false) ? true : false;

        /*
         * If the input is boolean FALSE, don't return false for a blank string
         */
        if ($original_input === false)
        {
            $required = false;
        }

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is a valid card number
     * @param  string $input    Input string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateCardNumber($input = '', $required = true)
    {

        $original_input = $input;
        $input          = (string)$input;
        $input          = str_replace(' ', '', $input);
        $fixed_input    = $input;
        $input          = strrev($input);
        $input          = str_split($input);

        $checksum = '';

        foreach ($input as $index => $digit)
        {

            if ((int)$index % 2 !== 0)
            {
                $checksum .= (string)((int)$digit * 2);
            }

            else
            {
                $checksum .= $digit;
            }

        }

        $checksum = str_split($checksum);
        $checksum = array_sum($checksum);
        $checksum = $checksum % 10;

        $is_valid = ($checksum === 0);

        /*
         * Special case for non-numeric input
         */
        if (!ctype_digit($fixed_input))
        {
            $is_valid = false;
        }

        return $this->validateCheck($original_input, $required, $is_valid);

    }


    /**
     * Convert a date to a DateTime object
     * @param  mixed    $date Date of some description
     * @return DateTime       DateTime object
     */
    protected function _dateConvert($date = 0)
    {

        $date_format = 'Y-m-d H:i:s';
        $result_date = DateTime::createFromFormat($date_format, $date);

        if ($result_date === false)
        {

            if (!is_numeric($date))
            {
                $result_date = strtotime($date);
            }

            else
            {
                $result_date = $date;
            }

            $result_date = DateTime::createFromFormat($date_format, date($date_format, $result_date));

        }
        
        return $result_date;

    }


    /**
     * Check whether an input date is before a set date
     * @param  string $input    Date string (Y-m-d H:i:s)
     * @param  string $date     Date string (Y-m-d H:i:s)
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateEarlierThan($input = '', $date = '', $required = true)
    {

        $original_input = $input;
        
        $input = $this->_dateConvert($input);
        $date  = $this->_dateConvert($date);
        
        $filter_result = ($input < $date) ? true : false;
        
        return $this->validateCheck($original_input, $required, $filter_result);
    
    }


    /**
     * Check whether an input date is after a set date
     * @param  string $input    Date string (Y-m-d H:i:s)
     * @param  string $date     Date string (Y-m-d H:i:s)
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateLaterThan($input = '', $date = '', $required = true)
    {

        $original_input = $input;

        $input = $this->_dateConvert($input);
        $date  = $this->_dateConvert($date);

        $filter_result = ($input > $date) ? true : false;

        return $this->validateCheck($original_input, $required, $filter_result);
    
    }
    
    
    /**
     * Check whether an input string looks like a date
     * @param  string $input    Date string
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateIsDate($input = '', $required = true)
    {

        $filter_result = (strtotime($input) !== false);

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether an input date is a set date
     * @param  string $input    Date string (Y-m-d H:i:s)
     * @param  string $date     Date string (Y-m-d H:i:s)
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateMatchesDate($input = '', $date = '', $required = true)
    {

        $original_input = $input;

        $input = $this->_dateConvert($input);
        $date  = $this->_dateConvert($date);

        $filter_result = ($input == $date) ? true : false;

        return $this->validateCheck($original_input, $required, $filter_result);

    }


    /**
     * Check whether a string is at least one character in length
     * @param  string $input Input string
     * @return bool          Whether the input passes validation
     */
    public function validateHasContent($input = '')
    {

        return $this->validateMinimumLength($input, 1, true);

    }


    /**
     * Check whether a string is at least a certain length
     * @param  string $input    Input string
     * @param  int    $length   Minimum length to check for
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateMinimumLength($input = '', $length = 1, $required = true)
    {

        $filter_result = (mb_strlen($input) >= $length) ? true : false;

        /*
         * If the minimum length is 0, don't return false for a blank string
         */
        if ($length == 0)
        {
            $required = false;
        }
        
        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string is no longer than a certain length
     * @param  string $input    Input string
     * @param  int    $length   Maximum length to check for
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateMaximumLength($input = '', $length = 10, $required = true)
    {

        $filter_result = (mb_strlen($input) <= $length) ? true : false;
        
        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a number is less than another number
     * @param  int  $input    Number to check
     * @param  int  $limit    Number limit to check against
     * @param  bool $required Whether the input is required to not be blank
     * @return bool           Whether the input passes validation
     */
    public function validateLessThan($input = 0, $limit = 0, $required = true)
    {

        $filter_result = ($input < $limit) ? true : false;

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a number is greater than another number
     * @param  int  $input    Number to check
     * @param  int  $limit    Number limit to check against
     * @param  bool $required Whether the input is required to not be blank
     * @return bool           Whether the input passes validation
     */
    public function validateGreaterThan($input = 0, $limit = 0, $required = true)
    {

        $filter_result = ($input > $limit) ? true : false;

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string matches another string
     * @param  string $input    Input string
     * @param  string $match    String to match against
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateMatch($input = '', $match = '', $required = true)
    {

        $filter_result = ($input == $match) ? true : false;
        
        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check whether a string matches a regex pattern
     * @param  string $input    Input string
     * @param  string $regex    Regex pattern to check against
     * @param  bool   $required Whether the input is required to not be blank
     * @return bool             Whether the input passes validation
     */
    public function validateRegex($input = '', $regex = '', $required = true)
    {

        $input         = (string)$input;
        $filter_result = !!preg_match($regex, $input);

        return $this->validateCheck($input, $required, $filter_result);

    }


    /**
     * Check a file's MIME type
     * @param  string $filename  Path to file
     * @param  mixed  $mime_type MIME type fragment string/array
     * @return bool              Whether the file passes validation
     */
    public function validateMimeType($filename = '', $mime_type = [])
    {

        if ((string)$filename !== '' AND is_readable($filename))
        {

            /*
             * If the MIME type is not an array, convert it to one
             */
            if (!is_array($mime_type))
            {
                $mime_type = [$mime_type];
            }

            /*
             * Instantiate an finfo object and break apart the file's MIME type
             */
            $finfo = new finfo(FILEINFO_MIME);

            $mime_info = $finfo->file($filename);
            $mime_info = explode(';', $mime_info);
            $mime_info = reset($mime_info);
            $mime_info = strtolower($mime_info);

            $mime_array = explode('/', str_replace('-', '/', $mime_info));

            /*
             * Iterate through the MIME fragment array looking for a match
             */
            foreach ($mime_type as $value)
            {

                if ($mime_info == $value OR
                    in_array(strtolower($value), $mime_array))
                {
                    return true;
                }

            }

        }

        return false;

    }


}