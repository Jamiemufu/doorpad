<?php

namespace Whiskey\Bourbon\Helper;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\Helper\Component\SafeString;


/**
 * Input class
 * @package Whiskey\Bourbon\Helper
 */
class Input
{


    protected $_dependencies = null;


    /**
     * Instantiate the Input object
     * @param Request    $request     Request object
     * @param SafeString $safe_string SafeString object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Request $request, SafeString $safe_string)
    {

        if (!isset($request) OR
            !isset($safe_string))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies              = new stdClass();
        $this->_dependencies->request     = $request;
        $this->_dependencies->safe_string = $safe_string;

    }


    /**
     * Fetch input variable
     * @param  string       $type     Type of input variable
     * @param  string|array $key      Variable name (or array of names)
     * @param  bool         $sanitise Whether to sanitise the value
     * @return string|array           Variable value (or array of variable values, with NULL on fail)
     * @throws InvalidArgumentException if the input type is not valid
     */
    protected function _fetch($type = 'get', $key = '', $sanitise = true)
    {

        $type             = strtolower($type);
        $acceptable_types = ['get', 'post', 'request', 'put'];

        if (!in_array($type, $acceptable_types))
        {
            throw new InvalidArgumentException('Invalid input type');
        }

        /*
         * Multiple variables
         */
        if (is_array($key))
        {

            $result = [];

            foreach ($key as $variable_key)
            {
                $result[$variable_key] = $this->$type($variable_key, $sanitise);
            }

            return $result;

        }

        /*
         * Single variable
         */
        else
        {

            $key    = (string)$key;
            $values = $this->_dependencies->request->$type;

            /*
             * Look for a direct match
             */
            if (isset($values[$key]))
            {

                $value = $values[$key];

                if (!is_null($value))
                {

                    if (!$sanitise)
                    {
                        $value = $this->_dependencies->safe_string->unsanitise($value);
                    }

                }

                return $value;

            }

            /*
             * Look for a nested match with dot notation
             */
            else if (stristr($key, '.') !== false)
            {

                $keys = explode('.', $key);

                foreach ($keys as $level_key)
                {

                    if (!isset($values[$level_key]))
                    {
                        return null;
                    }

                    $values = $values[$level_key];

                }

                if (!is_null($values))
                {

                    if (!$sanitise)
                    {
                        $values = $this->_dependencies->safe_string->unsanitise($values);
                    }

                }

                return $values;

            }

        }

        return null;

    }


    /**
     * Fetch GET variable
     * @param  string|array $key      Variable name (or array of names)
     * @param  bool         $sanitise Whether to sanitise the value
     * @return string|array           Variable value (or array of variable values, with NULL on fail)
     */
    public function get($key = '', $sanitise = true)
    {

        return $this->_fetch('get', $key, $sanitise);

    }


    /**
     * Fetch POST variable
     * @param  string|array $key      Variable name (or array of names)
     * @param  bool         $sanitise Whether to sanitise the value
     * @return string|array           Variable value (or array of variable values, with NULL on fail)
     */
    public function post($key = '', $sanitise = true)
    {

        return $this->_fetch('post', $key, $sanitise);

    }


    /**
     * Fetch $_REQUEST variable
     * @param  string|array $key      Variable name (or array of names)
     * @param  bool         $sanitise Whether to sanitise the value
     * @return string|array           Variable value (or array of variable values, with NULL on fail)
     */
    public function request($key = '', $sanitise = true)
    {

        return $this->_fetch('request', $key, $sanitise);

    }


    /**
     * Fetch PUT variable
     * @param  string|array $key      Variable name (or array of names)
     * @param  bool         $sanitise Whether to sanitise the value
     * @return string|array           Variable value (or array of variable values, with NULL on fail)
     */
    public function put($key = '', $sanitise = true)
    {

        return $this->_fetch('put', $key, $sanitise);

    }


}