<?php


namespace Whiskey\Bourbon\Storage\File;


use stdClass;
use InvalidArgumentException;


/**
 * StorageFile class
 * @package Whiskey\Bourbon\Storage\File
 */
class StorageFile
{


    const _NO_REUSE = true;


    protected $_dependencies  = null;
    protected $_id            = '';
    protected $_full_filename = '';
    protected $_filename      = '';
    protected $_relative_path = '';
    protected $_absolute_path = '';
    protected $_extension     = '';
    protected $_mime_type     = '';
    protected $_created       = 0;
    protected $_size          = 0;


    /**
     * Instantiate a StorageFile object
     * @param StorageAbstract $store         Storage instance
     * @param string          $id            Unique ID of file
     * @param string          $full_filename Full filename
     * @param string          $filename      Filename
     * @param string          $relative_path Relative public path to file
     * @param string          $absolute_path Absolute public path to file
     * @param string          $extension     File extension
     * @param string          $mime_type     MIME type
     * @param int             $created       Creation date
     * @param int             $size          File size
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(StorageAbstract $store, $id = '', $full_filename = '', $filename = '', $relative_path = '', $absolute_path = '', $extension = '', $mime_type = '', $created = 0, $size = 0)
    {

        if (!isset($store))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies        = new stdClass();
        $this->_dependencies->store = $store;

        $this->_id            = $id;
        $this->_full_filename = $full_filename;
        $this->_filename      = $filename;
        $this->_relative_path = $relative_path;
        $this->_absolute_path = $absolute_path;
        $this->_extension     = $extension;
        $this->_mime_type     = $mime_type;
        $this->_created       = $created;
        $this->_size          = $size;

    }


    /**
     * Get the file ID
     * @return string File ID
     */
    public function getId()
    {

        return $this->_id;

    }


    /**
     * Get the full filename
     * @return string Full filename
     */
    public function getFullFilename()
    {

        return $this->_full_filename;

    }


    /**
     * Get the filename
     * @return string Filename
     */
    public function getFilename()
    {

        return $this->_filename;

    }


    /**
     * Get the relative file path
     * @return string Relative file path
     */
    public function getRelativePath()
    {

        return $this->_relative_path;

    }


    /**
     * Get the absolute file path
     * @return string Absolute file path
     */
    public function getAbsolutePath()
    {

        return $this->_absolute_path;

    }


    /**
     * Get the file extension
     * @return string File extension
     */
    public function getExtension()
    {

        return $this->_extension;

    }


    /**
     * Get the raw storage filename
     * @return string Name of filename in storage engine
     */
    public function getRawFilename()
    {

        return basename($this->getRelativePath());

    }


    /**
     * Get the file creation date
     * @return int File creation timestamp
     */
    public function getCreatedDate()
    {

        return $this->_created;

    }


    /**
     * Get a human-readable version of the file creation date
     * @param  string $format Date format
     * @return string         File creation date
     */
    public function getFriendlyCreatedDate($format = 'jS F Y, H:i:s')
    {

        return date($format, $this->getCreatedDate());

    }


    /**
     * Get the file size
     * @return string File size
     */
    public function getSize()
    {

        return sprintf('%u', $this->_size);

    }


    /**
     * Get a human-readable version of the file size
     * @return string File size
     */
    public function getFriendlySize()
    {

        $filesize = $this->getSize();
        $result   = '1 B';

        $filesize_array =
            [
                '1'                         => 'B',
                '1024'                      => 'KiB',
                '1048576'                   => 'MiB',
                '1073741824'                => 'GiB',
                '1099511627776'             => 'TiB',
                '1125899906842624'          => 'PiB',
                '1152921504606846976'       => 'EiB',
                '1180591620717411303424'    => 'ZiB',
                '1208925819614629174706176' => 'YiB'
            ];

        $temp_filesize = 1;

        while ($filesize >= $temp_filesize)
        {

            $decimal_places = 0;

            if ($temp_filesize != 1)
            {
                $decimal_places = 2;
            }

            $result        = number_format(($filesize / $temp_filesize), $decimal_places) . ' ' . $filesize_array[$temp_filesize];
            $temp_filesize = ($temp_filesize * 1024);

        }

        if (!$filesize)
        {
            return '0 B';
        }

        return $result;

    }


    /**
     * Get the file MIME type
     * @return string File MIME type
     */
    public function getMimeType()
    {

        return $this->_mime_type;

    }


    /**
     * Output the file to the browser
     */
    public function output()
    {

        $this->_dependencies->store->output($this);

    }


    /**
     * Get the file contents
     * @return string|bool File contents or FALSE on fail
     */
    public function getContent()
    {

        return $this->_dependencies->store->getContent($this);

    }


    /**
     * Get the group name
     * @return string Group name
     */
    public function getGroup()
    {

        return $this->_dependencies->store->getGroup($this);

    }


    /**
     * Delete the file
     * @return bool Whether the file was successfully deleted
     */
    public function delete()
    {

        return $this->_dependencies->store->delete($this);

    }


    /**
     * Copy the file
     * @param  bool             $shared Whether the copied file will be shared
     * @param  string           $group  Group the copied file will belong to
     * @return StorageFile|bool         StorageFile object of copied file or FALSE on fail
     */
    public function copyTo($shared = false, $group = '')
    {

        return $this->_dependencies->store->copyTo($this, $shared, $group);

    }


    /**
     * Duplicate the file within the same group
     * @return StorageFile|bool StorageFile object of duplicated file or FALSE on fail
     */
    public function duplicate()
    {

        return $this->_dependencies->store->duplicate($this);

    }


    /**
     * Move the file
     * @param  bool             $shared Whether the moved file will be shared
     * @param  string           $group  Group the moved file will belong to
     * @return StorageFile|bool         StorageFile object of moved file or FALSE on fail
     */
    public function moveTo($shared = false, $group = '')
    {

        return $this->_dependencies->store->moveTo($this, $shared, $group);

    }


    /**
     * Rename the file
     * @param  string           $filename New filename
     * @return StorageFile|bool           StorageFile object of renamed file or FALSE on fail
     */
    public function rename($filename)
    {

        return $this->_dependencies->store->rename($this, $filename);

    }


    /**
     * Check whether the file [still] exists on disk
     * @return bool Whether the file exists
     */
    public function exists()
    {

        return $this->_dependencies->store->exists($this);

    }


}