<?php
/**
 * @copyright   (c) 2014, Vrok
 * @license     http://customlicense CustomLicense
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;

/**
 * Module bootstrapping.
 */
class Module implements
    BootstrapListenerInterface,
    ConfigProviderInterface,
    ControllerPluginProviderInterface,
    ServiceProviderInterface,
    ViewHelperProviderInterface
{
    /**
     * Returns the modules default configuration.
     *
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    /**
     * Holds factory closures that shouldn't be stored in the config as these can not be
     * cached.
     *
     * @return array
     */
    public function getControllerPluginConfig()
    {
        return array(
            'factories' => array(
                'loginRedirector' => function($controllerPluginManager) {
                    $serviceLocator = $controllerPluginManager->getServiceLocator();
                    $url = $serviceLocator->get('viewhelpermanager')->get('url');

                    $helper = new \Vrok\Mvc\Controller\Plugin\LoginRedirector();
                    $helper->setUrlHelper($url);
                    $helper->setRequest($serviceLocator->get('Request'));
                    return $helper;
                },
            ),
        );
    }

    /**
     * Return additional serviceManager config with closures that should not be in the
     * config files to allow caching of the complete configuration.
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Vrok\Owner\OwnerService' => function($sm) {
                    $service = new \Vrok\Owner\OwnerService();

                    $config = $sm->get('Config');
                    if (!empty($config['owner_service']['allowed_owners'])) {
                        $allowedOwners = $config['owner_service']['allowed_owners'];
                        $service->setAllowedOwners($allowedOwners);
                    }
                    return $service;
                },
                'Vrok\Service\Email' => function($sm) {
                    $vhm = $sm->get('ViewHelperManager');
                    $transport = $sm->get('Zend\Mail\Transport');
                    $service = new \Vrok\Service\Email($transport, $vhm);

                    $config = $sm->get('Config');
                    if (!empty($config['email_service'])) {
                        $service->setOptions($config['email_service']);
                    }

                    return $service;
                },
                'Vrok\Service\Meta' => function($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    $service = new \Vrok\Service\Meta($em);

                    $config = $sm->get('Config');
                    if (!empty($config['meta_service']['defaults'])) {
                        $service->setDefaults($config['meta_service']['defaults']);
                    }
                    return $service;
                },
                'Vrok\Service\Todo' => function($sm) {
                    $service = new \Vrok\Service\Todo();
                    $service->setServiceLocator($sm);

                    $config = $sm->get('Config');
                    if (!empty($config['todo_service']['timeouts'])) {
                        $service->setTimeouts($config['todo_service']['timeouts']);
                    }
                    return $service;
                },
                'Vrok\User\Manager' => function($sm) {
                    $manager = new \Vrok\User\Manager();

                    $config = $sm->get('Config');
                    if (!empty($config['user_manager'])) {
                        $manager->setConfig($config['user_manager']);
                    }
                    return $manager;
                },
                'Vrok\Validation\Manager' => function($sm) {
                    $manager = new \Vrok\Validation\Manager();

                    $config = $sm->get('Config');
                    if (!empty($config['validation_manager']['timeouts'])) {
                        $manager->setTimeouts($config['validation_manager']['timeouts']);
                    }
                    return $manager;
                },
                'Zend\Mail\Transport' => function($sm) {
                    $spec = array();
                    $config = $sm->get('Config');
                    if (!empty($config['email_service']['transport'])) {
                        $spec = $config['email_service']['transport'];
                    }
                    return \Zend\Mail\Transport\Factory::create($spec);
                },
            ),
        );
    }

    /**
     * Retrieve additional view helpers using factories that are not set in the config.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'fullUrl'  => function($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $config = $serviceLocator->get('Config');
                    if (empty($config['general']['full_url'])) {
                        throw new \RuntimeException('"full_url" is not set in the [general] config!');
                    }

                    $helper = new \Vrok\View\Helper\FullUrl($config['general']['full_url']);
                    return $helper;
                },
            ),
        );
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

        // we want to lazy load the strategy object only when needed, so we use a
        // closure here
        $sharedEvents->attach('OwnerService', 'getOwnerStrategy', function($e) {
            return \Vrok\Owner\UserStrategy::onGetOwnerStrategy($e);
        });

        // Listen to the CRON events, they are rare, don't instantiate any objects yet
        $sharedEvents->attach('Vrok\Controller\CronController', 'cronDaily', function($e) {
	    return \Vrok\SlmQueue\Job\PurgeValidations::onCronDaily($e);
        });
        $sharedEvents->attach('Vrok\Controller\CronController', 'cronDaily', function($e) {
            return \Vrok\SlmQueue\Job\CheckTodos::onCronDaily($e);
        });
    }
}
