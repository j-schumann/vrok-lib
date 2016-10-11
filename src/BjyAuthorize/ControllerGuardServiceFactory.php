<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\BjyAuthorize;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Overwrites the default factory to use our own Guard class.
 */
class ControllerGuardServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return \BjyAuthorize\Guard\Controller
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('BjyAuthorize\Config');

        return new ControllerGuard($config['guards']['BjyAuthorize\Guard\Controller'], $serviceLocator);
    }

    // @todo remove zf3
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services, ControllerGuard::class);
    }
}
