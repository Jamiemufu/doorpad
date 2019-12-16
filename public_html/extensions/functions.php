<?php


use Whiskey\Bourbon\App\Facade\AppEnv;
use Whiskey\Bourbon\Helper\Component\SafeString;
use Whiskey\Bourbon\App\Bootstrap as Bourbon;


/*
 * Any user-defined global functions should reside here
 */


/*
 * Some framework helper functions
 */

/**
 * Retrieve AppEnv values
 * @param  string     $key AppEnv method name (key)
 * @return mixed|null      AppEnv value (or NULL if value has not been set)
 */
function app_env($key = '')
{

    return AppEnv::$key();

}


/**
 * Retrieve general configuration value
 * @param  string     $key Configuration entry key
 * @return mixed|null      Configuration entry value (or NULL if value has not been set)
 */
function config($key = '')
{

    return Bourbon::getInstance()->getConfiguration($key);

}


/**
 * Get the client side path to the vendor directory (useful for plugins with
 * asset directories with .htaccess files allowing access)
 * @return string Path to vendor directory
 */
function vendor_dir()
{

    $path  = Bourbon::getInstance()->getPublicPath();
    $path  = rtrim(rtrim($path, '/'), '_public');
    $path .= 'vendor/';

    return $path;

}


/**
 * Get the client side path to the _public directory
 * @return string Path to _public directory
 */
function public_dir()
{

    return Bourbon::getInstance()->getPublicPath();

}


/**
 * Get the client side path to the image directory
 * @return string Path to image directory
 */
function image_dir()
{

    return Bourbon::getInstance()->getPublicPath('images');

}


/**
 * Get the client side path to the CSS directory
 * @return string Path to CSS directory
 */
function css_dir()
{

    return Bourbon::getInstance()->getPublicPath('css');

}


/**
 * Get the client side path to the JS directory
 * @return string Path to JS directory
 */
function js_dir()
{

    return Bourbon::getInstance()->getPublicPath('js');

}


/**
 * Convert braces, quotes, etc. in strings (and the string values of arrays)
 * to their HTML entity equivalents
 * @param  string|array $string String/array to sanitise
 * @return string|array         Sanitised string/array
 */
function sanitise($string = '')
{

    return SafeString::sanitise($string);

}


/**
 * Convert HTML entity braces, quotes, etc. in strings (and the string
 * values of arrays) to their plaintext equivalents
 * @param  string|array $string String/array to unsanitise
 * @return string|array         Unsanitised string/array
 */
function unsanitise($string = '')
{

    return SafeString::unsanitise($string);

}


/**
 * Get an instance of the application bootstrapper
 * @return Bourbon Instance of Bourbon (Bootstrap) object
 */
function app()
{

    return Bourbon::getInstance();

}