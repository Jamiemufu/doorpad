<?php


namespace Whiskey\Bourbon\Templating;


use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Templating\InvalidDirectoryException;


/**
 * TemplatingInterface Interface
 * @package Whiskey\Bourbon\Templating
 */
interface TemplatingInterface
{


    /**
     * Get the name of the templating engine
     * @return string Name of templating engine
     */
    public function getName();


    /**
     * Check whether the templating engine is active
     * @return bool Whether the templating engine is active
     */
    public function isActive();


    /**
     * Get the raw engine object
     * @return mixed Underlying rendering engine object
     */
    public function getEngine();


    /**
     * Set the cache directory
     * @param string $directory Path to cache directory
     * @throws InvalidDirectoryException if the cache directory is not writable
     */
    public function setCacheDirectory($directory);


    /**
     * Add a base directory
     * @param string $directory Path to base directory
     * @throws InvalidDirectoryException if the base template directory is not readable
     */
    public function addBaseDirectory($directory);


    /**
     * Render a template file
     * @param  string      $filename  Relative path to template file
     * @param  array       $variables Variables to include in the local scope
     * @param  bool        $return    Whether to return the parsed file rather than output it
     * @return string|null            Parsed template file (or null if $return is FALSE)
     * @throws EngineNotInitialisedException if the templating engine has not been initialised
     */
    public function render($filename, array $variables, $return = false);


}