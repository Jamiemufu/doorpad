<?php


namespace Whiskey\Bourbon\Auth\Oauth\Provider;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Auth\Oauth\IrretrievableLoginUrlException;
use Whiskey\Bourbon\Exception\Auth\Oauth\MissingCredentialsException;
use Whiskey\Bourbon\Exception\Auth\Oauth\AuthorisationFailedException;
use Whiskey\Bourbon\Auth\Oauth\OauthAbstract;
use Whiskey\Bourbon\Auth\Oauth\User;
use Whiskey\Bourbon\Security\Csrf;
use Whiskey\Bourbon\Io\Http as Http;
use Whiskey\Bourbon\Routing\Handler as Router;


/**
 * OAuth Twitter class
 * @package Whiskey\Bourbon\Auth\Oauth\Provider
 */
class Twitter extends OauthAbstract
{


    const _NO_REUSE = true;


    protected $_client_id     = null;
    protected $_client_secret = null;
    protected $_access_token  = null;
    protected $_access_secret = null;
    protected $_api_base_url  = 'https://api.twitter.com/';


    /**
     * Instantiate a Twitter OAuth object
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

        return 'twitter';

    }


    /**
     * Set the client ID
     * @param  string $id Client ID
     * @return self       Twitter object for chaining
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
     * @return self           Twitter object for chaining
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
     * Compile POST arguments into a single header POST string
     * @param  string $oauth_url Twitter API endpoint
     * @param  array  $params    POST arguments
     * @param  string $method    HTTP method
     * @return string            Compiled argument string
     */
    protected function _compileArguments($oauth_url = '', array $params = [], $method = 'POST')
    {

        ksort($params);

        $params = http_build_query($params);

        return $method . '&' . rawurlencode($oauth_url) . '&' . rawurlencode($params);

    }


    /**
     * Compile OAuth authorisation header
     * @param  array  $params POST arguments
     * @return string         OAuth authorisation header
     */
    protected function _compileAuthHeader(array $params = [])
    {

        $result = [];

        foreach ($params as $var => $value)
        {
            $result[] = $var . '="' . rawurlencode($value) . '"';
        }

        return 'Authorization: OAuth ' . implode(', ', $result);

    }


    /**
     * Compile a request for a user with an authentication token
     * @param  string $api_endpoint Twitter API endpoint
     * @param  array  $params       Request parameters
     * @param  string $method       HTTP method
     * @return array                Header array
     */
    protected function _compileRequest($api_endpoint = '', array $params = [], $method = 'POST')
    {

        $oauth_array = ['oauth_consumer_key'     => $this->_client_id,
                        'oauth_nonce'            => mb_substr($this->_dependencies->csrf->generateToken(), 0, 32),
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_timestamp'        => time(),
                        'oauth_token'            => $this->_access_token,
                        'oauth_version'          => '1.0'];

        $hash = hash_hmac('sha1',
                          $this->_compileArguments($api_endpoint, array_merge($params, $oauth_array), $method),
                          rawurlencode($this->_client_secret) . '&' . rawurlencode($this->_access_secret),
                          true);

        $oauth_array['oauth_signature'] = base64_encode($hash);

        ksort($oauth_array);

        $header = [$this->_compileAuthHeader($oauth_array)];

        return $header;

    }


    /**
     * Get Twitter authorisation URL for user to click through to
     * @param  array  $scope Array of permissions to request (not used by Twitter)
     * @return string        Twitter authorisation URL
     * @throws MissingCredentialsException if provider details are missing
     * @throws IrretrievableLoginUrlException if the login URL could not be retrieved
     */
    public function getLoginUrl(array $scope = [])
    {

        if (is_null($this->_client_id) OR
            is_null($this->_client_secret) OR
            is_null($this->_redirect_uri))
        {
            throw new MissingCredentialsException('Insufficient details provided to set up OAuth object');
        }

        $request_token_uri = $this->_api_base_url . 'oauth/request_token';

        /*
         * Prepare the header
         */
        $oauth_array = ['oauth_callback'         => $this->_redirect_uri,
                        'oauth_nonce'            => $this->_dependencies->csrf->generateToken(),
                        'oauth_signature_method' => 'HMAC-SHA1',
                        'oauth_timestamp'        => time(),
                        'oauth_consumer_key'     => $this->_client_id,
                        'oauth_version'          => '1.0'];

        $hash = hash_hmac('sha1',
                          $this->_compileArguments($request_token_uri, $oauth_array),
                          rawurlencode($this->_client_secret) . '&',
                          true);

        $oauth_array['oauth_signature'] = base64_encode($hash);

        /*
         * Request an access token
         */
        $header     = [$this->_compileAuthHeader($oauth_array), 'Expect:'];
        $token_data = $this->_dependencies->http->post($request_token_uri, [], $header);

        /*
         * Check if a token was returned
         */
        if ($token_data)
        {

            $token_data_array = [];

            parse_str($token_data, $token_data_array);

            if (isset($token_data_array['oauth_token']))
            {
                return $this->_api_base_url . 'oauth/authenticate?oauth_token=' . urlencode($token_data_array['oauth_token']);
            }
        }

        throw new IrretrievableLoginUrlException('Could not retrieve Twitter login URL');

    }


    /**
     * Validate a Twitter OAuth callback and return user information
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

        if (isset($_GET['oauth_token']) AND
            isset($_GET['oauth_verifier']))
        {

            $request_token_uri = $this->_api_base_url . 'oauth/access_token';

            /*
             * Prepare the header
             */
            $oauth_array = ['oauth_nonce'            => $this->_dependencies->csrf->generateToken(),
                            'oauth_signature_method' => 'HMAC-SHA1',
                            'oauth_timestamp'        => time(),
                            'oauth_consumer_key'     => $this->_client_id,
                            'oauth_token'            => $_GET['oauth_token'],
                            'oauth_verifier'         => $_GET['oauth_verifier'],
                            'oauth_version'          => '1.0'];

            $hash = hash_hmac('sha1',
                              $this->_compileArguments($request_token_uri, $oauth_array),
                              rawurlencode($this->_client_secret) . '&',
                              true);

            $oauth_array['oauth_signature'] = base64_encode($hash);

            /*
             * Get account details
             */
            $header    = [$this->_compileAuthHeader($oauth_array), 'Expect:'];
            $user_info = $this->_dependencies->http->post($request_token_uri, [], $header);

            if ($user_info)
            {

                $user_info_object = [];

                parse_str($user_info, $user_info_object);

                $user             = clone $this->_dependencies->user;
                $user_info_object = (object)$user_info_object;
                $user_id          = $user_info_object->user_id;
                $screen_name      = $user_info_object->screen_name;

                /*
                 * Get the OAuth token
                 */
                $token                = new stdClass();
                $token->access_token  = $user_info_object->oauth_token;
                $token->access_secret = $user_info_object->oauth_token_secret;

                $this->_access_token  = $token->access_token;
                $this->_access_secret = $token->access_secret;

                /*
                 * Retrieve the rest of the profile
                 */
                $api_endpoint = $this->_api_base_url . '1.1/users/show.json';
                $avatar       = '';
                $params       = ['screen_name' => $user_info_object->screen_name];
                $header       = $this->_compileRequest($api_endpoint, $params, 'GET');

                $response = $this->_dependencies->http->get($api_endpoint . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986), $header);

                if (($profile_info = json_decode($response)) !== false)
                {

                    unset($profile_info->status);

                    $avatar                      = $profile_info->profile_image_url_https;
                    $profile_info->access_token  = $token->access_token;
                    $profile_info->access_secret = $token->access_secret;

                    $user_info_object = $profile_info;

                }

                $user->setNetwork('twitter');
                $user->setId($user_id);
                $user->setName($screen_name);
                $user->setEmail('');
                $user->setAvatar($avatar);
                $user->setToken($token);
                $user->setRaw($user_info_object);

                return $user;

            }

        }

        throw new AuthorisationFailedException('Twitter authorisation failed');

    }


}