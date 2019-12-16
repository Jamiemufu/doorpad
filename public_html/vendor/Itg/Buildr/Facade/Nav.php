<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * Nav class
 * @package Itg\Buildr\Facade
 */
class Nav extends Instance
{


    public static function _getTarget()
    {

        return \Itg\Buildr\Nav::class;

    }


}