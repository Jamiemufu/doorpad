<?php


namespace Whiskey\Bourbon\Helper\Colour\Profile;


use Whiskey\Bourbon\Helper\Colour\RgbTemplate;
use Whiskey\Bourbon\Helper\Colour\ProfileInterface;


/**
 * Hex colour class
 * @package Whiskey\Bourbon\Helper\Colour\Profile
 */
class Hex implements ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName()
    {

        return 'hex';

    }


    /**
     * Convert the colour value to its RGB counterpart
     * @param  string      $hex Hex colour string
     * @return RgbTemplate      RgbTemplate object
     */
    public function toRgb($hex = '')
    {

        $hex = ltrim($hex, '#');

        /*
         * Short hex codes
         */
        if (mb_strlen($hex) == 3)
        {
            $red   = hexdec(str_repeat(mb_substr($hex, 0, 1), 2));
            $green = hexdec(str_repeat(mb_substr($hex, 1, 1), 2));
            $blue  = hexdec(str_repeat(mb_substr($hex, 2, 1), 2));
        }

        /*
         * Long hex codes
         */
        else
        {
            $red   = hexdec(mb_substr($hex, 0, 2));
            $green = hexdec(mb_substr($hex, 2, 2));
            $blue  = hexdec(mb_substr($hex, 4, 2));
        }

        return new RgbTemplate($red, $green, $blue);

    }


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return string                    Hex colour string
     */
    public function fromRgb(RgbTemplate $rgb_template)
    {

        $hex_red   = dechex(max(0, min(255, $rgb_template->getRed())));
        $hex_green = dechex(max(0, min(255, $rgb_template->getGreen())));
        $hex_blue  = dechex(max(0, min(255, $rgb_template->getBlue())));

        $hex_red   = str_pad($hex_red,   2, '0', STR_PAD_LEFT);
        $hex_green = str_pad($hex_green, 2, '0', STR_PAD_LEFT);
        $hex_blue  = str_pad($hex_blue,  2, '0', STR_PAD_LEFT);

        return $hex_red . $hex_green . $hex_blue;

    }


}