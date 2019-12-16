<?php


namespace Whiskey\Bourbon\Templating;


use Whiskey\Bourbon\Exception\EngineNotRegisteredException;
use Whiskey\Bourbon\Exception\Templating\InvalidDirectoryException;
use Whiskey\Bourbon\Instance;


/**
 * Templating Handler class
 * @package Whiskey\Bourbon\Templating
 */
class Handler
{


    protected $_engines   = [];
    protected $_base_dirs = [];
    protected $_cache_dir = '';


    /**
     * Add a base template directory
     * @param string $path Base directory path
     * @throws InvalidDirectoryException if the path could not be read or does not exist
     */
    public function addBaseDirectory($path = '')
    {

        if (!is_readable($path))
        {
            throw new InvalidDirectoryException('Directory \'' . $path . '\' could not be read or does not exist');
        }

        $this->_base_dirs[] = realpath($path);

    }


    /**
     * Set the cache directory
     * @param string $path Cache directory path
     * @throws InvalidDirectoryException if the path could not be read or does not exist
     */
    public function setCacheDirectory($path = '')
    {

        if (!is_readable($path))
        {
            throw new InvalidDirectoryException('Directory \'' . $path . '\' could not be read or does not exist');
        }

        $this->_cache_dir = realpath($path);

    }


    /**
     * Get the base directories
     * @return array Array of base directories
     */
    public function getBaseDirectories()
    {

        return $this->_base_dirs;

    }


    /**
     * Get the cache directory path
     * @return string Path to cache directory
     */
    public function getCacheDirectory()
    {

        return $this->_cache_dir;

    }


    /**
     * Register a templating engine
     * @param string $extension    File extension that the engine should render
     * @param string $engine_class Fully-qualified engine class name
     */
    public function registerEngine($extension = '', $engine_class = '')
    {

        $extension      = '.' . ltrim(strtolower($extension), '.');
        $engine_details = ['name' => '', 'engine' => $engine_class];
        
        $this->_engines[$extension] = $engine_details;

    }
    
    
    /**
     * Instantiate an engine and return a package of the instance and engine name
     * @param  string $class_name Fully-qualified engine class name
     * @return array              Array of engine name and instance
     */
    protected function _instantiateEngine($class_name = '')
    {
        
        $engine = Instance::_retrieve($class_name);

        foreach ($this->getBaseDirectories() as $directory)
        {
            $engine->addBaseDirectory($directory);
        }

        $cache_directory = $this->getCacheDirectory();

        if ($cache_directory != '' AND
            is_writable($cache_directory))
        {
            $engine->setCacheDirectory($cache_directory);
        }

        $name           = strtolower($engine->getName());
        $engine_details = ['name' => $name, 'engine' => $engine];
        
        return $engine_details;
        
    }


    /**
     * Get the registered engine instances
     * @return array Array of engine details and instances implementing TemplatingInterface
     */
    public function getEngines()
    {
        
        foreach ($this->_engines as &$engine_details)
        {

            if (is_string($engine_details['engine']))
            {
                $engine_details = $this->_instantiateEngine($engine_details['engine']);
            }

        }

        return $this->_engines;

    }


    /**
     * Retrieve a templating engine object for direct use
     * @param  string              $name Name of templating engine
     * @return TemplatingInterface       Templating engine object implementing TemplatingInterface
     * @throws EngineNotRegisteredException if the requested engine has not been registered
     */
    public function getLoader($name = '')
    {

        $name = strtolower($name);
        
        foreach ($this->_engines as &$engine_details)
        {

            if (is_string($engine_details['engine']))
            {
                $engine_details = $this->_instantiateEngine($engine_details['engine']);
            }

            if ($engine_details['name'] == $name)
            {
                return $engine_details['engine'];
            }

        }

        throw new EngineNotRegisteredException('Templating engine \'' . $name . '\' has not been registered');
        
    }


    /**
     * Get the templating engine for a given file
     * @param  string              $filename Filename to find templating engine for
     * @return TemplatingInterface           Templating engine loader object
     * @throws EngineNotRegisteredException if a templating engine for the template file could not be found
     */
    public function getLoaderFor($filename = '')
    {

        foreach ($this->_engines as $extension => &$engine_details)
        {

            if (strtolower(mb_substr($filename, (0 - mb_strlen($extension)))) == strtolower($extension))
            {

                if (is_string($engine_details['engine']))
                {
                    $engine_details = $this->_instantiateEngine($engine_details['engine']);
                }

                return $engine_details['engine'];

            }

        }

        throw new EngineNotRegisteredException('Templating engine for template \'' . $filename . '\' not registered');

    }


}