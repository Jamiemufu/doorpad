<?php


namespace Whiskey\Bourbon\Helper\Component\Captcha\Engine;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\DependencyNotInitialisedException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Helper\Component\Captcha\CaptchaInterface;
use Whiskey\Bourbon\Helper\Input;
use Whiskey\Bourbon\Helper\Utils;
use Whiskey\Bourbon\Storage\Session;


/**
 * Simple class
 * @package Whiskey\Bourbon\Helper\Component\Captcha\Engine
 */
class Simple implements CaptchaInterface
{


    protected $_dependencies = null;


    /**
     * Instantiate the Simple CAPTCHA object
     * @param Input   $input   Input object
     * @param Utils   $utils   Utils object
     * @param Session $session Session object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Input $input, Utils $utils, Session $session)
    {

        if (!isset($input) OR
            !isset($utils) OR
            !isset($session))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies          = new stdClass();
        $this->_dependencies->input   = $input;
        $this->_dependencies->utils   = $utils;
        $this->_dependencies->session = $session;

    }


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName()
    {

        return 'simple';

    }


    /**
     * Get the name of the CAPTCHA value input field
     * @return string Name of input field
     */
    public function getInputName()
    {

        return '_bourbon_captcha_value';

    }


    /**
     * Check whether the CAPTCHA engine has been successfully initialised
     * @return bool Whether the CAPTCHA engine is active
     */
    public function isActive()
    {

        return is_readable($this->_getFontPath());

    }


    /**
     * Get the path to the CAPTCHA font
     * @return string Path to the CAPTCHA font
     */
    protected function _getFontPath()
    {

        $current_dir = rtrim(dirname(__FILE__), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $font_file   = 'captcha.ttf';

        return $current_dir . $font_file;

    }


    /**
     * Display a CAPTCHA form
     * @return string CAPTCHA HTML
     * @throws EngineNotInitialisedException if the engine has not been initialised
     * @throws DependencyNotInitialisedException if required image libraries are not available
     */
    public function display()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Simple CAPTCHA engine not initialised');
        }

        if (!extension_loaded('gd'))
        {
            throw new DependencyNotInitialisedException('GD extension missing');
        }

        if (!function_exists('imagettftext'))
        {
            throw new DependencyNotInitialisedException('FreeType extension missing');
        }

        /*
         * Decide upon a key and answer
         */
        $key = $this->_dependencies->utils->random(32);
        $key = hash('md5', $key);

        $answer = $this->_dependencies->utils->random(32);
        $answer = preg_replace('/[^A-Za-z]/', '', $answer);
        $answer = substr($answer, 0, 8);
        $answer = strtoupper($answer);

        $this->_dependencies->session->write('_bourbon_captcha_' . $key, $answer);

        /*
         * Create the image
         */
        $image = imagecreate(205, 60);
        imagecolorallocate($image, 220, 220, 220);

        $answer_array = str_split($answer);
        $count        = 0;

        /*
         * Add the text
         */
        foreach ($answer_array as $character)
        {

            $offset = (rand(5, 10) + ($count * 23));
            $colour = imagecolorallocate($image, 0, 0, 0);

            imagettftext($image, rand(25, 35), rand(-10, 10), $offset, rand(30, 50), $colour, $this->_getFontPath(), $character);

            $count++;

        }

        /*
         * Overlay some lines
         */
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        for ($i = 0; $i < 8; $i++)
        {
            imagesetthickness($image, rand(1, 3));
            imagearc($image, rand(0, 205), rand(0, 60), rand(0, 300), rand(0, 300), rand(0, 300), rand(0, 300), (rand(0, 1) ? $black : $white));
        }

        /*
         * Output the image
         */
        ob_start();
        imagejpeg($image, null, 25);

        $jpeg_image = ob_get_clean();
        $jpeg_image = base64_encode($jpeg_image);
        $jpeg_image = 'data:image/jpeg;base64,' . $jpeg_image;

        /*
         * Return usable HTML
         */
        $html_image = '<img src="' . $jpeg_image . '" alt="" />';
        $html_key   = '<input type="hidden" name="_bourbon_captcha_key" value="' . $key . '" />';
        $html_value = '<input type="text" name="_bourbon_captcha_value" placeholder="Enter CAPTCHA text" />';

        return '<div class="_bourbon_simple_captcha">' . $html_image . $html_key . $html_value . '</div>';

    }


    /**
     * Check whether a CAPTCHA challenge has passed
     * @return bool Whether a CAPTCHA challenge has passed
     * @throws EngineNotInitialisedException if the engine has not been initialised
     */
    public function isValid()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('Simple CAPTCHA engine not initialised');
        }

        $key         = $this->_dependencies->input->post('_bourbon_captcha_key', false);
        $answer      = $this->_dependencies->session->read('_bourbon_captcha_' . $key);
        $user_answer = $this->_dependencies->input->post('_bourbon_captcha_value', false);
        $user_answer = strtoupper($user_answer);

        if (!is_null($key) AND !is_null($answer) AND $user_answer == $answer)
        {
            return true;
        }

        return false;

    }


}