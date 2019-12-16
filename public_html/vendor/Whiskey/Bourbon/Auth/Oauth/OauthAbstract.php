<?php


namespace Whiskey\Bourbon\Auth\Oauth;


use InvalidArgumentException;


/**
 * Abstract OauthAbstract class
 * @package Whiskey\Bourbon\Auth\Oauth
 */
abstract class OauthAbstract implements OauthInterface
{


    protected $_dependencies = null;
    protected $_redirect_uri = '';


    /**
     * Generate an absolute URL based upon an array of routing components
     * @param  array  $route Array of routing components
     * @return string        Absolute URL to route
     */
    public function generateAbsoluteUrl(array $route = [])
    {

        $domain = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];
        $route  = call_user_func_array([$this->_dependencies->router, 'generateUrl'], $route);

        return $domain . $route;

    }


    /**
     * Set the redirect URL
     * @param  string|array $url Redirect URL (or array of route components)
     * @return self              Facebook object for chaining
     * @throws InvalidArgumentException if the redirect URL is not valid
     */
    public function setRedirectUrl($url = '')
    {

        if (is_array($url))
        {
            $url = $this->generateAbsoluteUrl($url);
        }

        if ($url == '')
        {
            throw new InvalidArgumentException('Invalid redirect URL');
        }

        $this->_redirect_uri = $url;

        return $this;

    }


}