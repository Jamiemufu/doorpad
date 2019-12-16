<?php


namespace Whiskey\Bourbon\Templating\Engine\Ice;


use Closure;
use Whiskey\Bourbon\Exception\Templating\Ice\InvalidParserException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Templating\InvalidDirectoryException;
use Whiskey\Bourbon\Templating\TemplatingInterface;
use Whiskey\Bourbon\Templating\Engine\Ice\Renderer as Ice;


/**
 * Ice Loader class
 * @package Whiskey\Bourbon\Templating\Engine\Ice
 */
class Loader implements TemplatingInterface
{


    protected $_engine = null;


    /**
     * Instantiate the Loader object and retrieve an Ice instance
     */
    public function __construct()
    {

        /*
         * Instantiate new Ice engine
         */
        $this->_engine = new Ice();

        /*
         * Add a _renderBlock() method to the $_helper object that should be
         * passed by the framework
         */
        $this->_engine->registerParser(function($content)
        {

            return preg_replace("/@block\(((.|\s)*?(?=\)\s))\)/",
                                '{{ $_helper->_renderBlock($1) }}',
                                $content);

        });

    }


    /**
     * Add a custom Ice parser
     * @param  Closure $callback Closure to be executed
     * @return bool              Whether the parser was successfully added
     * @throws InvalidParserException if the parser is not callable
     */
    public function registerParser(Closure $callback)
    {

        if ((is_object($callback) AND ($callback instanceof Closure)))
        {
            return $this->_engine->registerParser($callback);
        }

        throw new InvalidParserException('Custom Ice parser is not valid');

    }


    /**
     * Get the name of the templating engine
     * @return string Name of templating engine
     */
    public function getName()
    {

        return 'ice';

    }


    /**
     * Check whether the templating engine is active
     * @return bool Whether the templating engine is active
     */
    public function isActive()
    {

        return $this->_engine->isActive();

    }


    /**
     * Get the raw engine object
     * @return Ice Instance of Ice rendering engine
     */
    public function getEngine()
    {

        return $this->_engine;

    }


    /**
     * Set the cache directory
     * @param string $directory Path to cache directory
     * @throws InvalidDirectoryException if the cache directory is not writable
     */
    public function setCacheDirectory($directory = '')
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (is_dir($directory) AND
            is_writable($directory))
        {
            $this->_engine->setCacheDirectory($directory);
        }

        else
        {
            throw new InvalidDirectoryException('Cache directory is not writable');
        }

    }


    /**
     * Add a base directory
     * @param string $directory Path to base directory
     * @throws InvalidDirectoryException if the base template directory is not readable
     */
    public function addBaseDirectory($directory = '')
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (is_dir($directory) AND
            is_readable($directory))
        {
            $this->_engine->addBaseDirectory($directory);
        }

        else
        {
            throw new InvalidDirectoryException('Base template directory is not readable');
        }

    }


    /**
     * Render a template file
     * @param  string      $filename  Relative path to template file
     * @param  array       $variables Variables to include in the local scope
     * @param  bool        $return    Whether to return the parsed file rather than output it
     * @return string|null            Parsed template file (or null if $return is FALSE)
     * @throws EngineNotInitialisedException if Ice has not been initialised
     */
    public function render($filename = '', array $variables = [], $return = false)
    {

        if (is_null($this->_engine))
        {
            throw new EngineNotInitialisedException('Ice has not been initialised');
        }

        return $this->_engine->render($filename, $variables, $return);

    }


}