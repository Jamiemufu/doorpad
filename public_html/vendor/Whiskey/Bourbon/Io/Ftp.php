<?php


namespace Whiskey\Bourbon\Io;


use InvalidArgumentException;
use Whiskey\Bourbon\Exception\Io\InvalidConnectionException;


/**
 * Ftp class
 * @package Whiskey\Bourbon\Io
 */
class Ftp
{


    const _NO_REUSE = true;


    protected $_connection = null;


    /**
     * Instantiate an Ftp object and attempt an FTP connection
     * @param string $username FTP account username
     * @param string $password FTP account password
     * @param string $host     FTP host
     * @param int    $port     FTP port
     * @throws InvalidConnectionException if a connection could not be made
     */
    public function __construct($username = '', $password = '', $host = '', $port = 21)
    {

        $this->_connection = ftp_connect($host, $port);

        /*
         * Try to make a connection to the remote server
         */
        if (ftp_login($this->_connection, $username, $password))
        {
            ftp_pasv($this->_connection, true);
            ftp_set_option($this->_connection, FTP_TIMEOUT_SEC, 300);
        }

        else
        {
            throw new InvalidConnectionException('FTP connection unsuccessful');
        }

    }


    /**
     * Close the FTP connection when the object is no longer in use
     */
    public function __destruct()
    {

        if ($this->_connection !== null)
        {
            ftp_close($this->_connection);
        }

    }


    /**
     * Download a file from a remote FTP location
     * @param  string $remote_file Path to remote file
     * @param  string $local_file  Local path to save the file to
     * @return bool                Whether the file transfer was successful
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the filename(s) are not valid
     */
    public function download($remote_file = '', $local_file = '')
    {

        if (is_null($this->_connection))
        {
            throw new InvalidConnectionException('Invalid FTP connection');
        }

        if ((string)$remote_file == '' OR
            (string)$local_file  == '')
        {
            throw new InvalidArgumentException('Invalid filename(s)');
        }

        /*
         * Try to download the file
         */
        if (ftp_get($this->_connection, $local_file, $remote_file, FTP_BINARY))
        {

            /*
             * Check that the file exists locally on disk (if permissions
             * stopped it from being saved we don't want to return a false
             * positive)
             */
            if (is_readable($local_file))
            {
                return true;
            }

        }

        return false;

    }


    /**
     * Upload a file to a remote FTP location
     * @param  string $local_file  Path to local file
     * @param  string $remote_file Remote path to save the file to
     * @return bool                Whether the file transfer was successful
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the filename(s) are not valid
     *
     */
    public function upload($local_file = '', $remote_file = '')
    {

        if (is_null($this->_connection))
        {
            throw new InvalidConnectionException('Invalid FTP connection');
        }

        if ((string)$remote_file == '' OR
            (string)$local_file  == '')
        {
            throw new InvalidArgumentException('Invalid filename(s)');
        }

        /*
         * Try to upload the file
         */
        if (ftp_put($this->_connection, $remote_file, $local_file, FTP_BINARY))
        {
            return true;
        }

        return false;

    }


    /**
     * Delete a file from an FTP server
     * @param  string $remote_file Remote path to file to delete
     * @return bool                Whether the file was successfully deleted
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the filename is not valid
     */
    public function delete($remote_file = '')
    {

        if (is_null($this->_connection))
        {
            throw new InvalidConnectionException('Invalid FTP connection');
        }

        if ((string)$remote_file == '')
        {
            throw new InvalidArgumentException('Invalid filename(s)');
        }

        /*
         * Try to delete the file
         */
        if (ftp_delete($this->_connection, $remote_file))
        {
            return true;
        }

        return false;

    }


    /**
     * Create a directory on an FTP server
     * @param  string $directory Directory name
     * @return bool              Whether the directory was successfully created
     * @throws InvalidConnectionException if the connection is not valid
     * @throws InvalidArgumentException if the connection or directory name is not valid
     */
    public function createDirectory($directory = '')
    {

        if (is_null($this->_connection))
        {
            throw new InvalidConnectionException('Invalid FTP connection');
        }

        $directory = trim($directory, DIRECTORY_SEPARATOR);

        if ($directory == '')
        {
            throw new InvalidArgumentException('Invalid directory name');
        }

        /*
         * Break apart the path so it can be created one directory at a time
         */
        $directory_fragments = explode(DIRECTORY_SEPARATOR, $directory);

        foreach ($directory_fragments as $directory_fragment)
        {

            /*
             * Attempt to navigate to each subdirectory; if the operation fails
             * then we know that it does not exist
             */
            if (!(@ftp_chdir($this->_connection, $directory_fragment)))
            {

                /*
                 * Create the subdirectory and (if the operation does not fail)
                 * navigate to it
                 */
                if (ftp_mkdir($this->_connection, $directory_fragment) === false)
                {

                    $this->_changeToRootDirectory();

                    return false;

                }

                ftp_chdir($this->_connection, $directory_fragment);

            }

        }

        $this->_changeToRootDirectory();

        return true;

    }


    /**
     * Construct a relative path to the root directory and navigate back to it
     * @return bool Whether the operation was successful
     */
    protected function _changeToRootDirectory()
    {

        $current_path = explode('/', ftp_pwd($this->_connection));
        $root_path    = str_repeat('../', count($current_path) - 1);

        return ftp_chdir($this->_connection, $root_path);

    }


}