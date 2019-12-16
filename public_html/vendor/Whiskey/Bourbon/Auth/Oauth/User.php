<?php


namespace Whiskey\Bourbon\Auth\Oauth;


use stdClass;


/**
 * OAuth User class
 * @package Whiskey\Bourbon\Auth\Oauth
 */
class User
{


    const _NO_REUSE = true;


    protected $_network = '';
    protected $_id      = '';
    protected $_name    = '';
    protected $_email   = '';
    protected $_avatar  = '';
    protected $_token   = '';
    protected $_raw     = null;


    /**
     * Instantiate the User object
     * @param string $network Social network
     * @param string $id      User's ID
     * @param string $name    User's name
     * @param string $email   User's e-mail address
     * @param string $avatar  User's avatar
     * @param string $token   User's authorisation token
     * @param object $raw     Raw data object from the provider
     */
    public function __construct($network = '', $id = '', $name = '', $email = '', $avatar = '', $token = '', $raw = null)
    {

        $this->setNetwork($network);
        $this->setId($id);
        $this->setName($name);
        $this->setEmail($email);
        $this->setAvatar($avatar);
        $this->setToken($token);
        $this->setRaw($raw);

    }


    /**
     * Get the social network
     * @return string Social network
     */
    public function getNetwork()
    {

        return $this->_network;

    }


    /**
     * Get the user's ID
     * @return string User's ID
     */
    public function getId()
    {

        return $this->_id;

    }


    /**
     * Get the user's name
     * @return string User's name
     */
    public function getName()
    {

        return $this->_name;

    }


    /**
     * Get the user's email
     * @return string User's email address
     */
    public function getEmail()
    {

        return $this->_email;

    }


    /**
     * Get the user's avatar URL
     * @return string User's avatar URL
     */
    public function getAvatar()
    {

        return $this->_avatar;

    }


    /**
     * Get the user's authorisation token
     * @return string User's authorisation token
     */
    public function getToken()
    {

        return $this->_token;

    }


    /**
     * Get the raw login object details from the provider
     * @return object Object of login details
     */
    public function getRaw()
    {

        return $this->_raw;

    }


    /**
     * Set the social network
     * @param string $network Social network
     */
    public function setNetwork($network = '')
    {

        $this->_network = $network;

    }


    /**
     * Set the user's ID
     * @param string $id User's ID
     */
    public function setId($id = '')
    {

        $this->_id = $id;

    }


    /**
     * Set the user's name
     * @param string $name User's name
     */
    public function setName($name = '')
    {

        $this->_name = $name;

    }


    /**
     * Set the user's email
     * @param string $email User's email
     */
    public function setEmail($email = '')
    {

        $this->_email = $email;

    }


    /**
     * Set the user's avatar
     * @param string $avatar User's avatar
     */
    public function setAvatar($avatar = '')
    {

        $this->_avatar = $avatar;

    }


    /**
     * Set the user's token
     * @param string $token User's token
     */
    public function setToken($token = '')
    {

        $this->_token = $token;

    }


    /**
     * Set the user's raw login data
     * @param object $raw Object of user's raw login data
     */
    public function setRaw($raw = null)
    {

        if (!is_null($raw) AND is_object($raw))
        {
            $this->_raw = $raw;
        }

        else
        {
            $this->_raw = new stdClass();
        }

    }


}