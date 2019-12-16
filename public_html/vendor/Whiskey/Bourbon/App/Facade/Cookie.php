<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Cookie façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Cookie extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Storage\Cookie::class;

    }


}