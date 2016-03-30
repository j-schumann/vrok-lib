<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\Validator;

use DoctrineModule\Validator\UniqueObject;

/**
 * Allows to validate for unique objects with composite keys.
 */
class UniqueComposite extends UniqueObject
{
    /**
     * The base UniqueObject validator allows to set multiple fields to use as
     * identifiers but when adding it to a concrete field it only receives this
     * single value as $value and ignores the $context which contains the other
     * fields, the validation fails with "Provided values count is 1, while
     * expected number of fields to be matched is 2".
     *
     * Using a fieldset and adding the validator in an inputfilter with the name
     * of the fieldset works but prohibits using the inputfilter from the
     * fieldset itself, e.g. we can either validate the single inputs or the
     * UniqueObject.
     *
     * To fix this we simply validate the $context here instead of the $value.
     *
     * @see https://github.com/doctrine/DoctrineModule/issues/252
     * @see https://github.com/doctrine/DoctrineModule/issues/433
     */
    public function isValid($value, $context = null)
    {
        return parent::isValid($context, $context);
    }
}
