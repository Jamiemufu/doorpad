<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Tracking façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Tracking extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Helper\Tracking::class;

    }


}