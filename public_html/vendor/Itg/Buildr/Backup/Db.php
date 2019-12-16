<?php


namespace Itg\Buildr\Backup;


use Whiskey\Bourbon\App\Bootstrap as Bourbon;


/**
 * Db backup class
 * @package Itg\Buildr\Backup
 */
class Db extends BackupAbstract
{


    /**
     * Get the backup directory
     * @return string Backup directory
     */
    public function getBackupDirectory()
    {

        return Bourbon::getInstance()->getDataDirectory() . 'db_backups' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the backup file extension
     * @return string Backup file extension
     */
    public function getExtension()
    {

        return 'sql';

    }


    /**
     * Create a new backup
     * @return bool Whether the backup was successfully created
     */
    public function create()
    {

        if (!$this->_dependencies->db->connected())
        {
            return false;
        }

        return $this->_dependencies->db->dump($this->getBackupDirectory() . time() . '.' . $this->getExtension());

    }


}