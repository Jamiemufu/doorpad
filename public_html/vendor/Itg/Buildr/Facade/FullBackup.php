<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * FullBackup class
 * @package Itg\Buildr\Facade
 */
class FullBackup extends Instance
{


    public static function _getTarget()
    {

        return \Itg\Buildr\Backup\Full::class;

    }


}