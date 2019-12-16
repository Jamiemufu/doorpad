<?php


namespace Whiskey\Bourbon\Storage\File\Engine;


use stdClass;
use Exception;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\MissingDependencyException;
use Whiskey\Bourbon\Exception\Storage\UnwritableFileException;
use Whiskey\Bourbon\Storage\File\StorageAbstract;
use Whiskey\Bourbon\Storage\File\StorageFile;
use Whiskey\Bourbon\App\Http\Response;
use Aws\S3\S3Client;


/**
 * S3 storage class
 * @package Whiskey\Bourbon\Storage\File\Engine
 */
class S3 extends StorageAbstract
{


    protected $_dependencies   = null;
    protected $_s3_bucket      = '';
    protected $_s3_credentials = ['key' => '', 'secret' => ''];
    protected $_s3_client      = null;


    /**
     * Instantiate the S3 storage object
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
     * Initialise the filesystem
     * @throws MissingDependencyException if the Amazon SDK is not available
     */
    protected function _init()
    {

        if (!class_exists(S3Client::class))
        {
            throw new MissingDependencyException('Amazon SDK could not be found');
        }

        if ($this->_s3_client === null AND
            isset($this->_s3_credentials['key']) AND
            isset($this->_s3_credentials['secret']) AND
            $this->_s3_credentials['key'] != '' AND
            $this->_s3_credentials['secret'] != '')
        {
            $this->_s3_client = S3Client::factory($this->_s3_credentials);
            $this->_s3_client->registerStreamWrapper();
        }

    }


    /**
     * Check whether the engine has been successfully initialised
     * @return bool Whether the engine is active
     */
    public function isActive()
    {

        if (!class_exists(S3Client::class))
        {
            return false;
        }

        $this->_init();

        if ($this->_s3_client === null)
        {
            return false;
        }

        return true;

    }


    /**
     * Get the path to the server side root storage directory
     * @return string Path to root storage directory
     */
    protected function _getStorageDirServer()
    {

        return $this->_s3_bucket . DIRECTORY_SEPARATOR;

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

        return $destination_dir;

    }


    /**
     * Get the server side destination path excluding the bucket
     * @param  bool   $private Whether the file is private
     * @param  string $group   Group name
     * @return string          Server side destination path excluding bucket
     */
    protected function _getDestinationPathWithoutBucketServer($private = true, $group = '')
    {

        $path   = $this->_getDestinationPathServer($private, $group);
        $bucket = $this->_s3_bucket;

        return mb_substr($path, (mb_strlen($bucket) + 1));

    }


    /**
     * Get the path to the client side root storage directory
     * @return string Path to root storage directory
     */
    protected function _getStorageDirClient()
    {

        $protocol = 'https://';

        /*
         * Workaround for S3 wildcard SSL cert issue that stops buckets with
         * dots being valid
         */
        if (mb_strstr($this->_s3_bucket, '.'))
        {
            $protocol = 'http://';
        }

        return $protocol . $this->_s3_bucket . '.s3.amazonaws.com/';

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

        return 's3';

    }


    /**
     * Store the name of the bucket to connect to and the credentials to make
     * the connection
     * @param string $bucket Bucket name
     * @param string $key    AWS key
     * @param string $secret AWS secret
     */
    public function connectToBucket($bucket = null, $key = null, $secret = null)
    {

        if (!is_null($bucket))
        {
            $this->_s3_bucket = $bucket;
        }

        if (!is_null($key))
        {
            $this->_s3_credentials['key'] = $key;
            $this->_s3_client             = null;
        }

        if (!is_null($secret))
        {
            $this->_s3_credentials['secret'] = $secret;
            $this->_s3_client                = null;
        }

        try
        {
            $this->_init();
        }

        catch (Exception $exception) {}

    }


    /**
     * Put a file into Amazon S3 storage
     * @param string $destination Remote path
     * @param string $source      Local path
     * @param string $mime_type   MIME type
     */
    protected function _s3Put($destination = '', $source = '', $mime_type = '')
    {

        $this->_s3_client->putObject(['Bucket'      => $this->_s3_bucket,
                                      'ContentType' => $mime_type,
                                      'Key'         => $destination,
                                      'SourceFile'  => $source,
                                      'ACL'         => $this->_private ? 'private' : 'public-read']);

    }


    /**
     * Put a local file into storage
     * @param  string           $filename Filename
     * @param  string           $source   File path
     * @return StorageFile|bool           StorageFile object of new file or FALSE on fail
     */
    protected function _putLocal($filename = '', $source = '')
    {

        /*
         * Work out the meta data and filename
         */
        $filename    = $this->_generateFilename($filename, $source);
        $destination = $this->_getDestinationPathWithoutBucketServer($this->_private, $this->_group) . $filename;

        /*
         * Try saving the file
         */
        try
        {

            $mime_type = $this->_parseFilename($filename);
            $mime_type = $mime_type['mime_type'];

            $this->_s3Put($destination, $source, $mime_type);

            return $this->get($this->_s3_bucket . DIRECTORY_SEPARATOR . $destination);

        }

        catch (Exception $exception) {}

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
            $destination = $this->_getDestinationPathWithoutBucketServer($this->_private, $this->_group) . $filename;

            /*
             * Try saving the file
             */
            try
            {

                $mime_type = $this->_parseFilename($filename);
                $mime_type = $mime_type['mime_type'];

                $this->_s3Put($destination, $temp_filename, $mime_type);

                @unlink($temp_filename);

                return $this->get($this->_s3_bucket . DIRECTORY_SEPARATOR . $destination);

            }

            catch (Exception $exception) {}

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
            $destination = $this->_getDestinationPathWithoutBucketServer($this->_private, $this->_group) . $filename;

            /*
             * Try saving the file
             */
            try
            {

                $mime_type = $this->_parseFilename($filename);
                $mime_type = $mime_type['mime_type'];

                $this->_s3Put($destination, $temp_filename, $mime_type);

                @unlink($temp_filename);

                return $this->get($this->_s3_bucket . DIRECTORY_SEPARATOR . $destination);

            }

            catch (Exception $exception) {}

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
        $absolute_path    = $relative_path;
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
        $excluded_files   = ['.', '..'];
        $files            = scandir('s3://' . $directory_server);

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
        $files     = scandir('s3://' . $root_dir);
        $blacklist = ['.', '..'];
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

        if (!is_readable('s3://' . $path))
        {
            $this->_dependencies->response->notFound();
        }

        else
        {

            $this->_dependencies->response->headers->set('Content-Type: ' . $file->getMimeType());
            $this->_dependencies->response->headers->set('Content-Length: ' . $file->getSize());
            $this->_dependencies->response->headers->set('Content-Disposition: inline;filename="' . $file->getFullFilename() . '"');

            $this->_dependencies->response->headers->output();

            readfile('s3://' . $path);

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

        if (is_readable('s3://' . $path))
        {
            return file_get_contents('s3://' . $path);
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

        return is_readable('s3://' . $path);

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

        return @unlink('s3://' . $path);

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

        try
        {

            $filename    = $this->_updateFilenameTimestamp(basename($file->getId()));
            $destination = $this->_getDestinationPathWithoutBucketServer(!$shared, $group) . $filename;

            $this->_s3_client->copyObject(['Bucket'     => $this->_s3_bucket,
                                           'Key'        => $destination,
                                           'CopySource' => $this->_s3_bucket . '/' . $file->getId(),
                                           'ACL'        => !$shared ? 'private' : 'public-read']);

            /*
             * Copy the storage object so that this one can be reused at a later
             * date if required
             */
            $copied_file = clone $this;

            $copied_file->shared($shared);
            $copied_file->group($group);

            return $copied_file->get($destination);

        }

        catch (Exception $exception) {}

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

        try
        {

            $filename     = $this->_updateFilenameTimestamp(basename($file->getId()));
            $destination  = mb_substr($file->getId(), 0, (0 - mb_strlen($file->getRawFilename())));
            $destination .= $filename;
            $private      = (mb_substr($file->getId(), 0, 7) == 'private') ? true : false;

            $this->_s3_client->copyObject(['Bucket'     => $this->_s3_bucket,
                                           'Key'        => $destination,
                                           'CopySource' => $this->_s3_bucket . '/' . $file->getId(),
                                           'ACL'        => $private ? 'private' : 'public-read']);

            return $this->get($destination);

        }

        catch (Exception $exception) {}

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

        try
        {

            $filename = $this->_filenameCleanse($filename);

            if ($filename == '')
            {
                throw new InvalidArgumentException('Invalid destination filename');
            }

            $filename     = $this->_updateFilenameName(basename($file->getId()), $filename);
            $filename     = $this->_updateFilenameTimestamp($filename);
            $destination  = mb_substr($file->getId(), 0, (0 - mb_strlen($file->getRawFilename())));
            $destination .= $filename;
            $private      = (mb_substr($file->getId(), 0, 7) == 'private') ? true : false;

            $this->_s3_client->copyObject(['Bucket'     => $this->_s3_bucket,
                                           'Key'        => $destination,
                                           'CopySource' => $this->_s3_bucket . '/' . $file->getId(),
                                           'ACL'        => $private ? 'private' : 'public-read']);

            $file->delete();

            return $this->get($destination);

        }

        catch (Exception $exception) {}

        return false;

    }


}