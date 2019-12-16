<?php


namespace Itg\Buildr\Backup;


/**
 * BackupInterface interface
 * @package Itg\Buildr\Backup
 */
interface BackupInterface
{


    /**
     * Get the backup directory
     * @return string Backup directory
     */
    public function getBackupDirectory();


    /**
     * Get the backup file extension
     * @return string Backup file extension
     */
    public function getExtension();


    /**
     * Create a new backup
     * @return bool Whether the backup was successfully created
     */
    public function create();


}