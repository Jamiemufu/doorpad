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
 * OAuth LinkedIn class
 * @package Whiskey\Bourbon\Auth\Oauth\Provider
 */
class LinkedIn extends OauthAbstract
{


    const _NO_REUSE = true;


    protected $_client_id     = null;
    protected $_client_secret = null;


    /**
     * Instantiate a LinkedIn OAuth object
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

        return 'linkedin';

    }


    /**
     * Set the client ID
     * @param  string $id Client ID
     * @return self       LinkedIn object for chaining
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
     * @return self           LinkedIn object for chaining
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
     * Get LinkedIn authorisation URL for user to click through to
     * @param  array  $scope Array of permissions to request
     * @return string        LinkedIn authorisation URL
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

        $base_scope = ['r_basicprofile', 'r_emailaddress'];
        $scope      = array_merge($scope, $base_scope);
        $scope      = array_unique($scope);
        $scope      = implode(' ', $scope);
        $scope      = urlencode($scope);

        return 'https://www.linkedin.com/uas/oauth2/authorization?client_id=' . urlencode($this->_client_id) . '&amp;response_type=code&amp;scope=' . $scope . '&amp;redirect_uri=' . urlencode($this->_redirect_uri) . '&amp;state=' . urlencode($this->_dependencies->csrf->generateToken());

    }


    /**
     * Validate a LinkedIn OAuth callback and return user information
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

        if (isset($_GET['error']))
        {
            throw new AuthorisationFailedException('LinkedIn authorisation failed');
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
                $token_data = $this->_dependencies->http->get('https://www.linkedin.com/uas/oauth2/accessToken?code=' . urlencode($_GET['code']) . '&client_id=' . urlencode($this->_client_id) . '&client_secret=' . urlencode($this->_client_secret) . '&redirect_uri=' . urlencode($this->_redirect_uri) . '&grant_type=authorization_code');

                if ($token_data AND
                    $token_data = json_decode($token_data) AND
                    isset($token_data->access_token))
                {

                    /*
                     * Get user details
                     */
                    $user_info = $this->_dependencies->http->get('https://api.linkedin.com/v1/people/~:(id,first-name,last-name,picture-url,email-address)?format=json&oauth2_access_token=' . urlencode($token_data->access_token));

                    if ($user_info AND
                        $user_info = json_decode($user_info))
                    {

                        $user_info->access_token = $token_data->access_token;

                        $user = clone $this->_dependencies->user;

                        $user->setNetwork('linkedin');
                        $user->setId($user_info->id);
                        $user->setName($user_info->firstName . ' ' . $user_info->lastName);
                        $user->setEmail($user_info->emailAddress);
                        $user->setAvatar($user_info->pictureUrl);
                        $user->setToken($user_info->access_token);
                        $user->setRaw($user_info);

                        return $user;

                    }

                }

            }

        }

        throw new AuthorisationFailedException('LinkedIn authorisation failed');

    }


}