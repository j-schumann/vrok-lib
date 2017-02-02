<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\Translator;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

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
            $trConfig['cache'] = $container->get($trConfig['cache']);
        }

        $translator = Translator::factory($trConfig);

        // The base class Zend\I18n\Translator\Translator does not implement the
        // EventManagerAwareInterface so the serviceManager won't inject the
        // (shared) eventManager automagically -> do this here, as we trigger
        // some events. If you dont need the EM, e.g you want to set
        // isEventManagerEnabled to false, use a custom factory.
        $events = $container->get('EventManager');
        $translator->setEventManager($events);

        return $translator;
    }
}
