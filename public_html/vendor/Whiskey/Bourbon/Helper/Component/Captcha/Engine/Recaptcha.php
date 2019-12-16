<?php


namespace Whiskey\Bourbon\Helper\Component\Captcha\Engine;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Exception\EngineNotInitialisedException;
use Whiskey\Bourbon\Helper\Component\Captcha\CaptchaInterface;
use Whiskey\Bourbon\Helper\Input;
use Whiskey\Bourbon\Io\Http;


/**
 * Recaptcha class
 * @package Whiskey\Bourbon\Helper\Component\Captcha\Engine
 */
class Recaptcha implements CaptchaInterface
{


    const _NO_REUSE = true;


    protected $_url            = 'https://www.google.com/recaptcha/api/siteverify';
    protected $_key            = null;
    protected $_secret         = null;
    protected $_dependencies   = null;
    protected $_session_checks = [];


    /**
     * Instantiate the Recaptcha object
     * @param Http  $http  Http object
     * @param Input $input Input object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Http $http, Input $input)
    {

        if (!isset($http) OR
            !isset($input))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies        = new stdClass();
        $this->_dependencies->http  = $http;
        $this->_dependencies->input = $input;

    }


    /**
     * Set the credentials required to connect to the service
     * @param string $key    Site key
     * @param string $secret Shared secret
     */
    public function setCredentials($key = '', $secret = '')
    {

        $this->_key    = $key;
        $this->_secret = $secret;

    }


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName()
    {

        return 'recaptcha';

    }


    /**
     * Get the name of the CAPTCHA value input field
     * @return string Name of input field
     */
    public function getInputName()
    {

        return 'g-recaptcha-response';

    }


    /**
     * Check whether the CAPTCHA engine has been successfully initialised
     * @return bool Whether the CAPTCHA engine is active
     */
    public function isActive()
    {

        return (!empty($this->_key) AND !empty($this->_secret));

    }


    /**
     * Display a CAPTCHA form
     * @return string CAPTCHA HTML
     * @throws EngineNotInitialisedException if the engine has not been initialised
     */
    public function display()
    {

        if (!$this->isActive())
        {
            throw new EngineNotInitialisedException('reCAPTCHA engine not initialised');
        }

        $html = '<div class="g-recaptcha" data-sitekey="' . $this->_key . '"></div>';
        $script = '<script src="https://www.google.com/recaptcha/api.js"></script>';

        return $html . $script;

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
            throw new EngineNotInitialisedException('reCAPTCHA engine not initialised');
        }

        $http   = $this->_dependencies->http;
        $input  = $this->_dependencies->input;
        $answer = $input->post('g-recaptcha-response', false);

        if (!isset($this->_session_checks[$answer]))
        {

            $response = $http->build()->setUrl($this->_url)
                             ->addParameter('secret', $this->_secret)
                             ->addParameter('response', $answer)
                             ->addParameter('remoteip', $_SERVER['REMOTE_ADDR'])
                             ->post();

            $response = json_decode($response);

            $this->_session_checks[$answer] = ($response !== false AND $response->success == true);

        }

        return $this->_session_checks[$answer];

    }


}