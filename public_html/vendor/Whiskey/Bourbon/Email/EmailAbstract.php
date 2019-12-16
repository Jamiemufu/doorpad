<?php


namespace Whiskey\Bourbon\Email;


use finfo;


/**
 * EmailAbstract class
 * @package Whiskey\Bourbon\Email
 */
abstract class EmailAbstract implements EmailInterface
{


    protected $_to          = [];
    protected $_cc          = [];
    protected $_bcc         = [];
    protected $_from        = [];
    protected $_reply_to    = [];
    protected $_attachments = [];
    protected $_subject     = '';
    protected $_text        = '';
    protected $_html        = '';
    protected $_headers     = [];
    protected $_smtp        = ['ssl'      => false,
                               'server'   => '',
                               'port'     => 0,
                               'username' => '',
                               'password' => ''];


    /**
     * Reset and return the current instance
     * @return self Email object for chaining
     */
    public function create()
    {

        $this->_to          = [];
        $this->_cc          = [];
        $this->_bcc         = [];
        $this->_from        = [];
        $this->_reply_to    = [];
        $this->_attachments = [];
        $this->_subject     = '';
        $this->_text        = '';
        $this->_html        = '';
        $this->_headers     = [];
        $this->_smtp        = ['ssl'      => false,
                               'server'   => '',
                               'port'     => 0,
                               'username' => '',
                               'password' => ''];

        return $this;

    }


    /**
     * Add a custom header
     * @param  string $name  Header name
     * @param  string $value Header value
     * @return self          Email object for chaining
     */
    public function addHeader($name = '', $value = '')
    {

        $this->_headers[] = ['name'  => $name,
                             'value' => $value];

        return $this;

    }


    /**
     * Add a new recipient
     * @param  string $to   Recipient e-mail address
     * @param  string $name Recipient name
     * @return self         Email object for chaining
     */
    public function to($to = '', $name = null)
    {

        if (!is_null($name))
        {
            $to = [$to => $name];
        }
    
        $this->_to[] = $to;
        
        return $this;
    
    }


    /**
     * Add a new CC recipient
     * @param  string $cc   Recipient e-mail address
     * @param  string $name Recipient name
     * @return self         Email object for chaining
     */
    public function cc($cc = '', $name = null)
    {

        if (!is_null($name))
        {
            $cc = [$cc => $name];
        }

        $this->_cc[] = $cc;

        return $this;

    }


    /**
     * Add a new BCC recipient
     * @param  string $bcc  Recipient e-mail address
     * @param  string $name Recipient name
     * @return self         Email object for chaining
     */
    public function bcc($bcc = '', $name = null)
    {

        if (!is_null($name))
        {
            $bcc = [$bcc => $name];
        }

        $this->_bcc[] = $bcc;

        return $this;

    }


    /**
     * Set the sender's e-mail address
     * @param  string $from Sender e-mail address
     * @param  string $name Sender name
     * @return self         Email object for chaining
     */
    public function from($from = '', $name = null)
    {

        if (!is_null($name))
        {
            $from = [$from => $name];
        }
    
        $this->_from = [$from];
        
        return $this;
    
    }


    /**
     * Set the 'reply to' e-mail address
     * @param  string $reply_to 'Reply To' e-mail address
     * @param  string $name     'Reply To' name
     * @return self             Email object for chaining
     */
    public function replyTo($reply_to = '', $name = null)
    {

        if (!is_null($name))
        {
            $reply_to = [$reply_to => $name];
        }
    
        $this->_reply_to = [$reply_to];
        
        return $this;
    
    }


    /**
     * Attach a file to the e-mail
     * @param  string $attachment_file_path Path to the attachment
     * @return self                         Email object for chaining
     */
    public function attach($attachment_file_path = '')
    {
    
        if ($attachment_file_path AND is_readable($attachment_file_path))
        {

            $finfo = new finfo(FILEINFO_MIME);

            $attachment = ['file_path' => $attachment_file_path,
                           'filename'  => basename($attachment_file_path),
                           'mime'      => $finfo->file($attachment_file_path),
                           'data'      => file_get_contents($attachment_file_path)];

            $this->_attachments[] = $attachment;

        }
        
        return $this;
    
    }


    /**
     * Set the plain text form of the message body
     * @param  string $text Message body
     * @return self         Email object for chaining
     */
    public function body($text = '')
    {
    
        $this->_text = $text;
        
        if (!$this->_html)
        {
            $this->_html = nl2br($text);
        }
        
        return $this;
    
    }


    /**
     * Convert HTML to basic plain text
     * @param  string $html HTML code
     * @return string       Plain text version of HTML
     */
    protected function _plainTextConvert($html = '')
    {

        return strip_tags(preg_replace("/<br\\s*?\/??>/i", "\n", $html));

    }


    /**
     * Set the HTML form of the message body
     * @param  string $html HTML message body
     * @return self         Email object for chaining
     */
    public function html($html = '')
    {

        $this->_html = $html;
        
        if (!$this->_text)
        {
            $this->_text = $this->_plainTextConvert($html);
        }
        
        return $this;

    }


    /**
     * Set subject line of e-mail
     * @param  string $subject E-mail subject line
     * @return self            Email object for chaining
     */
    public function subject($subject = '')
    {
    
        $this->_subject = $subject;
        
        return $this;
    
    }


    /**
     * Provide SMTP login credentials
     * @param  bool   $ssl      Whether to use an SSL connection
     * @param  string $server   Server address
     * @param  int    $port     Server SMTP port
     * @param  string $username Mail account username
     * @param  string $password Mail account password
     * @return self   $self     Email object
     */
    public function smtp($ssl = false, $server = '', $port = 25, $username = '', $password = '')
    {
    
        $this->_smtp = ['ssl'      => (bool)$ssl,
                        'server'   => (string)$server,
                        'port'     => (int)$port,
                        'username' => (string)$username,
                        'password' => (string)$password];
        
        return $this;
    
    }


}