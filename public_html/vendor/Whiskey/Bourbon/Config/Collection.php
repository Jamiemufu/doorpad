<?php


namespace Whiskey\Bourbon\Config;


/**
 * Configuration collection class
 * @package Whiskey\Bourbon\Config
 */
class Collection
{


    protected $_configs = [];


    /**
     * Add a configuration object to the collection
     * @param TemplateInterface $config Configuration object
     */
    public function add(TemplateInterface $config)
    {

        $name                    = $config->getName();
        $this->_configs[$name][] = $config;

    }


    /**
     * Get all configuration objects
     * @return array Array of configuration objects, by type
     */
    public function getAll()
    {

        return $this->_configs;

    }


    /**
     * Get configuration objects by type
     * @param  string $name Name of configuration type to get
     * @return array        Array of configuration objects
     */
    public function get($name = '')
    {

        if (isset($this->_configs[$name]))
        {
            return $this->_configs[$name];
        }

        return [];

    }


}