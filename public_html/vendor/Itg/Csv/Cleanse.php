<?php


namespace Itg\Csv;


use Exception;
use Whiskey\Bourbon\App\Facade\Utils;


/**
 * Cleanse class (to cleanse CSV data)
 * @package Itg\Csv
 * @author David Maidment <davidmaidment@inspiredthinkinggroup.com>
 */
class Cleanse
{


    const ALL_LOWER   = 1;
    const ALL_UPPER   = 2;
    const FIRST_UPPER = 3;
    const APPEND      = 4;
    const REMOVE      = 5;
    const REPLACE     = 6;
    const REPLACE_IF  = 7;
    const TRIM        = 8;


    protected $_download = false;
    protected $_raw      = '';
    protected $_array    = [];
    protected $_filters  = [];


    /**
     * Instantiate the Cleanse object and load in the raw data
     * @param string $raw Raw CSV data
     */
    public function __construct($raw = '')
    {

        if ($raw != '')
        {
            $this->_raw   = $raw;
            $this->_array = Utils::csvToArray($this->_raw);
        }

    }


    /**
     * Load a CSV file from disk
     * @param string $filename Path to CSV file
     * @throws Exception if the input file cannot be read or does not exist
     */
    public function load($filename = '')
    {

        if (is_readable($filename))
        {
            $this->_raw   = file_get_contents($filename);
            $this->_array = Utils::csvToArray($this->_raw);
        }

        else
        {
            throw new Exception('Input CSV file cannot be read or does not exist');
        }

    }


    /**
     * Make the class output the file as a download
     * @return self Cleanse object
     */
    public function download()
    {

        $this->_download = true;

        return $this;

    }


    /**
     * Add a filter to the cleansing process
     * @param  int          $filter Filter name (class constant)
     * @param  string|array $column Name of column (or array of column names) to apply filter to (or asterisk for all)
     * @param  string       $info   Additional information that might be needed
     * @return self                 Cleanse object
     */
    public function addFilter($filter = 0, $column = '*', $info = '')
    {

        if (is_array($column))
        {

            foreach ($column as $column_name)
            {
                $this->addFilter($filter, $column_name, $info);
            }

        }

        else
        {

            $this->_filters[] =
                [
                    'type' => $filter,
                    'key'  => strtolower($column),
                    'info' => $info
                ];

        }

        return $this;

    }


    /**
     * Apply lowercase filter
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @return string         Altered value
     */
    protected function _lowercase($key = '', $value = '')
    {

        return strtolower($value);

    }


    /**
     * Apply uppercase filter
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @return string         Altered value
     */
    protected function _uppercase($key = '', $value = '')
    {

        return strtoupper($value);

    }


    /**
     * Apply the uppercase filter to the first letter of each word
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @return string         Altered value
     */
    protected function _firstUpper($key = '', $value = '')
    {

        /*
         * Capitalise the first letter
         */
        $value = ucwords($value);

        /*
         * Fix capitalisation where parentheses have got in the way
         */
        $bracket_start = strpos($value, '(');

        if ($bracket_start > -1)
        {
            
            $value = substr($value, 0, ($bracket_start + 1)) . 
                     strtoupper(substr($value, ($bracket_start + 1), 1)) . 
                     substr($value, ($bracket_start + 2));

        }

        /*
         * Fix capitalisation where slashes have got in the way
         */
        $slash_start = strpos($value, '/');

        if ($slash_start > -1)
        {
            
            $value = substr($value, 0, ($slash_start + 1)) . 
                     strtoupper(substr($value, ($slash_start + 1), 1)) . 
                     substr($value, ($slash_start + 2));

        }

        /*
         * Fix capitalisation where Mcs have got in the way
         */
        $mc_start = strpos($value, 'Mc');
        
        if ($mc_start > -1)
        {
            
            $value = substr($value, 0, ($mc_start + 2)) . 
                     ucwords(substr($value, ($mc_start + 2)));

        }

        return $value;

    }


    /**
     * Apply string append filter
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @param  string $info   Additional information
     * @return string         Altered value
     */
    protected function _append($key = '', $value = '', $info = '')
    {

        return $value = $value . $info;

    }


    /**
     * Apply replace filter
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @param  array  $info   Additional information
     * @return string         Altered value
     */
    protected function _replace($key = '', $value = '', array $info = [])
    {

        return str_replace(reset($info), end($info), $value);

    }


    /**
     * Apply 'replace if' filter
     * @param  string $key    Column name
     * @param  string &$value Entry value
     * @param  array  $info   Additional information
     * @return string         Altered value
     */
    protected function _replaceIf($key = '', $value = '', array $info = [])
    {

        if ($value == reset($info))
        {
            $value = end($info);
        }

        return $value;

    }


    /**
     * Cleanse the CSV
     * @param  string $filename Optional filename for download
     * @return string           CSV string (or null if forcing a download)
     */
    public function run($filename = '')
    {

        $output    = [];
        $to_remove = [];

        foreach ($this->_array as $input_key => &$entry)
        {

            foreach ($entry as $key => &$value)
            {

                /*
                 * Run any applicable filters
                 */
                foreach ($this->_filters as $filter)
                {

                    if ($filter['key'] == strtolower($key) OR $filter['key'] == '*')
                    {

                        switch ($filter['type'])
                        {

                            case self::ALL_LOWER:
                                $value = $this->_lowercase($key, $value);
                                break;

                            case self::ALL_UPPER:
                                $value = $this->_uppercase($key, $value);
                                break;

                            case self::FIRST_UPPER:
                                $value = $this->_firstUpper($key, $value);
                                break;

                            case self::APPEND:
                                $value = $this->_append($key, $value, $filter['info']);
                                break;

                            case self::REPLACE:
                                $value = $this->_replace($key, $value, $filter['info']);
                                break;

                            case self::REPLACE_IF:
                                $value = $this->_replaceIf($key, $value, $filter['info']);
                                break;

                            case self::TRIM:
                                $value = trim($value, ($filter['info'] != '') ? $filter['info'] : " \t\n\r\0\x0B");
                                break;

                            case self::REMOVE:
                                $to_remove[strtolower($key)] = true;
                                break;

                        }

                    }

                }

            }

        }

        /*
         * Remove unneeded columns
         */
        foreach ($this->_array as $input_key => &$entry)
        {

            foreach ($entry as $key => &$value)
            {

                if (!isset($to_remove[strtolower($key)]))
                {
                    $output[$input_key][$key] = $value;
                }
            
            }
        
        }

        /*
         * Prepare the data for output
         */
        $output = empty($output) ? '' : Utils::arrayToCsv($output);

        /*
         * Download it
         */
        if ($this->_download)
        {

            header('Content-Encoding: UTF-8');
            header('Content-type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            echo "\xEF\xBB\xBF";
            echo $output;

            exit;

        }

        /*
         * Or return it
         */
        else
        {
            return $output;
        }

    }


}