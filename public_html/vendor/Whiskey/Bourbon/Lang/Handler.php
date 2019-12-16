<?php


namespace Whiskey\Bourbon\Lang;


use Whiskey\Bourbon\Exception\Lang\MissingPluralFormException;


/**
 * Lang Handler class
 * @package Whiskey\Bourbon\Lang
 */
class Handler
{


    protected $_words            = [];
    protected $_default_language = 'en';


    /**
     * Set the default language
     * @param string $language Language key
     */
    public function setDefault($language = '')
    {

        $this->_default_language = strtolower($language);

    }


    /**
     * Add a word to the translation list
     * @param string       $language Language key
     * @param string       $key      Unique identifier word key
     * @param string|array $word     Word to store (or array of singular/plural forms of the word)
     * @throws MissingPluralFormException if a plural form of the word is not provided
     */
    public function add($language = 'en', $key = '', $word = '')
    {

        $language = strtolower($language);

        if (!is_array($word))
        {
            $word = [$word, $word];
        }

        else if (count($word) < 2)
        {
            throw new MissingPluralFormException('Plural form of word \'' . $key . '\' not provided');
        }

        $this->_words[$language][$key] = $word;

    }


    /**
     * Get the default language's version of a word
     * @param  string $key          Word key
     * @param  array  $placeholders Array of values to swap out
     * @param  int    $count        Optional count for pluralisation
     * @return string               Default language's version of word
     */
    public function get($key = '', array $placeholders = [], $count = 1)
    {

        return $this->{$this->_default_language}($key, $placeholders, $count);

    }


    /**
     * Replace placeholders in string
     * @param  string $string       String to replace values in
     * @param  array  $placeholders Array of values to swap out
     * @return string               String with replaced values
     */
    protected function _fillPlaceholders($string = '', array $placeholders = [])
    {

        foreach ($placeholders as $placeholder_name => $placeholder_value)
        {
            $string = str_replace('{' . $placeholder_name . '}', $placeholder_value, $string);
        }

        return $string;

    }


    /**
     * Catch missing method calls, taking the name as the language key
     * @param  string $language  Method/language key
     * @param  array  $arguments Method arguments
     * @return string            Language word
     */
    public function __call($language = '', array $arguments = [])
    {

        $language     = strtolower($language);
        $key          = array_shift($arguments);
        $placeholders = array_shift($arguments);
        $count        = array_shift($arguments);

        if (!is_array($placeholders))
        {
            $placeholders = [];
        }

        /*
         * Look for the word in the requested language
         */
        if (isset($this->_words[$language][$key]))
        {

            /*
             * If the object is singular (or we do not know), return the first
             * entry in the word array
             */
            if ($count == 1 OR is_null($count))
            {
                return $this->_fillPlaceholders(reset($this->_words[$language][$key]), $placeholders);
            }

            /*
             * If the object is in the plural, return the last (second) entry in
             * the word array
             */
            return $this->_fillPlaceholders(end($this->_words[$language][$key]), $placeholders);

        }

        /*
         * If a match could not be found, look for the word in the default
         * language
         */
        if (isset($this->_words[$this->_default_language][$key]))
        {
            return $this->get($key, $placeholders, $count);
        }

        /*
         * If no matches existed anywhere, return a blank string
         */
        return '';

    }


}