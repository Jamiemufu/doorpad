<?php


namespace Itg\Email\Engine;


use Exception;
use Whiskey\Bourbon\Email\EmailAbstract;
use Whiskey\Bourbon\App\Facade\Http;


/**
 * ITG API e-mail class
 * @package Itg\Email\Engine
 */
class Itg extends EmailAbstract
{


    const _NO_REUSE = true;


    protected $_api_url   = 'https://api.itgproduction.com/email/send_complex/';
    protected $_api_token = null;


    /**
     * Instantiate the ITG API e-mail engine and set the API token
     */
    public function __construct()
    {

        $this->_api_token = (isset($_ENV['ITG_API_KEY']) ? $_ENV['ITG_API_KEY'] : null);

    }


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName()
    {

        return 'itg';

    }


    /**
     * Check whether the e-mail engine has been successfully initialised
     * @return bool Whether the e-mail engine is active
     */
    public function isActive()
    {

        return !is_null($this->_api_token);

    }


    /**
     * Attach a file to the e-mail
     * @param  string $attachment_file_path Path to the attachment
     * @return self                         Email object for chaining
     * @throws Exception because attachments aren't (yet) supported by the ITG API
     * @todo Make this class work with attachments (attach file contents to POST data?), then remove this method
     */
    public function attach($attachment_file_path = '')
    {

        throw new Exception('Attachments not supported');

    }


    /**
     * Send the e-mail
     * @return bool Whether the e-mail was successfully sent
     * @throws Exception if sender or recipient details were not provided
     */
    public function send()
    {

        if (empty($this->_to) OR empty($this->_from))
        {
            throw new Exception('Insufficient sender/recipient information');
        }

        try
        {

            $mail_url = $this->_api_url . $this->_api_token;
            $payload  = get_object_vars($this);

            foreach ($payload as $key => $value)
            {

                $payload[substr($key, 1)] = $value;

                unset($payload[$key]);

            }

            $post_data = ['payload' => json_encode($payload)];
            $response  = Http::post($mail_url, $post_data);

            if ($response = json_decode($response, true) AND
                $response['success'] == true)
            {
                return true;
            }

        }

        catch (Exception $exception) {}

        return false;

    }


}