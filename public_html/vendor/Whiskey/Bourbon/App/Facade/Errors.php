<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Errors façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Errors extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\ErrorReporting\Handler::class;

    }


}