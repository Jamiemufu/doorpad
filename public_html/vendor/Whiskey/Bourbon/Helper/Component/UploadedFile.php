<?php


namespace Whiskey\Bourbon\Helper\Component;


use finfo;
use Whiskey\Bourbon\Exception\Helper\UploadedFile\MissingFileException;


class UploadedFile
{


    protected $_file   = [];
    protected $_errors =
        [
            UPLOAD_ERR_OK         => 'UPLOAD_ERR_OK',
            UPLOAD_ERR_INI_SIZE   => 'UPLOAD_ERR_INI_SIZE',
            UPLOAD_ERR_FORM_SIZE  => 'UPLOAD_ERR_FORM_SIZE',
            UPLOAD_ERR_PARTIAL    => 'UPLOAD_ERR_PARTIAL',
            UPLOAD_ERR_NO_FILE    => 'UPLOAD_ERR_NO_FILE',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
            UPLOAD_ERR_EXTENSION  => 'UPLOAD_ERR_EXTENSION'
        ];


    /**
     * Instantiate the UploadedFile object and check whether the file has been
     * uploaded
     * @param mixed $key $_FILES variable name or $_FILES element
     */
    public function __construct($key = '')
    {

        /*
         * First look for a matching entry in $_FILES
         */
        if (is_string($key) AND isset($_FILES[$key]))
        {
            $this->_file = $_FILES[$key];
        }
        
        /*
         * Otherwise see if a specific $_FILES element has been passed
         */
        else if (is_array($key) AND isset($key['name']))
        {
            $this->_file = $key;
        }

        /*
         * If no matches, set to null
         */
        else
        {
            $this->_file = null;
        }

    }


    /**
     * Check whether the uploaded file exists on disk
     * @return bool Whether or not the uploaded file exists
     */
    public function exists()
    {

        if (!is_null($this->_file) AND
            isset($this->_file['tmp_name']) AND
            is_readable($this->_file['tmp_name']))
        {
            return true;
        }

        return false;

    }


    /**
     * Get the full path to the file's current location
     * @return string Path to file
     * @throws MissingFileException if the file does not exist
     */
    public function getPath()
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return $this->_file['tmp_name'];

    }


    /**
     * Get the MD5 digest of the file
     * @return string MD5 digest
     * @throws MissingFileException if the file does not exist
     */
    public function getMd5()
    {

        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return md5_file($this->getPath());

    }


    /**
     * Get the SHA-1 digest of the file
     * @return string SHA-1 digest
     * @throws MissingFileException if the file does not exist
     */
    public function getSha1()
    {

        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return sha1_file($this->getPath());

    }


    /**
     * Move the uploaded file to a new location
     * @param  string $new_path New filename, including full path
     * @return bool             Whether the file was successfully moved
     * @throws MissingFileException if the file does not exist
     */
    public function move($new_path = '')
    {

        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        $new_path = (string)$new_path;
        $new_path = str_replace("\0", '', $new_path);

        if (move_uploaded_file($this->getPath(), $new_path))
        {

            $this->_file['tmp_name'] = $new_path;
            $this->_file['name']     = basename($new_path);

            return true;

        }

        return false;

    }


    /**
     * Get file size
     * @return string String representation of the file's size in bytes
     * @throws MissingFileException if the file does not exist
     */
    public function getSize()
    {

        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        clearstatcache(true, $this->getPath());

        /*
         * Return the filesize as a string by reading in an unsigned integer
         * (for 32-bit systems this should double the filesize that can be
         * returned)
         */
        return sprintf('%u', filesize($this->getPath()));

    }


    /**
     * Get original filename, or new filename if move() has been called
     * @return string Filename
     * @throws MissingFileException if the file does not exist
     */
    public function getFilename()
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        $filename = $this->_file['name'];

        /*
         * Remove null bytes and URL decode
         */
        $filename = str_replace("\0", '', $filename);
        $filename = urldecode($filename);

        return $filename;
    
    }


    /**
     * Get original extension, or new extension if move() has been called
     * @return string File extension
     * @throws MissingFileException if the file does not exist
     */
    public function getExtension()
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }
        
        $ext = strrchr($this->getFilename(), '.');
        
        return $ext ? ltrim($ext, '.') : '';

    }


    /**
     * Get MIME type
     * @return string MIME type
     * @throws MissingFileException if the file does not exist
     */
    public function getMimeType()
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        $finfo = new finfo(FILEINFO_MIME);

        $mime_type = $finfo->file($this->getPath());
        $mime_type = explode(';', $mime_type);

        return reset($mime_type);

    }


    /**
     * Check whether the file matches a MIME type using validateMimeType()
     * @param  mixed $type MIME type fragment string/array
     * @return bool        Whether the MIME type(s) match
     * @throws MissingFileException if the file does not exist
     */
    public function isType($type = '')
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return $this->_checkMimeType($type);

    }


    /**
     * Check the file's MIME type
     * @param  mixed $mime_type MIME type fragment string/array
     * @return bool             Whether the file's MIME type matches
     */
    protected function _checkMimeType($mime_type = [])
    {

        $filename = $this->getPath();

        if ((string)$filename !== '')
        {

            /*
             * If the MIME type is not an array, convert it to one
             */
            if (!is_array($mime_type))
            {
                $mime_type = [$mime_type];
            }

            /*
             * Instantiate an finfo object and break apart the file's MIME type
             */
            $finfo = new finfo(FILEINFO_MIME);

            $mime_info = $finfo->file($filename);
            $mime_info = explode(';', $mime_info);
            $mime_info = reset($mime_info);
            $mime_info = strtolower($mime_info);

            $mime_array = explode('/', str_replace('-', '/', $mime_info));

            /*
             * Iterate through the MIME fragment array to look for a match
             */
            foreach ($mime_type as $value)
            {

                if (in_array(strtolower($value), $mime_array))
                {
                    return true;
                }

            }

        }

        return false;

    }


    /**
     * Return the contents of the file
     * @return string File contents
     * @throws MissingFileException if the file does not exist
     */
    public function getContents()
    {
    
        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return file_get_contents($this->getPath());

    }


    /**
     * Write the contents of the file to the output buffer
     * @return int Number of bytes output
     * @throws MissingFileException if the file does not exist
     */
    public function output()
    {

        if (!$this->exists())
        {
            throw new MissingFileException('File does not exist');
        }

        return readfile($this->getPath());

    }


    /**
     * Return the name of the PHP error constant for the upload
     * @return string Error constant name (falls back to 'Unknown')
     */
    public function getError()
    {

        if (isset($this->_errors[$this->_file['error']]))
        {
            return $this->_errors[$this->_file['error']];
        }

        return 'Unknown';

    }


}