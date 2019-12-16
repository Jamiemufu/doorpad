<?php


namespace Whiskey\Bourbon\App\Migration;


use stdClass;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\Database\RecordWriteException;
use Whiskey\Bourbon\Instance;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;


/**
 * Migration Handler class
 * @package Whiskey\Bourbon\App\Migration
 */
class Handler
{


    protected $_dependencies = null;
    protected $_db_table     = '_bourbon_migrations';
    protected $_directory    = null;


    /**
     * Instantiate the migration Handler object
     * @param Db       $db                 Db object
     * @param Instance $instance_container Instance object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Db $db, Instance $instance_container)
    {

        if (!isset($db) OR
            !isset($instance_container))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies                     = new stdClass();
        $this->_dependencies->db                 = $db;
        $this->_dependencies->instance_container = $instance_container;

    }


    /**
     * Set the migration storage directory
     * @param  string $directory Path to migration storage directory
     * @return bool              Whether the migration storage directory was successfully set
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
     * Set up a migration table in the database
     * @return bool Whether the table was successfully set up
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

        if ($this->isActive())
        {
            return true;
        }

        else
        {

            /*
             * Directory check
             */
            if (!is_readable($this->_directory))
            {
                mkdir($this->_directory);
                file_put_contents($this->_directory . 'index.html', '');
            }

            if ($this->isActive())
            {
                return true;
            }

            else
            {

                /*
                 * Database check
                 */
                $table_columns   = [];
                $table_columns[] = ['field' => 'id', 'type' => 'bigint', 'length' => 20, 'auto_increment' => true, 'primary_key' => true];
                $table_columns[] = ['field' => 'migration', 'type' => 'int', 'length' => 11, 'null' => false, 'default' => 0];
                $table_columns[] = ['field' => 'datetime', 'type' => 'int', 'length' => 11, 'null' => false, 'default' => 0];
                
                try
                {
                    $this->_dependencies->db->create($this->_db_table, $table_columns);
                }

                catch (Exception $exception) {}

                if ($this->isActive())
                {
                    return true;
                }

                else
                {

                    /*
                     * Permission check
                     */
                    if (is_readable($this->_directory))
                    {
                        chmod($this->_directory, 0777);
                    }

                }

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
     * Get all migrations
     * @return array Array of migration Job objects
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function getAll()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }
        
        $migrations    = [];
        $migrations[0] = $this->_dependencies->instance_container->_retrieve(Job_0::class);

        if (is_readable($this->_directory))
        {

            $files = scandir($this->_directory);

            foreach ($files as $value)
            {

                if (!is_dir($value) AND mb_substr($value, -4) == '.php')
                {

                    /*
                     * Get just the timestamp from the filename
                     */
                    $migration_short_class_name = explode('.', $value);
                    $migration_short_class_name = array_shift($migration_short_class_name);

                    try
                    {
                        $migration_class                 = $this->getMigrationClassName($migration_short_class_name);
                        $migration                       = $this->_dependencies->instance_container->_retrieve($migration_class);
                        $migrations[$migration->getId()] = $migration;
                    }

                    catch (Exception $exception) {}

                }

            }

        }

        /*
         * Put into descending date order
         */
        ksort($migrations, SORT_NATURAL);

        $migrations = array_reverse($migrations, true);

        return $migrations;

    }


    /**
     * Reset the migration index
     * @return bool Whether the migration data was successfully reset
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function reset()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        return $this->_dependencies->db->truncate($this->_db_table);

    }


    /**
     * Run migration(s)
     * @param  int  $migration Migration to work towards
     * @return bool            Whether all migrations were successfully run
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function run($migration = null)
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        $migrations       = $this->getAll();
        $latest_migration = $this->getLatest();

        /*
         * Check that the target migration exists and is not the current
         * migration
         */
        if (($migration == 0 OR isset($migrations[$migration])) AND
            $migration != $latest_migration->getId())
        {

            /*
             * Ascertain in which direction the migrations are being run
             */
            if ($migration < $latest_migration->getId())
            {
                $direction  = 'down';
                $migrations = array_reverse($migrations, true);
            }

            else
            {
                $direction  = 'up';
            }

            /*
             * Shuffle off unneeded migrations until the starting point is
             * reached
             */
            foreach ($migrations as $migration_id => $temp_migration)
            {

                if ($direction == 'up' AND
                    ($temp_migration->getId() <= $latest_migration->getId() OR
                     $temp_migration->getId() > $migration))
                {
                    unset($migrations[$migration_id]);
                }

                if ($direction == 'down' AND
                    ($temp_migration->getId() > $latest_migration->getId() OR
                     $temp_migration->getId() < $migration))
                {
                    unset($migrations[$migration_id]);
                }
                
            }

            $migrations = array_reverse($migrations);

            /*
             * Iterate through each migration
             */
            foreach ($migrations as $active_migration)
            {

                /*
                 * Check that the active migration is within bounds
                 */
                if (($direction == 'up' AND $active_migration->getId() <= $migration) OR
                    ($direction == 'down' AND $active_migration->getId() >= $migration))
                {

                    /*
                     * Action the migration, unless we are moving down and this
                     * migration is the target
                     */
                    if (!($active_migration->getId() == $migration AND $direction == 'down'))
                    {
                        $active_migration->$direction();
                    }

                    /*
                     * Make a note of it if necessary
                     */
                    if ($active_migration->getId() != $latest_migration->getId())
                    {

                        try
                        {
                            $this->_addToLog($active_migration);
                        }

                        /*
                         * In the case of an error, halt here to minimise damage
                         */
                        catch (Exception $exception)
                        {
                            return false;
                        }

                    }

                }

            }

            /*
             * Successful if no errors were encountered
             */
            return true;

        }

        return false;

    }


    /**
     * Action a specific migration
     * @param  int  $migration    Migration to work towards
     * @param  bool $reorder_logs Whether to rewrite the logs so that the migration appears to have been run in order
     * @return bool               Whether all migrations were successfully run
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function action($migration = null, $reorder_logs = false)
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        $migrations = $this->getAll();

        if (isset($migrations[$migration]))
        {

            $migrations[$migration]->up();

            try
            {
                return $this->_addToLog($migrations[$migration], $reorder_logs);
            }

            catch (Exception $exception)
            {
                return false;
            }

        }

        return false;

    }


    /**
     * Create a new migration file
     * @return string Migration file name
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function create()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        return Job::create($this->_directory);

    }


    /**
     * Get the latest migration that has been run
     * @return Job Migration Job object
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function getLatest()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        $migration = $this->_dependencies->db->build()
                                             ->table($this->_db_table)
                                             ->fetch(1)
                                             ->orderBy('id', 'DESC')
                                             ->getField('migration');

        if ($migration)
        {

            $migration_class = $this->getMigrationClassName('Job_' . $migration);

            return $this->_dependencies->instance_container->_retrieve($migration_class);

        }

        /*
         * 'Origin' job
         */
        return $this->_dependencies->instance_container->_retrieve(Job_0::class);

    }


    /**
     * Add a migration's ID to the migration log
     * @param  Job  $migration    Migration Job object
     * @param  bool $reorder_logs Whether to rewrite the logs so that the migration appears to have been run in order
     * @return bool               Whether the migration log was successfully updated
     * @throws EngineNotInitialisedException if migrations are not enabled
     * @throws RecordWriteException if the migration log could not be updated
     */
    protected function _addToLog($migration = null, $reorder_logs = false)
    {

        $this->_init();
        
        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        /*
         * Add a log of the migration in the order it would have appeared if
         * executed by the run() method
         */
        if ($reorder_logs)
        {

            /*
             * Get all migration logs in reverse order, add on a dummy origin
             * entry and inspect them all
             */
            $migration_logs = $this->_dependencies->db->build()
                                                      ->table($this->_db_table)
                                                      ->select();

            $migration_logs   = array_reverse($migration_logs);
            $migration_logs[] = ['migration' => 0];

            foreach ($migration_logs as $position => $entry)
            {

                /*
                 * If a migration from the log is later than the one to be
                 * inserted, but the next migration in the log is earlier than
                 * the one to be inserted, insert a log of the migration at that
                 * point
                 */
                if ($entry['migration'] > $migration->getId() AND
                    isset($migration_logs[$position + 1]['migration']) AND
                    $migration_logs[$position + 1]['migration'] < $migration->getId())
                {

                    $new_entry =
                        [
                            'migration' => $migration->getId(),
                            'datetime'  => time()
                        ];

                    array_splice($migration_logs, ($position + 1), 0, [$new_entry]);

                    /*
                     * Put the list back into ascending order, remove the dummy
                     * origin entry, clear the logs and reinsert all entries
                     */
                    $migration_logs = array_reverse($migration_logs);
                    array_shift($migration_logs);

                    $this->_dependencies->db->toggleAutoCommit(false);
                    $this->_dependencies->db->truncate($this->_db_table);

                    foreach ($migration_logs as $migration_log)
                    {

                        try
                        {

                            $this->_dependencies->db->build()
                                                    ->table($this->_db_table)
                                                    ->data('migration', $migration_log['migration'])
                                                    ->data('datetime', $migration_log['datetime'])
                                                    ->insert();

                        }

                        /*
                         * If any one insertion fails, roll back changes
                         */
                        catch (Exception $exception)
                        {

                            $this->_dependencies->db->finalise(false);

                            break 2;

                        }

                    }

                    $this->_dependencies->db->finalise(true);

                    return true;

                }

            }

        }

        /*
         * Add a log of the migration without reordering the existing list
         */
        else
        {

            try
            {

                $this->_dependencies->db->build()
                                        ->table($this->_db_table)
                                        ->data('migration', $migration->getId())
                                        ->data('datetime', time())
                                        ->insert();

                return true;

            }

            catch (Exception $exception) {}

        }

        throw new RecordWriteException('Migration log could not be updated');

    }


    /**
     * Check if migrations can be used
     * @return bool Whether all migration prerequisites have been fulfilled
     */
    public function isActive()
    {

        if ($this->_dependencies->db->connected())
        {

            /*
             * Check if directory exists and is writable
             */
            if (!is_null($this->_directory) AND
                is_readable($this->_directory) AND
                is_dir($this->_directory) AND
                is_writable($this->_directory))
            {

                $table_name = $this->_dependencies->db->escape($this->_db_table);

                /*
                 * Check if the migration table exists
                 */
                $table_check = $this->_dependencies->db->raw('SHOW TABLES LIKE \'' . $table_name . '\'');

                foreach ($table_check as $value)
                {
                    foreach ($value as $value_2)
                    {
                        if ($value_2 == $this->_db_table)
                        {
                            return true;
                        }
                    }
                }

            }

        }

        return false;

    }


    /**
     * Get the fully-qualified class name of a migration
     * @param  string $migration Short migration class name
     * @return string            Fully-qualified migration class name
     * @throws InvalidArgumentException if the migration does not exist
     */
    public function getMigrationClassName($migration = 'Job_0')
    {

        $filename = $this->_directory . $migration . '.php';

        if ($migration == 'Job_0' OR is_readable($filename))
        {
            return trim(__NAMESPACE__, '\\') . '\\' . $migration;
        }

        throw new InvalidArgumentException('Invalid migration');

    }


    /**
     * Check whether any migrations that have not been executed exist past the current latest job
     * @return bool Whether outstanding migrations exist
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function areJobsOutstanding()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        $all    = $this->getAll();
        $newest = reset($all);
        $latest = $this->getLatest();

        if ($latest->getId() !== $newest->getId())
        {
            return true;
        }

        return false;

    }


    /**
     * Get any migrations that have been skipped over
     * @return array Array of migration Job objects
     * @throws EngineNotInitialisedException if migrations are not enabled
     */
    public function getSkipped()
    {

        $this->_init();

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Migrations not enabled');
        }

        /*
         * Get a list of the applied jobs, all jobs and the latest (applied)
         * job
         */
        $all_jobs     = $this->getAll();
        $all_jobs     = array_reverse($all_jobs);
        $latest_job   = $this->getLatest();
        $applied_jobs = $this->_dependencies->db->build()->table($this->_db_table)->select();

        foreach ($applied_jobs as $key => $applied_job)
        {
            $applied_jobs[$key] = $applied_job['migration'];
        }

        /*
         * Follow the list of jobs up and down, filtering to a new list
         */
        $current_job           = 0;
        $filtered_applied_jobs = [];

        foreach ($applied_jobs as $applied_job)
        {

            $job_has_migrated_down = ($applied_job < $current_job);
            $job_ahead_of_latest   = ($applied_job > $latest_job->getId());
            $current_job           = $applied_job;

            /*
             * Next sequential job (but not last run job)
             */
            if (!$job_has_migrated_down AND !$job_ahead_of_latest)
            {
                $filtered_applied_jobs[] = $applied_job;
            }

        }

        $filtered_applied_jobs = array_unique($filtered_applied_jobs);

        /*
         * Look for entries in the job list that have been skipped over
         */
        $result = [];

        foreach ($all_jobs as $job)
        {

            $job_is_past_latest               = $job->getId() > $latest_job->getId();
            $job_is_latest                    = $job->getId() == $latest_job->getId();
            $job_was_run_up                   = !in_array($job->getId(), $filtered_applied_jobs);
            $job_is_latest_but_was_not_run_up = ($job_is_latest AND !$job_was_run_up);
            $job_is_not_origin                = ($job->getId() != 0);

            if ($job_is_past_latest OR $job_is_latest_but_was_not_run_up)
            {
                break;
            }

            if ($job_is_not_origin AND $job_was_run_up)
            {
                $result[] = $job;
            }

        }

        return $result;

    }


}