<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib;

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
     * @param type $instance
     * @param type $className
     *
     * @return type
     */
    public static function objectToObject($instance, $className)
    {
        return unserialize(sprintf(
            'O:%d:"%s"%s',
            strlen($className),
            $className,
            strstr(strstr(serialize($instance), '"'), ':')
        ));
    }
}
