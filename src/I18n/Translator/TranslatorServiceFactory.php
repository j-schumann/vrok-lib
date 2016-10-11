<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\Translator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Creates an instance of our translator injecting the config.
 */
class TranslatorServiceFactory implements FactoryInterface
{
    /**
     * {@inheritDoc}
     *
     * @return Translator
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config   = $container->get('Config');
        $trConfig = isset($config['translator']) ? $config['translator'] : [];

        // the default translator factory is not able to use an existing service
        if (isset($trConfig['cache']) && is_string($trConfig['cache'])) {
            $trConfig['cache'] = $serviceLocator->get($trConfig['cache']);
        }

        $translator = Translator::factory($trConfig);

        return $translator;
    }

    // @todo remove zf3
    public function createService(ServiceLocatorInterface $services)
    {
        return $this($services, Translator::class);
    }
}
