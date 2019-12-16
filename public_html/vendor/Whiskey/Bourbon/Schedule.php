<?php


namespace Whiskey\Bourbon;


use Whiskey\Bourbon\App\Bootstrap as Bourbon;
use Whiskey\Bourbon\App\Http\Response;
use Whiskey\Bourbon\App\Facade\Router;
use Whiskey\Bourbon\App\Schedule\Handler as ScheduleHandler;


/**
 * Schedule class
 * @package Whiskey\Bourbon
 */
class Schedule
{


    protected static $_js_path   = '_whsky/schedule/execute';
    protected static $_link_root = null;


    /**
     * Get the URL fragment to access the schedule execution script
     * @param  bool   $full Whether to include the link root
     * @return string       URL path
     */
    public static function getPath($full = true)
    {

        return ($full ? self::getLinkRoot() : '') . static::$_js_path;

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
if (Router::isCurrentUrl(Schedule::getPath(false)))
{

    $response = Instance::_retrieve(Response::class);
    $schedule = Instance::_retrieve(ScheduleHandler::class);

    /*
     * Execute due scheduled jobs
     */
    $schedule->run();

    /*
     * Set and output the body
     */
    $response->body = "Scheduled jobs successfully run\n";

    $response->output();

    exit;

}