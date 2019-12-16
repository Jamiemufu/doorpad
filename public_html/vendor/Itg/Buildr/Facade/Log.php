<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Log class
 * @package Itg\Buildr\Facade
 */
class Log extends Instance
{


    public static function _getTarget()
    {

        return \Itg\Buildr\Log\Handler::class;

    }


}