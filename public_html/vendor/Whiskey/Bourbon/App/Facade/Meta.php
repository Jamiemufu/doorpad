<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Meta façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Meta extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Storage\Meta\Handler::class;

    }


}