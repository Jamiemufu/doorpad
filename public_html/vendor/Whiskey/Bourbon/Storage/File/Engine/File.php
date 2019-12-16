<?php


namespace Whiskey\Bourbon\Storage\File\Engine;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Storage\UnwritableFileException;
use Whiskey\Bourbon\Storage\File\StorageAbstract;
use Whiskey\Bourbon\Storage\File\StorageFile;
use Whiskey\Bourbon\App\Http\Response;


/**
 * File storage class
 * @package Whiskey\Bourbon\Storage\File\Engine
 */
class File extends StorageAbstract
{


    protected $_dependencies     = null;
    protected $_server_directory = null;
    protected $_client_directory = null;


    /**
     * Instantiate the File storage object
     * @param Response $response Response object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Response $response)
    {

        if (!isset($response))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies           = new stdClass();
        $this->_dependencies->response = $response;

    }


    /**
     * Set the server side storage directory
     * @param  string $directory Path to server side storage directory
     * @return bool              Whether the server side storage directory was successfully set
     */
    public function setServerDirectory($directory = null)
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_null($directory) AND
            is_readable($directory) AND
            is_writable($directory))
        {

            $this->_server_directory = $directory;

            return true;

        }

        return false;

    }


    /**
     * Set the client side storage directory
     * @param  string $directory Path to client side storage directory
     * @return bool              Whether the client side storage directory was successfully set
     */
    public function setClientDirectory($directory = null)
    {

        $directory = rtrim($directory, '/') . '/';

        if (!is_null($directory))
        {

            $this->_client_directory = $directory;

            return true;

        }

        return false;

    }


    /**
     * Create a directory if it doesn't exist and add a blank index.html file
     * @param string $directory Directory path
     * @throws InvalidArgumentException if no directory is provided
     */
    protected function _setUpDirectory($directory = '')
    {

        if ($directory == '')
        {
            throw new InvalidArgumentException('No directory was supplied');
        }

        if (!file_exists($directory))
        {
            mkdir($directory);
        }

        if (!file_exists($directory . 'index.html'))
        {
            file_put_contents($directory . 'index.html', '');
        }

    }


    /**
     * Get an array of top-level storage directories
     * @return array Array of storage directories
     */
    protected function _getDirectoryArray()
    {

        $storage_dir = $this->_getStorageDirServer();
        $public_dir  = $this->_getPublicDirServer();
        $private_dir = $this->_getPrivateDirServer();

        return [$storage_dir, $public_dir, $private_dir];

    }


    /**
     * Initialise the filesystem
     * @throws EngineNotInitialisedException if a .htaccess file can't be written
     */
    protected function _init()
    {

        /*
         * Stop here if directories have not been set
         */
        if (is_null($this->_server_directory) OR
            is_null($this->_client_directory))
        {
            return;
        }

        /*
         * Set up storage directories
         */
        $storage_dirs = $this->_getDirectoryArray();
        $private_dir  = $this->_getPrivateDirServer();

        foreach ($storage_dirs as $directory)
        {
            $this->_setUpDirectory($directory);
        }

        /*
         * Create a .htaccess file in the 'private' directory, if necessary
         */
        if (!file_exists($private_dir . '.htaccess'))
        {
            file_put_contents($private_dir . '.htaccess', 'deny from all');
        }

        /*
         * Throw an exception if the .htaccess file still isn't present (break
         * the application if storage isn't secured)
         */
        if (!file_exists($private_dir . '.htaccess'))
        {
            throw new EngineNotInitialisedException('File store not secured');
        }

    }


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive()
    {

        /*
         * Stop here if directories have not been set
         */
        if (is_null($this->_server_directory) OR
            is_null($this->_client_directory))
        {
            return false;
        }

        $this->_init();

        $storage_dirs = $this->_getDirectoryArray();

        foreach ($storage_dirs as $directory)
        {

            if (!is_writable($directory))
            {
                return false;
            }

        }

        return true;

    }


    /**
     * Get the path to the server side root storage directory
     * @return string Path to root storage directory
     */
    protected function _getStorageDirServer()
    {

        return $this->_server_directory;

    }


    /**
     * Get the path to the server side public storage directory
     * @return string Path to public storage directory
     */
    protected function _getPublicDirServer()
    {

        return $this->_getStorageDirServer() . 'public' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the path to the server side private storage directory
     * @return string Path to private storage directory
     */
    protected function _getPrivateDirServer()
    {

        return $this->_getStorageDirServer() . 'private' . DIRECTORY_SEPARATOR;

    }


    /**
     * Get the server side destination path
     * @param  bool   $private Whether the file is private
     * @param  string $group   Group name
     * @return string          Server side destination path
     */
    protected function _getDestinationPathServer($private = true, $group = '')
    {

        $base_dir        = $private ? $this->_getPrivateDirServer() : $this->_getPublicDirServer();
        $group_dir       = $this->generateGroupName($group) . DIRECTORY_SEPARATOR;
        $destination_dir = $base_dir . $group_dir;

        $this->_setUpDirectory($destination_dir);

        return $destination_dir;

    }


    /**
     * Get the path to the client side root storage directory
     * @return string Path to root storage directory
     */
    protected function _getStorageDirClient()
    {

        return $this->_client_directory;

    }


    /**
     * Get the path to the client side public storage directory
     * @return string Path to public storage directory
     */
    protected function _getPublicDirClient()
    {

        return $this->_getStorageDirClient() . 'public' . '/';

    }


    /**
     * Get the path to the client side private storage directory
     * @return string Path to private storage directory
     */
    protected function _getPrivateDirClient()
    {

        return $this->_getStorageDirClient() . 'private' . '/';

    }


    /**
     * Get the name of the storage engine
     * @return string Name of the storage engine
     */
    public function getName()
    {

        return 'file';

    }


    /**
     * Put a local file into storage
     * @param  string           $filename Filename
     * @param  string           $source   File path
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     */
    protected function _putLocal($filename = '', $source = '')
    {

        $filename    = $this->_generateFilename($filename, $source);
        $destination = $this->_getDestinationPathServer($this->_private, $this->_group) . $filename;

        if (@copy($source, $destination))
        {
            return $this->get($destination);
        }

        return false;

    }


    /**
     * Put a remote file into storage
     * @param  string           $filename Filename
     * @param  string           $source   File path
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     * @throws UnwritableFileException if a temporary file cannot be created
     */
    protected function _putRemote($filename = '', $source = '')
    {

        /*
         * Get a copy of the file
         */
        $temp_filename = tempnam(sys_get_temp_dir(), '_bourbon_');

        if (!$temp_filename OR !is_readable($temp_filename))
        {
            throw new UnwritableFileException('Cannot create temporary file');
        }

        $handle = @fopen($source, 'r');

        if ($handle !== false AND
            @file_put_contents($temp_filename, $handle) !== false)
        {

            fclose($handle);

            /*
             * Work out the meta data and filename
             */
            $filename    = $this->_generateFilename($filename, $temp_filename);
            $destination = $this->_getDestinationPathServer($this->_private, $this->_group) . $filename;

            /*
             * Try saving the file
             */
            if (@copy($temp_filename, $destination) !== false)
            {

                @unlink($temp_filename);

                return $this->get($destination);

            }

        }

        @unlink($temp_filename);

        return false;

    }


    /**
     * Put raw data into storage
     * @param  string           $filename Filename
     * @param  string           $source   Raw data
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     * @throws UnwritableFileException if a temporary file cannot be created
     */
    protected function _putRaw($filename = '', $source = '')
    {

        /*
         * Work out the filename and meta information
         */
        $temp_filename = tempnam(sys_get_temp_dir(), '_bourbon_');

        if (!$temp_filename OR !is_readable($temp_filename))
        {
            throw new UnwritableFileException('Cannot create temporary file');
        }

        if (@file_put_contents($temp_filename, $source) !== false)
        {

            $filename    = $this->_generateFilename($filename, $temp_filename);
            $destination = $this->_getDestinationPathServer($this->_private, $this->_group) . $filename;

            /*
             * Try saving the file
             */
            if (@copy($temp_filename, $destination) !== false)
            {

                @unlink($temp_filename);

                return $this->get($destination);

            }

        }

        @unlink($temp_filename);

        return false;

    }


    /**
     * Put a file into storage
     * @param  string           $source   Path to source file
     * @param  string           $filename Filename to use for saved file
     * @param  bool             $raw_data Whether raw data is being passed
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     * @throws EngineNotInitialisedException if the store has not been initialised
     * @throws InvalidArgumentException if a source file was not specified
     * @throws InvalidArgumentException if the destination name is not valid
     */
    public function put($source = '', $filename = '', $raw_data = false)
    {

        $filename = $this->_filenameCleanse($filename);

        $original_filename = $filename;

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('File store not initialised');
        }

        if ($source == '' AND !$raw_data)
        {
            throw new InvalidArgumentException('Source file not specified');
        }

        /*
         * If a filename has not been provided, create one from the source
         */
        if ($filename == '')
        {
            $filename = basename($source);
        }

        if ($filename == '' AND !$raw_data)
        {
            throw new InvalidArgumentException('Invalid destination filename');
        }

        /*
         * Local file
         */
        if (!$raw_data AND
            @is_readable($source) AND
            @is_file($source))
        {

            return $this->_putLocal($filename, $source);

        }

        /*
         * Remote file
         */
        else if (!$raw_data AND filter_var($source, FILTER_VALIDATE_URL))
        {

            return $this->_putRemote($filename, $source);

        }

        /*
         * Raw data
         */
        else if ($raw_data)
        {

            if ($original_filename == '')
            {
                throw new InvalidArgumentException('Raw data must be accompanied by a filename');
            }

            return $this->_putRaw($filename, $source);

        }

        return false;

    }


    /**
     * Get a StorageFile object
     * @param  string      $id File ID
     * @return StorageFile     StorageFile object
     * @throws EngineNotInitialisedException if the store has not been initialised
     * @throws InvalidArgumentException if the file ID is not valid
     */
    public function get($id = '')
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('File store not initialised');
        }

        if ($id == '')
        {
            throw new InvalidArgumentException('Invalid file ID');
        }

        /*
         * Add the store directory on to the beginning of the filename if it is
         * missing, to ensure that we can perform full path checks
         */
        if (mb_substr($id, 0, mb_strlen($this->_getStorageDirServer())) != $this->_getStorageDirServer())
        {
            $id = $this->_getStorageDirServer() . $id;
        }

        /*
         * Check that the file resides within the store directory
         */
        $temp_id          = $id;
        $id               = realpath($id);
        $directory_server = realpath($this->_getStorageDirServer());

        /*
         * If either of the above failed, fall back to the unverified file paths
         */
        if ($id === false OR $directory_server === false)
        {
            $id               = $temp_id;
            $directory_server = $this->_getStorageDirServer();
        }

        if (mb_substr($id, 0, mb_strlen($directory_server)) != $directory_server)
        {
            throw new InvalidArgumentException('Invalid file ID');
        }

        /*
         * Remove the store directory to make the ID more compact
         */
        if (mb_substr($id, 0, mb_strlen($this->_getStorageDirServer())) == $this->_getStorageDirServer())
        {
            $id = mb_substr($id, mb_strlen($this->_getStorageDirServer()));
        }

        /*
         * Assemble all of the file meta data
         */
        $id               = ltrim($id, DIRECTORY_SEPARATOR);
        $base_name        = basename($id);
        $directory_client = $this->_getStorageDirClient();
        $file_meta        = $this->_parseFilename($base_name);
        $full_filename    = $file_meta['full_filename'];
        $filename         = $file_meta['filename'];
        $relative_path    = $directory_client . $id;
        $absolute_path    = 'http' . ((!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] !== 'off') ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . $relative_path;
        $extension        = $file_meta['extension'];
        $created          = (int)$file_meta['created'];
        $mime_type        = $file_meta['mime_type'];
        $size             = $file_meta['size'];

        return new StorageFile($this,
                               $id,
                               $full_filename,
                               $filename,
                               $relative_path,
                               $absolute_path,
                               $extension,
                               $mime_type,
                               $created,
                               $size);

    }


    /**
     * Get details of all files in the store
     * @return array Array of StorageFile objects
     * @throws EngineNotInitialisedException if the store has not been initialised
     */
    public function getAll()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('File store not initialised');
        }

        $result           = [];
        $directory_server = $this->_getDestinationPathServer($this->_private, $this->_group);
        $excluded_files   = ['.', '..', '.htaccess', 'index.html'];
        $files            = scandir($directory_server);

        foreach ($files as $file)
        {

            if (!in_array($file, $excluded_files))
            {
                $result[] = $this->get($directory_server . $file);
            }

        }

        return $result;

    }


    /**
     * Get the group name
     * @param  StorageFile $file StorageFile object
     * @return string            Group name
     */
    public function getGroup(StorageFile $file)
    {

        $file_path  = $file->getAbsolutePath();
        $file_path  = explode('/', $file_path);
        $group_name = $file_path[count($file_path) - 2];
        $group_name = $this->parseGroupName($group_name);

        return $group_name;

    }


    /**
     * Get a list of all groups
     * @return array Array of group names
     */
    public function getGroups()
    {

        $root_dir  = $this->_private ? $this->_getPrivateDirServer() : $this->_getPublicDirServer();
        $files     = scandir($root_dir);
        $blacklist = ['.', '..', 'index.html', '.htaccess'];
        $result    = [];

        foreach ($files as $file)
        {

            if (!in_array($file, $blacklist))
            {
                $result[] = $this->parseGroupName($file);
            }

        }

        return $result;

    }


    /**
     * Output the file to the browser
     * @param StorageFile $file StorageFile object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function output(StorageFile $file)
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $path = $this->_getStorageDirServer() . $file->getId();

        if (!is_readable($path))
        {
            $this->_dependencies->response->notFound();
        }

        else
        {

            $this->_dependencies->response->headers->set('Content-Type: ' . $file->getMimeType());
            $this->_dependencies->response->headers->set('Content-Length: ' . $file->getSize());
            $this->_dependencies->response->headers->set('Content-Disposition: inline;filename="' . $file->getFullFilename() . '"');

            $this->_dependencies->response->headers->output();

            readfile($path);

        }

        exit;

    }


    /**
     * Get the file contents
     * @param  StorageFile $file StorageFile object
     * @return string|bool       File contents or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function getContent(StorageFile $file)
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $path = $this->_getStorageDirServer() . $file->getId();

        if (is_readable($path))
        {
            return file_get_contents($path);
        }

        return false;

    }


    /**
     * Check whether the file [still] exists on disk
     * @param  StorageFile $file StorageFile object
     * @return bool              Whether the file exists
     */
    public function exists(StorageFile $file)
    {

        $path = $this->_getStorageDirServer() . $file->getId();

        return is_readable($path);

    }


    /**
     * Delete the file
     * @param  StorageFile $file StorageFile object
     * @return bool              Whether the file was successfully deleted
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function delete(StorageFile $file)
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $path = $this->_getStorageDirServer() . $file->getId();

        return @unlink($path);

    }


    /**
     * Copy the file
     * @param  StorageFile      $file   StorageFile object
     * @param  bool             $shared Whether the copied file will be shared
     * @param  string           $group  Group the copied file will belong to
     * @return StorageFile|bool         StorageFile object of copied file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function copyTo(StorageFile $file, $shared = false, $group = '')
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $path = $this->_getStorageDirServer() . $file->getId();

        /*
         * Copy the storage object so that this one can be reused at a later
         * date if required
         */
        $file_storage = clone $this;

        $file_storage->shared($shared);
        $file_storage->group($group);

        $copied_file = $file_storage->put($path, $file->getFullFilename());

        if ($copied_file !== false)
        {
            return $copied_file;
        }

        return false;

    }


    /**
     * Duplicate the file within the same group
     * @param  StorageFile      $file StorageFile object
     * @return StorageFile|bool       StorageFile object of duplicated file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function duplicate(StorageFile $file)
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $source       = $this->_getStorageDirServer() . $file->getId();
        $filename     = $this->_updateFilenameTimestamp(basename($file->getId()));
        $destination  = $this->_getStorageDirServer();
        $destination .= mb_substr($file->getId(), 0, (0 - mb_strlen($file->getRawFilename())));
        $destination .= $filename;

        if (@copy($source, $destination) !== false)
        {
            return $this->get($destination);
        }

        return false;

    }


    /**
     * Move the file
     * @param  StorageFile      $file   StorageFile object
     * @param  bool             $shared Whether the moved file will be shared
     * @param  string           $group  Group the moved file will belong to
     * @return StorageFile|bool         StorageFile object of moved file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function moveTo(StorageFile $file, $shared = false, $group = '')
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $moved_file = $this->copyTo($file, $shared, $group);

        if ($moved_file !== false)
        {

            $file->delete();

            return $moved_file;

        }

        return false;

    }


    /**
     * Rename the file
     * @param  StorageFile      $file     StorageFile object
     * @param  string           $filename New filename
     * @return StorageFile|bool           StorageFile object of renamed file or FALSE on fail
     * @throws InvalidArgumentException if dependencies are not provided
     * @throws InvalidArgumentException if the destination filename is not valid
     */
    public function rename(StorageFile $file, $filename = '')
    {

        if (!isset($file))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $filename = $this->_filenameCleanse($filename);

        if ($filename == '')
        {
            throw new InvalidArgumentException('Invalid destination filename');
        }

        $source       = $this->_getStorageDirServer() . $file->getId();
        $filename     = $this->_updateFilenameName(basename($file->getId()), $filename);
        $filename     = $this->_updateFilenameTimestamp($filename);
        $destination  = $this->_getStorageDirServer();
        $destination .= mb_substr($file->getId(), 0, (0 - mb_strlen($file->getRawFilename())));
        $destination .= $filename;

        if (@rename($source, $destination) !== false)
        {
            return $this->get($destination);
        }

        return false;

    }


}