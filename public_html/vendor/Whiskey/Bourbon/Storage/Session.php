<?php


namespace Whiskey\Bourbon\Storage;


use Closure;
use InvalidArgumentException;


/**
 * Session class
 * @package Whiskey\Bourbon\Storage
 */
class Session implements DataStorageInterface
{


    /**
     * Instantiate and set up the session
     */
    public function __construct()
    {

        $this->create();

    }


    /**
     * Check to see if a session is active and start one if not
     * @return bool Whether the session was successfully started
     */
    public function create()
    {

        if (session_status() == PHP_SESSION_NONE)
        {
            
            if (session_start())
            {
                return true;
            }

        }

        return false;

    }


    /**
     * Unset all session variables and destroy the active session
     * @return bool Whether the session was successfully destroyed
     */
    public function destroy()
    {

        if (session_destroy())
        {

            foreach ($_SESSION as $var => $value)
            {
                $this->clear($var);
            }

            return true;

        }

        return false;

    }


    /**
     * Retrieve a stored session variable
     * @param  string $key Name of session variable
     * @return mixed       Session variable value
     */
    public function read($key = null)
    {

        $this->create();

        $key = (string)$key;
        
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;

    }


    /**
     * Store a session variable
     * @param  string $key   Name of session variable
     * @param  mixed  $value Session variable value
     * @return bool          Whether the session variable was successfully written
     */
    public function write($key = null, $value = '')
    {

        if (!is_null($key) AND (!((string)$key === '')))
        {

            $this->create();

            $key            = (string)$key;
            $_SESSION[$key] = $value;
            
            return true;

        }

        return false;

    }


    /**
     * Retrieve a session variable by its key if it exists, executing and
     * storing the result of a closure if it does not
     * @param  string  $key     Name of session variable
     * @param  Closure $closure Closure to execute to obtain value (if required)
     * @return mixed            Session variable value
     * @throws InvalidArgumentException if the closure is not valid
     */
    public function remember($key = '', Closure $closure)
    {

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid closure passed');
        }

        $result = $this->read($key);

        if (is_null($result))
        {

            $result = $closure();

            $this->write($key, $result);

        }

        return $result;

    }


    /**
     * Unset a session variable
     * @param  string $key Name of session variable
     * @return bool        Whether the session variable was successfully cleared
     */
    public function clear($key = null)
    {

        $this->create();
        
        $key = (string)$key;
        
        if (isset($_SESSION[$key]))
        {

            unset($_SESSION[$key]);

            return true;

        }

        return false;

    }


    /**
     * Unset session variables that begin with a certain string
     * @param  string $key_fragment Initial fragment of session key name
     * @return bool                 Whether the session variables were successfully cleared
     */
    public function prefixClear($key_fragment = null)
    {

        $this->create();

        $key_fragment = (string)$key_fragment;
        
        foreach ($_SESSION as $var => $value)
        {
            if (mb_substr($var, 0, mb_strlen($key_fragment)) == $key_fragment)
            {
                unset($_SESSION[$var]);
            }
        }

        return true;

    }


}