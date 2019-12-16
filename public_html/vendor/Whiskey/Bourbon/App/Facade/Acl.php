<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Acl façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Acl extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Auth\Acl\Handler::class;

    }


}