<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;

/**
 * Module bootstrapping.
 */
class Module implements AutoloaderProviderInterface, BootstrapListenerInterface,
        ConfigProviderInterface
{
    /**
     * Returns the modules default configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * Attach some listeners to the shared eventmanager.
     *
     * @param EventInterface $e
     */
    public function onBootstrap(EventInterface $e)
    {
        $application = $e->getApplication();
        $sharedEvents = $application->getEventManager()->getSharedManager();

        // we want to lazy load the strategy object only when needed to we use a static
        // function here
        $sharedEvents->attach('OwnerService', 'getOwnerStrategy',
                array('\Vrok\Owner\UserStrategy', 'onGetOwnerStrategy'));
    }

    /**
     * Returns the autoloader definiton to use to load classes within this module.
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
