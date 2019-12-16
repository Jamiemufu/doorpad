<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Auth façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Auth extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Auth\Handler::class;

    }


}