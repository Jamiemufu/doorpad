<?php


namespace Whiskey\Bourbon\App\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Db façade class
 * @package Whiskey\Bourbon\App\Facade
 */
class Db extends Instance
{


    /**
     * Get the façade target class
     * @return string Façade target class
     */
    protected static function _getTarget()
    {

        return \Whiskey\Bourbon\Storage\Database\Mysql\Handler::class;

    }


}