<?php


namespace Whiskey\Bourbon\Templating\Engine\Ice;


/**
 * Vfs class
 * @package Whiskey\Bourbon\Templating\Engine\Ice
 * @link http://php.net/manual/en/stream.streamwrapper.example-1.php
 */
class Vfs
{


    protected $_position = 0;
    protected $_filename = '';


    static protected $_files = [];


    /**
     * Open the stream
     * @param  string $path        Path
     * @param  string $mode        Mode
     * @param  int    $options     Options
     * @param  string $opened_path Opened path
     * @return bool                Whether the stream was successfully opened
     */
    public function stream_open($path = '', $mode = '', $options = 0, &$opened_path = null)
    {

        $url             = parse_url($path);
        $this->_filename = sha1($url['host']);
        $this->_position = 0;

        if (!isset(static::$_files[$this->_filename]))
        {
            static::$_files[$this->_filename] = '';
        }

        return true;
    }


    /**
     * Read from the stream
     * @param  int    $count Number of bytes to read
     * @return string        File data
     */
    public function stream_read($count = 0)
    {

        $result           = substr(static::$_files[$this->_filename], $this->_position, $count);
        $this->_position += strlen($result);

        return $result;

    }


    /**
     * Write to the stream
     * @param  string $data Data to write
     * @return int          Number of bytes written
     */
    public function stream_write($data = '')
    {

        $before                            = substr(static::$_files[$this->_filename], 0, $this->_position);
        $after                             = substr(static::$_files[$this->_filename], $this->_position + strlen($data));
        static::$_files[$this->_filename]  = $before . $data . $after;
        $this->_position                  += strlen($data);

        return strlen($data);

    }


    /**
     * Get the pointer position within the stream
     * @return int Position of pointer
     */
    public function stream_tell()
    {

        return $this->_position;

    }


    /**
     * Check whether the pointer is at the end of the file
     * @return bool Whether the pointer is at the end of the file
     */
    public function stream_eof()
    {

        return $this->_position >= strlen(static::$_files[$this->_filename]);

    }


    /**
     * Seek to a specific position in the stream
     * @param  int  $offset Position offset
     * @param  int  $whence Location to offset from
     * @return bool         Whether the position was successfully set
     */
    public function stream_seek($offset = 0, $whence = SEEK_CUR)
    {

        switch ($whence)
        {

            case SEEK_SET:

                if ($offset < strlen(static::$_files[$this->_filename]) && $offset >= 0)
                {
                    $this->_position = $offset;
                    return true;
                }

                else
                {
                    return false;
                }

                break;

            case SEEK_CUR:

                if ($offset >= 0)
                {
                    $this->_position += $offset;
                    return true;
                }

                else
                {
                    return false;
                }

                break;

            case SEEK_END:

                if (strlen(static::$_files[$this->_filename]) + $offset >= 0)
                {
                    $this->_position = strlen(static::$_files[$this->_filename]) + $offset;
                    return true;
                }

                else
                {
                    return false;
                }

                break;

            default:
                return false;

        }

    }


    /**
     * Set metadata on the stream
     * @param  string $path   File path
     * @param  int    $option Option
     * @param  mixed  $value  (Optional) value
     * @return bool           Whether the metadata was successfully set
     */
    public function stream_metadata($path = '', $option = STREAM_META_TOUCH, $value = null)
    {

        if ($option == STREAM_META_TOUCH)
        {

            $url      = parse_url($path);
            $filename = sha1($url['host']);

            if (!isset(static::$_files[$filename]))
            {
                static::$_files[$filename] = '';
            }

            return true;

        }

        return false;

    }


    /**
     * Get information about the file resource
     * @return array Array of file resource information
     */
    public function stream_stat()
    {

        $time       = time();
        $size       = isset(static::$_files[$this->_filename]) ? mb_strlen(static::$_files[$this->_filename]) : 0;
        $block_size = 4096;

        return
            [
                0         => 0,
                1         => 0,
                2         => 0,
                3         => 0,
                4         => 0,
                5         => 0,
                6         => 0,
                7         => $size,
                8         => $time,
                9         => $time,
                10        => $time,
                11        => $block_size,
                12        => ceil($size / $block_size),
                'dev'     => 0,
                'ino'     => 0,
                'mode'    => 0,
                'nlink'   => 0,
                'uid'     => 0,
                'gid'     => 0,
                'rdev'    => 0,
                'size'    => $size,
                'atime'   => $time,
                'mtime'   => $time,
                'ctime'   => $time,
                'blksize' => $block_size,
                'blocks'  => ceil($size / $block_size)
            ];

    }


    /**
     * Get information about a URL resource
     * @param  string   $path  URL path
     * @param  int|null $flags Optional flags
     * @return array           Array of URL resource information
     */
    public function url_stat($path = '', $flags = null)
    {

        return $this->stream_stat();

    }


    /**
     * Unlink a file
     * @param  string $filename Filename to unlink
     * @return bool             Whether the file was successfully unlinked
     */
    public function unlink($filename = '')
    {

        $filename = parse_url($filename);
        $filename = sha1($filename['host']);

        unset(static::$_files[$filename]);

        return !isset(static::$_files[$filename]);

    }


}