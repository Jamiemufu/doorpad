<?php


namespace Whiskey\Bourbon\Cron;


use Exception;


/**
 * Cron Handler class
 * @package Whiskey\Bourbon\Cron
 */
class Handler
{


    /**
     * Check for the existence and writability of the temporary directory
     * @return bool Whether the directory exists and is writable
     */
    public function isActive()
    {

        /*
         * Check that the system has a writable temporary directory
         */
        $temp_dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);

        if (is_readable($temp_dir) AND
            is_dir($temp_dir) AND
            is_writable($temp_dir))
        {

            /*
             * Check for Linux cron allow/deny files
             */
            if (strtolower(PHP_OS) == 'linux')
            {
                $cron_allow_file = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'cron.allow';
                $cron_deny_file  = DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'cron.deny';
            }

            /*
             * Check for Mac OS X cron allow/deny files -- Windows won't work
             * anyway, so 'else' is fine here
             */
            else
            {
                $cron_allow_file = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'cron.allow';
                $cron_deny_file  = DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'cron.deny';
            }

            /*
             * Get the current system user and determine if they're allowed to
             * use the crontab
             */
            $unix_user = trim(shell_exec('whoami'));

            if (is_readable($cron_allow_file))
            {
                
                $cron_allow = file_get_contents($cron_allow_file);
                $cron_allow = preg_split("/\n|\r/", $cron_allow, -1, PREG_SPLIT_NO_EMPTY);
                
                if (in_array($unix_user, $cron_allow))
                {
                    return true;
                }

            }

            /*
             * Also check if the user is denied permission to use the crontab
             */
            else if (is_readable($cron_deny_file))
            {

                $cron_deny = file_get_contents($cron_deny_file);
                $cron_deny = preg_split("/\n|\r/", $cron_deny, -1, PREG_SPLIT_NO_EMPTY);
                
                if (in_array($unix_user, $cron_deny))
                {
                    return false;
                }

                return true;

            }

            /*
             * Conditions by this point ought to be fine, as long as we're not
             * on a Windows server
             */
            else if (mb_substr(strtolower(PHP_OS), 0, 3) != 'win')
            {
                return true;
            }

        }

        /*
         * In all other circumstances, the crontab does not appear to be
         * accessible
         */
        return false;

    }


    /**
     * Get raw output of 'crontab -l'
     * @return array Array of cron jobs
     */
    public function getAllRaw()
    {
    
        $result = [];

        try
        {
            exec('crontab -l', $result);
        }

        catch (Exception $exception)
        {
            return [];
        }

        return $result;

    }


    /**
     * Get array of cron jobs, broken down by detail
     * @return array Array of cron Job objects
     */
    public function getAll()
    {

        $result  = [];
        $crontab = $this->getAllRaw();
        $count   = 0;
        
        foreach ($crontab as $cron_command)
        {

            if ($cron_command)
            {

                $line = explode(' ', $cron_command);

                $result[$count++] = new Job($this,                // Handler
                                            array_shift($line),   // Minute
                                            array_shift($line),   // Hour
                                            array_shift($line),   // Day
                                            array_shift($line),   // Month
                                            array_shift($line),   // Day of week
                                            implode(' ', $line)); // Command

            }

        }

        return $result;

    }


    /**
     * Add a cron job
     * @param  string $minute      Minute argument
     * @param  string $hour        Hour argument
     * @param  string $day         Day argument
     * @param  string $month       Month argument
     * @param  string $day_of_week Day of week argument
     * @param  string $command     Command to execute
     * @return bool                Whether the job was successfully added
     */
    public function add($minute = '*', $hour = '*', $day = '*', $month = '*', $day_of_week = '*', $command = '')
    {

        if ($command !== '' AND $this->isActive())
        {

            $job = new Job($this, $minute, $hour, $day, $month, $day_of_week, $command);

            return $job->save();

        }

        return false;

    }


    /**
     * Remove a cron job
     * @param  string $minute      Minute argument
     * @param  string $hour        Hour argument
     * @param  string $day         Day argument
     * @param  string $month       Month argument
     * @param  string $day_of_week Day of week argument
     * @param  string $command     Command to execute
     * @return bool                Whether the job was successfully removed
     */
    public function remove($minute = '*', $hour = '*', $day = '*', $month = '*', $day_of_week = '*', $command = '')
    {

        if ($command !== '' AND $this->isActive())
        {

            $job = new Job($this, $minute, $hour, $day, $month, $day_of_week, $command);

            return $job->delete();

        }

        return false;

    }


}