<?php


namespace Whiskey\Bourbon\Helper;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Storage\Cookie;


/**
 * Tracking class
 * @package Whiskey\Bourbon\Helper
 */
class Tracking
{


    protected $_dependencies    = null;
    protected $_cookie_duration = 157680000;
    protected $_user_id_key     = 'u_id';
    protected $_auto_id_key     = 'a_id';
    protected $_user_id         = '';
    protected $_auto_id         = '';


    /**
     * Instantiate a Tracking object and determine IDs from the environment
     * @param Cookie $cookie Cookie object
     * @param Utils  $utils  Utils object
     * @param Input  $input  Input object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Cookie $cookie, Utils $utils, Input $input)
    {

        if (!isset($cookie) OR
            !isset($utils) OR
            !isset($input))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies         = new stdClass();
        $this->_dependencies->cookie = $cookie;
        $this->_dependencies->utils  = $utils;
        $this->_dependencies->input  = $input;

        /*
         * Get any values that have already been set
         */
        $auto_id_cookie = $this->_dependencies->cookie->read($this->_auto_id_key);
        $user_id_cookie = $this->_dependencies->cookie->read($this->_user_id_key);
        $user_id_query  = $this->_dependencies->input->get($this->_user_id_key, false);

        /*
         * If an auto ID is in a cookie
         */
        if (!is_null($auto_id_cookie))
        {
            $this->_auto_id = $auto_id_cookie;
        }

        /*
         * Otherwise create a new auto ID
         */
        else
        {
            $this->_auto_id = hash('sha256', microtime(true) . $this->_dependencies->utils->random());
            $this->_dependencies->cookie->write($this->_auto_id_key, $this->_auto_id, $this->_cookie_duration);
        }

        /*
         * If a user ID is in the query string
         */
        if (!is_null($user_id_query))
        {

            /*
             * If a cookie-based user ID has not yet been set
             */
            if (is_null($user_id_cookie))
            {
                $this->_user_id = $user_id_query;
                $this->_dependencies->cookie->write($this->_user_id_key, $user_id_query, $this->_cookie_duration);
            }

            /*
             * Otherwise use the user ID from the cookie
             */
            else
            {
                $this->_user_id = $user_id_cookie;
            }

        }

        /*
         * Otherwise check if a user ID has been set in a cookie
         */
        else if (!is_null($user_id_cookie))
        {
            $this->_user_id = $user_id_cookie;
        }

    }


    /**
     * Get the user ID
     * @return string User ID
     */
    public function getUserId()
    {

        return $this->_user_id;

    }


    /**
     * Get the auto ID
     * @return string Auto ID
     */
    public function getAutoId()
    {

        return $this->_auto_id;

    }


}