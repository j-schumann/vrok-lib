<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Creates an instance of the action logger.
 */
class ActionLoggerServiceFactory implements FactoryInterface
{
    /**
     * Inject the dependencies into the new service instance.
     *
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array $options
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
}
