<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok\I18n\Translator;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Creates an instance of our translator injecting the config.
 */
class TranslatorServiceFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $trConfig = isset($config['translator']) ? $config['translator'] : array();

        // the default translator factory is not able to use an existing service
        if (isset($trConfig['cache']) && is_string($trConfig['cache'])) {
            $trConfig['cache'] = $serviceLocator->get($trConfig['cache']);
        }

        $translator = Translator::factory($trConfig);
        return $translator;
    }
}
