<?php


namespace Whiskey\Bourbon\App\Listener;


/**
 * Listener template class
 * @package Whiskey\Bourbon\App\Listener
 */
class Listener
{


    const _NO_REUSE = true;


    protected $_hook = '';


    /**
     * Get the name of the hook that the listener responds to
     * @return string Name of hook listened for
     */
    public function getHook()
    {
        return $this->_hook;
    }


    /**
     * Action to be executed when the listened-for event is broadcast
     */
    public function run() {}


}