<?php


namespace Whiskey\Bourbon\Html;


use stdClass;
use InvalidArgumentException;
use Whiskey\Bourbon\Storage\Session;


/**
 * FlashMessage class
 * @package Whiskey\Bourbon\Html
 */
class FlashMessage
{


    protected $_dependencies     = null;
    protected $_message_content  = '';
    protected $_message_mood     = true;
    protected $_message_template =
        [
            'message'   => '<div class="alert alert-{mood}">{message}</div>',
            'mood_good' => 'success',
            'mood_bad'  => 'danger'
        ];


    /**
     * Instantiate a FlashMessage object
     * @param Session $session Session object
     * @throws InvalidArgumentException if dependencies are not provided
     */
    public function __construct(Session $session)
    {

        if (!isset($session))
        {
            throw new InvalidArgumentException('Dependencies not provided');
        }

        $this->_dependencies          = new stdClass();
        $this->_dependencies->session = $session;

    }


    /**
     * Get the raw message contents
     * @return object|bool Object of message details (or FALSE if none exist)
     */
    protected function _getRaw()
    {

        /*
         * See if a message has been set directly
         */
        if ($this->_message_content)
        {
            $message      = $this->_message_content;
            $good_message = $this->_message_mood;
        }

        /*
         * If not, fall back to a session message
         */
        else if ($session_message = $this->_dependencies->session->read('_bourbon_flash_message'))
        {
            $message      = $session_message['message'];
            $good_message = $session_message['good_message'];
        }

        else
        {
            return false;
        }

        $result               = new stdClass();
        $result->message      = $message;
        $result->good_message = $good_message;

        return $result;

    }


    /**
     * Set a flash message
     * @param string $message      Message content
     * @param bool   $good_message Whether a message is considered positive or not
     * @param bool   $persist      Whether the message should persist across redirects
     */
    public function set($message = '', $good_message = true, $persist = true)
    {

        $existing_message = $this->_getRaw();

        if ($existing_message !== false)
        {
            $message = $existing_message->message . '<hr />' . $message;
        }

        /*
         * If we want the message to persist across redirects, use session
         * storage
         */
        if ($persist)
        {

            $message_package = ['message' => $message, 'good_message' => $good_message];

            $this->_dependencies->session->write('_bourbon_flash_message', $message_package);

        }

        else
        {
            $this->_message_content = $message;
            $this->_message_mood    = $good_message;
        }

    }


    /**
     * Return the last flash message to have been set
     * @return string HTML snippet containing message, formatted using message template
     */
    public function get()
    {

        $message = $this->_getRaw();

        if ($message !== false)
        {

            $this->_dependencies->session->clear('_bourbon_flash_message');

            $message_template = $this->_message_template;
            $message_text     = $message_template['message'];
            $mood_string      = $message->good_message ? $message_template['mood_good'] : $message_template['mood_bad'];
            $message_text     = str_replace(['{mood}', '{message}'], [$mood_string, $message->message], $message_text);

            return $message_text;

        }

        return '';

    }


    /**
     * Overwrite the default template
     * @param string $template_string  Template string
     * @param string $mood_string_good String to replace {mood} for positive messages
     * @param string $mood_string_bad  String to replace {mood} for negative messages
     * @throws InvalidArgumentException if the message string does not contain a {message} tag
     */
    public function setTemplate($template_string = '', $mood_string_good = 'success', $mood_string_bad = 'danger')
    {

        if (stristr($template_string, '{message}') === false)
        {
            throw new InvalidArgumentException('Message string does not contain {message} tag');
        }

        $this->_message_template =
            [
                'message'   => $template_string,
                'mood_good' => $mood_string_good,
                'mood_bad'  => $mood_string_bad
            ];

    }


}