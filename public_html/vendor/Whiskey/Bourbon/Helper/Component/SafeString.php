<?php


namespace Whiskey\Bourbon\Helper\Component;


/**
 * SafeString class
 * @package Whiskey\Bourbon\Helper\Component
 */
class SafeString
{


    protected $_string      = '';
    protected $_safe_string = '';


    /**
     * Instantiate the SafeString object
     * @param string $string String to sanitise
     */
    public function __construct($string = '')
    {

        $string             = (string)$string;
        $this->_string      = $string;
        $this->_safe_string = static::sanitise($string);

    }


    /**
     * Get the raw (unsanitised) string
     * @return string Raw string
     */
    public function raw()
    {

        return $this->_string;

    }


    /**
     * Get the sanitised string
     * @return string Sanitised string
     */
    public function sanitised()
    {

        return $this->_safe_string;

    }


    /**
     * Return the object as a [sanitised] string
     * @return string Sanitised string
     */
    public function __toString()
    {

        return $this->sanitised();

    }


    /**
     * Convert braces, quotes, etc. in strings (and the string values of arrays)
     * to their HTML entity equivalents
     * @param  string|array $original_string String/array to sanitise
     * @return string|array                  Sanitised string/array
     */
    public static function sanitise($original_string = '')
    {

        if (is_array($original_string))
        {

            foreach ($original_string as $var => $value)
            {
                $original_string[$var] = call_user_func(__METHOD__, $value);
            }

            return $original_string;

        }

        else if (is_string($original_string))
        {

            $originals    = [';',     '&',     '&amp;#', '"',      '\'',    '<',    '>',    '\\',    '/',     '(',     ')',     '%',     '`'];
            $replacements = ['&#59;', '&amp;', '&#',     '&quot;', '&#39;', '&lt;', '&gt;', '&#92;', '&#47;', '&#40;', '&#41;', '&#37;', '&#96;'];

            return str_replace($originals, $replacements, $original_string);

        }

        else
        {
            return $original_string;
        }

    }


    /**
     * Convert HTML entity braces, quotes, etc. in strings (and the string
     * values of arrays) to their plaintext equivalents
     * @param  string|array $original_string String/array to unsanitise
     * @return string|array                  Unsanitised string/array
     */
    public static function unsanitise($original_string = '')
    {

        if (is_array($original_string))
        {

            foreach ($original_string as $var => $value)
            {
                $original_string[$var] = call_user_func(__METHOD__, $value);
            }

            return $original_string;

        }

        else if (is_string($original_string))
        {

            $originals    = ['&#47;', '&#92;', '&gt;', '&lt;', '&#39;', '&quot;', '&#40;', '&#41;', '&#37;', '&#96;', '&amp;', '&#59;'];
            $replacements = ['/',     '\\',    '>',    '<',    '\'',    '"',      '(',     ')',     '%',     '`',     '&',     ';'];

            return str_replace($originals, $replacements, $original_string);

        }

        else
        {
            return $original_string;
        }

    }


}