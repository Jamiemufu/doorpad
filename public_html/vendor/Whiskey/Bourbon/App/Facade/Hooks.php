<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Hooks façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Hooks extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Hooks\Handler::class;

    }


}