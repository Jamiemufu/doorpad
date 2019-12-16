<?php


namespace Whiskey\Bourbon\Config\Type;


use Closure;
use InvalidArgumentException;
use Whiskey\Bourbon\Config\AbstractTemplateMulti;
use Whiskey\Bourbon\Exception\Config\Routes\InvalidRegexException;


/**
 * Class to define HTTP routes
 * @package Whiskey\Bourbon\Config
 */
class Routes extends AbstractTemplateMulti
{


    protected $_regexes           = [];
    protected $_global_middleware = [];


    protected $_options_template =
        [
            'http_method' => ['get', 'post', 'put', 'delete', 'ajax'], // Can also be a single string
            'model'       => '',    // Fully-qualified class name -- can be omitted if not required
            'controller'  => '',    // Fully-qualified class name -- can be omitted if not required
            'action'      => '',    // Name of controller method, or closure to execute instead
            'slugs'       => [],    // Array of hard-coded slugs
            'middleware'  => [],    // Array of fully-qualified middleware class names
            '404'         => false, // Whether the route serves as the 404 page
            '500'         => false  // Whether the route serves as the 500 page
        ];


    /**
     * Get the name of the configuration class
     * @return string Name of the configuration class
     */
    public function getName()
    {

        return 'routes';

    }


    /**
     * Add a custom regex pattern
     * @param string $tag     Tag to represent the regex in routes
     * @param string $pattern Regular expression pattern
     * @throws InvalidRegexException if the tag or regex are not valid
     */
    public function addRegex($tag = '', $pattern = '')
    {

        if ($tag == '' OR
            $pattern == '')
        {
            throw new InvalidRegexException('Invalid tag or regular expression');
        }

        $this->_regexes[$tag] = $pattern;

    }


    /**
     * Get all stored regexes
     * @return array Array of regexes with tags as keys
     */
    public function getRegexes()
    {

        return $this->_regexes;

    }


    /**
     * Add a global middleware class
     * @param string|array $middleware Fully-qualified middleware class name (or array of class names)
     */
    public function addGlobalMiddleware($middleware = '')
    {

        /*
         * Array of middleware class names
         */
        if (is_array($middleware))
        {

            foreach ($middleware as $entry)
            {
                $this->addGlobalMiddleware($entry);
            }

        }

        /*
         * Individual class name
         */
        else if ($middleware != '')
        {
            $this->_global_middleware[] = $middleware;
        }

    }


    /**
     * Get array of global middleware class names
     * @return array Array of fully-qualified middleware class names
     */
    public function getGlobalMiddleware()
    {

        return $this->_global_middleware;

    }


    /**
     * Set a route
     * @param string $url     URL of route
     * @param array  $options Array of route configuration values
     * @throws InvalidArgumentException if a name is not passed
     * @throws InvalidArgumentException if an options array is not passed
     */
    public function set($url = null, $options = [])
    {

        if (is_null($url))
        {
            throw new InvalidArgumentException('Route URL not provided');
        }

        if (!is_array($options))
        {
            throw new InvalidArgumentException('Route options not provided');
        }

        $url = '/' . trim($url, '/') . '/';

        /*
         * Fill in any missing values from the template, convert singular HTTP
         * methods & middleware to arrays and set the route details against each
         * HTTP method
         */
        $options = $options + $this->_options_template;

        if (!is_array($options['middleware']) OR
            (count($options['middleware']) == 1 AND is_array(reset($options['middleware'])) AND is_string(reset(array_keys($options['middleware'])))))
        {
            $options['middleware'] = [$options['middleware']];
        }

        if (!is_array($options['http_method']))
        {
            $options['http_method'] = [$options['http_method']];
        }

        foreach ($options['http_method'] as $http_method)
        {
            $this->_values[$url][$http_method] = $options;
        }

    }


    /**
     * Check to see if two domain names match
     * @param  string $domain_one Domain to check against (can contain '*' wildcard subdomains)
     * @param  string $domain_two Domain to check
     * @return bool               Whether the domain names match
     */
    protected function _compareDomains($domain_one = '', $domain_two = '')
    {

        $domain_one = parse_url(trim($domain_one));
        $domain_two = parse_url(trim($domain_two));

        if ($domain_one !== false AND
            $domain_two !== false AND
            isset($domain_one['host']) AND
            isset($domain_two['host']))
        {

            /*
             * Get the host names
             */
            $host_one = $domain_one['host'];
            $host_two = $domain_two['host'];

            /*
             * Convert the first domain into a regex where '*' is a wildcard
             */
            $host_one = explode('.', $host_one);

            foreach ($host_one as &$part)
            {

                /*
                 * Wildcards
                 */
                if ($part == '*')
                {
                    $part = '[^.]+';
                }

                /*
                 * Regular domain parts
                 */
                else
                {
                    $part = preg_quote($part);
                }

            }

            $host_one = '/' . implode('\\.', $host_one) . '/';

            preg_match($host_one, $host_two, $matches);

            if ($matches !== false AND
                isset($matches[0]) AND
                $matches[0] == $host_two)
            {
                return true;
            }

        }

        return false;

    }


    /**
     * Apply routes on a per-domain basis
     * @param string|array $domains  Domain name (or array of domain names)
     * @param Closure      $closure  Closure to execute when on specified domain(s)
     * @throws InvalidArgumentException if the closure is not valid
     */
    public function byDomain($domains = '', Closure $closure)
    {

        if (!(is_object($closure) AND ($closure instanceof Closure)))
        {
            throw new InvalidArgumentException('Invalid closure provided');
        }

        $current_domain = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

        /*
         * If a single domain has been passed, place it in an array
         */
        if (!is_array($domains))
        {
            $domains = [$domains];
        }

        foreach ($domains as $domain)
        {

            if ($this->_compareDomains($domain, $current_domain))
            {
                $closure();
            }

        }

    }


}