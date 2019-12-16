<?php


namespace Itg;


/**
 * GooGl class
 * @package Itg
 */
class GooGl
{


    protected static $_api_url = 'https://www.googleapis.com/urlshortener/v1/url?key=AIzaSyDC29L8hAo-KShE0ECQiBvSFe8pc224_Ts';


    /**
     * Shorten a URL
     * @param  string      $url URL to shorten
     * @return string|bool      Short-form URL (or FALSE on fail)
     */
    public static function shorten($url = '')
    {

        $response = self::_send($url);
        
        return isset($response['id']) ? $response['id'] : false;

    }


    /**
     * Expand a short-form URL
     * @param  string      $url URL to expand
     * @return string|bool      Expanded URL (or FALSE on fail)
     */
    public static function expand($url = '')
    {

        $response = self::_send($url, false);
        
        return isset($response['longUrl']) ? $response['longUrl'] : false;

    }


    /**
     * Contact Google with request
     * @param  string $url     URL to operate on
     * @param  bool   $shorten Whether to shorten (true) or expand (false) the URL
     * @return array           Array of response information from Google
     */
    protected static function _send($url = '', $shorten = true)
    {

        $ch = curl_init();

        /*
         * If we're shortening a URL
         */
        if ($shorten)
        {
            curl_setopt($ch, CURLOPT_URL,        self::$_api_url);
            curl_setopt($ch, CURLOPT_POST,       1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['longUrl' => $url]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        /*
         * If we're expanding a URL
         */
        else
        {
            curl_setopt($ch, CURLOPT_URL, self::$_api_url . '&shortUrl=' . $url);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);

        curl_close($ch);

        return json_decode($result, true);

    }


}