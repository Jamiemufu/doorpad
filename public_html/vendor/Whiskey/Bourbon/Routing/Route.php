<?php


namespace Whiskey\Bourbon\Routing;


use stdClass;
use Closure;
use InvalidArgumentException;


/**
 * Route class
 * @package Whiskey\Bourbon\Routing
 */
class Route
{


    protected $_url         = '';
    protected $_http_method = 'GET';
    protected $_model       = '';
    protected $_controller  = '';
    protected $_action      = '';
    protected $_slugs       = [];
    protected $_closure     = null;
    protected $_regexes     = [];
    protected $_additional  = null;
    protected $_route_regex = '';


    /**
     * Instantiate the Route object
     * @param string  $url        Route URL
     * @param array   $details    Array of static model, controller, view and slugs for route
     * @param Closure $closure    Route closure
     * @param array   $regexes    Array of regex patterns
     * @param array   $additional Additional miscellaneous information
     * @throws InvalidArgumentException if a route URL has not been provided
     * @throws InvalidArgumentException if a HTTP method has not been provided
     * @throws InvalidArgumentException if a controller/action or closure has not been provided
     */
    public function __construct($url = '', array $details = [], $closure = null, array $regexes = [], array $additional = [])
    {

        if ($url == '')
        {
            throw new InvalidArgumentException('Route URL not supplied');
        }

        if (empty($details['http_method']))
        {
            throw new InvalidArgumentException('HTTP method not supplied');
        }

        if (empty($details['controller']) AND
            empty($details['action']) AND
            empty($closure))
        {
            throw new InvalidArgumentException('Route target or closure not supplied');
        }

        /*
         * Populate any missing details
         */
        foreach (['model', 'controller', 'action'] as $detail)
        {

            if (empty($details[$detail]))
            {
                $details[$detail] = '';
            }

        }

        if (empty($details['slugs']))
        {
            $details['slugs'] = [];
        }

        $this->_regexes     = $regexes;
        $this->_url         = $this->_standardiseUrl($url);
        $this->_http_method = strtolower($details['http_method']);
        $this->_model       = $details['model'];
        $this->_controller  = $details['controller'];
        $this->_action      = $details['action'];
        $this->_slugs       = $details['slugs'];
        $this->_closure     = $closure;
        $this->_additional  = $additional;

    }


    /**
     * Remove any leading and trailing slashes, reappend them and remove any
     * double slashes, to standardise a URL
     * @param  string $url URL to standardise
     * @return string      Standardised URL
     */
    protected function _standardiseUrl($url = '')
    {

        return str_replace('//', '/', ('/' . trim($url, '/') . '/'));

    }


    /**
     * Get the raw route URL (with wildcards)
     * @return string Raw route URL
     */
    public function getRawUrl()
    {

        return $this->_url;

    }


    /**
     * Get the route HTTP method
     * @return string HTTP method
     */
    public function getHttpMethod()
    {

        return $this->_http_method;

    }


    /**
     * Get the route model
     * @return string Fully-qualified model class name
     */
    public function getModel()
    {

        return $this->_model;

    }


    /**
     * Get the route controller
     * @return string Fully-qualified controller class name
     */
    public function getController()
    {

        return $this->_controller;

    }


    /**
     * Get the route action
     * @return string Action name
     */
    public function getAction()
    {

        return $this->_action;

    }


    /**
     * Get the static route slugs
     * @param  string $url Optional URL from which to obtain client-supplied slugs
     * @return array       Array of static slug values
     */
    public function getSlugs($url = '')
    {

        /*
         * Hard-coded route slugs only
         */
        if ($url == '')
        {
            return $this->_slugs;
        }
        
        /*
         * Hard-coded route slugs and client-supplied slugs
         */
        $url   = $this->_standardiseUrl($url);
        $regex = $this->_getRegex();

        preg_match($regex, $url, $matches);

        /*
         * Check that matches have been found and that the whole route has been
         * matched
         */
        if (!empty($matches) AND
            ($matches[0] == $url))
        {
            
            $result = $this->_slugs;

            /*
             * Shuffle off the matched string
             */
            array_shift($matches);

            /*
             * Go through each match and compile an array of the fragments
             * (slugs)
             */
            foreach ($matches as $fragment)
            {

                /*
                 * Split and sort through terminating wildcard fragments
                 */
                if (mb_strstr($fragment, '/'))
                {

                    $inner_fragments = explode('/', $fragment);

                    foreach ($inner_fragments as $inner_fragment)
                    {

                        /*
                         * Only include fragments that are not blank
                         */
                        if ($inner_fragment != '')
                        {
                            $result[] = $inner_fragment;
                        }

                    }

                    /*
                     * Return the result immediately, as other matches will be
                     * duplicates
                     */
                    return $result;

                }

                /*
                 * Sort through regular wildcard fragments (if they are not
                 * blank)
                 */
                else if ($fragment != '')
                {
                    $result[] = $fragment;
                }

            }

            return $result;

        }
        
        /*
         * If the URL did not match the regular expression, just return the
         * hard-coded route slugs
         */
        else
        {
            return $this->_slugs;
        }

    }


    /**
     * Get the route closure
     * @return Closure Route closure
     */
    public function getClosure()
    {

        return $this->_closure;

    }


    /**
     * Get the additional information
     * @return mixed Additional route information
     */
    public function getAdditionalInformation()
    {

        return $this->_additional;

    }


    /**
     * Return a URL based upon provided slugs
     * @param  array  $slugs Array of URL slugs
     * @return string        Route URL
     */
    public function generateUrl(array $slugs = [])
    {

        $url   = '';
        $route = explode('/', trim($this->_url, '/'));

        /*
         * Compile the URL one fragment at a time
         */
        foreach ($route as $fragment)
        {

            $url .= '/';

            /*
             * Regular wildcard or regex pattern
             */
            if ($fragment == '*' OR isset($this->_regexes[$fragment]))
            {
                $url .= urlencode(array_shift($slugs));
            }

            /*
             * Terminating wildcard
             */
            else if ($fragment == ':')
            {
                $url .= implode('/', array_map('urlencode', $slugs));
            }

            /*
             * Regular fragment
             */
            else
            {
                $url .= urlencode($fragment);
            }

        }

        return $url;

    }
    
    
    /**
     * Get the regular expression that describes the route 
     * @return string Regular expression describing the route
     */
    protected function _getRegex()
    {
        
        if ($this->_route_regex != '')
        {
            return $this->_route_regex;
        }
        
        $route      = rtrim($this->_url, '/');
        $terminator = '';

        /*
         * Check whether the route has a terminating wildcard
         */
        if (mb_substr($route, -1) == ':')
        {
            $terminator = '/:';
        }

        /*
         * Reformat the route to standardise it and convert it to an array of
         * fragments
         */
        $route = rtrim($route, ':');
        $route = trim($route, '/');
        $route = $route . $terminator;
        $route = explode('/', $route);

        $regex = '/';

        /*
         * Compile a matching regular expression, one fragment at a time
         */
        foreach ($route as $fragment)
        {

            /*
             * Leading slash for everything except terminating wildcards
             */
            if ($fragment != ':')
            {
                $regex .= "\/";
            }

            /*
             * Regular wildcard
             */
            if ($fragment == '*')
            {
                $regex .= "(.*?(?=\/))";
            }

            /*
             * Terminating wildcard
             */
            else if ($fragment == ':')
            {
                $regex .= "($|\/(.*\/))";
            }

            else
            {

                $used_custom = false;

                /*
                 * Check fragment against custom regex patterns
                 */
                foreach ($this->_regexes as $tag => $pattern)
                {

                    if ($fragment == $tag)
                    {
                        $regex       .= '(' . $pattern . ')';
                        $used_custom  = true;
                    }

                }

                /*
                 * Regular fragment
                 */
                if (!$used_custom)
                {
                    $regex .= preg_quote($fragment);
                }

            }

        }

        $regex .= "\//";

        /*
         * Tidy up the regex if necessary
         */
        $regex = str_replace("\/\/", "\/", $regex);
        $regex = str_replace("(.*\/))\/", "(.*\/*))", $regex);

        /*
         * Tidy up the regex if it is the application root with a terminating
         * wildcard
         */

        if ($regex == "/\/($|\/(.*\/*))/")
        {
            $regex = "/(.*)\//";
        }

        $this->_route_regex = $regex;
        
        return $this->_route_regex;
        
    }


    /**
     * Check whether the route can describe a given URL
     * @param  string $url URL to check
     * @return bool        Whether the route can describe the given URL
     */
    public function check($url = '')
    {

        $url   = $this->_standardiseUrl($url);
        $regex = $this->_getRegex();

        preg_match($regex, $url, $matches);

        /*
         * Check that matches have been found and that the whole route has been
         * matched
         */
        if (!empty($matches) AND
            ($matches[0] == $url))
        {

            return true;

        }

        return false;

    }


    /**
     * Get the route details
     * @return object Object of route details
     */
    public function getDetails()
    {

        $result             = new stdClass();
        $result->controller = $this->getController();
        $result->action     = $this->getAction();
        $result->slugs      = $this->getSlugs();
        $result->url        = $this->_url;

        return $result;

    }


}