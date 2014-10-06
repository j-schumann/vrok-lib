<?php
/**
 * Vrok-Lib config
 */
return array(
// <editor-fold defaultstate="collapsed" desc="asset_manager">
    'asset_manager' => array(
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../public',
            ),
            'map' => array(
                        ),
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="console">
    'console' => array(
        'router' => array(
            'routes' => array(
                'cron-hourly' => array(
                    'options' => array(
                        'route' => 'cron-hourly',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action' => 'cron-hourly',
                        ),
                    ),
                ),
                'cron-daily' => array(
                    'options' => array(
                        'route' => 'cron-daily',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action' => 'cron-daily',
                        ),
                    ),
                ),
                'cron-monthly' => array(
                    'options' => array(
                        'route' => 'cron-monthly',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Cron',
                            'action' => 'cron-monthly',
                        ),
                    ),
                ),
                'purge-validations' => array(
                    'options' => array(
                        'route' => 'purge-validations',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Validation',
                            'action' => 'purge',
                        ),
                    ),
                ),
                'check-jobs' => array(
                    'options' => array(
                        'route' => 'check-jobs',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\SlmQueue',
                            'action' => 'check-jobs',
                        ),
                    ),
                ),
            ),
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="controllers">
    'controllers' => array(
        'invokables' => array(
            'Vrok\Controller\Cron' => 'Vrok\Controller\CronController',
            'Vrok\Controller\SlmQueue' => 'Vrok\Controller\SlmQueueController',
            'Vrok\Controller\Validation' => 'Vrok\Controller\ValidationController',
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="controller_plugins">
    'controller_plugins' => array(
        'invokables' => array(
            'translate' => 'Vrok\Mvc\Controller\Plugin\Translate',
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="doctrine">
    'doctrine' => array(
        'configuration' => array(
            'orm_default' => array(
                'entity_listener_resolver'
                        => 'Vrok\Doctrine\Orm\Mapping\EntityListenerResolver',
                'types' => array(
                    // this extends the default JSON column to allow using VARCHAR
                    // instead of TINYTEXT for lengths <= 255 so it can be indexed
                    'json_data' => 'Vrok\Doctrine\DBAL\Types\JsonType',
                ),
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
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="listeners">
    'listeners' => array(
        'Vrok\Notification\AdminNotifications',
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="navigation">
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
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="owner_service">
    'owner_service' => array(
        'allowed_owners' => array(
            'Vrok\Entity\Validation' => array(
                'Vrok\Entity\User',
            ),
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="router">
    'router' => array(
        'routes' => array(
            'slm-queue' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/slm-queue/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Vrok\Controller',
                        'controller' => 'SlmQueue',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'recover' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'recover/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'recover',
                            ),
                        ),
                    ),
                    'list-buried' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'list-buried/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'list-buried',
                            ),
                        ),
                    ),
                    'list-running' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'list-running/[:name][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                            ),
                            'defaults' => array(
                                'action' => 'list-running',
                            ),
                        ),
                    ),
                    'delete' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'delete/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'delete',
                            ),
                        ),
                    ),
                    'release' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'release/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'action' => 'release',
                            ),
                        ),
                    ),
                    'unbury' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => 'unbury/[:name]/[:id][/]',
                            'constraints' => array(
                                'name' => '[a-zA-Z0-9_-]+',
                                'id' => '[0-9]+',
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
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="service_manager">
    'service_manager' => array(
        // required to overwrite existing services with an alias etc.
        'allow_override' => true,

        // add some short names that hopefully don't conflict
        'aliases' => array(
            'AuthorizeRedirectStrategy' => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
            'ClientInfo'                => 'Vrok\Client\Info',
            'MetaService'               => 'Vrok\Service\Meta',
            'OwnerService'              => 'Vrok\Service\Owner',
            'UserManager'               => 'Vrok\Service\UserManager',
            'ValidationManager'         => 'Vrok\Service\ValidationManager',
            'AuthenticationService'     => 'Zend\Authentication\AuthenticationService',

            // BjyAuthorize only searches for zfcuser_user_service -> point to our
            // own service
            'zfcuser_user_service' => 'Vrok\Service\UserManager',
        ),

        // classes that have no dependencies or are ServiceLocatorAware
        'invokables' => array(
            'Vrok\Authentication\Adapter\Doctrine' => 'Vrok\Authentication\Adapter\Doctrine',
            'Vrok\Authentication\Storage\Doctrine' => 'Vrok\Authentication\Storage\Doctrine',
            'Vrok\Client\Info' => 'Vrok\Client\Info',
            'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver' => 'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver',
            'Vrok\Notification\AdminNotifications' => 'Vrok\Notification\AdminNotifications',
            'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy' => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
        ),

        'factories' => array(
            // replace the default translator with our custom extension
            'Zend\I18n\Translator\TranslatorInterface'
                    => 'Vrok\I18n\Translator\TranslatorServiceFactory',

            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
            'Vrok\Service\FieldHistory' => 'Vrok\Service\FieldHistoryServiceFactory',
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="slm_queue">
    'slm_queue' => array(
        // after how many seconds are jobs reported as long running by
        // SlmQueueController::checkJobsAction?
        'runtime_threshold' => 3600, // 60 * 60

        'job_manager' => array(
            'invokables' => array(
                'Vrok\SlmQueue\Job\CheckTodos' => 'Vrok\SlmQueue\Job\CheckTodos',
                'Vrok\SlmQueue\Job\ExitWorker' => 'Vrok\SlmQueue\Job\ExitWorker',
                'Vrok\SlmQueue\Job\PurgeValidations' => 'Vrok\SlmQueue\Job\PurgeValidations',
            ),
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="validation_manager">
    'validation_manager' => array(
        'timeouts' => array(
            'password' => 172800, //48*60*60
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_helpers">
    'view_helpers' => array(
        'invokables' => array(
            'alternativeUrl' => 'Vrok\View\Helper\AlternativeUrl',
            'flashMessenger' => 'Vrok\View\Helper\FlashMessenger',

            'durationFormat' => '\Vrok\I18n\View\Helper\DurationFormat',
            'translatePlural' => '\Vrok\I18n\View\Helper\TranslatePlural',

            'formDecorator' => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'formDurationSelect' => 'Vrok\Form\View\Helper\FormDurationSelect',
            // @todo used? necessary?
            'formMultiText' => 'Vrok\Form\View\Helper\FormMultiText',
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_manager">
    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
// </editor-fold>
);
