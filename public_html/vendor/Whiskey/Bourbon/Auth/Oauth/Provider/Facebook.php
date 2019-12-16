<?php


namespace Whiskey\Bourbon\Auth\Oauth\Provider;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Auth\Oauth\MissingCredentialsException;
use Whiskey\Bourbon\Exception\Auth\Oauth\AuthorisationFailedException;
use Whiskey\Bourbon\Auth\Oauth\OauthAbstract;
use Whiskey\Bourbon\Auth\Oauth\User;
use Whiskey\Bourbon\Security\Csrf;
use Whiskey\Bourbon\Io\Http as Http;
use Whiskey\Bourbon\Routing\Handler as Router;


/**
 * OAuth Facebook class
 * @package Whiskey\Bourbon\Auth\Oauth\Provider
 */
class Facebook extends OauthAbstract
{


    const _NO_REUSE = true;


    protected $_client_id     = null;
    protected $_client_secret = null;


    /**
     * Instantiate a Facebook OAuth object
     * @param Csrf   $csrf   Csrf object
     * @param Http   $http   Http object
     * @param Router $router Router object
     * @param User   $user   User object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Csrf $csrf, Http $http, Router $router, User $user)
    {

        if (!isset($csrf) OR
            !isset($http) OR
            !isset($router) OR
            !isset($user))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies         = new stdClass();
        $this->_dependencies->csrf   = $csrf;
        $this->_dependencies->http   = $http;
        $this->_dependencies->router = $router;
        $this->_dependencies->user   = $user;

    }


    /**
     * Get the provider name
     * @return string Provider name
     */
    public function getName()
    {

        return 'facebook';

    }


    /**
     * Set the client ID
     * @param  string $id Client ID
     * @return self       Facebook object for chaining
     * @throws InvalidArgumentException if the client ID is not valid
     */
    public function setClientId($id = '')
    {

        if ($id == '')
        {
            throw new InvalidArgumentException('Invalid client ID');
        }

        $this->_client_id = $id;

        return $this;

    }


    /**
     * Set the client secret
     * @param  string $secret Client secret
     * @return self           Facebook object for chaining
     * @throws InvalidArgumentException if the client secret is not valid
     */
    public function setClientSecret($secret = '')
    {

        if ($secret == '')
        {
            throw new InvalidArgumentException('Invalid client secret');
        }

        $this->_client_secret = $secret;

        return $this;

    }


    /**
     * Get Facebook authorisation URL for user to click through to
     * @param  array  $scope Array of permissions to request
     * @return string        Facebook authorisation URL
     * @throws MissingCredentialsException if provider details are missing
     */
    public function getLoginUrl(array $scope = [])
    {

        if (is_null($this->_client_id) OR
            is_null($this->_client_secret) OR
            is_null($this->_redirect_uri))
        {
            throw new MissingCredentialsException('Insufficient details provided to set up OAuth object');
        }

        $base_scope = ['email', 'public_profile'];
        $scope      = array_merge($scope, $base_scope);
        $scope      = array_unique($scope);
        $scope      = array_map('urlencode', $scope);
        $scope      = implode(',', $scope);

        return 'https://www.facebook.com/v2.5/dialog/oauth?client_id=' . urlencode($this->_client_id) . '&amp;redirect_uri=' . urlencode($this->_redirect_uri) . '&amp;scope=' . $scope . '&amp;state=' . urlencode($this->_dependencies->csrf->generateToken());

    }


    /**
     * Validate a Facebook OAuth callback and return user information
     * @return User User object of user information
     * @throws MissingCredentialsException if the provider details are missing
     * @throws AuthorisationFailedException if authorisation fails
     */
    public function fetch()
    {

        if (is_null($this->_client_id) OR
            is_null($this->_client_secret) OR
            is_null($this->_redirect_uri))
        {
            throw new MissingCredentialsException('Insufficient details provided to set up OAuth object');
        }

        if (isset($_GET['code']) AND
            isset($_GET['state']))
        {

            /*
             * Check that the state token is valid
             */
            if ($this->_dependencies->csrf->checkToken($_GET['state']))
            {

                /*
                 * Get an access token
                 */
                $token_data = $this->_dependencies->http->get('https://graph.facebook.com/v2.5/oauth/access_token?client_id=' . urlencode($this->_client_id) . '&redirect_uri=' . urlencode($this->_redirect_uri) . '&client_secret=' . urlencode($this->_client_secret) . '&code=' . urlencode($_GET['code']));

                if ($token_data = json_decode($token_data))
                {

                    $token_data_array = (array)$token_data;

                    if (isset($token_data_array['access_token']))
                    {

                        /*
                         * Get user details
                         */
                        $user_info = $this->_dependencies->http->get('https://graph.facebook.com/v2.5/me?access_token=' . urlencode($token_data_array['access_token']) . '&fields=first_name,last_name,email');

                        if ($user_info AND
                            $user_info = json_decode($user_info))
                        {

                            $user_info->access_token = $token_data_array['access_token'];

                            $user = clone $this->_dependencies->user;

                            $user->setNetwork('facebook');
                            $user->setId($user_info->id);
                            $user->setName($user_info->first_name . ' ' . $user_info->last_name);
                            $user->setEmail($user_info->email);
                            $user->setAvatar('https://graph.facebook.com/' . $user_info->id . '/picture?type=large&amp;width=720&amp;height=720');
                            $user->setToken($user_info->access_token);
                            $user->setRaw($user_info);

                            return $user;

                        }

                    }

                }

            }

        }

        throw new AuthorisationFailedException('Facebook authorisation failed');

    }


}