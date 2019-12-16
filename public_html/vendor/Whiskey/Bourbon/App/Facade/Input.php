<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Input façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Input extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Helper\Input::class;

    }


}