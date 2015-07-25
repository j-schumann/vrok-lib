<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
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
    public static function escapeString($str)
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
     * @param type $string
     *
     * @return string
     */
    public static function removeBOM($string)
    {
        if (substr($string, 0, 3) == pack('CCC', 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
        }

        return $string;
    }
}
