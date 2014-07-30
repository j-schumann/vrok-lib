<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Extends the default implementation to use the serviecManager to allow listeners
 * with dependencies.
 */
class EntityListenerResolver extends DefaultEntityListenerResolver
    implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Retrieve the requested entity listener.
     * Checks the service locator for a service by the given name, if none found falls
     * back to try instantiation of the name as class.
     *
     * @param string $name
     * @return object
     */
    public function resolve($name)
    {
        return $this->getServiceLocator()->has($name)
            ? $this->getServiceLocator()->get($name)
            : parent::resolve($name);
    }
}
