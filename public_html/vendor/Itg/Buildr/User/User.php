<?php


namespace Itg\Buildr\User;


use Exception;
use stdClass;
use Itg\GetterSetterTrait;
use Itg\Buildr\Facade\User as UserFacade;
use Whiskey\Bourbon\Auth\Handler as Auth;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\Security\Password;
use Whiskey\Bourbon\Helper\Utils;


/**
 * User class
 * @package Itg\Buildr\User
 */
class User
{


    use GetterSetterTrait;


    const _NO_REUSE = true;


    protected $_dependencies = null;
    protected $_table        = 'users';
    protected $_user_roles   =
        [
            UserFacade::ROLE_SUPER_USER => 'Super User',
            UserFacade::ROLE_ADMIN      => 'Administrator',
            UserFacade::ROLE_USER       => 'User'
        ];

    
    /**
     * Instantiate a User object
     * @param Auth     $auth     Auth object
     * @param Password $password Password object
     * @param Db       $db       Db object
     * @param Utils    $utils    Utils object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Auth $auth, Password $password, Db $db, Utils $utils)
    {

        if (!isset($auth) OR
            !isset($password) OR
            !isset($db) OR
            !isset($utils))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies = new stdClass();

        $this->_dependencies->auth     = $auth;
        $this->_dependencies->password = $password;
        $this->_dependencies->db       = $db;
        $this->_dependencies->utils    = $utils;

    }


    /**
     * Get an empty User object
     * @return User Empty User object
     */
    public function getEmpty()
    {

        return new self($this->_dependencies->auth,
                        $this->_dependencies->password,
                        $this->_dependencies->db,
                        $this->_dependencies->utils);

    }


    /**
     * Return an instantiated User object
     * @param  int  $id User ID
     * @return self     User object
     */
    public function get($id = 0)
    {

        $this->setId($id);

        return $this;

    }


    /**
     * Check whether a user exists in the database
     * @param  int  $id User ID
     * @return bool     Whether or not the user exists
     */
    public function exists($id = 0)
    {

        $db = $this->_dependencies->db;

        return $db->build()->table($this->_table)
                           ->where('id', $id)
                           ->exists();

    }


    /**
     * Return an instantiated User object by the user name
     * @param  string $username Username of user
     * @return self             User object
     * @throws Exception if the username is not valid
     */
    public function getByUsername($username = '')
    {

        $db = $this->_dependencies->db;

        $user_id = $db->build()->table($this->_table)
                               ->where('username', $username)
                               ->getField('id');

        if (!$user_id)
        {
            throw new Exception('Invalid username');
        }

        return $this->get($user_id);

    }


    /**
     * Set the ID of the user and populate the object with their details
     * @param int $id User ID
     */
    public function setId($id = 0)
    {

        $this->_populateFromDatabase($this->_table, 'id', $id);

    }


    /**
     * Create a new user
     * @param  string $username Username
     * @return User             User object
     * @throws Exception if the user could not be created
     */
    public function create($username = '')
    {

        if ($username == '')
        {
            throw new Exception('Invalid username supplied');
        }

        $db = $this->_dependencies->db;

        $user_exists = $db->build()->table($this->_table)
                                   ->where('username', $username)
                                   ->exists();

        if ($user_exists)
        {
            throw new Exception('Username already in use');
        }

        try
        {

            $id = $db->build()->table($this->_table)
                              ->data('username', $username)
                              ->insert();

            $user = $this->getEmpty();

            $user->setId($id);

            return $user;

        }

        catch (Exception $exception)
        {
            throw new Exception('Error creating user \'' . $username . '\'');
        }

    }


    /**
     * Update the user's password, hashing it as required
     * @param  string $password New (plaintext) password
     * @return bool             Whether or not the password could be updated
     * @throws Exception if invalid password was supplied
     */
    public function updatePassword($password = '')
    {

        if ($password == '')
        {
            throw new Exception('Invalid password supplied');
        }

        $hashed_password = $this->hashPassword($password);

        if ($this->setPassword($hashed_password))
        {
            return true;
        }

        return false;

    }


    /**
     * Create a hashed version of a password
     * @param  string $password Password
     * @return string           Hashed version of password
     */
    public function hashPassword($password = '')
    {

        return $this->_dependencies->password->hash($password);

    }


    /**
     * Check whether a password matches the stored hash
     * @param  string $password Password to check
     * @return bool             Whether the password and hash match
     */
    public function checkPassword($password = '')
    {

        $actual_password_hash = $this->getPassword();

        return $this->_dependencies->password->check($password, $actual_password_hash);

    }


    /**
     * Update the password if the hashing algorithm has changed
     * @param string $password Password to rehash (if necessary)
     */
    public function rehashPassword($password = '')
    {

        $current_password_hash = $this->getPassword();
        $password_needs_rehash = $this->_dependencies->password->needsRehash($current_password_hash);

        if ($password_needs_rehash)
        {

            try
            {
                $this->updatePassword($password);
            }

            catch (Exception $exception) {}

        }

    }


    /**
     * Attempt to log the user in
     * @return bool Whether the user was successfully logged in
     */
    public function performLogin()
    {

        $user_credentials =
            [
                'id'       => $this->getId(),
                'password' => $this->getPassword()
            ];

        $success = $this->_dependencies->auth->logIn($this->_table, $user_credentials);

        return $success;

    }


    /**
     * Log the user out
     */
    public function logOut()
    {

        $this->_dependencies->auth->logOut();

    }


    /**
     * Get the user's role name
     * @return string Name of user's role
     */
    public function getRoleName()
    {

        $role_id = $this->getRole();

        if (isset($this->_user_roles[$role_id]))
        {
            return $this->_user_roles[$role_id];
        }

        return 'Unknown';

    }


    /**
     * Check whether the user is a super user
     * @return bool Whether the user is a super user
     */
    public function isSuperUser()
    {

        if ($this->getRole() == UserFacade::ROLE_SUPER_USER)
        {
            return true;
        }

        return false;

    }


    /**
     * Check whether the user is an administrator
     * @return bool Whether the user is an administrator
     */
    public function isAdmin()
    {

        if ($this->getRole() <= UserFacade::ROLE_ADMIN)
        {
            return true;
        }

        return false;

    }


    /**
     * Get a human-readable 'last online' date string
     * @return string Human-readable 'last onine' date
     */
    public function getLastSeenDate()
    {

        $last_seen = $this->getLastOnline();

        if (!$last_seen)
        {
            return 'never';
        }

        return date('jS F Y, H:i', $last_seen);

    }


    /**
     * Get the user's client side icon path
     * @return string Path to user icon
     */
    public function getIcon()
    {

        return $this->_dependencies->utils->getGravatar($this->getEmail());

    }


    /**
     * Get an array of users that the current user is related to
     * @param  array $excluding Array of user IDs to exclude from return value
     * @return array            Array of user details
     */
    public function getRelatedUsers(array $excluding = [])
    {

        try
        {

            $db = $this->_dependencies->db;

            /*
             * Get users
             */
            $users = $db->build()->table($this->_table)
                                 ->where(function($query)
                                 {
                                     $query->whereGreaterThan('role', $this->getRole())
                                           ->orWhere('id', $this->getId())
                                           ->orWhere('parent_id', $this->getId());
                                 })
                                 ->orderBy('role')
                                 ->orderBy('username')
                                 ->cache(3600)
                                 ->select();

            $result = [];

            /*
             * Build a FormBuilder::select() compatible array
             */
            foreach ($users as $user)
            {

                /*
                 * Skip over records that we want to exclude
                 */
                if (in_array($user['id'], $excluding))
                {
                    continue;
                }

                $user_id   = $user['id'];
                $username  = $user['username'];
                $role_id   = $user['role'];
                $role_name = 'Unknown';

                if (isset($this->_user_roles[$role_id]))
                {
                    $role_name = $this->_user_roles[$role_id];
                }

                $result[$role_name][$user_id] = $username;

            }

            /*
             * Alphabetically order the contents of each optgroup
             */
            foreach ($result as $role_name => $role_group)
            {
                natcasesort($result[$role_name]);
            }

            return $result;

        }

        catch (Exception $exception)
        {
            return [];
        }

    }


    /**
     * Get a list of online users that the user is permitted to see
     * @return array Array of online users
     */
    public function getOnline()
    {

        try
        {

            $db   = $this->_dependencies->db;
            $time = (time() - 60);

            $users = $db->build()->table($this->_table)
                                 ->where(function($query)
                                 {
                                     $query->whereGreaterThan('role', $this->getRole())
                                           ->orWhere('parent_id', $this->getId());
                                 })
                                 ->whereGreaterThan('last_online', $time)
                                 ->orderBy('username')
                                 ->select('username', 'role', 'last_online');

            foreach ($users as &$user)
            {

                $this->setId($user['id']);

                $user['last_online'] = date('H:i, jS M');
                $user['icon']        = $this->getIcon();
                $user['role']        = $this->getRoleName();

            }

            return array_values($users);

        }

        catch (Exception $exception)
        {
            return [];
        }

    }


    /**
     * Get a paginated list of users that the user is permitted to see
     * @param  int   $limit Pagination limit
     * @return array        Array of users
     */
    public function getPaginated($limit = 25)
    {

        try
        {

            $db = $this->_dependencies->db;

            $users = $db->build()->table($this->_table)
                        ->where(function($query)
                        {
                            $query->whereGreaterThan('role', $this->getRole())
                                  ->orWhere('parent_id', $this->getId());
                        })
                        ->orderBy('role')
                        ->orderBy('username')
                        ->paginate($limit)
                        ->select('username', 'email', 'role');

            foreach ($users as &$user)
            {

                $user_object = $this->getEmpty();

                $user_object->setId($user['id']);

                $user['icon'] = $user_object->getIcon();
                $user['role'] = $user_object->getRoleName();

            }

            return array_values($users);

        }

        catch (Exception $exception)
        {
            return [];
        }

    }


    /**
     * Get a list of user roles beneath the user
     * @return array Associative array of user roles
     */
    public function getRoles()
    {

        $user_roles =  $this->_user_roles;

        foreach ($user_roles as $role_id => $role_name)
        {

            if ($role_id <= $this->getRole())
            {
                unset($user_roles[$role_id]);
            }

        }

        return $user_roles;

    }


    /**
     * Check whether the user can interact with another user
     * @param  User $user User to check
     * @return bool               Whether the users can interact
     */
    public function canInteractWith(User $user)
    {

        $role_pass   = ($user->getRole() > $this->getRole());
        $parent_pass = ($user->getParentId() == $this->getId());

        if ($role_pass OR $parent_pass)
        {
            return true;
        }

        return false;

    }


}