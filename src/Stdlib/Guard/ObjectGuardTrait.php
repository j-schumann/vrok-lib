<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib\Guard;

/**
 * Provide a guard method for is_object
 */
trait ObjectGuardTrait
{
    /**
     * Verifies that the data is an object
     *
     * @param  mixed  $data           the data to verify
     * @param  string $dataName       the data name
     * @param  string $exceptionClass FQCN for the exception
     * @throws \Exception
     */
    protected function guardForObject(
        $data,
        $dataName = 'Argument',
        $exceptionClass = 'Zend\Stdlib\Exception\InvalidArgumentException'
    ) {
        if (!is_object($data)) {
            $message = sprintf(
                "%s must be an object, [%s] given",
                $dataName,
                gettype($data)
            );
            throw new $exceptionClass($message);
        }
    }
}
