<?php


namespace Whiskey\Bourbon\Config\Type;


use Whiskey\Bourbon\Config\AbstractTemplateMulti;


/**
 * Class to define engine plugins
 * @package Whiskey\Bourbon\Config
 */
class Engines extends AbstractTemplateMulti
{


    protected $_mappings = [];
    protected $_configs  = [];


    /**
     * Get the name of the configuration class
     * @return string Name of the configuration class
     */
    public function getName()
    {

        return 'engines';

    }


    /**
     * Store configuration values to be passed to a handler class when it is
     * instantiated
     * @param string $class_name  Fully-qualified handler class name
     * @param string $method_name Name of handler class configuration method
     * @param array  $arguments   Array of values to pass to method
     */
    public function config($class_name = '', $method_name = '', array $arguments = [])
    {

        $this->_configs[$class_name][$method_name][] = $arguments;

    }


    /**
     * Get the configuration values stored for a handler class
     * @param  string $class_name Fully-qualified class name
     * @return array              Multidimensional array of methods (keys) and their values
     */
    public function getConfigValues($class_name = '')
    {

        return isset($this->_configs[$class_name]) ? $this->_configs[$class_name] : [];

    }


    /**
     * Set engine key handler class mappings
     * @param array $mappings Array of engine key handler class mappings
     */
    public function setHandlerMappings(array $mappings = [])
    {

        $this->_mappings = $mappings;

    }


    /**
     * Get engine key handler class mappings
     * @return array Array of engine key handler class mappings
     */
    public function getHandlerMappings()
    {

        return $this->_mappings;

    }


}