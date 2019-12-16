<?php


namespace Whiskey\Bourbon\Storage\File;


use finfo;


/**
 * StorageAbstract class
 * @package Whiskey\Bourbon\Storage\File
 */
abstract class StorageAbstract implements StorageInterface
{


    protected $_group   = '';
    protected $_private = true;


    const MAX_FILENAME_LENGTH   = 110;
    const MAX_GROUP_NAME_LENGTH = 150;


    /**
     * Specify whether or not the file should is shared
     * @param  bool $shared Whether the file is shared
     * @return self         StorageAbstract descendant for chaining
     */
    public function shared($shared = true)
    {

        $this->_private = !$shared;

        return $this;

    }


    /**
     * Specify to what group the file should belong
     * @param  string $group Group name
     * @return $this         StorageAbstract descendant for chaining
     */
    public function group($group = '')
    {

        $this->_group = (string)$group;

        return $this;

    }


    /**
     * Generate an encoded group name
     * @param  string $group Group name
     * @return string        Encoded group name
     */
    public function generateGroupName($group = '')
    {

        if (mb_strlen($group) > static::MAX_GROUP_NAME_LENGTH)
        {
            $group = mb_substr($group, 0, static::MAX_GROUP_NAME_LENGTH);
        }

        if ($group == '')
        {
            $group = 'default';
        }

        $group = base64_encode($group);
        $group = rtrim(strtr($group, '+/', '-_'), '=');

        return $group;

    }


    /**
     * Parse the group name
     * @param  string $group Encoded group name
     * @return string        Decoded group name
     */
    public function parseGroupName($group = '')
    {

        return base64_decode(str_pad(strtr($group, '-_', '+/'), (mb_strlen($group) % 4), '=', STR_PAD_RIGHT));

    }


    /**
     * Cleanse a filename to protect against directory traversal
     * @param  string $filename Filename
     * @return string           Cleansed filename
     */
    protected function _filenameCleanse($filename = '')
    {

        $filename = (string)$filename;
        $filename = explode('/', $filename);
        $filename = end($filename);

        return $filename;

    }


    /**
     * Inspect a filename and truncate it to a safe length if it is too long
     * @param  string $filename Filename to inspect
     * @return string           [Possibly] truncated filename
     */
    protected function _truncateFilename($filename = '')
    {

        /*
         * Shorten the filename if it's too long
         */
        if (mb_strlen($filename) > static::MAX_FILENAME_LENGTH)
        {
            $to_remove         = (mb_strlen($filename) - static::MAX_FILENAME_LENGTH);
            $filename_parts    = explode('.', $filename);
            $first_part        = reset($filename_parts);
            $first_part        = mb_substr($first_part, 0 , (mb_strlen($first_part) - $to_remove));
            $filename_parts[0] = $first_part;
            $filename          = implode('.', $filename_parts);
        }

        return $filename;

    }


    /**
     * Generate a filename containing basic meta data
     * @param  string $filename Input filename
     * @param  string $raw_path Full path to file
     * @return string           Output filename
     */
    protected function _generateFilename($filename = '', $raw_path = '')
    {

        $finfo     = new finfo(FILEINFO_MIME);
        $mime_type = $finfo->file($raw_path);
        $mime_type = explode(';', $mime_type);
        $mime_type = reset($mime_type);

        $size = filesize($raw_path);

        $filename  = $this->_truncateFilename($filename);
        $filename  = str_replace("\n", '', $filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $result       = [];
        $result['f']  = $filename;
        $result['c']  = microtime(true);
        $result['m']  = $mime_type;
        $result['s']  = $size;
        $result       = json_encode($result);
        $result       = rtrim(strtr(base64_encode($result), '+/', '-_'), '=');
        $result      .= '.' . $extension;

        return $result;

    }


    /**
     * Extract meta information from a filename
     * @param  string $file Filename
     * @return array        Array of file meta information
     */
    protected function _parseFilename($file = '')
    {

        $result =
            [
                'full_filename' => '',
                'filename'      => '',
                'extension'     => '',
                'created'       => 0,
                'mime_type'     => '',
                'size'          => 0
            ];

        $file = explode('.', $file);
        $file = reset($file);
        $file = base64_decode(str_pad(strtr($file, '-_', '+/'), (mb_strlen($file) % 4), '=', STR_PAD_RIGHT));
        $file = json_decode($file);

        if (!is_null($file) AND
            isset($file->f) AND
            isset($file->c) AND
            isset($file->m) AND
            isset($file->s))
        {
            $result['full_filename'] = $file->f;
            $result['filename']      = pathinfo($file->f, PATHINFO_FILENAME);
            $result['extension']     = pathinfo($file->f, PATHINFO_EXTENSION);
            $result['created']       = round((int)$file->c);
            $result['mime_type']     = $file->m;
            $result['size']          = (int)$file->s;
        }

        return $result;

    }


    /**
     * Convert a meta data array back into a 'generated' filename
     * @param  array  $file_meta_array Array of meta data
     * @return string                  Compiled filename
     */
    protected function _unparseFilename(array $file_meta_array = [])
    {

        $result       = [];
        $result['f']  = $file_meta_array['full_filename'];
        $result['c']  = $file_meta_array['created'];
        $result['m']  = $file_meta_array['mime_type'];
        $result['s']  = $file_meta_array['size'];
        $result       = json_encode($result);
        $result       = rtrim(strtr(base64_encode($result), '+/', '-_'), '=');
        $result      .= '.' . $file_meta_array['extension'];

        return $result;

    }


    /**
     * Update a filename with the current timestamp
     * @param  string $filename Old filename
     * @return string           New filename
     */
    protected function _updateFilenameTimestamp($filename = '')
    {

        $new_filename            = $this->_parseFilename($filename);
        $new_filename['created'] = microtime(true);
        $new_filename            = $this->_unparseFilename($new_filename);

        return $new_filename;

    }


    /**
     * Update a filename with a new name
     * @param  string $filename Old filename
     * @param  string $new_name New filename name
     * @return string           New filename
     */
    protected function _updateFilenameName($filename = '', $new_name = '')
    {

        $new_name                      = $this->_truncateFilename($new_name);
        $new_filename                  = $this->_parseFilename($filename);
        $new_filename['full_filename'] = $new_name;
        $new_filename['extension']     = pathinfo($new_name, PATHINFO_EXTENSION);
        $new_filename                  = $this->_unparseFilename($new_filename);

        return $new_filename;

    }


    /**
     * Get a filtered file list
     * @param  string $search_term Search terms
     * @param  array  $extensions  Optional array of accepted file extensions
     * @return array               Array of filtered StorageFile objects
     */
    public function search($search_term = '', $extensions = [])
    {

        $result       = $this->getAll();
        $extensions   = array_map('strtolower', $extensions);
        $search_terms = explode(' ', (string)$search_term);

        foreach ($result as $key => $file)
        {

            $extension = strtolower($file->getExtension());

            $extension_check   = (empty($extensions) OR in_array($extension, $extensions));
            $search_term_check = true;

            /*
             * Only do filename term search if the extension is okay
             */
            if ($extension_check)
            {

                $filename = $file->getFilename();

                /*
                 * Ensure that all search terms are present in the filename
                 */
                foreach ($search_terms as $term)
                {

                    if ($term != '' AND stristr($filename, $term) === false)
                    {

                        $search_term_check = false;

                        break;

                    }

                }

            }

            /*
             * Remove entries that fail at least one of the search criteria
             */
            if (!$extension_check OR !$search_term_check)
            {
                unset($result[$key]);
            }

        }

        return $result;

    }


}