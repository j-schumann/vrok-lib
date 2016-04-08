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
 * Uses ext/mcrypt as source.
 */
class Mcrypt implements Source
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
        // Don't use the mcrypt function on http://en.wikipedia.org/wiki/Phalanger
        // as /dev/[u]random is not available
        if (!function_exists('mcrypt_create_iv') || defined('PHALANGER') || $size < 1) {
            return str_repeat(chr(0), $size);
        }

        return mcrypt_create_iv($size, MCRYPT_DEV_URANDOM)
            ?: str_repeat(chr(0), $size);
    }
}
