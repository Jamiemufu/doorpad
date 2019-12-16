<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * DbBackup class
 * @package Itg\Buildr\Facade
 */
class DbBackup extends Instance
{


    public static function _getTarget()
    {

        return \Itg\Buildr\Backup\Db::class;

    }


}