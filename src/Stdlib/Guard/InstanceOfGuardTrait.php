<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Stdlib\Guard;

/**
 * Provide a guard method for instanceof
 */
trait InstanceOfGuardTrait
{
    /**
     * Verifies that the data is an instance of the given class
     *
     * @param  mixed  $data           the data to verify
     * @param  string $className      the class name to match
     * @param  string $dataName       the data name
     * @param  string $exceptionClass FQCN for the exception
     * @throws \Exception
     */
    protected function guardForInstanceOf(
        $data,
        $className,
        $dataName = 'Argument',
        $exceptionClass = 'Zend\Stdlib\Exception\InvalidArgumentException'
    ) {
        if (!($data instanceof $className)) {
            $message = sprintf(
                "%s must be an instance of %s, [%s] given",
                $dataName,
                $className,
                is_object($data) ? get_class($data) : gettype($data)
            );
            throw new $exceptionClass($message);
        }
    }
}
