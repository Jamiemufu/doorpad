<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Ice façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Ice extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Templating\Engine\Ice\Loader::class;

    }


}