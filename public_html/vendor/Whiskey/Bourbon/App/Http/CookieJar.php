<?php


namespace Whiskey\Bourbon\App\Http;


use stdClass;
use ArrayObject;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;
use Whiskey\Bourbon\Helper\Component\SafeString;
use Whiskey\Bourbon\Storage\Cookie;


/**
 * CookieJar class
 * @package Whiskey\Bourbon\App\Http
 */
class CookieJar extends ArrayObject implements IteratorAggregate
{


    protected $_dependencies = null;
    protected $_array        = [];


    /**
     * Instantiate the CookieJar object
     * @param SafeString $safe_string SafeString object
     * @param Cookie     $cookie      Cookie object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(SafeString $safe_string, Cookie $cookie)
    {

        if (!isset($safe_string) OR
            !isset($cookie))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies              = new stdClass();
        $this->_dependencies->safe_string = $safe_string;
        $this->_dependencies->cookie      = $cookie;

    }


    /**
     * Get (and store) a cookie, if it exists
     * @param  string     $key Name of cookie
     * @return mixed|null      The cookie's (sanitised) value (or NULL, if it does not exist)
     */
    public function offsetGet($key = '')
    {

        if (!isset($this->_array[$key]))
        {
            $safe_string        = $this->_dependencies->safe_string;
            $cookie             = $this->_dependencies->cookie;
            $value              = $cookie->isValid($key) ? $cookie->read($key) : (isset($_COOKIE[$key]) ? $_COOKIE[$key] : null);
            $this->_array[$key] = $safe_string->sanitise($value);
        }

        return $this->_array[$key];

    }


    /**
     * Method to iterate through object as an array
     * @return ArrayIterator ArrayIterator object
     */
    public function getIterator()
    {

        /*
         * Ensure that all cookies have been read and decrypted
         */
        foreach ($_COOKIE as $key => $value)
        {
            $this->offsetGet($key);
        }

        return new ArrayIterator($this->_array);

    }


}