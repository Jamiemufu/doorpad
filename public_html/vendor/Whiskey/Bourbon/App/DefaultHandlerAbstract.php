<?php


namespace Whiskey\Bourbon\App;


use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\EngineNotRegisteredException;
use Whiskey\Bourbon\Instance;


/**
 * DefaultHandlerAbstract class
 * @package Whiskey\Bourbon\App
 */
abstract class DefaultHandlerAbstract
{


    protected $_engines        = [];
    protected $_default_engine = '';
    protected $_type_hint      = '';


    /**
     * Register an engine
     * @param string|object $engine_class Fully-qualified engine class name or instance
     * @throws InvalidArgumentException if the engine is not the correct type
     */
    public function registerEngine($engine_class = '')
    {

        /*
         * Check that the engine is the correct type, if the handler has defined
         * one
         */
        if ($this->_type_hint != '')
        {

            if (!is_subclass_of($engine_class, $this->_type_hint))
            {
                throw new InvalidArgumentException('Engine is not the correct type');
            }

        }

        $name             = is_string($engine_class) ? '' : $engine_class->getName();
        $this->_engines[] = ['name' => $name, 'engine' => $engine_class];

    }


    /**
     * Instantiate an engine and return a package of the instance and engine name
     * @param  string $class_name Fully-qualified engine class name
     * @return array              Array of engine name and instance
     */
    protected function _instantiateEngine($class_name = '')
    {

        /*
         * Retrieve the engine instance object
         */
        $engine         = Instance::_retrieve($class_name);
        $name           = strtolower($engine->getName());
        $engine_details = ['name' => $name, 'engine' => $engine];

        /*
         * Set it as the default engine if one has not yet been set (and the
         * engine in question is active)
         */
        if ($this->_default_engine == '' AND
            $engine->isActive())
        {
            $this->_default_engine = $name;
        }

        return $engine_details;

    }


    /**
     * Get the registered engine instances
     * @return array Array of engine details and instances
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
     * Retrieve an engine object for direct use
     * @param  string $name Name of engine
     * @return object       Engine instance object
     * @throws EngineNotRegisteredException if the requested engine has not been registered
     */
    public function getEngine($name = '')
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

        throw new EngineNotRegisteredException('Engine \'' . $name . '\' has not been registered');

    }


    /**
     * Set the default engine
     * @param string $name Name of engine
     * @throws EngineNotRegisteredException if the requested engine is not valid
     * @throws EngineNotInitialisedException if the requested engine has not been initialised
     */
    public function setDefaultEngine($name = '')
    {

        $name = strtolower($name);

        /*
         * Instantiate the engine and set it as the default if it is active
         */
        try
        {

            if ($this->getEngine($name)->isActive())
            {
                $this->_default_engine = $name;
            }

        }

        catch (Exception $exception)
        {
            throw new EngineNotRegisteredException('Invalid engine', 0, $exception);
        }

        /*
         * If an exception was not caught above then the engine existed but did
         * not describe itself as being active
         */
        if ($this->_default_engine != $name)
        {
            throw new EngineNotInitialisedException('Engine has not been initialised');
        }

    }


    /**
     * Get the default engine object
     * @return object Engine instance object
     * @throws EngineNotInitialisedException if no default (active) engines have been registered
     */
    public function getDefaultEngine()
    {

        /*
         * See if a default engine has already been set
         */
        if ($this->_default_engine != '')
        {
            return $this->getEngine($this->_default_engine);
        }

        /*
         * Instantiate engines until one of them is active
         */
        foreach ($this->_engines as &$engine_details)
        {

            if (is_string($engine_details['engine']))
            {
                $engine_details = $this->_instantiateEngine($engine_details['engine']);
            }

            if ($engine_details['engine']->isActive())
            {
                return $engine_details['engine'];
            }

        }

        throw new EngineNotInitialisedException('No active engines found');

    }


    /**
     * Handle calls to the default engine
     * @param  string $name      Method name
     * @param  array  $arguments Method arguments
     * @return mixed             Result of method call
     */
    public function __call($name = '', array $arguments = [])
    {

        /*
         * Return a specific engine, if it is called for
         */
        $engine_name = strtolower($name);

        try
        {
            return $this->getEngine($engine_name);
        }

        /*
         * In all other instances, pass the call on to the default engine
         */
        catch (Exception $exception)
        {

            $default_engine = $this->getDefaultEngine();

            return call_user_func_array([$default_engine, $name], $arguments);

        }

    }


}