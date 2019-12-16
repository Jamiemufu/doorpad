<?php


namespace Itg\Buildr\Backup;


use Exception;
use stdClass;
use Whiskey\Bourbon\Storage\Database\Mysql\Handler as Db;
use Whiskey\Bourbon\Helper\Utils;


/**
 * Abstract BackupAbstract class
 * @package Itg\Buildr\Backup
 */
abstract class BackupAbstract implements BackupInterface
{


    protected $_dependencies;


    /**
     * Instantiate the Backup object
     * @param Db    $db    Db object
     * @param Utils $utils Utils object
     * @throws Exception if dependencies are not provided
     */
    public function __construct(Db $db, Utils $utils)
    {

        if (!isset($db) OR
            !isset($utils))
        {
            throw new Exception('Dependencies not provided');
        }

        $this->_dependencies        = new stdClass();
        $this->_dependencies->db    = $db;
        $this->_dependencies->utils = $utils;

        $this->_setUp();

    }


    /**
     * Set up the backup directory
     */
    protected function _setUp()
    {

        /*
         * Create the backup directory if it doesn't exist
         */
        if (!file_exists($this->getBackupDirectory()))
        {
            mkdir($this->getBackupDirectory());
        }

        /*
         * If no .htaccess file exists to deny all requests, create it
         */
        if (!file_exists($this->getBackupDirectory() . '.htaccess'))
        {
            file_put_contents($this->getBackupDirectory() . '.htaccess', 'deny from all');
        }

        /*
         * If no index.html file exists to block directory views, create it
         */
        if (!file_exists($this->getBackupDirectory() . 'index.html'))
        {
            file_put_contents($this->getBackupDirectory() . 'index.html', '');
        }

    }


    /**
     * Create a new backup if required
     */
    public function checkAndCreate()
    {

        if ($this->_isBackupDue())
        {
            $this->create();
        }

    }


    /**
     * Check whether a new backup is due
     * @return bool Whether a new backup is due
     */
    protected function _isBackupDue()
    {

        /*
         * Initialise a variable to keep track of the most recent backup
         */
        $newest_backup = 0;

        /*
         * Go through all previous backup files
         */
        $files = scandir($this->getBackupDirectory());

        foreach ($files as $var => $value)
        {

            if ($value != '.' AND $value != '..')
            {

                $time = explode('.', $value);

                /*
                 * Check for files that are actual backup files
                 */
                if ($time[1] == $this->getExtension())
                {

                    /*
                     * Make a note of the backup time, if it's a more recent one
                     */
                    if ((int)$time[0] > $newest_backup)
                    {
                        $newest_backup = (int)$time[0];
                    }

                    /*
                     * If the backup is old, remove it
                     */
                    if ((time() - (int)$time[0]) > (isset($_ENV['BACKUP_EXPIRY']) ? (int)$_ENV['BACKUP_EXPIRY'] : 43200))
                    {
                        $this->delete((int)$time[0]);
                    }

                }

            }

        }

        /*
         * Perform a backup if enough time has passed
         */
        if ((time() - $newest_backup) > (isset($_ENV['BACKUP_PERIOD']) ? (int)$_ENV['BACKUP_PERIOD'] : 1209600))
        {
            return true;
        }

        return false;

    }


    /**
     * Get an array of details of all backups
     * @return array Array of backup details
     */
    public function getAll()
    {

        $result = [];
        $files  = glob($this->getBackupDirectory() . '*.' . $this->getExtension());

        natcasesort($files);

        $files = array_reverse($files);

        foreach ($files as $file)
        {

            $backup    = new stdClass();
            $filename  = basename($file);
            $file_time = basename($file, '.' . $this->getExtension());
            $file_size = filesize($file);

            $backup->filename      = $filename;
            $backup->path          = $this->getBackupDirectory() . $filename;
            $backup->size          = $file_size;
            $backup->friendly_size = $this->_dependencies->utils->friendlyFileSize($file_size);
            $backup->time          = $file_time;
            $backup->friendly_time = date('jS F Y, H:i', $file_time);

            $result[] = $backup;

        }

        return $result;

    }


    /**
     * Check whether a backup file exists
     * @param  int  $timestamp Timestamp from backup filename
     * @return bool            Whether the backup file exists
     */
    public function exists($timestamp = 0)
    {

        $timestamp = (int)$timestamp;
        $filename  = $this->getBackupDirectory() . $timestamp . '.' . $this->getExtension();

        if (is_readable($filename))
        {
            return true;
        }

        return false;

    }


    /**
     * Download a backup file
     * @param  int  $timestamp Timestamp from backup filename
     * @return bool            Whether the file exists to be downloaded
     */
    public function download($timestamp = 0)
    {

        $timestamp = (int)$timestamp;
        $filename  = $this->getBackupDirectory() . $timestamp . '.' . $this->getExtension();

        if (is_readable($filename))
        {

            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Cache-Control: post-check=0, pre-check=0', false);
            header('Pragma: no-cache');
            header('Content-Disposition: attachment; filename="' . $timestamp . '.' . $this->getExtension() . '"');

            readfile($filename);

            return true;

        }

        return false;

    }


    /**
     * Delete a backup file
     * @param  int  $timestamp Timestamp from backup filename
     * @return bool            Whether the backup was successfully deleted
     */
    public function delete($timestamp = 0)
    {

        $timestamp = (int)$timestamp;
        $filename  = $this->getBackupDirectory() . $timestamp . '.' . $this->getExtension();

        @unlink($filename);

        if (!is_readable($filename))
        {

            return true;

        }

        return false;

    }


}