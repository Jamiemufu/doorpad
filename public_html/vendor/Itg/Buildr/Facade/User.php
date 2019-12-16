<?php


namespace Itg\Buildr\Facade;


use Whiskey\Bourbon\Instance;


/**
 * User class
 * @package Itg\Buildr\Facade
 */
class User extends Instance
{


    const ROLE_SUPER_USER     = 0;
    const ROLE_ADMIN          = 1;
    const ROLE_USER           = 2;
    const PASSWORD_MIN_LENGTH = 6;


    public static function _getTarget()
    {

        return \Itg\Buildr\User\User::class;

    }


}