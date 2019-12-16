<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Me class
 * @package Itg\Buildr\Facade
 */
class Me extends Instance
{


    public static function _getTarget()
    {

        return \Itg\Buildr\User\Me::class;

    }


}