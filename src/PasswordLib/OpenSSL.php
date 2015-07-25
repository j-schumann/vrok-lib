<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\PasswordLib;

use PasswordLib\Core\Strength;

/**
 * OpenSSL Random Number Source for PasswordLib.
 * Uses the OpenSSL extension to generate random numbers.
 */
class OpenSSL implements \PasswordLib\Random\Source
{
    /**
     * Return an instance of Strength indicating the strength of the source.
     *
     * @return Strength An instance of one of the strength classes
     */
    public static function getStrength()
    {
        return new Strength(Strength::MEDIUM);
    }

    /**
     * Generate a random string of the specified size.
     *
     * @param int $size The size of the requested random string
     *
     * @return string A string of the requested size
     */
    public function generate($size)
    {
        if (!function_exists('openssl_random_pseudo_bytes')) {
            throw new Exception\RuntimeException('Trying to use '.__CLASS__
                    .' while the OpenSSL extension is not available!');
        }

        $data = openssl_random_pseudo_bytes($size);

        return str_pad($data, $size, chr(0));
    }
}
