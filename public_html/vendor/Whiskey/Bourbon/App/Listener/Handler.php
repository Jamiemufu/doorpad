<?php


namespace Whiskey\Bourbon\App\Listener;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Hooks\Handler as Hooks;


/**
 * Listener Handler class
 * @package Whiskey\Bourbon\App\Listener
 */
class Handler
{


    protected $_directory    = null;
    protected $_dependencies = null;


    /**
     * Instantiate the listener Handler object
     * @param Instance $instance_container Instance object
     * @param Hooks    $hooks              Hooks object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Instance $instance_container, Hooks $hooks)
    {

        if (!isset($instance_container) OR
            !isset($hooks))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies                     = new stdClass();
        $this->_dependencies->instance_container = $instance_container;
        $this->_dependencies->hooks              = $hooks;

    }


    /**
     * Set the listener directory
     * @param  string $directory Path to listener directory
     * @return bool              Whether the listener directory was successfully set
     */
    public function setDirectory($directory = null)
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_null($directory) AND
            is_readable($directory) AND
            is_writable($directory))
        {

            $this->_directory = $directory;

            $this->_init();

            return true;

        }

        return false;

    }


    /**
     * Set up the listener directory
     * @return bool Whether the listener was successfully set up
     */
    protected function _init()
    {

        /*
         * Fail immediately if the directory has not been set
         */
        if (is_null($this->_directory))
        {
            return false;
        }

        if (!$this->isActive())
        {

            /*
             * Directory check
             */
            if (!is_readable($this->_directory))
            {
                mkdir($this->_directory);
                file_put_contents($this->_directory . 'index.html', '');
            }

        }

        /*
         * Final check after all of the above
         */
        if ($this->isActive())
        {
            return true;
        }

        return false;

    }


    /**
     * Check if the listener directory has been set up
     * @return bool Whether the listener directory has been set up
     */
    public function isActive()
    {

        /*
         * Check if directory exists and is writable
         */
        if (!is_null($this->_directory) AND
            is_readable($this->_directory) AND
            is_dir($this->_directory))
        {
            return true;
        }

        return false;

    }


    /**
     * Get all listeners
     * @return array Array of Listener objects
     * @throws EngineNotInitialisedException if listeners are not enabled
     */
    public function getAll()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Listener not enabled');
        }

        $listeners = [];

        if (is_readable($this->_directory))
        {

            /*
             * Retrieve a list of all files in the listener directory
             */
            $files = scandir($this->_directory);

            foreach ($files as $value)
            {

                /*
                 * Determine the fully-qualified class name of each file if it
                 * is a .php file
                 */
                if (!is_dir($value) AND mb_substr($value, -4) == '.php')
                {

                    $short_listener_class_name = explode('.', $value);
                    $short_listener_class_name = array_shift($short_listener_class_name);
                    $long_listener_class_name  = trim(__NAMESPACE__, '\\') . '\\' . $short_listener_class_name;

                    /*
                     * Instantiate the listener class and add it to an array to
                     * be returned
                     */
                    $listener    = $this->_dependencies->instance_container->_retrieve($long_listener_class_name);
                    $listeners[] = $listener;

                }

            }

        }

        return $listeners;

    }


    /**
     * Register all listeners with the Hooks engine
     * @throws EngineNotInitialisedException if listeners are not enabled
     */
    public function registerHooks()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Listener not enabled');
        }

        $listeners = $this->getAll();
        $hooks     = $this->_dependencies->hooks;

        foreach ($listeners as $listener)
        {
            $hooks->addListener($listener->getHook(), function() use ($listener)
            {
                call_user_func_array([$listener, 'run'], func_get_args());
            });
        }

    }


}