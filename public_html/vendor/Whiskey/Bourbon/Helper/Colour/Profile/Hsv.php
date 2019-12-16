<?php


namespace Whiskey\Bourbon\Helper\Colour\Profile;


use stdClass;
use Whiskey\Bourbon\Helper\Colour\RgbTemplate;
use Whiskey\Bourbon\Helper\Colour\ProfileInterface;


/**
 * Hsv colour class
 * @package Whiskey\Bourbon\Helper\Colour\Profile
 */
class Hsv implements ProfileInterface
{


    /**
     * Get the name of the colour profile
     * @return string Name of colour profile
     */
    public function getName()
    {

        return 'hsv';

    }


    /**
     * Convert the colour value to its RGB counterpart
     * @param  float       $hue        Hue value
     * @param  float       $saturation Saturation value
     * @param  float       $value      Value value
     * @return RgbTemplate             RgbTemplate object
     */
    public function toRgb($hue = 0.0, $saturation = 0.0, $value = 0.0)
    {

        $h = (($hue        % 360) / 360);
        $s = (($saturation % 101) / 100);
        $v = (($value      % 101) / 100);

        $i = ($h * 6);
        $f = ($i - floor($i));
        $p = ($v * (1 - $s));
        $q = ($v * (1 - ($f * $s)));
        $t = ($v * (1 - ((1 - $f) * $s)));

        if (($i % 6) == 0)
        {
            $r = $v;
            $g = $t;
            $b = $p;
        }

        else if (($i % 6) == 1)
        {
            $r = $q;
            $g = $v;
            $b = $p;
        }

        else if (($i % 6) == 2)
        {
            $r = $p;
            $g = $v;
            $b = $t;
        }

        else if (($i % 6) == 3)
        {
            $r = $p;
            $g = $q;
            $b = $v;
        }

        else if (($i % 6) == 4)
        {
            $r = $t;
            $g = $p;
            $b = $v;
        }

        else
        {
            $r = $v;
            $g = $p;
            $b = $q;
        }

        return new RgbTemplate(($r * 255), ($g * 255), ($b * 255));

    }


    /**
     * Get a colour value from its RGB counterpart
     * @param  RgbTemplate $rgb_template RgbTemplate object
     * @return object                    Object with 'h', 's' and 'v' colour values
     */
    public function fromRgb(RgbTemplate $rgb_template)
    {

        $r = (((int)$rgb_template->getRed()   % 256) / 255);
        $g = (((int)$rgb_template->getGreen() % 256) / 255);
        $b = (((int)$rgb_template->getBlue()  % 256) / 255);

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);

        $d = ($max - $min);
        $h = 0;
        $s = (($max === 0) ? 0 : ($d / $max));
        $v = $max;

        if ($max !== $min)
        {

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
        $result->v = number_format(($v * 100), 2);

        return $result;

    }


}