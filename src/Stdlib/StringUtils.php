<?php

/**
 * @copyright   (c) 2017, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

/**
 * Utility class for manipulation of string.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class StringUtils
{
    /**
     * Escapes a string to be used in a regular expression.
     *
     * @link http://www.php.net/manual/en/function.preg-replace.php#92456
     *
     * @param string $str
     *
     * @return string
     */
    public static function escapeString(string $str) : string
    {
        //All regex special chars
        // \ ^ . $ | ( ) [ ]
        // * + ? { } ,

        $patterns = ['/\//', '/\^/', '/\./', '/\$/', '/\|/',
            '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/',
            '/\?/', '/\{/', '/\}/', '/\,/', ];
        $replace = ['\/', '\^', '\.', '\$', '\|', '\(', '\)',
            '\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,', ];

        return preg_replace($patterns, $replace, $str);
    }

    /**
     * Removes the UTF8 BOM from the given string if there is one.
     * Used when parsing files.
     *
     * @param string $string
     *
     * @return string
     */
    public static function removeBOM(string $string) : string
    {
        if (substr($string, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
        }

        return $string;
    }

    /**
     * Create a human readable, as URL usable string from the given input.
     *
     * @param string $string
     *
     * @return string
     */
    public static function slugify(string $string) : string
    {
        // replace non letter or digits by -
        $noSpecials = preg_replace('~[^\\pL\d]+~u', '-', $string);

        $trimmed = trim($noSpecials, '-');

        // transliterate
        if (function_exists('iconv')) {
            $transliterated = iconv('utf-8', 'us-ascii//TRANSLIT', $trimmed);
        }

        $lower = strtolower($transliterated);

        // remove unwanted characters
        $cleaned = preg_replace('~[^-\w]+~', '', $lower);

        return $cleaned;
    }
}
