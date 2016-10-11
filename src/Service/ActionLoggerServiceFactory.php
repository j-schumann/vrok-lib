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
 * Creates an instance of the action logger.
 */
class ActionLoggerServiceFactory implements FactoryInterface
{
    /**
     * Inject the dependencies into the new service instance.
     *
     * @param ContainerInterface $container
     * @todo params doc
     *
     * @return \Vrok\Service\ActionLogger
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $em = $container->get('Doctrine\ORM\EntityManager');
        $as = $container->get('Zend\Authentication\AuthenticationService');
        $ci = $container->get('Vrok\Client\Info');

        $logger = new ActionLogger($em, $as, $ci);

        return $logger;
    }

    // @todo remove zf3
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services, ActionLogger::class);
    }
}
