<?php

namespace Itg\Cms\Http\Model;


use Exception;
use Whiskey\Bourbon\App\Facade\Session;
use Whiskey\Bourbon\App\Http\MainModel;
use Itg\Buildr\Facade\Me;
use Itg\Buildr\Facade\User;


/**
 * AccountModel class
 * @package Whiskey\Bourbon\App\Http\Model
 */
class AccountModel extends MainModel
{


    /**
     * Check whether a user is viewable by the current user
     * @param  int                        $id User ID to check
     * @return \Itg\Buildr\User\User|bool     User object, or FALSE on fail
     */
    public function checkViewableUser($id = 0)
    {

        try
        {

            $user = User::get($id);

            if (Me::canInteractWith($user))
            {
                return $user;
            }

        }

        catch (Exception $exception) {}

        return false;

    }


    /**
     * Attempt to log a user in
     * @param  string $username Username
     * @param  string $password Password
     * @return bool             Whether the login attempt was successful
     */
    public function attemptLogin($username = '', $password = '')
    {

        Session::clear('last_logged_in_user_id');

        if (!is_null($username) AND !is_null($password))
        {

            /*
             * Get a User object to work with
             */
            try
            {
                $user = User::getByUsername($username);
            }

            catch (Exception $exception)
            {
                return false;
            }

            /*
             * See if the password is valid
             */
            $login_check = $user->checkPassword($password);

            if ($login_check)
            {

                /*
                 * Rehash (and update) the password if a better hashing
                 * algorithm is now in use
                 */
                $user->rehashPassword($password);

                /*
                 * Try to log the user in
                 */
                $login_success = $user->performLogin();

                if ($login_success)
                {

                    Session::write('last_logged_in_user_id', $user->getId());

                    return true;

                }

            }

        }

        return false;

    }


    /**
     * Check to see if a user is related to the logged-in user
     * @param  int   $id       User ID
     * @param  array $excluded Users to exclude from consideration
     * @return bool            Whether the user is related to the logged in user
     */
    public function isUserRelated($id = 0, array $excluded = [])
    {

        try
        {

            $user = User::get($id);

            if (($user->getRole() > Me::getRole()) OR
                ($user->getParentId() == Me::getId()) OR
                ($user->getId() == Me::getId()))
            {

                if (!in_array($user->getId(), $excluded))
                {
                    return true;
                }

            }

        }

        catch (Exception $exception) {}

        return false;

    }


}