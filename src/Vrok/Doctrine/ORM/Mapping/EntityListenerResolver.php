<?php

namespace Vrok\Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultEntityListenerResolver;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

class EntityListenerResolver extends DefaultEntityListenerResolver
    implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function resolve($name)
    {
        return $this->getServiceLocator()->has($name)
            ? $this->getServiceLocator()->get($name)
            : parent::resolve($name);
    }
}
