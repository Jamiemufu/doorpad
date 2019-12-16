<?php


namespace Whiskey\Bourbon\App;


use InvalidArgumentException;


/**
 * AppEnv class
 * @package Whiskey\Bourbon\App
 */
class AppEnv
{


    protected $_values = [];


    /**
     * Set a value
     * @param string $key   Key name
     * @param mixed  $value Value
     * @throws InvalidArgumentException if a key is not provided
     */
    public function set($key = '', $value = null)
    {

        if ($key == '')
        {
            throw new InvalidArgumentException('Key not provided for application environment value');
        }

        $this->_values[$key] = $value;

    }


    /**
     * Get a stored value
     * @param  string $key       Key name
     * @param  array  $arguments Method arguments
     * @return mixed             Value (or NULL if not set)
     */
    public function __call($key = '', array $arguments = [])
    {

        if (isset($this->_values[$key]))
        {
            return $this->_values[$key];
        }

        return null;

    }


}