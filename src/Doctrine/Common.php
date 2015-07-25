<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine;

abstract class Common
{
    /**
     * Helper function to return a unified string to use as translation identifer.
     *
     * @param string $class     FQCN of the entity
     * @param string $fieldName (optional) the fieldname to append to the string
     *
     * @return string
     */
    public static function getEntityTranslationString($class, $fieldName = null)
    {
        $string = str_replace('\\', '.', $class);
        if ($fieldName) {
            $string .= '.'.$fieldName;
        }

        return $string;
    }
}
