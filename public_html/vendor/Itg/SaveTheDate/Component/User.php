<?php


namespace Itg\SaveTheDate\Component;


use Whiskey\Bourbon\App\Facade\Db;
use Whiskey\Bourbon\App\Facade\Crypt;


/**
 * User class
 * @package Itg\SaveTheDate\Component
 */
class User extends Template
{


    protected static $_table = '_std_users';


    /**
     * Set up the database table (proxy method)
     */
    protected function _setUp()
    {

        static::_setUpDatabase();

    }


    /**
     * Set up the database table
     */
    protected static function _setUpDatabase()
    {

        Db::buildSchema()->table(static::$_table)
                         ->autoId()
                         ->varChar('name')
                         ->varChar('email')
                         ->varChar('code')
                         ->timestamp()
                         ->create();

    }


    /**
     * Create a new record
     * @return self New Template object
     */
    public static function create()
    {

        static::_setUpDatabase();

        $code = sha1(Crypt::encrypt(microtime(true)));
        $id   = Db::build()->table(static::$_table)->data('code', $code)->insert();

        return (new static($id));

    }


    /**
     * Get a User object from a code
     * @param  string $code Code value
     * @return User         User object
     */
    public static function getByCode($code = '')
    {

        static::_setUpDatabase();

        $id = Db::build()->table(static::$_table)->where('code', $code)->getField('id');

        return (new static($id));

    }


}