<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Interop\Container\ContainerInterface;
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
     * @param ContainerInterface $container
     * @todo params doc
     *
     * @return \Vrok\Service\FieldHistory
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('Doctrine\ORM\EntityManager');
        $as = $container->get('Zend\Authentication\AuthenticationService');

        $service = new FieldHistory($em, $as);

        return $service;
    }

    // @todo remove zf3
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services, FieldHistory::class);
    }
}
