<?php


namespace Whiskey\Bourbon\Config;


/**
 * Configuration class interface, to be implemented by individual configuration
 * files
 * @package Whiskey\Bourbon\Config
 */
interface TemplateInterface
{


    /**
     * Get the name of the extended configuration class
     * @return string Name of the configuration class
     */
    public function getName();


}