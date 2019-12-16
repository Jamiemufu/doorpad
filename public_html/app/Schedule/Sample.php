<?php


namespace Whiskey\Bourbon\App\Schedule;


use stdClass;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;


/**
 * Sample scheduled job class
 * @package Whiskey\Bourbon\App\Schedule
 */
class Sample extends Job
{


    protected $_dependencies = null;


    /**
     * Instantiate the scheduled job object and gather dependencies
     * @param Db $db Db object
     */
    public function __construct(Db $db)
    {

        /*
         * Set dependencies -- type-hinted class arguments will be automatically
         * injected into the constructor
         */
        $this->_dependencies     = new stdClass();
        $this->_dependencies->db = $db;

        /*
         * Set the schedule for the job
         */
        $this->_everyFiveMinutes();

    }


    /**
     * Action to be executed when the job is due
     */
    public function run()
    {

        // Code to execute goes here

    }


}