<?php


namespace Whiskey\Bourbon\Security;


use Whiskey\Bourbon\Exception\Security\Crypt\CryptErrorException;


/**
 * Password class
 * @package Whiskey\Bourbon\Security
 */
class Password
{


    protected $_password_hash_cost = 12;
    protected $_password_hash_algo = PASSWORD_DEFAULT;


    /**
     * Create a hashed version of a password
     * @param  string $password Password to hash
     * @return string           Hashed version of the password
     * @throws CryptErrorException if password could not be hashed
     */
    public function hash($password = '')
    {

        $options = ['cost' => $this->_password_hash_cost];
        $result  = password_hash($password, $this->_password_hash_algo, $options);

        if ($result === false)
        {
            throw new CryptErrorException('Could not hash password');
        }

        return $result;

    }


    /**
     * Check whether a password matches a hash
     * @param  string $password Password to check
     * @param  string $hash     Hash to check against
     * @return bool             Whether the password and hash match
     */
    public function check($password = '', $hash = '')
    {

        return password_verify($password, $hash);

    }


    /**
     * Check whether a password hash needs to be recalculated
     * @param  string $hash Hash to check
     * @return bool         Whether the hash needs to be recalculated
     */
    public function needsRehash($hash = '')
    {

        $options = ['cost' => $this->_password_hash_cost];

        return password_needs_rehash($hash, $this->_password_hash_algo, $options);

    }


}