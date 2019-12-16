<?php


namespace Whiskey\Bourbon\Templating\Engine\Ice;


use Closure;
use Exception;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Exception\Templating\Ice\TemporaryFileWriteException;
use Whiskey\Bourbon\Exception\Templating\InvalidDirectoryException;
use Whiskey\Bourbon\Exception\Templating\Ice\InvalidParserException;
use Whiskey\Bourbon\Exception\Templating\MissingTemplateException;


/**
 * Ice Renderer class
 * @package Whiskey\Bourbon\Templating\Engine\Ice
 */
class Renderer
{


    protected $_latest_variables = [];
    protected $_custom_parsers   = [];
    protected $_base_dirs        = [];
    protected $_cache_dir        = null;
    protected $_vfs_protocol     = 'ice';


    /**
     * Register the virtual filesystem
     */
    public function __construct()
    {

        $this->_vfs_protocol .= uniqid();

        stream_wrapper_register($this->_vfs_protocol, Vfs::class);

    }


    /**
     * Unregister the virtual filesystem
     */
    public function __destruct()
    {

        stream_wrapper_unregister($this->_vfs_protocol);

    }


    /**
     * Check whether the templating engine is active
     * @return bool Whether the templating engine is active
     */
    public function isActive()
    {

        return !!count($this->_base_dirs);

    }


    /**
     * Make a copy of the current renderer
     * @return Renderer Ice renderer
     */
    protected function _cloneRenderer()
    {

        $ice = new static();

        foreach ($this->_base_dirs as $directory)
        {
            $ice->addBaseDirectory($directory);
        }

        if (!is_null($this->_cache_dir))
        {
            $ice->setCacheDirectory($this->_cache_dir);
        }

        return $ice;

    }


    /**
     * Determine if the template extends another
     * @param  string      $html HTML string
     * @return string|null       Path to extended template (or NULL if it doesn't extend any)
     */
    protected function _getExtendedTemplate($html = '')
    {

        $pattern = "/@extends\([\\\"|\'](.*)[\\\"|\']\)/";

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        /*
         * Only use the last @extends() tag
         */
        $match = end($matches);

        if (isset($match[1]))
        {
            return $match[1];
        }

        return null;

    }


    /**
     * Get the sections from the template
     * @param  string $html HTML string
     * @return array        Array of sections
     */
    protected function _getSections($html = '')
    {

        $pattern = "/@section\([\\\"|\'](\\S*)[\\\"|\']\)(.*?(?=@endsection))@endsection/s";

        preg_match_all($pattern, $html, $matches, PREG_SET_ORDER);

        $sections = [];

        foreach ($matches as $match)
        {
            $sections[$match[1]] = $match[2];
        }

        return $sections;

    }


    /**
     * @param  string $html     HTML string
     * @param  array  $sections Array of sections
     * @return string           HTML string with sections replaced
     */
    protected function _replaceSections($html = '', array $sections = [])
    {

        foreach ($sections as $section_name => $content)
        {

            $pattern     = "/@section\([\\\"|\'](" . preg_quote($section_name) . ")[\\\"|\']\)(.*?(?=@endsection))@endsection/s";
            $replacement = "@section('$1')" . str_replace('$', '\\$', $content) . "@endsection";
            $html        = preg_replace($pattern, $replacement, $html);

        }

        return $html;

    }


    /**
     * Parse sanitised 'echo' tags
     * @param  string $html            HTML string
     * @param  string $ternary_pattern Regex pattern for 'or' statements
     * @return string                  Parsed HTML string
     */
    protected function _parseSanitisedEchos($html = '', $ternary_pattern = '')
    {
        
        $pattern = "/(?<!@){{{\s*(.+?)\s*}}}/s";

        $html = preg_replace_callback($pattern, function($matches) use ($ternary_pattern)
        {
            return "<?php echo htmlentities(" . preg_replace($ternary_pattern, "isset($1) ? $1 : $2", $matches[1]) . ", ENT_QUOTES); ?>";
        },
        $html);

        return $html;

    }


    /**
     * Parse unsanitised 'echo' tags
     * @param  string $html            HTML string
     * @param  string $ternary_pattern Regex pattern for 'or' statements
     * @return string                  Parsed HTML string
     */
    protected function _parseUnsanitisedEchos($html = '', $ternary_pattern = '')
    {

        $pattern = "/(?<!@){!\s*(.+?)\s*!}/s";

        $html = preg_replace_callback($pattern, function($matches) use ($ternary_pattern)
        {
            return "<?php echo html_entity_decode(" . preg_replace($ternary_pattern, "isset($1) ? $1 : $2", $matches[1]) . ", ENT_QUOTES); ?>";
        },
        $html);

        return $html;

    }


    /**
     * Parse 'echo' tags
     * @param  string $html            HTML string
     * @param  string $ternary_pattern Regex pattern for 'or' statements
     * @return string                  Parsed HTML string
     */
    protected function _parseEchos($html = '', $ternary_pattern = '')
    {
        
        $pattern = "/(?<!@){{\s*(.+?)\s*}}/s";

        $html = preg_replace_callback($pattern, function($matches) use ($ternary_pattern)
        {
            return "<?php echo " . preg_replace($ternary_pattern, "isset($1) ? $1 : $2", $matches[1]) . "; ?>";
        },
        $html);

        return $html;

    }


    /**
     * Parse 'PHP' tags
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parsePhpTags($html = '')
    {

        $pattern = "/(?<!@){\?\s*(.+?)\s*\?}/s";

        $html = preg_replace_callback($pattern, function($matches)
        {
            return "<?php " . $matches[1] . "; ?>";
        },
        $html);

        return $html;

    }


    /**
     * Parse opening statements
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parseOpenings($html = '')
    {

        $pattern     = "/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/";
        $replacement = "$1<?php $2$3: ?>";
        
        return preg_replace($pattern, $replacement, $html);

    }


    /**
     * Parse closing statements
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parseClosings($html = '')
    {

        $pattern = "/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/";
        $replacement = "$1<?php $2; ?>$3";

        return preg_replace($pattern, $replacement, $html);

    }


    /**
     * Parse 'else' statements
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parseElses($html = '')
    {

        $pattern     = "/(?<!\w)(\s*)@else(\s*)/";
        $replacement = "$1<?php else: ?>$2";

        return preg_replace($pattern, $replacement, $html);

    }


    /**
     * Parse 'unless' statements
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parseUnlesses($html = '')
    {

        $pattern = "/(?<!\w)(\s*)@unless(\s*\(.*\))/";
        $replacement = "$1<?php if ( !$2): ?>";

        return preg_replace($pattern, $replacement, $html);

    }


    /**
     * Parse 'end' statements
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parseEnds($html = '')
    {

        $pattern     = "/(?<!\w)(\s*)@endunless(\s*)/";
        $replacement = "$1<?php endif; ?>$2";

        return preg_replace($pattern, $replacement, $html);

    }


    /**
     * Parse a HTML block
     * @param  string $html HTML string
     * @return string       Parsed HTML string
     */
    protected function _parse($html = '')
    {

        /*
         * Check to see if the template extends another
         */
        $extended_template = $this->_getExtendedTemplate($html);

        /*
         * If it does, load the other and swap out the sections
         */
        if (!is_null($extended_template) AND
            is_readable($this->getBaseDirectory($extended_template) . $extended_template))
        {

            /*
             * Get the sections of the 'override' template
             */
            $sections = $this->_getSections($html);

            /*
             * Get (and parse) the parent template
             */
            $ice  = $this->_cloneRenderer();
            $html = $ice->render($extended_template, $this->_latest_variables, true, false, true);

            /*
             * Swap out any sections
             */
            $html = $this->_replaceSections($html, $sections);

            /*
             * Add the @extends tag back in
             */
            $html = $this->_stripExtendsTags($html);
            $html = $this->_addExtendsTag($html, $extended_template);

        }

        /*
         * Run custom parsers on the template
         */
        $parsers = $this->_custom_parsers;

        foreach ($parsers as $parser)
        {
            $html = $parser($html);
        }

        /*
         * Run stock parsers on the template
         */
        $ternary_pattern = "/^(?=\\$)(.+?)(?:\s+or\s+)(.+?)$/s";

        $html = $this->_parsePhpTags($html);
        $html = $this->_parseSanitisedEchos($html, $ternary_pattern);
        $html = $this->_parseUnsanitisedEchos($html, $ternary_pattern);
        $html = $this->_parseEchos($html, $ternary_pattern);
        $html = $this->_parseOpenings($html);
        $html = $this->_parseClosings($html);
        $html = $this->_parseElses($html);
        $html = $this->_parseUnlesses($html);
        $html = $this->_parseEnds($html);

        return $html;

    }


    /**
     * Add a custom Ice parser
     * @param  Closure $callback Closure to be executed
     * @return bool              Whether the parser was successfully added
     * @throws InvalidParserException if the parser is not callable
     */
    public function registerParser(Closure $callback)
    {

        if ((is_object($callback) AND ($callback instanceof Closure)))
        {

            $this->_custom_parsers[] = $callback;

            return true;

        }

        throw new InvalidParserException('Custom Ice parser is not valid');

    }


    /**
     * Set the cache directory
     * @param string $directory Path to cache directory
     * @throws InvalidDirectoryException if the cache directory is not writable
     */
    public function setCacheDirectory($directory = '')
    {

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (is_dir($directory) AND
            is_writable($directory))
        {
            $this->_cache_dir = $directory;
        }

        else
        {
            throw new InvalidDirectoryException('Cache directory is not writable');
        }

    }


    /**
     * Add a base directory
     * @param string $directory Path to base directory
     * @throws InvalidDirectoryException if the base template directory is not readable
     */
    public function addBaseDirectory($directory = '')
    {

        if (is_dir($directory) AND
            is_readable($directory))
        {
            $directory          = rtrim(realpath($directory), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            $this->_base_dirs[] = $directory;
        }

        else
        {
            throw new InvalidDirectoryException('Base template directory is not readable');
        }

    }


    /**
     * Get the base directory that contains a template file
     * @param  string $filename File to check for
     * @return string           Path to base directory
     * @throws MissingTemplateException if the template file could not be found in any of the base directories
     */
    public function getBaseDirectory($filename = '')
    {

        foreach ($this->_base_dirs as $directory)
        {

            if (is_readable($directory . $filename))
            {
                return $directory;
            }

        }

        throw new MissingTemplateException('Template file \'' . $filename . '\' cannot be found');

    }


    /**
     * Determine if a template extends another
     * @param  string      $filename Template file path
     * @return string|bool           Path of template that is extended from (or FALSE if there is no inheritance)
     */
    protected function _checkTemplateForInheritance($filename = '')
    {

        $html   = file_get_contents($filename);
        $result = $this->_getExtendedTemplate($html);

        if (!is_null($result))
        {
            return $result;
        }

        return false;

    }


    /**
     * Check whether a cached version of a file exists
     * @param  string      $filename Path to source file
     * @return string|bool           Filename of cached version (or FALSE if a cached version does not exist)
     */
    protected function _isFileCached($filename = '')
    {

        if (!is_null($this->_cache_dir) AND
            is_dir($this->_cache_dir) AND
            is_writable($this->_cache_dir))
        {

            $_ice_cached_filename = $this->_cache_dir . $this->_hashFilename($filename) . '.php';

            /*
             * See if a cached version of the template exists and is newer than
             * the template itself
             */
            if (is_readable($_ice_cached_filename) AND
                filemtime($_ice_cached_filename) > filemtime($filename))
            {

                /*
                 * Also see if it extends another template
                 */
                if (($_ice_extended_template = $this->_checkTemplateForInheritance($_ice_cached_filename)) !== false AND
                    is_readable($this->getBaseDirectory($_ice_extended_template) . $_ice_extended_template))
                {
                    $extended_filename        = $this->getBaseDirectory($_ice_extended_template) . $_ice_extended_template;
                    $cached_extended_filename = $this->_cache_dir . $this->_hashFilename($extended_filename) . '.php';
                }

                /*
                 * If the template does extend another and the cached version
                 * of the template that it extends is stale, remove the cached
                 * versions of both templates
                 */
                if (isset($extended_filename) AND
                    isset($cached_extended_filename) AND
                    is_readable($extended_filename) AND
                    is_readable($cached_extended_filename) AND
                    (filemtime($extended_filename) > filemtime($cached_extended_filename) OR
                     filemtime($cached_extended_filename) > filemtime($_ice_cached_filename)))
                {
                    @unlink($cached_extended_filename);
                    @unlink($_ice_cached_filename);
                }

                /*
                 * Otherwise all is well and we can use the cached version
                 */
                else
                {
                    return $_ice_cached_filename;
                }

            }

            /*
             * If the cached version of the template is stale, delete it
             */
            else if (is_readable($_ice_cached_filename))
            {
                @unlink($_ice_cached_filename);
            }

        }

        return false;

    }


    /**
     * Cache the contents of a parsed file
     * @param  string $filename Path to source file
     * @param  string $contents File contents
     * @return bool             Whether the file was successfully cached
     */
    protected function _cacheFile($filename = '', $contents = '')
    {

        if ($this->_cache_dir !== null AND
            is_dir($this->_cache_dir) AND
            is_writable($this->_cache_dir))
        {

            $filename = $this->_cache_dir . $this->_hashFilename($filename) . '.php';
            $result   = file_put_contents($filename, $contents);

            clearstatcache();

            return ($result === false) ? false : true;

        }

        return false;

    }


    /**
     * Check whether the cached file needs to be regenerated prior to being
     * output and re-rendering it if necessary
     * @param array $variables Array of local scope variables
     */
    public function renderCachedTemplate(array $variables = [])
    {

        extract($variables);

        /*
         * Check for template extensions
         */
        if (isset($_ice_extended_template) AND
            is_readable($this->getBaseDirectory($_ice_extended_template) . $_ice_extended_template))
        {

            try
            {
                $cache_check = $this->_isFileCached($this->getBaseDirectory($_ice_extended_template) . $_ice_extended_template);
            }

            catch (Exception $exception)
            {
                $cache_check = false;
            }

            /*
             * If the parent template is outdated, clear the cache of this
             * template and re-render it
             */
            if ($cache_check === false)
            {

                @unlink($_ice_cached_filename);

                $ice = $this->_cloneRenderer();

                $ice->render($_ice_relative_filename, $this->_latest_variables, true, false);

            }

        }

        /*
         * Output the cached template
         */
        echo $rendered_template;

    }


    /**
     * Strip any @section() and @endsection tags from the template
     * @param  string $html Template contents
     * @return string       Template contents with @section() and @endsection tags stripped
     */
    protected function _stripSectionTags($html = '')
    {

        $html = preg_replace("/@section\([\\\"|\'](.*)[\\\"|\']\)/", '', $html);
        $html = str_replace('@endsection', '', $html);
        
        return $html;

    }


    /**
     * Strip any @extends() tags from the template
     * @param  string $html Template contents
     * @return string       Template contents with @extends tags stripped
     */
    protected function _stripExtendsTags($html = '')
    {

        return preg_replace("/@extends\([\\\"|\'](.*)[\\\"|\']\)/", '', $html);

    }


    /**
     * Strip any @{ tags from the template
     * @param  string $html Template contents
     * @return string       Template contents with @{ tags stripped
     */
    protected function _stripBraceAts($html = '')
    {

        $html = str_replace('{@{{', '{{{', $html);
        $html = str_replace('@{{',  '{{', $html);
        $html = str_replace('@{!',  '{!', $html);
        $html = str_replace('@{?',  '{?', $html);

        return $html;

    }


    /**
     * Strip any informational tags from the template
     * @param  string $html Template contents
     * @return string       Template contents with informational tags stripped
     */
    protected function _stripInformationalTags($html = '')
    {

        $html = $this->_stripSectionTags($html);
        $html = $this->_stripExtendsTags($html);
        $html = $this->_stripBraceAts($html);

        return $html;

    }


    /**
     * Add an @extends tag onto the template
     * @param  string $html              Template contents
     * @param  string $extended_template Path to extended template
     * @return string                    Template contents appended with @extends tag
     */
    protected function _addExtendsTag($html = '', $extended_template = '')
    {

        $opening_php_tag_count = mb_substr_count($html, '<?');
        $closing_php_tag_count = mb_substr_count($html, '?>');

        // If there are an uneven number of opening and closing PHP tags...
        if ($opening_php_tag_count != $closing_php_tag_count)
        {
            $html .= "\n?>";
        }

        $html .= "\n@extends('" . $extended_template . "')";

        return $html;

    }


    /**
     * Parse and render an Ice template file
     * @param  string      $_ice_filename         Relative path to template file
     * @param  array       $_ice_variables        Variables to include in the local scope
     * @param  bool        $_ice_return_rendered  Whether to return the parsed file rather than output it
     * @param  bool        $_ice_initial_template Whether the template is the initial template in the chain (not an extended template)
     * @param  bool        $_ice_return_php       Whether to return the parsed PHP rather than the rendered output
     * @return string|null                        Parsed template file (or null if $_ice_return_rendered is FALSE)
     * @throws EngineNotInitialisedException if a base directory has not been set
     */
    public function render($_ice_filename = '', array $_ice_variables = [], $_ice_return_rendered = false, $_ice_initial_template = true, $_ice_return_php = false)
    {

        if (empty($this->_base_dirs))
        {
            throw new EngineNotInitialisedException('Base directory not set');
        }

        if ($_ice_return_rendered)
        {
            ob_start();
        }

        $_ice_relative_filename  = $_ice_filename;
        $_ice_filename           = $this->getBaseDirectory($_ice_filename) . $_ice_filename;
        $this->_latest_variables = $_ice_variables;
        $_ice_cached_filename    = $this->_isFileCached($_ice_filename);

        /*
         * Load from cache
         */
        if ($_ice_cached_filename !== false)
        {

            /*
             * Get the PHP file rather than the rendered version
             */
            if ($_ice_return_php)
            {
                return file_get_contents($_ice_cached_filename);
            }

            extract($_ice_variables);

            ob_start();

            require($_ice_cached_filename);

            $rendered_template      = ob_get_clean();
            $_ice_extended_template = $this->_getExtendedTemplate($rendered_template);

            if ($_ice_initial_template)
            {
                $rendered_template = $this->_stripInformationalTags($rendered_template);
            }

            $this->renderCachedTemplate(get_defined_vars());

        }

        /*
         * Generate and cache
         */
        else
        {

            ob_start();
            $rendered = $this->_requirePassthrough($_ice_filename, $_ice_variables);
            $output   = ob_get_clean();

            $this->_cacheFile($_ice_filename, $rendered);

            /*
             * Get the PHP file rather than the rendered version
             */
            if ($_ice_return_php)
            {
                return $rendered;
            }

            /*
             * Otherwise tidy up the output and echo it
             */
            if ($_ice_initial_template)
            {
                $output = $this->_stripInformationalTags($output);
            }

            echo $output;

        }

        $result = null;

        if ($_ice_return_rendered)
        {
            $result = ob_get_clean();
        }

        return $result;

    }


    /**
     * Hash a source filename, to be used as the cache filename
     * @param  string $filename Source filename
     * @return string           Cache target filename
     */
    protected function _hashFilename($filename = '')
    {

        return hash('sha512', $filename);

    }


    /**
     * Require a file, passing its contents through a callback closure
     * @param  string $_ice_filename  Filename to load
     * @param  array  $_ice_variables Array of variables
     * @return string                 Contents of the file
     * @throws MissingTemplateException if the template file cannot be read
     * @throws TemporaryFileWriteException if a temporary file cannot be created or written to
     */
    protected function _requirePassthrough($_ice_filename = '', array $_ice_variables = [])
    {

        extract($_ice_variables);

        if (!is_readable($_ice_filename))
        {
            throw new MissingTemplateException('File does not exist');
        }

        $_ice_temp_filename = $this->_vfs_protocol . '://ice_' . microtime(true) . '_' . mt_rand(10, 10000);
        $_ice_temp_file     = file_get_contents($_ice_filename);
        $_ice_temp_file     = $this->_parse($_ice_temp_file);

        if (file_put_contents($_ice_temp_filename, $_ice_temp_file) === false)
        {
            throw new TemporaryFileWriteException('Cannot write to temporary file');
        }

        require($_ice_temp_filename);

        $contents = file_get_contents($_ice_temp_filename);

        unlink($_ice_temp_filename);

        return $contents;

    }


}