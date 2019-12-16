<?php


namespace Itg\SaveTheDate;


use InvalidArgumentException;
use Itg\SaveTheDate\Component\User;
use Itg\SaveTheDate\Component\Option;


/**
 * Handler class
 * @package Itg\SaveTheDate
 */
class Handler
{


    const TYPE_LIST = 'list';
    const TYPE_TEXT = 'text';
    const TYPE_BOTH = 'both';


    protected static $_types   = [];
    protected static $_options = [];


    /**
     * Specify an option
     * @param string $name   Name of option
     * @param array  $values Possible option values
     * @param string $type   What type the option should be (list, text or both)
     * @throws InvalidArgumentException if an option name is not provided
     * @throws InvalidArgumentException if option values are not provided
     * @throws InvalidArgumentException if the option type is invalid
     */
    public static function addOption($name = '', array $values = [], $type = 'list')
    {

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('Option name not provided');
        }

        if (empty($values))
        {
            throw new InvalidArgumentException('Option values not provided');
        }

        if (!in_array(strtolower($type), [static::TYPE_LIST, static::TYPE_TEXT, static::TYPE_BOTH]))
        {
            throw new InvalidArgumentException('Invalid option type');
        }

        static::$_types[$name]   = strtolower($type);
        static::$_options[$name] = $values;

    }


    /**
     * Get a multidimensional array of options and their values
     * @return array Multidimensional array of options and values
     */
    public static function getOptions()
    {

        return static::$_options;

    }


    /**
     * Get the type of an option
     * @param  string $name Name of option
     * @return string       Option type
     * @throws InvalidArgumentException if the name has not been provided
     * @throws InvalidArgumentException if the option does not exist
     */
    public static function getOptionType($name = '')
    {

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('Option name not provided');
        }

        if (!isset(static::$_types[$name]))
        {
            throw new InvalidArgumentException('Option \'' . $name . '\' does not exist');
        }

        return static::$_types[$name];

    }


    /**
     * Get the values for an option
     * @param  string $name Name of option
     * @return array        Array of option values
     * @throws InvalidArgumentException if the name has not been provided
     * @throws InvalidArgumentException if the option does not exist
     */
    public static function getOptionValues($name = '')
    {

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('Option name not provided');
        }

        if (!isset(static::$_options[$name]))
        {
            throw new InvalidArgumentException('Option \'' . $name . '\' does not exist');
        }

        return static::$_options[$name];

    }


    /**
     * Check whether an option value is valid
     * @param  string $name  Option name
     * @param  string $value Value to check
     * @return bool          Whether the value is valid for the option
     * @throws InvalidArgumentException if the name has not been provided
     * @throws InvalidArgumentException if the option does not exist
     */
    public static function isValidOptionValue($name = '', $value = '')
    {

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('Option name not provided');
        }

        if (!isset(static::$_options[$name]))
        {
            throw new InvalidArgumentException('Option \'' . $name . '\' does not exist');
        }

        $values = static::getOptionValues($name);
        $type   = static::getOptionType($name);

        if (in_array($value, $values) OR  // From list
            $type == static::TYPE_TEXT OR // Free text
            $type == static::TYPE_BOTH)   // Either of the above
        {
            return true;
        }

        return false;

    }


    /**
     * Create a new user
     * @param  string $name  User name
     * @param  string $email User e-mail address
     * @return User          User object
     * @throws InvalidArgumentException if a user name has not been provided
     * @throws InvalidArgumentException if the user's e-mail address is not valid
     */
    public static function addUser($name = '', $email = '')
    {

        if ((string)$name === '')
        {
            throw new InvalidArgumentException('User name not provided');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new InvalidArgumentException('Invalid e-mail address provided');
        }

        $user        = User::create();
        $user->name  = $name;
        $user->email = $email;

        return $user;

    }


    /**
     * Get option selections for a user
     * @param  User  $user User object
     * @return array       Array of option selections
     * @throws InvalidArgumentException if the user is not provided
     */
    public function getSelectionsForUser(User $user)
    {

        if (!($user instanceof User))
        {
            throw new InvalidArgumentException('User not provided');
        }

        $all_options  = static::getOptions();
        $user_options = Option::getByUser($user);
        $result       = [];

        foreach ($all_options as $option)
        {
            $result[$option] = '';
        }

        foreach ($user_options as $option)
        {
            $all_options[$option->name] = $option->value;
        }

        return $all_options;

    }


}