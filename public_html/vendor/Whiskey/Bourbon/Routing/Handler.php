<?php


namespace Whiskey\Bourbon\Routing;


use Whiskey\Bourbon\Exception\Routing\InvalidRegexException;
use Whiskey\Bourbon\Exception\Routing\MatchingRouteNotFoundException;


/**
 * Route Handler class
 * @package Whiskey\Bourbon\Routing
 */
class Handler
{


    protected $_routes    = [];
    protected $_regexes   = [];
    protected $_url_path  = '';
    protected $_link_root = '';


    /**
     * Set the current URL path (relative to the application root)
     * @param string $path Current URL path
     */
    public function setUrlPath($path = '')
    {

        $this->_url_path = $path;

    }


    /**
     * Set the link root (application path relative to the domain root)
     * @param string $path Link root path
     */
    public function setRootPath($path = '')
    {

        $this->_link_root = $path;

    }


    /**
     * Add a custom regex pattern
     * @param string $tag     Tag to represent the regex in routes
     * @param string $pattern Regular expression pattern
     * @throws InvalidRegexException if the tag or regex are not valid
     */
    public function addRegex($tag = '', $pattern = '')
    {

        if ($tag == '' OR $pattern == '')
        {
            throw new InvalidRegexException('Invalid tag or regular expression');
        }

        $this->_regexes[$tag] = $pattern;

    }


    /**
     * Get an array of custom regex patterns
     * @return array Array of regex patterns
     */
    public function getRegexes()
    {

        return $this->_regexes;

    }


    /**
     * Add a route
     * @param Route Route object
     */
    public function add(Route $route)
    {

        $url         = $route->getRawUrl();
        $http_method = $route->getHttpMethod();

        $this->_routes[$url][$http_method] = $route;

    }


    /**
     * Check whether a route's HTTP method matches the current HTTP method
     * @param  Route $route Route object
     * @return bool         Whether the HTTP method matches
     */
    protected function _routeHttpMethodMatches(Route $route)
    {

        /*
         * AJAX requests
         */
        if (strtolower($route->getHttpMethod()) == 'ajax' AND
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
        {
            return true;
        }

        /*
         * Standard HTTP method requests
         */
        return (strtolower($_SERVER['REQUEST_METHOD']) == strtolower($route->getHttpMethod()));

    }


    /**
     * Get details of the current route
     * @return Route Route object for the current route
     * @throws MatchingRouteNotFoundException if a matching route cannot be found
     */
    public function getCurrentRoute()
    {

        foreach ($this->_routes as $url => $http_method_group)
        {

            foreach ($http_method_group as $http_method => $route)
            {

                if ($this->_routeHttpMethodMatches($route) AND
                    $route->check($this->_url_path))
                {
                    return $route;
                }

            }

        }

        throw new MatchingRouteNotFoundException('Matching route not found');

    }


    /**
     * Get a route by an additional information property
     * @param  string $property Name of property to match
     * @param  string $value    Property value to match
     * @return Route            Matching Route object (or FALSE if no matches found)
     * @throws MatchingRouteNotFoundException if a matching route cannot be found
     */
    public function getByAdditionalInformation($property = '', $value = '')
    {

        foreach ($this->_routes as $url => $http_method_group)
        {

            foreach ($http_method_group as $http_method => $route)
            {

                $additional = $route->getAdditionalInformation();

                if (isset($additional[$property]) AND
                    $additional[$property] == $value)
                {
                    return $route;
                }

            }

        }

        throw new MatchingRouteNotFoundException('Matching route not found');

    }


    /**
     * Generate a URL relative to the domain root
     * @param  string ... Multiple strings representing fully-qualified controller class, action name and slugs
     * @return string     URL relative to domain root
     * @throws MatchingRouteNotFoundException if a matching route cannot be found
     */
    public function generateUrl()
    {

        $arguments  = func_get_args();
        $controller = strtolower(array_shift($arguments));
        $action     = strtolower(array_shift($arguments));
        $slugs      = $arguments;

        foreach ($this->_routes as $url => $http_method_group)
        {

            foreach ($http_method_group as $http_method => $route)
            {

                $route_controller = strtolower($route->getController());
                $route_action     = strtolower($route->getAction());

                if ($route_controller != '' AND $route_action != '' AND
                    $route_controller == $controller AND $route_action == $action)
                {

                    $url = $route->generateUrl($slugs);
                    $url = rtrim($this->_link_root, '/') . '/' . ltrim($url, '/');

                    return $url;

                }

            }

        }

        throw new MatchingRouteNotFoundException('Matching route not found');

    }


    /**
     * Check whether a given URL matches the current URL
     * @param  string $url_fragment  URL fragment to check
     * @param  bool   $match_partial Return true on partial matches
     * @return bool                  Whether the URL fragment matches
     */
    public function isCurrentUrl($url_fragment = '', $match_partial = false)
    {

        $path         = $this->_url_path;
        $url_fragment = trim($url_fragment, '/');
        $path         = trim($path, '/');

        /*
         * Check for a partial match
         */
        if ($match_partial AND
            mb_substr($path, 0, mb_strlen($url_fragment)) == $url_fragment)
        {
            return true;
        }

        /*
         * Check for a full match
         */
        else if ($path == $url_fragment)
        {
            return true;
        }

        return false;

    }


    /**
     * Get all routes
     * @param  bool  $routes_as_details Whether to return stdClass objects instead of Route objects
     * @param  bool  $exclude_slugs     Whether to exclude hard-coded slugs
     * @return array                    Multidimensional array Route objects
     */
    public function getAll($routes_as_details = false, $exclude_slugs = false)
    {

        /*
         * Route objects
         */
        if (!$routes_as_details)
        {
            return $this->_routes;
        }

        /*
         * stdClass route detail objects
         */
        else
        {

            $result = [];

            foreach ($this->_routes as $url => $http_method_group)
            {

                foreach ($http_method_group as $http_method => $route)
                {

                    if (is_null($route->getClosure()))
                    {

                        $result[$url][$http_method] = $route->getDetails();

                        if ($exclude_slugs)
                        {
                            unset($result[$url][$http_method]->slugs);
                        }

                    }

                }

            }

            return $result;

        }

    }


}