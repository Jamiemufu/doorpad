<?php


namespace Whiskey\Bourbon;


use ReflectionClass;
use ReflectionMethod;
use Exception;
use Whiskey\Bourbon\Exception\Instance\InvalidArgumentsException;
use Whiskey\Bourbon\Exception\Instance\InvalidDependencyException;
use Whiskey\Bourbon\Exception\Instance\NonpublicMethodCalledException;
use Whiskey\Bourbon\Hooks\Handler as Hooks;
use Whiskey\Bourbon\Config\Collection as ConfigCollection;


/**
 * Instance class
 * @package Whiskey\Bourbon
 */
class Instance
{


    protected static $_instances = [];
    protected static $_no_reuse  = [];


    /**
     * Normalise a class name
     * @param  string $class_name Fully-qualified class name
     * @return string             Normalised class name
     */
    protected static function _normaliseClassName($class_name = '')
    {

        return '\\' . ltrim($class_name, '\\');

    }


    /**
     * Register a class so that it will not be stored upon instantiation
     * @param string $instance_class Fully-qualified class name
     */
    public static function _registerNoReuse($instance_class = '')
    {
        
        $instance_class = self::_normaliseClassName($instance_class);

        if (!in_array(strtolower($instance_class), self::$_no_reuse))
        {
            self::$_no_reuse[] = strtolower($instance_class);
        }

    }


    /**
     * Check whether a class will be stored for reuse upon instantiation
     * @param  string $instance_class Fully-qualified class name
     * @return bool                   Whether or not the class will be stored for reuse
     */
    protected static function _isReused($instance_class = '')
    {
        
        $instance_class = self::_normaliseClassName($instance_class);

        if ((defined($instance_class . '::_NO_REUSE') AND $instance_class::_NO_REUSE) OR
            in_array(strtolower($instance_class), self::$_no_reuse))
        {
            return false;
        }

        return true;

    }
    
    
    /**
     * Manually register a class
     * @param object $class Class instance
     */
    public static function _manualClassRegister($class = null)
    {
        
        $name = get_class($class);
        $name = self::_normaliseClassName($name);
        $name = strtolower($name);
        
        if (!isset(self::$_instances[$name]))
        {
            self::$_instances[$name] = $class;
        }
        
    }


    /**
     * Catch static method calls
     * @param  string $name      Method name
     * @param  array  $arguments Method arguments
     * @return mixed             Return value of method
     * @throws NonpublicMethodCalledException if the called method is not public
     */
    public static function __callStatic($name = '', array $arguments = [])
    {

        /*
         * Detect which class was called and which class lies behind the façade
         */
        $is_public      = true;
        $class          = get_called_class();
        $instance_class = $class::_getTarget();
        $instance_class = self::_normaliseClassName($instance_class);

        /*
         * Create and store an instance of the class if necessary
         */
        if (!isset(self::$_instances[strtolower($instance_class)]) OR
            !self::_isReused($instance_class))
        {
            $instance = self::_instantiate($instance_class);
        }

        /*
         * If the instance is reused, fetch the stored version
         */
        if (self::_isReused($instance_class))
        {
            $instance = self::$_instances[strtolower($instance_class)];
        }

        try
        {

            $reflection = new ReflectionMethod($instance_class, $name);

            if (!$reflection->isPublic())
            {
                $is_public = false;
            }

            /*
             * Get the expected parameters and inject instantiated objects into
             * them where necessary
             */
            $reflected_arguments = $reflection->getParameters();
            $compiled_arguments  = [];

            foreach ($reflected_arguments as $reflected_argument)
            {

                /*
                 * First inject instantiable objects (unless one has been
                 * passed as an actual argument, which can be used directly)
                 */
                if (method_exists($reflected_argument, 'getClass') AND
                    isset($reflected_argument->getClass()->name) AND
                    ($dependency_class_name = $reflected_argument->getClass()->name) != '' AND
                    !is_a(reset($arguments), $dependency_class_name))
                {
                    $dependency_class_name = self::_normaliseClassName($dependency_class_name);
                    $compiled_arguments[]  = self::_retrieve($dependency_class_name);
                }

                /*
                 * Then inject regular (or manually-passed instantiated object)
                 * arguments
                 */
                else if (count($arguments))
                {
                    $compiled_arguments[] = array_shift($arguments);
                }

                /*
                 * Finally, look at arguments that haven't been provided but
                 * which do have a default value set
                 */
                else
                {

                    try
                    {
                        $compiled_arguments[] = $reflected_argument->getDefaultValue();
                    }

                    catch (Exception $exception) {}

                }

            }

            /*
             * Append any leftover arguments
             */
            foreach ($arguments as $argument)
            {
                $compiled_arguments[] = array_shift($arguments);
            }

            /*
             * Replace the original arguments array with the compiled array
             */
            $arguments = $compiled_arguments;

        }

        catch (Exception $exception) {}

        if (!$is_public)
        {
            throw new NonpublicMethodCalledException('Call to non-public method \'' . $instance_class . '::' . $name . '()\'');
        }

        /*
         * Call the requested method, passing arguments and the return value
         * through any registered hooks
         */
        $original_arguments = $arguments;
        $arguments          = self::_runBeforeActions($class, $name, $arguments);
        $result             = call_user_func_array([$instance, $name], $arguments);
        $result             = self::_runAfterActions($class, $name, $result, $original_arguments);

        return $result;

    }


    /**
     * Retrieve an instance of the Hooks Handler class
     * @return Hooks Hooks Handler instance
     */
    protected static function _getHooksHandler()
    {
        
        $hooks_class = self::_normaliseClassName(Hooks::class);

        if (!isset(self::$_instances[strtolower($hooks_class)]))
        {
            self::_instantiate($hooks_class);
        }

        return self::$_instances[strtolower($hooks_class)];

    }


    /**
     * Retrieve hooks for a method
     * @param  string $class    Fully-qualified class name
     * @param  string $name     Method name
     * @param  string $position Whether the hooks are to be executed before or after the method call
     * @return array            Array of hooks
     */
    protected static function _getAlterHooks($class = '', $name = '', $position = 'before')
    {

        $hooks_instance = self::_getHooksHandler();
        $hook_name      = $position . ':\\' . ltrim($class, '\\') . '::' . $name;
        $hooks          = $hooks_instance->get($hook_name);

        return $hooks;

    }


    /**
     * Intercept hooked method arguments and execute any registered closures
     * @param  string $class     Fully-qualified class name
     * @param  string $name      Method name
     * @param  array  $arguments Array of arguments
     * @return array             Array of altered arguments
     */
    protected static function _runBeforeActions($class = '', $name = '', array $arguments = [])
    {

        $hooks = self::_getAlterHooks($class, $name, 'before');

        foreach ($hooks as $closure)
        {
            $arguments = call_user_func_array($closure, [$arguments]);
        }

        return $arguments;

    }


    /**
     * Intercept hooked method output and execute any registered closures
     * @param  string $class     Fully-qualified class name
     * @param  string $name      Method name
     * @param  mixed  $result    Output from method
     * @param  array  $arguments Array of original arguments
     * @return mixed             Altered method output
     */
    protected static function _runAfterActions($class = '', $name = '', $result = null, array $arguments = [])
    {

        $hooks = self::_getAlterHooks($class, $name, 'after');

        foreach ($hooks as $closure)
        {
            $result = call_user_func_array($closure, [$result, $arguments]);
        }

        return $result;

    }


    /**
     * Get an instantiated object using the façade class's _getTarget() method
     * @return object Instantiated object
     */
    public static function _getInstance()
    {

        $class          = get_called_class();
        $instance_class = $class::_getTarget();

        return self::_retrieve($instance_class);

    }


    /**
     * Instantiate an Instance class
     * @param  string $instance_class Fully-qualified class name
     * @return object                 Class instance
     * @throws InvalidDependencyException if a requested dependency is not valid
     * @throws InvalidArgumentsException if invalid constructor arguments are passed
     */
    protected static function _instantiate($instance_class = '')
    {

        $instance_class = self::_normaliseClassName($instance_class);

        /*
         * Get the dependencies by inspecting the typehint and/or the default
         * values of the constructor
         */
        $dependencies = [];
        $reflection   = new ReflectionClass($instance_class);
        $constructor  = $reflection->getConstructor();
        $arguments    = $constructor ? $constructor->getParameters() : [];

        foreach ($arguments as $argument)
        {

            /*
             * First fetch instantiable objects
             */
            if (method_exists($argument, 'getClass') AND
                isset($argument->getClass()->name) AND
                ($dependency_class_name = $argument->getClass()->name) != '')
            {

                $dependency_class_name = self::_normaliseClassName($dependency_class_name);

                if (class_exists($dependency_class_name))
                {

                    $dependencies[] = ['type'  => 'class',
                                       'value' => $dependency_class_name];

                }

                else
                {
                    throw new InvalidDependencyException('Invalid dependency \'' . $dependency_class_name . '\' for instance class \'' . $instance_class . '\'');
                }

            }

            /*
             * Then fetch default values
             */
            else if ($argument->isDefaultValueAvailable())
            {

                $dependencies[] = ['type'  => 'value',
                                   'value' => $argument->getDefaultValue()];

            }

            else
            {
                throw new InvalidArgumentsException('Invalid constructor arguments for instance class \'' . $instance_class . '\'');
            }

        }

        /*
         * Ensure that an instance of each dependency is available
         */
        foreach ($dependencies as $var => $value)
        {

            /*
             * First get default values
             */
            if ($value['type'] == 'value')
            {
                $dependencies[$var] = $value['value'];
            }

            /*
             * Then get instantiable objects
             */
            else
            {

                $class_name = $value['value'];

                if (!isset(self::$_instances[strtolower($class_name)]))
                {
                    $dependency_instance = self::_instantiate($class_name);
                }

                /*
                 * Replace the dependency class name with the actual instance
                 */
                $dependencies[$var] = isset(self::$_instances[strtolower($class_name)]) ? self::$_instances[strtolower($class_name)] : $dependency_instance;

            }

        }

        /*
         * Instantiate the object and inject all dependencies
         */
        $reflection = new ReflectionClass($instance_class);
        $instance   = $reflection->newInstanceArgs($dependencies);

        /*
         * Apply any configuration settings
         */
        if (get_class($instance) != ConfigCollection::class)
        {

            $configuration = self::_retrieve(ConfigCollection::class);
            $configs       = $configuration->get('engines');

            foreach ($configs as $config)
            {

                foreach ($config->getConfigValues(get_class($instance)) as $method_name => $method_calls)
                {

                    foreach ($method_calls as $arguments)
                    {
                        call_user_func_array([$instance, $method_name], $arguments);
                    }

                }

            }

        }

        /*
         * If a fresh instance is required, return it
         */
        if (!self::_isReused($instance_class))
        {
            return $instance;
        }

        /*
         * Otherwise store the instance so it can be used as a dependency
         * elsewhere
         */
        self::$_instances[strtolower($instance_class)] = $instance;

        return self::$_instances[strtolower($instance_class)];

    }


    /**
     * Return the stored instance of a class
     * @param  string $class_name Fully-qualified class name
     * @return object             Instance object
     */
    public static function _retrieve($class_name = '')
    {

        $class_name = self::_normaliseClassName($class_name);

        if (!isset(self::$_instances[strtolower($class_name)]) OR
            !self::_isReused($class_name))
        {
            $instance = self::_instantiate($class_name);
        }

        /*
         * If a fresh instance is required, return it
         */
        if (!self::_isReused($class_name))
        {
            return $instance;
        }

        return self::$_instances[strtolower($class_name)];

    }


    /**
     * Check whether an instance of a class has been stored
     * @param  string $class_name Fully-qualified class name
     * @return bool               Whether an instance of the class has been instantiated
     */
    public static function _hasStoredVersion($class_name = '')
    {

        $class_name = self::_normaliseClassName($class_name);

        return isset(self::$_instances[strtolower($class_name)]);

    }


}