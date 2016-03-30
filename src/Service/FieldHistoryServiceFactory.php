<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Creates an instance of the history service.
 */
class FieldHistoryServiceFactory implements FactoryInterface
{
    /**
     * Inject the dependencies into the new service instance.
     *
     * @param \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
     *
     * @return \Vrok\Service\FieldHistory
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $em = $serviceLocator->get('Doctrine\ORM\EntityManager');
        $as = $serviceLocator->get('Zend\Authentication\AuthenticationService');

        $service = new FieldHistory($em, $as);

        return $service;
    }
}
