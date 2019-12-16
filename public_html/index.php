<?php


namespace Whiskey\Bourbon;


use Whiskey\Bourbon\App\Bootstrap as Bourbon;


/*
 * Define a shorthand directory separator constant
 */
define('DS', DIRECTORY_SEPARATOR);


/*
 * Set the working directory to the current directory
 */
chdir(__DIR__);


/*
 * Figure out enough directories to include the bootstrapper
 */
$public_dir        = rtrim($_SERVER['SCRIPT_NAME'], basename($_SERVER['SCRIPT_NAME'])) . '_public/';
$root_dir          = rtrim(__DIR__, DS) . DS;
$bootstrapper_path = $root_dir . 'vendor' . DS . 'Whiskey' . DS . 'Bourbon' . DS . 'App' . DS . 'Bootstrap.php';


/*
 * Include and execute the bootstrapper
 */
require_once($bootstrapper_path);


(new Bourbon($root_dir, $public_dir))->execute('../.env');