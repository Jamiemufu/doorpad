<?php


namespace Whiskey\Bourbon\Auth\Oauth\Provider;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Auth\Oauth\MissingCredentialsException;
use Whiskey\Bourbon\Exception\Auth\Oauth\AuthorisationFailedException;
use Whiskey\Bourbon\Auth\Oauth\OauthAbstract;
use Whiskey\Bourbon\Auth\Oauth\User;
use Whiskey\Bourbon\Io\Http as Http;
use Whiskey\Bourbon\Routing\Handler as Router;


/**
 * OAuth Microsoft class
 * @package Whiskey\Bourbon\Auth\Oauth\Provider
 */
class Microsoft extends OauthAbstract
{


    const _NO_REUSE = true;


    protected $_client_id     = null;
    protected $_client_secret = null;


    /**
     * Instantiate a Microsoft OAuth object
     * @param Http   $http   Http object
     * @param Router $router Router object
     * @param User   $user   User object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Http $http, Router $router, User $user)
    {

        if (!isset($http) OR
            !isset($router) OR
            !isset($user))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies         = new stdClass();
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

        return 'microsoft';

    }


    /**
     * Set the client ID
     * @param  string $id Client ID
     * @return self       Microsoft object for chaining
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
     * @return self           Microsoft object for chaining
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
     * Get Microsoft authorisation URL for user to click through to
     * @param  array  $scope Array of permissions to request
     * @return string        Microsoft authorisation URL
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

        $base_scope = ['wl.basic', 'wl.emails'];
        $scope      = array_merge($scope, $base_scope);
        $scope      = array_unique($scope);
        $scope      = implode(' ', $scope);
        $scope      = urlencode($scope);
    
        return 'https://login.live.com/oauth20_authorize.srf?client_id=' . urlencode($this->_client_id) . '&amp;scope=' . $scope . '&amp;response_type=code&amp;redirect_uri=' . urlencode($this->_redirect_uri);
    
    }


    /**
     * Validate a Microsoft OAuth callback and return user information
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

        if (isset($_GET['code']))
        {

            /*
             * Get an access token
             */
            $token_data = $this->_dependencies->http->get('https://login.live.com/oauth20_token.srf?client_id=' . urlencode($this->_client_id) . '&client_secret=' . urlencode($this->_client_secret) . '&code=' . urlencode($_GET['code']) . '&grant_type=authorization_code&redirect_uri=' . urlencode($this->_redirect_uri));

            if ($token_data AND
                $token_data = json_decode($token_data) AND
                isset($token_data->access_token))
            {

                /*
                 * Get user details
                 */
                $user_info = $this->_dependencies->http->get('https://apis.live.net/v5.0/me?access_token=' . urlencode($token_data->access_token));

                if ($user_info AND
                    $user_info = json_decode($user_info))
                {

                    $user_info->access_token = $token_data->access_token;

                    $user = clone $this->_dependencies->user;

                    $user->setNetwork('microsoft');
                    $user->setId($user_info->id);
                    $user->setName($user_info->name);
                    $user->setEmail($user_info->emails->preferred);
                    $user->setAvatar('https://apis.live.net/v5.0/' . $user_info->id . '/picture');
                    $user->setToken($user_info->access_token);
                    $user->setRaw($user_info);

                    return $user;

                }

            }
        }

        throw new AuthorisationFailedException('Microsoft authorisation failed');

    }


}