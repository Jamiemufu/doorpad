<?php


namespace Whiskey\Bourbon\Config;


use InvalidArgumentException;


/**
 * Abstract configuration template class, to be extended by individual
 * configuration files -- will overwrite existing values set with the same name
 * @package Whiskey\Bourbon\Config
 */
abstract class AbstractTemplateSingle implements TemplateInterface
{


    protected $_values = [];


    /**
     * Set a configuration value
     * @param string $name  Name of value to set
     * @param mixed  $value Value to set
     * @throws InvalidArgumentException if a name is not passed
     */
    public function set($name = null, $value = '')
    {

        if (is_null($name))
        {
            throw new InvalidArgumentException('Missing configuration entry name');
        }

        $this->_values[$name] = $value;

    }


    /**
     * Get a configuration value
     * @param  string $name Name of value to get
     * @return mixed        Configuration value (null if not found)
     * @throws InvalidArgumentException if a name is not passed
     */
    public function getValue($name = null)
    {

        if (is_null($name))
        {
            throw new InvalidArgumentException('Missing configuration entry name');
        }

        return isset($this->_values[$name]) ? $this->_values[$name] : null;

    }


    /**
     * Get all key/value sets passed to the configuration class
     * @return array Array of key/value sets
     */
    public function getAllValues()
    {

        return $this->_values;

    }


    /**
     * Get the name of the extended configuration class
     * @return string Name of the configuration class
     */
    abstract public function getName();



}