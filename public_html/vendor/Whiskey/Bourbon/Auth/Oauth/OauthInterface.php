<?php


namespace Whiskey\Bourbon\Auth\Oauth;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Auth\Oauth\MissingCredentialsException;
use Whiskey\Bourbon\Exception\Auth\Oauth\AuthorisationFailedException;


/**
 * OauthInterface interface
 * @package Whiskey\Bourbon\Auth\Oauth
 */
interface OauthInterface
{


    /**
     * Get the provider name
     * @return string Provider name
     */
    public function getName();


    /**
     * Set the client ID
     * @param  string $id Client ID
     * @return self       Social object for chaining
     * @throws InvalidArgumentException if the client ID is not valid
     */
    public function setClientId($id);


    /**
     * Set the client secret
     * @param  string $secret Client secret
     * @return self           Social object for chaining
     * @throws InvalidArgumentException if the client secret is not valid
     */
    public function setClientSecret($secret);


    /**
     * Set the redirect URL
     * @param  string|array $url Redirect URL (or array of route components)
     * @return self              Social object for chaining
     * @throws InvalidArgumentException if the redirect URL is not valid
     */
    public function setRedirectUrl($url);


    /**
     * Get provider authorisation URL for user to click through to
     * @param  array  $scope Array of permissions to request
     * @return string        Provider authorisation URL
     * @throws MissingCredentialsException if provider details are missing
     */
    public function getLoginUrl(array $scope);


    /**
     * Validate an OAuth callback and return user information
     * @return User User object of user information
     * @throws MissingCredentialsException if the provider details are missing
     * @throws AuthorisationFailedException if authorisation fails
     */
    public function fetch();


}