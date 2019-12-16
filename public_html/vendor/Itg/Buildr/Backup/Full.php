<?php


namespace Itg\Buildr\Backup;


use Whiskey\Bourbon\App\Bootstrap as Bourbon;


/**
 * Full backup class
 * @package Itg\Buildr\Backup
 */
class Full extends BackupAbstract
{


    /**
     * Get the backup directory
     * @return string Backup directory
     */
    public function getBackupDirectory()
    {

        return Bourbon::getInstance()->getDataDirectory() . 'backups' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the backup file extension
     * @return string Backup file extension
     */
    public function getExtension()
    {

        return 'tgz';

    }


    /**
     * Create a new backup
     * @return bool Whether the backup was successfully created
     */
    public function create()
    {

        $filename = $this->getBackupDirectory() . time() . '.' . $this->getExtension();

        exec('tar --exclude "*.' . $this->getExtension() . '" -czf "' . $filename . '" ' . rtrim(realpath(Bourbon::getInstance()->getBaseDirectory()), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);

        return file_exists($filename);

    }


}