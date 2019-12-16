<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Migration façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Migration extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\App\Migration\Handler::class;

    }


}