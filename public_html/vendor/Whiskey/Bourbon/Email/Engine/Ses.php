<?php


namespace Whiskey\Bourbon\Email\Engine;


use Exception;
use Whiskey\Bourbon\Email\EmailAbstract;
use Whiskey\Bourbon\Exception\Email\RecipientDetailsNotProvidedException;
use Swift_Message;
use Swift_Attachment;
use Aws\Ses\SesClient;


/**
 * Ses class
 * @package Whiskey\Bourbon\Email\Engine
 */
class Ses extends EmailAbstract
{


    const _NO_REUSE = true;


    protected $_ses = null;


    /**
     * Get the engine name
     * @return string Engine name
     */
    public function getName()
    {

        return 'ses';

    }


    /**
     * Set the AWS credentials
     * @param string $key    AWS key
     * @param string $secret AWS secret
     * @param string $region AWS region
     */
    public function setCredentials($key = '', $secret = '', $region = 'eu-west-1')
    {

        if ($key != '' AND $secret != '' AND $region != '')
        {

            $this->_ses = SesClient::factory(
                [
                    'key'    => $key,
                    'secret' => $secret,
                    'region' => $region
                ]);

        }

    }


    /**
     * Check whether the e-mail engine has been successfully initialised
     * @return bool Whether the e-mail engine is active
     */
    public function isActive()
    {

        return (class_exists('\\Swift_Message') AND
                class_exists('\\Aws\\Ses\\SesClient') AND
                !is_null($this->_ses));

    }


    /**
     * Send the e-mail
     * @return bool Whether the e-mail was successfully sent
     * @throws RecipientDetailsNotProvidedException if sender or recipient details were not provided
     */
    public function send()
    {

        if (empty($this->_to) OR empty($this->_from))
        {
            throw new RecipientDetailsNotProvidedException('Insufficient sender/recipient information');
        }
        
        try
        {

            $message = Swift_Message::newInstance();
            $headers = $message->getHeaders();
    
            foreach ($this->_headers as $header)
            {
                $headers->addTextHeader($header['name'], $header['value']);
            }
    
            $message->setSubject($this->_subject)
                    ->setFrom(reset($this->_from));
    
            if (!empty($this->_reply_to))
            {
                $message->setReplyTo(reset($this->_reply_to));
            }
    
            if (!empty($this->_to))
            {
    
                $to = [];
    
                foreach ($this->_to as $recipient)
                {
    
                    if (is_array($recipient))
                    {
                        $to[key($recipient)] = reset($recipient);
                    }
    
                    else
                    {
                        $to[] = $recipient;
                    }
    
                }
    
                $message->setTo($to);
    
            }
    
            if (!empty($this->_cc))
            {
    
                $cc = [];
    
                foreach ($this->_cc as $recipient)
                {
    
                    if (is_array($recipient))
                    {
                        $cc[key($recipient)] = reset($recipient);
                    }
    
                    else
                    {
                        $cc[] = $recipient;
                    }
    
                }
    
                $message->setCc($cc);
    
            }
    
            if (!empty($this->_bcc))
            {
    
                $bcc = [];
    
                foreach ($this->_bcc as $recipient)
                {
    
                    if (is_array($recipient))
                    {
                        $bcc[key($recipient)] = reset($recipient);
                    }
    
                    else
                    {
                        $bcc[] = $recipient;
                    }
    
                }
    
                $message->setBcc($bcc);
    
            }
    
            $message->setBody($this->_text, 'text/plain')
                    ->addPart($this->_html, 'text/html');
    
            if (!empty($this->_attachments))
            {
    
                foreach ($this->_attachments as $attachment)
                {
                    $message->attach(Swift_Attachment::fromPath($attachment['file_path']));
                }
    
            }

            $result = $this->_ses->sendRawEmail(['RawMessage' => ['Data' => base64_encode($message->toString())]]);

            return ($result !== false);
        
        }
        
        catch (Exception $exception)
        {
            return false;
        }

    }


}