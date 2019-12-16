<?php


namespace Itg\SaveTheDate\Component;


use InvalidArgumentException;
use Whiskey\Bourbon\App\Facade\Db;


/**
 * Option class
 * @package Itg\SaveTheDate\Component
 */
class Option extends Template
{


    const SINGLE = 'single';
    const MULTI  = 'multi';


    protected static $_table = '_std_options';


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
                         ->bigInt('user_id')
                         ->varChar('name')
                         ->varChar('value')
                         ->timestamp()
                         ->create();

    }


    /**
     * Make a selection
     * @param User   $user  User object
     * @param string $name  Name of option
     * @param string $value Option value
     * @param string $type  Type (whether it will overwrite existing options or add to them)
     * @throws InvalidArgumentException if the user is not provided
     * @throws InvalidArgumentException if the name is not provided
     */
    public static function makeSelection(User $user, $name = '', $value = '1', $type = 'single')
    {

        if (!($user instanceof User))
        {
            throw new InvalidArgumentException('User not provided');
        }

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('Option name not provided');
        }

        static::_setUpDatabase();

        if (strtolower($type) == static::SINGLE)
        {
            self::clearForUser($user, $name);
        }

        Db::build()->table(static::$_table)->data('user_id', $user->id)->data('name', $name)->data('value', $value)->insert();

    }


    /**
     * Clear all options set for a user
     * @param User   $user User object
     * @param string $name Name of option
     * @throws InvalidArgumentException if the user is not provided
     */
    public static function clearForUser(User $user, $name = '')
    {

        if (!($user instanceof User))
        {
            throw new InvalidArgumentException('User not provided');
        }

        static::_setUpDatabase();

        Db::build()->table(static::$_table)->where('user_id', $user->id)->where('name', $name)->delete();

    }


    /**
     * Get all Options selected by a user
     * @param  User  $user User object
     * @return array       Array of Option objects
     * @throws InvalidArgumentException if the user is not provided
     */
    public static function getByUser(User $user)
    {

        if (!($user instanceof User))
        {
            throw new InvalidArgumentException('User not provided');
        }

        static::_setUpDatabase();

        $records = Db::build()->table(static::$_table)->where('id', $user->id)->select();
        $result  = [];

        foreach ($records as $key => $record)
        {
            $result[$key] = new static($record['id']);
        }

        return $result;

    }


}