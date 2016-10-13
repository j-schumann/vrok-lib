<?php

/**
 * @copyright   (c) 2014-16, Vrok
 * @license     MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @author      Jakob Schumann <schumann@vrok.de>
 */

namespace Vrok;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Zend\ModuleManager\Feature\FormElementProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;

/**
 * Module bootstrapping.
 */
class Module implements
    BootstrapListenerInterface,
    ConfigProviderInterface,
    ControllerPluginProviderInterface,
    FormElementProviderInterface,
    SlmQueue\JobProviderInterface,
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
                'loginRedirector' => function ($sm) {
                    $url = $sm->get('ViewHelperManager')->get('url');

                    $helper = new Mvc\Controller\Plugin\LoginRedirector();
                    $helper->setUrlHelper($url);
                    $helper->setRequest($sm->get('Request'));

                    return $helper;
                },
            ],
        ];
    }

    /**
     * Return additional serviceManager config with closures that should not be in the
     * config files to allow caching of the complete configuration.
     *
     * @return array
     */
    public function getFormElementConfig()
    {
        return [
            'factories' => [
                'Vrok\Form\ConfirmationForm' => function ($sm) {
                    $form = new Form\ConfirmationForm();
                    $form->setEntityManager($sm->get('Doctrine\ORM\EntityManager'));
                    $form->setTranslator($sm->get('MvcTranslator'));
                    return $form;
                },
            ],
        ];
    }

    /**
     * Retrieve factories for SlmQueue jobs.
     *
     * @return array
     */
    public function getJobManagerConfig()
    {
        return [
            'factories' => [
                'Vrok\SlmQueue\Job\CheckTodos' => function ($sl) {
                    $todoService = $sl->get('Vrok\Service\Todo');
                    return new SlmQueue\Job\CheckTodos($todoService);
                },
                'Vrok\SlmQueue\Job\PurgeValidations' => function ($sl) {
                    $vm = $sl->get('Vrok\Service\ValidationManager');
                    return new SlmQueue\Job\PurgeValidations($vm);
                },
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

                    $vr = $sm->get('ViewRenderer');
                    return new Asset\ViewScriptResolver($vr, $map);
                },
                'Vrok\Authentication\Adapter\Cookie' => function ($sm) {
                    $em = $sm->get('Doctrine\ORM\EntityManager');
                    return new Authentication\Adapter\Cookie($em);
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
                    $service->setAuthenticationService($sm->get('Zend\Authentication\AuthenticationService'));
                    $service->setEntityManager($sm->get('Doctrine\ORM\EntityManager'));
                    $service->setViewHelperManager($sm->get('ViewHelperManager'));

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
                    $manager = new Service\ValidationManager();
                    $manager->setEntityManager($sm->get('Doctrine\ORM\EntityManager'));
                    $manager->setControllerPluginManager($sm->get('ControllerPluginManager'));
                    $manager->setViewHelperManager($sm->get('ViewHelperManager'));

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
                'fullUrl' => function ($sl) {
                    $config = $sl->get('Config');
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
     * Register the SlmQueue JobManager as ServiceManager to automagically load
     * the factories from modules implementing our JobProviderInterface.
     *
     * @param \Zend\ModuleManager\ModuleManager $moduleManager
     * @return void
     */
    public function init($moduleManager)
    {
        $event = $moduleManager->getEvent();
        $container = $event->getParam('ServiceManager');
        $serviceListener = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            'SlmQueue\Job\JobPluginManager',
            'job_manager', // slmQueue uses slm_queue/job_manager
            'Vrok\SlmQueue\JobProviderInterface',
            'getJobManagerConfig'
        );
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

            $userManager = $sm->get(Service\UserManager::class);

            return new Owner\UserStrategy($userManager);
        });
    }
}
