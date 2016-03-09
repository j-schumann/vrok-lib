<?php

/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Extends the default implementation to use the serviecManager to allow listeners
 * with dependencies.
 */
class EntityListenerResolver extends DefaultEntityListenerResolver
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $serviceLocator = null;

    /**
     * Class constructor - stores the ServiceLocator instance.
     * We inject the locator directly as not all services are lazy loaded
     * but some are only used in rare cases.
     * @todo lazyload all required services and include them in the factory
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function __construct(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;;
    }

    /**
     * Retrieve the stored service manager instance.
     *
     * @return ServiceLocatorInterface
     */
    private function getServiceLocator()
    {
        return $this->serviceLocator;
    }

    /**
     * Retrieve the requested entity listener.
     * Checks the service locator for a service by the given name, if none found falls
     * back to try instantiation of the name as class.
     *
     * @param string $name
     *
     * @return object
     */
    public function resolve($name)
    {
        return $this->getServiceLocator()->has($name)
            ? $this->getServiceLocator()->get($name)
            : parent::resolve($name);
    }
}
