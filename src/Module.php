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
        return include __DIR__.'/../config/module.config.php';
    }

    /**
     * Holds factory closures that shouldn't be stored in the config as these can not be
     * cached.
     *
     * @return array
     */
    public function getControllerPluginConfig()
    {
        return [
            'factories' => [
                'loginRedirector' => function ($controllerPluginManager) {
                    $serviceLocator = $controllerPluginManager->getServiceLocator();
                    $url = $serviceLocator->get('viewhelpermanager')->get('url');

                    $helper = new Mvc\Controller\Plugin\LoginRedirector();
                    $helper->setUrlHelper($url);
                    $helper->setRequest($serviceLocator->get('Request'));

                    return $helper;
                },
            ],
            'invokables' => [
                'currentUser' => 'Vrok\Mvc\Controller\Plugin\CurrentUser',
            ],
        ];
    }

    /**
     * Return additional serviceManager config with closures that should not be in the
     * config files to allow caching of the complete configuration.
     *
     * @return array
     */
    public function getServiceConfig()
    {
        return [
            'factories' => [
                'Vrok\Asset\ViewScriptResolver' => function ($sm) {
                    $config = $sm->get('Config');
                    $map = [];
                    if (isset($config['asset_manager']['resolver_configs']['view_scripts'])) {
                        $map = $config['asset_manager']['resolver_configs']['view_scripts'];
                    }
                    $vm = $sm->get('ViewManager');

                    return new Asset\ViewScriptResolver($vm, $map);
                },
                'Vrok\Authentication\Adapter\Doctrine' => function ($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    return new Authentication\Adapter\Doctrine($em);
                },
                'Vrok\Authentication\Storage\Doctrine' => function ($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    return new Authentication\Storage\Doctrine($em);
                },
                'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver' => function ($sm) {
                    return new Doctrine\ORM\Mapping\EntityListenerResolver($sm);
                },
                'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy' => function ($sm) {
                    return new Mvc\View\Http\AuthorizeRedirectStrategy($sm);
                },
                'Vrok\Service\Email' => function ($sm) {
                    $vhm = $sm->get('ViewHelperManager');
                    $service = new Service\Email($vhm);

                    $config = $sm->get('Config');
                    if (!empty($config['email_service'])) {
                        $service->setOptions($config['email_service']);
                    }

                    return $service;
                },
                'Vrok\Service\Meta' => function ($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    $service = new Service\Meta($em);

                    $config = $sm->get('Config');
                    if (!empty($config['meta_service']['defaults'])) {
                        $service->setDefaults($config['meta_service']['defaults']);
                    }

                    return $service;
                },
                'Vrok\Service\Owner' => function ($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    $service = new Service\Owner($em);

                    $config = $sm->get('Config');
                    if (!empty($config['owner_service']['allowed_owners'])) {
                        $allowedOwners = $config['owner_service']['allowed_owners'];
                        $service->setAllowedOwners($allowedOwners);
                    }

                    return $service;
                },
                'Vrok\Service\Todo' => function ($sm) {
                    $service = new Service\Todo();
                    $service->setServiceLocator($sm);

                    $config = $sm->get('Config');
                    if (!empty($config['todo_service']['timeouts'])) {
                        $service->setTimeouts($config['todo_service']['timeouts']);
                    }

                    return $service;
                },
                'Vrok\Service\UserManager' => function ($sm) {
                    $manager = new Service\UserManager($sm);

                    $config = $sm->get('Config');
                    if (!empty($config['user_manager'])) {
                        $manager->setConfig($config['user_manager']);
                    }

                    return $manager;
                },
                'Vrok\Service\ValidationManager' => function ($sm) {
                    $manager = new Service\ValidationManager($sm);

                    $config = $sm->get('Config');
                    if (!empty($config['validation_manager']['timeouts'])) {
                        $manager->setTimeouts($config['validation_manager']['timeouts']);
                    }

                    return $manager;
                },
            ],
        ];
    }

    /**
     * Retrieve additional view helpers using factories that are not set in the config.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        return [
            'factories' => [
                'currentUser' => function ($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $authService = $serviceLocator->get('AuthenticationService');

                    $helper = new \Vrok\View\Helper\CurrentUser();
                    $helper->setAuthService($authService);

                    return $helper;
                },
                'fullUrl' => function ($helperPluginManager) {
                    $serviceLocator = $helperPluginManager->getServiceLocator();
                    $config = $serviceLocator->get('Config');
                    if (empty($config['general']['full_url'])) {
                        throw new \RuntimeException('"full_url" is not set in the [general] config!');
                    }

                    $helper = new \Vrok\View\Helper\FullUrl($config['general']['full_url']);

                    return $helper;
                },
            ],
        ];
    }

    /**
     * Attach some listeners to the shared eventmanager.
     *
     * @param EventInterface $e
     */
    public function onBootstrap(EventInterface $e)
    {
        /* @var $e \Zend\Mvc\MvcEvent */
        $application  = $e->getApplication();
        $sharedEvents = $application->getEventManager()->getSharedManager();
        $sm           = $application->getServiceManager();

        // we want to lazy load the strategy object only when needed, so we use a
        // closure here
        $sharedEvents->attach('OwnerService', 'getOwnerStrategy', function ($e) use ($sm) {
            // @todo strategy nicht via event laden sondern über config?
            // @todo strategy als service einrichten?
            $classes = $e->getParam('classes');
            if (!in_array('Vrok\Entity\User', $classes)) {
                return;
            }

            $userManager = $sm->get('UserManager');

            return new \Vrok\Owner\UserStrategy($userManager);
        });
    }
}
