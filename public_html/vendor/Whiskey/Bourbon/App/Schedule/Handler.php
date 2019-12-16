<?php


namespace Whiskey\Bourbon\App\Schedule;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Instance;


/**
 * Schedule Handler class
 * @package Whiskey\Bourbon\App\Schedule
 */
class Handler
{


    protected $_directory    = null;
    protected $_dependencies = null;


    /**
     * Instantiate the schedule Handler object
     * @param Instance $instance_container Instance object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Instance $instance_container)
    {

        if (!isset($instance_container))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies                     = new stdClass();
        $this->_dependencies->instance_container = $instance_container;

    }


    /**
     * Set the schedule job directory
     * @param  string $directory Path to schedule job directory
     * @return bool              Whether the schedule job directory was successfully set
     */
    public function setDirectory($directory = null)
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_null($directory) AND
            is_readable($directory) AND
            is_writable($directory))
        {

            $this->_directory = $directory;

            $this->_init();

            return true;

        }

        return false;

    }


    /**
     * Set up the scheduled jobs directory
     * @return bool Whether the scheduled jobs was successfully set up
     */
    protected function _init()
    {

        /*
         * Fail immediately if the directory has not been set
         */
        if (is_null($this->_directory))
        {
            return false;
        }

        if (!$this->isActive())
        {

            /*
             * Directory check
             */
            if (!is_readable($this->_directory))
            {
                mkdir($this->_directory);
                file_put_contents($this->_directory . 'index.html', '');
            }

        }

        /*
         * Final check after all of the above
         */
        if ($this->isActive())
        {
            return true;
        }

        return false;

    }


    /**
     * Check if the scheduled jobs directory has been set up
     * @return bool Whether the scheduled jobs directory has been set up
     */
    public function isActive()
    {

        /*
         * Check if directory exists and is writable
         */
        if (!is_null($this->_directory) AND
            is_readable($this->_directory) AND
            is_dir($this->_directory))
        {
            return true;
        }

        return false;

    }


    /**
     * Get all scheduled jobs
     * @return array Array of scheduled Job objects
     * @throws EngineNotInitialisedException if scheduled jobs are not enabled
     */
    public function getAll()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Schedule not enabled');
        }

        $jobs = [];

        if (is_readable($this->_directory))
        {

            /*
             * Retrieve a list of all files in the job directory
             */
            $files = scandir($this->_directory);

            foreach ($files as $value)
            {

                /*
                 * Determine the fully-qualified class name of each file if it
                 * is a .php file
                 */
                if (!is_dir($value) AND mb_substr($value, -4) == '.php')
                {

                    $short_job_class_name = explode('.', $value);
                    $short_job_class_name = array_shift($short_job_class_name);
                    $long_job_class_name  = trim(__NAMESPACE__, '\\') . '\\' . $short_job_class_name;

                    /*
                     * Instantiate the job class and add it to an array to be
                     * returned
                     */
                    $job    = $this->_dependencies->instance_container->_retrieve($long_job_class_name);
                    $jobs[] = $job;

                }

            }

        }

        return $jobs;

    }


    /**
     * Run scheduled jobs that are due
     * @throws EngineNotInitialisedException if scheduled jobs are not enabled
     */
    public function run()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Schedule not enabled');
        }

        $jobs = $this->getAll();

        foreach ($jobs as $job)
        {
            if ($job->isDue())
            {
                $job->run();
            }
        }

    }


}