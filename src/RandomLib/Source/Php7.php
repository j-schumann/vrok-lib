<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\RandomLib\Source;

use RandomLib\Source;
use SecurityLib\Strength;

/**
 * Uses random_bytes() available since PHP7.
 */
class Php7 implements Source
{
    /**
     * {@inheritdoc}
     */
    public static function getStrength()
    {
        return new Strength(Strength::MEDIUM);
    }

    /**
     * {@inheritdoc}
     */
    public function generate($size)
    {
        if (!function_exists('random_bytes') || $size < 1) {
            return str_repeat(chr(0), $size);
        }

        try {
            return random_bytes($size);
        }
        catch(Exception $e) {
            return str_repeat(chr(0), $size);
        }
        // @todo catch TypeError
        // @see http://php.net/manual/de/function.random-bytes.php
    }
}
