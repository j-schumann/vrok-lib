<?php

namespace Vrok\Form;

use Doctrine\Common\Persistence\ObjectManager;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

/**
 * Used to extend our fieldset and form classes by this functions because they
 * can not inherit from the same base.
 */
trait SharedFunctions
{
    use ServiceLocatorAwareTrait;

    /**
     * Retrieve the entity manager.
     *
     * @return ObjectManager
     */
    public function getEntityManager()
    {
        return $this->getServiceLocator() // returns the FormElementManager
            ->getServiceLocator() // returns the ServiceManager
            ->get('Doctrine\ORM\EntityManager');
    }
}
