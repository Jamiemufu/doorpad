<?php


namespace Whiskey\Bourbon\Helper\Colour\Profile;


use stdClass;
use Whiskey\Bourbon\Helper\Colour\RgbTemplate;
use Whiskey\Bourbon\Helper\Colour\ProfileInterface;


/**
 * Hsl colour class
 * @package Whiskey\Bourbon\Helper\Colour\Profile
 */
class Hsl implements ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName()
    {

        return 'hsl';

    }


    /**
     * Convert the colour value to its RGB counterpart
     * @param  float       $hue        Hue value
     * @param  float       $saturation Saturation value
     * @param  float       $lightness  Lightness value
     * @return RgbTemplate             RgbTemplate object
     */
    public function toRgb($hue = 0.0, $saturation = 0.0, $lightness = 0.0)
    {

        $h = (($hue        % 360) / 360);
        $s = (($saturation % 101) / 100);
        $l = (($lightness  % 101) / 100);

        if ($s === 0)
        {
            return new RgbTemplate(($l * 255), ($l * 255), ($l * 255));
        }

        else
        {

            $hue_to_rgb = function($p = 0, $q = 0, $t = 0)
            {

                if ($t < 0)
                {
                    $t += 1;
                }

                if ($t > 1)
                {
                    $t -= 1;
                }

                if ($t < (1 / 6))
                {
                    return ($p + ($q - $p) * 6 * $t);
                }

                if ($t < (1 / 2))
                {
                    return $q;
                }

                if ($t < (2 / 3))
                {
                    return ($p + ($q - $p) * (2 / 3 - $t) * 6);
                }

                return $p;

            };

            $q = ($l < 0.5) ? ($l * (1 + $s)) : (($l + $s) - ($l * $s));
            $p = ((2 * $l) - $q);
            $r = ($hue_to_rgb($p, $q, ($h + (1 / 3))) * 255);
            $g = ($hue_to_rgb($p, $q, $h) * 255);
            $b = ($hue_to_rgb($p, $q, ($h - (1 / 3))) * 255);

            return new RgbTemplate($r, $g, $b);

        }

    }


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return object                    Object with 'h', 's' and 'l' colour values
     */
    public function fromRgb(RgbTemplate $rgb_template)
    {

        $r = (((int)$rgb_template->getRed()   % 256) / 255);
        $g = (((int)$rgb_template->getGreen() % 256) / 255);
        $b = (((int)$rgb_template->getBlue()  % 256) / 255);

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $l = (($max + $min ) / 2);

        if ($max === $min)
        {
            $h = 0;
            $s = 0;
        }

        else
        {

            $d = ($max - $min);
            $s = ($l > 0.5) ? ($d / (2 - $max - $min)) : ($d / ($max + $min));

            if ($max == $r)
            {
                $h = (($g - $b) / $d + (($g < $b) ? 6 : 0));
            }

            else if ($max == $g)
            {
                $h = (($b - $r) / $d + 2);
            }

            else
            {
                $h = (($r - $g) / $d + 4);
            }

            $h = ($h / 6);

        }

        $result    = new stdClass();
        $result->h = number_format(($h * 360), 2);
        $result->s = number_format(($s * 100), 2);
        $result->l = number_format(($l * 100), 2);

        return $result;

    }


}