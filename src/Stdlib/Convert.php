<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

use DateTime;

/**
 * Utility class for converting objects and types.
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class Convert
{
    /**
     * "Cast" an object to another class.
     * Used for JSON-decoded stdClass objects which need to be converted to the correct
     * class.
     * E.g. {"date":"2002-02-01 00:00:00","timezone_type":3,"timezone":"Europe\/Berlin"}
     * would be decoded to stdClass, objectToObject($stdObject, 'DateTime') copies it to
     * a DateTime instance.
     *
     * @link http://stackoverflow.com/a/3243949/1341762
     *
     * @param object $instance
     * @param string $className
     *
     * @return object
     */
    public static function objectToObject($instance, string $className)
    {
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen($className),
            $className,
            strstr(strstr(serialize($instance), '"'), ':')
        ));
    }

    /**
     * Encodes the given hostname or mail address in puny code / IDNA.
     *
     * @param string $value
     * @return string
     * @throws RuntimeException if the intl extension is not installed
     */
    public static function toIdna(string $value) : string
    {
        if (! extension_loaded('intl')) {
            throw new RuntimeException('ext/intl required to convert to IDNA!');
        }

        // use non-transitional mode:
        // http://devblog.plesk.com/2014/12/what-is-the-problem-with-s/

        $matches = [];
        preg_match('/^(.+)@([^@]+)$/', $value, $matches);

        // check if the value is a mail address, if yes encode only the hostname
        if (count($matches) === 3) {
            $host = idn_to_ascii(
                $matches[2],
                IDNA_NONTRANSITIONAL_TO_ASCII,
                INTL_IDNA_VARIANT_UTS46
            ) ?: $matches[2];

            return $matches[1].'@'.$host;
        }

        return idn_to_ascii(
            $value,
            IDNA_NONTRANSITIONAL_TO_ASCII,
            INTL_IDNA_VARIANT_UTS46
        ) ?: $value;
    }

    /**
     * Converts the given (unix) timestamp into a DateTime object.
     * Can detect if milliseconds are used, they are stripped before creating
     * the DateTime.
     *
     * @param string $timestamp
     * @return DateTime|false
     */
    public static function timestampToDateTime(string $timestamp)
    {
        // detect milliseconds
        // max seconds      =  99999999999 = 16 Nov 5138
        // min milliseconds = 100000000000 = 03 Mar 1973
        if (strlen($timestamp) >= 12) {
            $timestamp = floor($timestamp / 1000);
        }

        return DateTime::createFromFormat('U', $timestamp);
    }
}
