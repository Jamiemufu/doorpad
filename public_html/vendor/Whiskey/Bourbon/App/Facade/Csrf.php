<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Csrf façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Csrf extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Security\Csrf::class;

    }


}