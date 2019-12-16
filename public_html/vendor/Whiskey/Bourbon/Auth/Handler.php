<?php


namespace Whiskey\Bourbon\Auth;


use stdClass;
use Closure;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Auth\Oauth\OauthInterface;
use Whiskey\Bourbon\Auth\Oauth\User;
use Whiskey\Bourbon\Exception\EngineNotRegisteredException;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Storage\Database\Orm\Template as OrmUser;
use Whiskey\Bourbon\Storage\Session;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\App\Http\Request;
use Whiskey\Bourbon\App\Http\Response;


/**
 * Authentication Handler class
 * @package Whiskey\Bourbon\Auth
 */
class Handler
{


    protected $_dependencies    = null;
    protected $_details         = null;
    protected $_oauth_providers = [];


    /**
     * Instantiate an authentication Handler object
     * @param Session  $session  Session object
     * @param Db       $db       Db object
     * @param Request  $request  Request object
     * @param Response $response Response object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Session $session, Db $db, Request $request, Response $response)
    {

        if (!isset($session) OR
            !isset($db) OR
            !isset($request) OR
            !isset($response))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_details                = new stdClass();
        $this->_dependencies           = new stdClass();
        $this->_dependencies->session  = $session;
        $this->_dependencies->db       = $db;
        $this->_dependencies->request  = $request;
        $this->_dependencies->response = $response;

    }


    /**
     * Initialise the auth object and attempt a login if credentials exist
     */
    protected function _init()
    {

        $this->_details       = new stdClass();
        $existing_credentials = $this->_dependencies->session->read('_bourbon_auth_data');

        if (!empty($existing_credentials) AND is_array($existing_credentials))
        {
            $this->logIn($existing_credentials['table'], $existing_credentials['login_values']);
        }

    }


    /**
     * Enable basic authentication
     * @param Closure $closure      Closure to be executed
     * @param string  $fail_message Message to be shown if no credentials are provided
     * @throws InvalidArgumentException if the callback is not callable
     */
    public function basic(Closure $closure, $fail_message = 'Authentication failed')
    {

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid callback passed for basic authentication');
        }

        /*
         * Check for a username and password
         */
        if (isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW']))
        {

            /*
             * Pass the credentials to the closure to check their validity and
             * save them to the session if they are valid
             */
            if ($closure($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) === true)
            {
                $this->_dependencies->session->write('_bourbon_basic_auth_username', $_SERVER['PHP_AUTH_USER']);
                $this->_dependencies->session->write('_bourbon_basic_auth_password', $_SERVER['PHP_AUTH_PW']);
            }

            /*
             * If the credentials are not valid, clear any that have been stored
             * in the session
             */
            else
            {
                $this->_dependencies->session->clear('_bourbon_basic_auth_username');
                $this->_dependencies->session->clear('_bourbon_basic_auth_password');
            }

        }

        /*
         * If a username and password cannot be found in the session, send a
         * 401 header and request authentication
         */
        if ($this->_dependencies->session->read('_bourbon_basic_auth_username') === null AND
            $this->_dependencies->session->read('_bourbon_basic_auth_password') === null)
        {

            $realm          = 'bourbon' . hash('md5', __DIR__);
            $response       = $this->_dependencies->response;
            $response->body = $fail_message;

            $response->deny(401, false);
            $response->headers->set('WWW-Authenticate: Basic realm="' . $realm . '"');
            $response->output();

            exit;

        }

    }


    /**
     * Instantiate a provider object and return a package of the instance and
     * provider name
     * @param  string $class_name Fully-qualified provider class name
     * @return array              Array of provider name and instance
     */
    protected function _instantiateProvider($class_name = '')
    {

        /*
         * Retrieve the provider instance object
         */
        $engine           = Instance::_retrieve($class_name);
        $name             = strtolower($engine->getName());
        $provider_details = ['name' => $name, 'engine' => $engine];

        return $provider_details;

    }


    /**
     * Fetch an OAuth provider instance
     * @param  string         $name OAuth provider name
     * @return OauthInterface       OAuth provider class implementing OauthInterface
     * @throws EngineNotRegisteredException if the OAuth provider has not been registered
     */
    public function oauth($name = '')
    {

        $name = strtolower($name);

        foreach ($this->_oauth_providers as &$provider_details)
        {

            if (is_string($provider_details['engine']))
            {
                $provider_details = $this->_instantiateProvider($provider_details['engine']);
            }

            if ($provider_details['name'] == $name)
            {
                return clone $provider_details['engine'];
            }

        }

        throw new EngineNotRegisteredException('Invalid OAuth provider \'' . $name . '\'');

    }


    /**
     * Register an engine
     * @param string $provider_class Fully-qualified engine class name
     * @throws InvalidArgumentException if the provider is not the correct type
     */
    public function registerEngine($provider_class = '')
    {

        if (!is_subclass_of($provider_class, OauthInterface::class))
        {
            throw new InvalidArgumentException('Engine is not the correct type');
        }

        $this->_oauth_providers[] = ['name' => '', 'engine' => $provider_class];

    }


    /**
     * Attempt a log in with a social User object
     * @param  User $user Social User object
     * @return bool       Whether the log in was successful
     */
    protected function _doSocialLogin(User $user)
    {

        try
        {

            /*
             * Create the login table if necessary
             */
            $this->_dependencies->db->buildSchema()->table('_bourbon_social_logins')
                                                   ->autoId()
                                                   ->varChar('network')
                                                   ->varChar('user_id')
                                                   ->varChar('name')
                                                   ->varChar('email')
                                                   ->varChar('avatar')
                                                   ->varChar('token_id')
                                                   ->varChar('token_secret')
                                                   ->create();

            $exists = $this->_dependencies->db->build()->table('_bourbon_social_logins')
                                                       ->where('network', $user->getNetwork())
                                                       ->where('user_id', $user->getId())
                                                       ->exists();

            if (!$exists)
            {

                /*
                 * OAuth 1.x
                 */
                if (isset($user->getToken()->access_secret))
                {
                    $token_id     = $user->getToken()->access_token;
                    $token_secret = $user->getToken()->access_secret;
                }

                /*
                 * OAuth 2.x
                 */
                else
                {
                    $token_id     = $user->getToken();
                    $token_secret = '';
                }

                $this->_dependencies->db->build()->table('_bourbon_social_logins')
                                                 ->data('network', $user->getNetwork())
                                                 ->data('user_id', $user->getId())
                                                 ->data('name', $user->getName())
                                                 ->data('email', $user->getEmail())
                                                 ->data('avatar', $user->getAvatar())
                                                 ->data('token_id', $token_id)
                                                 ->data('token_secret', $token_secret)
                                                 ->insert();

            }

            $credentials = ['network' => $user->getNetwork(),
                            'user_id' => $user->getId()];

            return $this->logIn('_bourbon_social_logins', $credentials);

        }

        catch (Exception $exception)
        {
            return false;
        }

    }


    /**
     * Attempt a log in with an ORM user object
     * @param  OrmUser $user OrmUser object
     * @return bool          Whether the log in was successful
     */
    protected function _doOrmLogin(OrmUser $user)
    {

        return $this->logIn($user->getTable(), $user->getDataArray());

    }


    /**
     * Attempt a log in using arguments to find a matching database result and
     * set result values as properties of the object
     * @param  string|User $table_or_object Database table in which to check for matches (or social User object)
     * @param  array       $login_values    Array of key/value pairs to match against
     * @return bool                         Whether or not the login attempt was successful
     */
    public function logIn($table_or_object = null, array $login_values = [])
    {

        $result = false;

        if ($this->_dependencies->db->connected())
        {

            if ($table_or_object instanceof User)
            {
                $result = $this->_doSocialLogin($table_or_object);
            }

            else if ($table_or_object instanceof OrmUser)
            {
                $result = $this->_doOrmLogin($table_or_object);
            }

            else if ((string)$table_or_object === '' OR empty($login_values))
            {
                $result = false;
            }

            else
            {

                $found_match      = false;
                $login_conditions = [];

                foreach ($login_values as $var => $value)
                {

                    $login_conditions[] = ['type'  => 'AND',
                                           'field' => $var,
                                           'value' => $value];

                }

                try
                {

                    $user_details = $this->_dependencies->db->select($table_or_object, $login_conditions);

                    if (!empty($user_details))
                    {

                        /*
                         * Store the record details as properties of
                         * $this->_details
                         */
                        foreach ($user_details as $value)
                        {

                            $this->_details = new stdClass();

                            foreach ($value as $var_2 => $value_2)
                            {
                                $this->_details->{$var_2} = $value_2;
                            }

                            $found_match = true;

                            /*
                             * Only need to get details for the first matching
                             * record
                             */
                            break;

                        }

                    }

                    /*
                     * Save credentials to the session and return 'true'
                     */
                    if ($found_match)
                    {

                        $login_details = ['table'        => $table_or_object,
                                          'login_values' => $login_values];

                        $this->_dependencies->session->write('_bourbon_auth_data', $login_details);

                        $result = true;

                    }

                }

                catch (Exception $exception)
                {
                    $result = false;
                }

            }

        }

        /*
         * If a login couldn't be achieved, trigger a logout to clear the
         * session data
         */
        if (!$result)
        {
            $this->logOut();
        }

        return $result;

    }


    /**
     * Clear login credentials so that future checks will fail
     */
    public function logOut()
    {

        $this->_dependencies->session->write('_bourbon_auth_data', []);

        $this->_details = new stdClass();

    }


    /**
     * Check whether a successful login occurred
     * @return bool Whether the user is logged in or not
     */
    public function isLoggedIn()
    {

        /*
         * Recheck the login status first
         */
        $this->_init();

        /*
         * See if credentials exists in the session (a failed _init() call will
         * result in the session entry being cleared)
         */
        $session_data = $this->_dependencies->session->read('_bourbon_auth_data');

        if (!empty($session_data))
        {
            return true;
        }

        return false;

    }


    /**
     * Check whether the user is a guest (not logged in)
     * @return bool Whether the user is not logged in
     */
    public function isGuest()
    {

        return !$this->isloggedIn();

    }


    /**
     * Check for a valid login and redirect the user to another action if not
     * @param string ... Multiple strings representing controller, view and slugs
     */
    public function enforce()
    {

        if ($this->isGuest())
        {
            call_user_func_array([$this->_dependencies->response, 'redirect'], func_get_args());
        }

    }


    /**
     * Check for a valid login and redirect the user to another action if one
     * exists
     * @param string ... Multiple strings representing controller, view and slugs
     */
    public function guestEnforce()
    {

        if ($this->isLoggedIn())
        {
            call_user_func_array([$this->_dependencies->response, 'redirect'], func_get_args());
        }

    }


    /**
     * Return details of the authorised user
     * @return object User details from database
     */
    public function details()
    {

        return $this->isLoggedIn() ? $this->_details : new stdClass();

    }


}