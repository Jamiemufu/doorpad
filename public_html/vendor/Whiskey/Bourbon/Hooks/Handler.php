<?php


namespace Whiskey\Bourbon\Hooks;


use Closure;
use Whiskey\Bourbon\Exception\Hooks\InvalidNameClosureException;


/**
 * Hooks Handler class
 * @package Whiskey\Bourbon\Hooks
 */
class Handler
{


    protected $listeners = [];


    /**
     * Add an event listener
     * @param string  $event_name Event name
     * @param Closure $closure    Closure to be executed
     * @throws InvalidNameClosureException if the event name or closure are not valid
     */
    public function addListener($event_name = '', Closure $closure)
    {

        if ($event_name != '' AND
            (is_object($closure) AND ($closure instanceof Closure)))
        {
            $this->listeners[$event_name][] = $closure;
        }

        else
        {
            throw new InvalidNameClosureException('Invalid event name or listener closure');
        }

    }


    /**
     * Get all hooks for a given event
     * @param  int|string $event_name Hook type to retrieve
     * @return array                  Array of hook closures
     */
    public function get($event_name = '')
    {

        if (isset($this->listeners[$event_name]))
        {
            return $this->listeners[$event_name];
        }

        return [];

    }


    /**
     * Trigger stored listener events
     * @param string $event_name Event name
     * @param mixed  ...         Additional arguments to pass to closure
     */
    public function broadcast($event_name = '')
    {

        $arguments  = func_get_args();
        $event_name = array_shift($arguments);

        if (isset($this->listeners[$event_name]))
        {

            foreach ($this->listeners[$event_name] as $closure)
            {
                call_user_func_array($closure, $arguments);
            }

        }

    }


}