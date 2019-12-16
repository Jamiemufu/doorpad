<?php


namespace Whiskey\Bourbon\Templating\Engine\Twig;


use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Routing\Handler as Router;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Templating\TemplatingInterface;
use Whiskey\Bourbon\Exception\Templating\InvalidDirectoryException;


/**
 * Twig Loader class
 * @package Whiskey\Bourbon\Templating\Engine\Twig
 */
class Loader implements TemplatingInterface
{


    protected $_filesystem = null;
    protected $_engine     = null;
    protected $_base_dir   = null;
    protected $_cache_dir  = null;


    /**
     * Get the name of the templating engine
     * @return string Name of templating engine
     */
    public function getName()
    {

        return 'twig';

    }


    /**
     * Check whether the templating engine is active
     * @return bool Whether the templating engine is active
     */
    public function isActive()
    {

        return !is_null($this->_engine);

    }


    /**
     * Get the raw engine object
     * @return \Twig_Environment Twig rendering engine
     */
    public function getEngine()
    {

        return $this->_engine;

    }


    /**
     * Initialise Twig with the settings provided by other methods
     */
    protected function _init()
    {

        if (!is_null($this->_base_dir) AND
            class_exists(Twig_Loader_Filesystem::class))
        {

            /*
             * Instantiate the Twig engine
             */
            $loader  = new Twig_Loader_Filesystem($this->_base_dir);
            $options = [];

            if (!is_null($this->_cache_dir))
            {
                $options['cache']       = $this->_cache_dir;
                $options['auto_reload'] = true;
            }

            $this->_filesystem = $loader;
            $this->_engine     = new Twig_Environment($loader, $options);

            /*
             * Add a link() function
             */
            $route_link_function = new Twig_SimpleFunction('link', function()
            {

                $router = Instance::_retrieve(Router::class);

                /*
                 * See if the controller exists -- if it does not, try prepending
                 * the default namespace
                 */
                $arguments  = func_get_args();
                $controller = reset($arguments);

                if (!class_exists($controller))
                {

                    $new_controller = 'Whiskey\\Bourbon\\App\\Http\\Controller\\' . $controller;

                    if (class_exists($new_controller))
                    {
                        $arguments[0] = $new_controller;
                    }

                }

                /*
                 * Pass the arguments onto the router's generateUrl() method
                 */
                return call_user_func_array(array($router, 'generateUrl'), $arguments);

            }, ['is_safe' => ['html']]);

            $this->_engine->addFunction($route_link_function);

        }

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

            $this->_cache_dir = $directory;
            $old_base_dir     = null;

            if (!is_null($this->_base_dir))
            {
                $old_base_dir    = $this->_base_dir;
                $this->_base_dir = null;
            }

            if (!is_null($old_base_dir))
            {
                $this->addBaseDirectory($old_base_dir);
            }

            $this->_init();

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

            if (is_null($this->_base_dir))
            {

                $this->_base_dir = $directory;

                $this->_init();

            }

            else if (!is_null($this->_filesystem))
            {
                $this->_filesystem->addPath($directory);
            }

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
     * @throws EngineNotInitialisedException if Twig has not been initialised
     */
    public function render($filename = '', array $variables = [], $return = false)
    {

        if (is_null($this->_engine))
        {
            throw new EngineNotInitialisedException('Twig has not been initialised');
        }

        $result = $this->_engine->render($filename, $variables);

        if (!$return)
        {

            echo $result;

            return null;

        }

        return $result;

    }


}