<?php
/**
 * Vrok-Lib config
 */
return array(
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../public',
            ),
            'map' => array(

            ),
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'cron-hourly' => array(
                    'options' => array(
                        'route' => 'cron-hourly',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action'     => 'cron-hourly',
                        ),
                    ),
                ),
                'cron-daily' => array(
                    'options' => array(
                        'route' => 'cron-daily',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action'     => 'cron-daily',
                        ),
                    ),
                ),
                'cron-monthly' => array(
                    'options' => array(
                        'route' => 'cron-monthly',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action'     => 'cron-monthly',
                        ),
                    ),
                ),
                'purge-validations' => array(
                    'options' => array(
                        'route' => 'purge-validations',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Validation',
                            'action'     => 'purge',
                        ),
                    ),
                ),
                'check-jobs' => array(
                    'options' => array(
                        'route' => 'check-jobs',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\SlmQueue',
                            'action'     => 'check-jobs',
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Vrok\Controller\Cron'        => 'Vrok\Controller\CronController',
            'Vrok\Controller\SlmQueue'    => 'Vrok\Controller\SlmQueueController',
            'Vrok\Controller\Validation'  => 'Vrok\Controller\ValidationController',
        ),
    ),

    'controller_plugins' => array(
        'invokables' => array(
            'translate' => 'Vrok\Mvc\Controller\Plugin\Translate',
        ),
    ),

    'doctrine' => array(
        'configuration' => array(
            'orm_default' => array(
                'entity_listener_resolver'
                        => 'Vrok\Doctrine\Orm\Mapping\EntityListenerResolver',
            ),
        ),
        'driver' => array(
            'vrok_entities' => array(
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Vrok/Entity')
            ),
            'orm_default' => array(
                'drivers' => array(
                    'Vrok\Entity' => 'vrok_entities'
                ),
            ),
        ),
        'eventmanager' => array(
            'orm_default' => array(
                'subscribers' => array(
                    'Gedmo\Timestampable\TimestampableListener',
                    'Gedmo\Sluggable\SluggableListener',
                ),
            ),
        ),
    ),

    'listeners' => array(
        'Vrok\Notification\AdminNotifications',
    ),

    'navigation' => array(
        'default' => array(
            'administration' => array(
                'label' => 'navigation.administration', // default label or none is rendered
                'uri'   => '#', // we need a either a route or an URI to avoid fatal error
                'order' => 1000,
                'pages' => array(
                    'server' => array(
                        'label' => 'navigation.administration.server', // default label or none is rendered
                        'uri'   => '#', // we need either a route or an URI to avoid fatal error
                        'order' => 1000,
                        'pages' => array(
                            array(
                                'label'     => 'navigation.slmQueue',
                                'route'     => 'slm-queue',
                                'resource'  => 'controller/Vrok\Controller\SlmQueue',
                                'privilege' => 'index',
                                'order'     => 1000,
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'owner_service' => array(
        'allowed_owners' => array(
            'Vrok\Entity\Validation' => array(
                'Vrok\Entity\User',
            ),
        ),
    ),

    'router' => array(
        'routes' => array(
            'slm-queue' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/slm-queue/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Vrok\Controller',
                        'controller'    => 'SlmQueue',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes'  => array(
                    'recover' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'recover/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'recover',
                            ),
                        ),
                    ),
                    'list-buried' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'list-buried/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'list-buried',
                            ),
                        ),
                    ),
                    'list-running' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'list-running/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'list-running',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'delete/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id'   => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                    'release' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'release/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id'   => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'release',
                            ),
                        ),
                    ),
                    'unbury' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => 'unbury/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id'   => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'unbury',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'service_manager' => array(
        // add some short names that hopefully don't conflict
        'aliases' => array(
            'AuthorizeRedirectStrategy' => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
            'ClientInfo'               => 'Vrok\Client\Info',
            'OwnerService'             => 'Vrok\Owner\OwnerService',
            'UserManager'              => 'Vrok\User\Manager',
            'ValidationManager'        => 'Vrok\Validation\Manager',
        ),

        // classes that have no dependencies or are ServiceLocatorAware
        'invokables' => array(
            'Vrok\Authentication\Adapter\Doctrine'             => 'Vrok\Authentication\Adapter\Doctrine',
            'Vrok\Client\Info'                                 => 'Vrok\Client\Info',
            'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver' => 'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver',
            'Vrok\Notification\AdminNotifications'             => 'Vrok\Notification\AdminNotifications',
            'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy'     => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
        ),

        'factories' => array(
            // replace the default translator with our custom extension
            'Zend\I18n\Translator\TranslatorInterface'
                => 'Vrok\I18n\Translator\TranslatorServiceFactory',

            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
        ),
    ),

    'slm_queue' => array(
        'job_manager' => array(
            'invokables' => array(
                'Vrok\SlmQueue\Job\CheckTodos'       => 'Vrok\SlmQueue\Job\CheckTodos',
                'Vrok\SlmQueue\Job\ExitWorker'       => 'Vrok\SlmQueue\Job\ExitWorker',
                'Vrok\SlmQueue\Job\PurgeValidations' => 'Vrok\SlmQueue\Job\PurgeValidations',
            ),
        ),
    ),

    'validation_manager' => array(
        'timeouts' => array(
            'password' => 172800, //48*60*60
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'alternativeUrl'       => 'Vrok\View\Helper\AlternativeUrl',
            'formDecorator'        => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'flashMessenger'       => 'Vrok\View\Helper\FlashMessenger',
            'translatePlural'      => '\Vrok\I18n\Translator\View\Helper\TranslatePlural',

            // @todo used? necessary?
            'formMultiText'        => 'Vrok\Form\View\Helper\FormMultiText',
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);
