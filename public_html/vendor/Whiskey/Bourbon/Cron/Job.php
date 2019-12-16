<?php


namespace Whiskey\Bourbon\Cron;


use stdClass;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Cron\TemporaryDirectoryWriteException;


/**
 * Cron Job class
 * @package Whiskey\Bourbon\Cron
 */
class Job
{


    protected $_dependencies         = null;
    protected $_filename             = null;
    protected $_original_minute      = '';
    protected $_original_hour        = '';
    protected $_original_day         = '';
    protected $_original_month       = '';
    protected $_original_day_of_week = '';
    protected $_original_command     = '';
    protected $_minute               = '';
    protected $_hour                 = '';
    protected $_day                  = '';
    protected $_month                = '';
    protected $_day_of_week          = '';
    protected $_command              = '';


    /**
     * Instantiate the Job object
     * @param Handler $handler     Handler object
     * @param string  $minute      Minute
     * @param string  $hour        Hour
     * @param string  $day         Day number
     * @param string  $month       Month number
     * @param string  $day_of_week Day of week
     * @param string  $command     Terminal command
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Handler $handler, $minute = '', $hour = '', $day = '', $month = '', $day_of_week = '', $command = '')
    {

        if (!isset($handler))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies          = new stdClass();
        $this->_dependencies->handler = $handler;

        /*
         * Job components
         */
        $this->_minute      = $this->_cleanse($minute);
        $this->_hour        = $this->_cleanse($hour);
        $this->_day         = $this->_cleanse($day);
        $this->_month       = $this->_cleanse($month);
        $this->_day_of_week = $this->_cleanse($day_of_week);
        $this->_command     = $command;

        /*
         * Copy of job components, to remove from crontab if the above are
         * edited and need to be reinserted with new values
         */
        $this->_original_minute      = $this->_minute;
        $this->_original_hour        = $this->_hour;
        $this->_original_day         = $this->_day;
        $this->_original_month       = $this->_month;
        $this->_original_day_of_week = $this->_day_of_week;
        $this->_original_command     = $this->_command;

    }


    /**
     * Get the minute value
     * @return string Minute value
     */
    public function getMinute()
    {

        return $this->_minute;

    }


    /**
     * Get the hour value
     * @return string Hour value
     */
    public function getHour()
    {

        return $this->_hour;

    }


    /**
     * Get the day value
     * @return string Day value
     */
    public function getDay()
    {

        return $this->_day;

    }


    /**
     * Get the month value
     * @return string Month value
     */
    public function getMonth()
    {

        return $this->_month;

    }


    /**
     * Get the day of week value
     * @return string Day of week value
     */
    public function getDayOfWeek()
    {

        return $this->_day_of_week;

    }


    /**
     * Get the command value
     * @return string Command value
     */
    public function getCommand()
    {

        return $this->_command;

    }


    /**
     * Set the minute value
     * @param string $minute Minute value
     */
    public function setMinute($minute = '')
    {

        $this->_minute = $minute;

    }


    /**
     * Set the hour value
     * @param string $hour Hour value
     */
    public function setHour($hour = '')
    {

        $this->_hour = $hour;

    }


    /**
     * Set the day value
     * @param string $day Day value
     */
    public function setDay($day = '')
    {

        $this->_day = $day;

    }


    /**
     * Set the month value
     * @param string $month Month value
     */
    public function setMonth($month = '')
    {

        $this->_month = $month;

    }


    /**
     * Set the day of week value
     * @param string $day_of_week Day of week value
     */
    public function setDayOfWeek($day_of_week = '')
    {

        $this->_day_of_week = $day_of_week;

    }


    /**
     * Set the command value
     * @param string $command Command value
     */
    public function setCommand($command = '')
    {

        $this->_command = $command;

    }


    /**
     * Check for the existence and writability of the temporary directory
     * @return bool Whether the directory exists and is writable
     */
    protected function _isActive()
    {

        return $this->_dependencies->handler->isActive();

    }


    /**
     * Get path to temporary crontab file
     * @return string Path to file
     * @throws TemporaryDirectoryWriteException if a temporary crontab file could not be created
     */
    protected function _getFilePath()
    {

        if (!is_null($this->_filename))
        {
            return $this->_filename;
        }

        else
        {

            $filename = tempnam(sys_get_temp_dir(), '_bourbon_crontab_');

            if ($filename === false)
            {
                throw new TemporaryDirectoryWriteException('Could not create a temporary crontab file');
            }

            $this->_filename = $filename;

            return $this->_filename;

        }

    }


    /**
     * Remove the temporary crontab file
     * @return bool Whether the file was successfully removed
     */
    protected function _clearTempFile()
    {

        $result = @unlink($this->_getFilePath());

        if ($result)
        {

            $this->_filename = null;

            return true;

        }

        return false;

    }


    /**
     * Strip illegal characters
     * @param  string $string String to cleanse
     * @return string         Cleansed string
     */
    protected function _cleanse($string = '')
    {

        return preg_replace("/[^0-9-,\/*]/", '', $string);

    }


    /**
     * Save the cron job
     * @return bool Whether the job was successfully saved
     */
    public function save()
    {

        if ($this->_isActive())
        {

            /*
             * Delete previous instances of both the original and new versions
             */
            $this->delete();

            $compiled_command = $this->_minute . ' ' .
                                $this->_hour . ' ' .
                                $this->_day . ' ' .
                                $this->_month . ' ' .
                                $this->_day_of_week . ' ' .
                                $this->_command;

            /*
             * Add the command to the list
             */
            $crontab   = $this->_dependencies->handler->getAllRaw();
            $crontab[] = $compiled_command;
            $crontab   = implode($crontab, PHP_EOL) . PHP_EOL;

            file_put_contents($this->_getFilePath(), $crontab);

            /*
             * Import the temporary file into the crontab
             */
            try
            {
                exec('crontab ' . $this->_getFilePath());
            }

            catch (Exception $exception)
            {

                $this->_clearTempFile();

                return false;

            }

            $this->_clearTempFile();

            /*
             * Check to see if the entry was added
             */
            $result = in_array($compiled_command, $this->_dependencies->handler->getAllRaw());

            /*
             * If the action was successful, replace the 'original' properties
             * with the new ones
             */
            if ($result)
            {
                $this->_original_minute      = $this->_minute;
                $this->_original_hour        = $this->_hour;
                $this->_original_day         = $this->_day;
                $this->_original_month       = $this->_month;
                $this->_original_day_of_week = $this->_day_of_week;
                $this->_original_command     = $this->_command;
            }

            return $result;

        }

        return false;

    }


    /**
     * Delete a cron job
     * @return bool Whether the job was successfully deleted
     */
    public function delete()
    {

        if ($this->_isActive())
        {

            $compiled_command = $this->_minute . ' ' .
                                $this->_hour . ' ' .
                                $this->_day . ' ' .
                                $this->_month . ' ' .
                                $this->_day_of_week . ' ' .
                                $this->_command;

            $compiled_command_original = $this->_original_minute . ' ' .
                                         $this->_original_hour . ' ' .
                                         $this->_original_day . ' ' .
                                         $this->_original_month . ' ' .
                                         $this->_original_day_of_week . ' ' .
                                         $this->_original_command;
            
            /*
             * Remove the command from the list
             */
            $crontab = $this->_dependencies->handler->getAllRaw();
            $crontab = array_diff($crontab, [$compiled_command, $compiled_command_original]);
            $crontab = implode($crontab, PHP_EOL) . PHP_EOL;

            file_put_contents($this->_getFilePath(), $crontab);

            /*
             * Import the temporary file into the crontab
             */
            try
            {
                exec('crontab ' . $this->_getFilePath());
            }

            catch (Exception $exception)
            {

                $this->_clearTempFile();

                return false;

            }

            $this->_clearTempFile();

            /*
             * Check to see if the entry was removed
             */
            return (!in_array($compiled_command, $this->_dependencies->handler->getAllRaw()));

        }

        return false;

    }


    /**
     * Get a string representation of the cron job
     * @return string String representation of cron job
     */
    public function __toString()
    {

        return $this->_minute . ' ' . $this->_hour . ' ' . $this->_day . ' ' . $this->_month . ' ' . $this->_day_of_week . ' ' . $this->_command;

    }


}