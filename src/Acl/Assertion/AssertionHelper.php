<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Acl\Assertion;

use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * The ACL cannot be cached if the assertions have dependencies (like the
 * usermanager) that can not be serialized.
 * Only solution atm is to exclude the dependencies from the assertions and not
 * using factories and fetch the dependencies on execution from a known context.
 * We could try to access $application or anything else but that could be
 * unavailable.
 *
 * This class simply provides a static context to keep a serviceLocator reference,
 * it should be initialized onBootstrap from the application module.
 *
 * @link https://github.com/bjyoungblood/BjyAuthorize/issues/213
 */
class AssertionHelper
{
    /**
     * @var Zend\ServiceManager\ServiceLocatorInterface
     */
    protected static $serviceLocator = null;

    /**
     * Retrieve the ServiceManager instance.
     *
     * @return Zend\ServiceManager\ServiceLocatorInterface
     */
    public static function getServiceLocator()
    {
        return self::$serviceLocator;
    }

    /**
     * Store the given ServiceManager instance.
     *
     * @param Zend\ServiceManager\ServiceLocatorInterface $sl
     */
    public static function setServiceLocator(ServiceLocatorInterface $sl)
    {
        self::$serviceLocator = $sl;
    }
}
