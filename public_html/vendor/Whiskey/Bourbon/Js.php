<?php


namespace Whiskey\Bourbon;


use Tholu\Packer\Packer;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\App\Facade\Router;
use Whiskey\Bourbon\App\Facade\Session;
use Whiskey\Bourbon\App\Facade\Url;
use Whiskey\Bourbon\App\Facade\Utils;


/**
 * Js class
 * @package Whiskey\Bourbon
 */
class Js
{


    protected static $_js_path   = '_whsky/scripts.min.js';
    protected static $_link_root = null;


    /**
     * Get the URL fragment to access the dashboard
     * @param  bool   $full      Whether to include the link root
     * @param  bool   $with_hash Whether to include the hash fragment
     * @return string            URL path
     */
    public static function getPath($full = true, $with_hash = true)
    {

        return ($full ? self::getLinkRoot() : '') . static::$_js_path . ($with_hash ? static::getHashFragment() : '');

    }


    /**
     * Get the URL hash fragment
     * @return string URL hash fragment
     */
    public static function getHashFragment()
    {

        return Session::remember('_bourbon_js_hash', function()
        {
            return '?' . hash('sha512', __DIR__ . date('Y-m-d') . Utils::random());
        });

    }


    /**
     * Get the application's link root
     * @return string Application link root
     */
    public static function getLinkRoot()
    {

        if (is_null(self::$_link_root))
        {
            self::$_link_root = Bourbon::getInstance()->getLinkRootPath();
        }

        return self::$_link_root;

    }


}


/*
 * Check to see if we've requested this page
 */
if (Router::isCurrentUrl(Js::getPath(false, false)) AND
    substr(Url::full(), (0 - mb_strlen(Js::getHashFragment()))) === Js::getHashFragment())
{

    $response = Instance::_retrieve(Response::class);

    /*
     * Set a JavaScript content type
     */
    $response->setContentType('javascript');

    /*
     * Compile an array of information that will be required by the JavaScript
     * library
     */
    $_whsky_public_dir = Bourbon::getInstance()->getPublicPath();
    $_whsky_image_dir  = Bourbon::getInstance()->getPublicPath('images');
    $_whsky_css_dir    = Bourbon::getInstance()->getPublicPath('css');
    $_whsky_js_dir     = Bourbon::getInstance()->getPublicPath('js');
    $_whsky_link_root  = Bourbon::getInstance()->getLinkRootPath();
    $_whsky_js_routes  = json_encode(Router::getAll(true, true));
    $_whsky_js_regexes = json_encode(Router::getRegexes());

    /*
     * Set and output the body
     */
    $whsky_js= <<<WHSKYJS
var _whskyJs = function()
{


    var routes    = $_whsky_js_routes;
    var regexes   = $_whsky_js_regexes;
    var link_root = '$_whsky_link_root';


    this.public_dir = '$_whsky_public_dir';
    this.image_dir  = '$_whsky_image_dir';
    this.css_dir    = '$_whsky_css_dir';
    this.js_dir     = '$_whsky_js_dir';


    var lTrim = function(string, substring)
    {

        var temp_result = string;

        if (temp_result.substr(0, substring.length) == substring)
        {
            temp_result = temp_result.slice(substring.length);
        }

        if (temp_result != string)
        {
            temp_result = lTrim(temp_result, substring);
        }

        return temp_result;

    };


    var rTrim = function(string, substring)
    {

        var temp_result = string;

        if (temp_result.substr(0 - substring.length) == substring)
        {
            temp_result = temp_result.slice(0, (0 - substring.length));
        }

        if (temp_result != string)
        {
            temp_result = rTrim(temp_result, substring);
        }

        return temp_result;

    };

    var checkController = function(controller)
    {

        var found_match = false;
        controller      = lTrim(controller, '\\\\');

        for (var url in routes)
        {

            if (routes.hasOwnProperty(url))
            {

                for (http_method in routes[url])
                {

                    if (routes[url].hasOwnProperty(http_method))
                    {

                        var route = routes[url][http_method];

                        if (controller.toLowerCase() == route.controller.toLowerCase())
                        {
                            found_match = true;
                        }

                    }

                }

            }

        }

        if (!found_match)
        {
            controller = 'Whiskey\\\\Bourbon\\\\App\\\\Http\\\\Controller\\\\' + controller;
        }

        return controller;

    };


    this.link = function()
    {

        var arguments  = [].slice.call(arguments);
        var controller = arguments.shift();
        var action     = arguments.shift().toLowerCase();
        var slugs      = arguments;

        controller = checkController(controller).toLowerCase();

        for (var url in routes)
        {

            if (routes.hasOwnProperty(url))
            {

                for (http_method in routes[url])
                {

                    if (routes[url].hasOwnProperty(http_method))
                    {

                        var route            = routes[url][http_method];
                        var route_controller = route.controller.toLowerCase();
                        var route_action     = route.action.toLowerCase();
                        var route_slugs      = slugs;

                        if (route_controller != '' && route_action != '' && route_controller == controller && route_action == action)
                        {

                            var route_url     = '';
                            var url_fragments = rTrim(lTrim(route.url, '/'), '/');
                            url_fragments     = url_fragments.split('/');

                            for (var fragment in url_fragments)
                            {

                                if (url_fragments.hasOwnProperty(fragment))
                                {

                                    route_url += '/';

                                    if (url_fragments[fragment] == '*' || (typeof regexes[url_fragments[fragment]] != 'undefined'))
                                    {
                                        route_url += encodeURIComponent(route_slugs.shift());
                                    }

                                    else if (url_fragments[fragment] == ':')
                                    {
                                        route_url += route_slugs.map(encodeURIComponent).join('/');
                                    }

                                    else
                                    {
                                        route_url += encodeURIComponent(url_fragments[fragment]);
                                    }

                                }

                            }

                            route_url = rTrim(link_root, '/') + '/' + lTrim(route_url, '/');

                            return route_url;

                        }

                    }

                }

            }

        }

        return '';

    };


};


var _whsky = new _whskyJs();


WHSKYJS
;

    $response->body = (new Packer($whsky_js, 'Normal', true, false, true))->pack();

    $response->output();

    exit;

}