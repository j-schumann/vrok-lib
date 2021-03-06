<?php

/**
 * Vrok-Lib config.
 */
return [
// <editor-fold defaultstate="collapsed" desc="asset_manager">
    'asset_manager' => [
        'resolvers' => [
            'Vrok\Asset\ViewScriptResolver' => 3001,
        ],
        'resolver_configs' => [
            'paths' => [
                __DIR__.'/../public',
            ],
            'map' => [],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="controller_plugins">
    'controller_plugins' => [
        'aliases' => [
            'translate' => 'Vrok\Mvc\Controller\Plugin\Translate',
        ],
        'factories' => [
            'Vrok\Mvc\Controller\Plugin\Translate' => 'Zend\ServiceManager\Factory\InvokableFactory',
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="doctrine">
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'entity_listener_resolver' => 'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver',
                'types'                    => [
                    // this extends the default JSON column to allow using VARCHAR
                    // instead of TINYTEXT for lengths <= 255 so it can be indexed
                    'json_data' => 'Vrok\Doctrine\DBAL\Types\JsonType',
                ],
            ],
        ],
        'driver' => [
            'vrok_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__.'/../src/Entity'],
            ],
            'orm_default' => [
                'drivers' => [
                    'Vrok\Entity' => 'vrok_entities',
                ],
            ],
        ],
        'eventmanager' => [
            'orm_default' => [
                'subscribers' => [
                    'Gedmo\Timestampable\TimestampableListener',
                    'Gedmo\Sluggable\SluggableListener',
                ],
            ],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="listeners">
    'listeners' => [
        'Vrok\Service\UserManager',
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="owner_service">
    'owner_service' => [
        'allowed_owners' => [
            'Vrok\Entity\Validation' => [
                'Vrok\Entity\User',
            ],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="service_manager">
    'service_manager' => [
        'delegators' => [
            'ControllerPluginManager'          => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'ViewHelperManager'                => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\ActionLogger'        => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\Email'               => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\FieldHistory'        => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\Meta'                => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\NotificationService' => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\Owner'               => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\Todo'                => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\UserManager'         => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Vrok\Service\ValidationManager'   => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],
            'Zend\Authentication\AuthenticationService' => ['Zend\ServiceManager\Proxy\LazyServiceFactory'],

        ],
        'factories' => [
            'Vrok\Client\Info'          => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
            'Vrok\Service\FieldHistory' => 'Vrok\Service\FieldHistoryServiceFactory',

            // the listeners need the shared eventmanager -> use the factory,
            // else they would be instantiated without applying the initializers
            'Vrok\Entity\Listener\NotificationListener' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\Entity\Listener\TodoListener'         => 'Zend\ServiceManager\Factory\InvokableFactory',

            // overwritten to use our extended controller guard
            'BjyAuthorize\Guard\Controller' => 'Vrok\BjyAuthorize\ControllerGuardServiceFactory',
        ],

        'lazy_services' => [
            'class_map' => [
                'ControllerPluginManager'          => 'Zend\Mvc\Controller\PluginManager',
                'ViewHelperManager'                => 'Zend\View\HelperPluginManager',
                'Vrok\Service\ActionLogger'        => 'Vrok\Service\ActionLogger',
                'Vrok\Service\Email'               => 'Vrok\Service\Email',
                'Vrok\Service\FieldHistory'        => 'Vrok\Service\FieldHistory',
                'Vrok\Service\Meta'                => 'Vrok\Service\Meta',
                'Vrok\Service\NotificationService' => 'Vrok\Service\NotificationService',
                'Vrok\Service\Owner'               => 'Vrok\Service\Owner',
                'Vrok\Service\Todo'                => 'Vrok\Service\Todo',
                'Vrok\Service\UserManager'         => 'Vrok\Service\UserManager',
                'Vrok\Service\ValidationManager'   => 'Vrok\Service\ValidationManager',
                'Zend\Authentication\AuthenticationService' => 'Zend\Authentication\AuthenticationService',
            ],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="slm_queue">
    'slm_queue' => [
        'job_manager' => [
            'factories' => [
                'Vrok\SlmQueue\Job\ExitWorker' => 'Zend\ServiceManager\Factory\InvokableFactory',
            ],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_helpers">
    'view_helpers' => [
        'aliases' => [
            'flashMessenger' => 'Vrok\View\Helper\FlashMessenger',
            'highlightText'  => 'Vrok\View\Helper\HighlightText',
            'texEscape'      => 'Vrok\View\Helper\TexEscape',

            'currencyFormat'  => 'Vrok\I18n\View\Helper\CurrencyFormat',
            'durationFormat'  => 'Vrok\I18n\View\Helper\DurationFormat',
            'numberFormat'    => 'Vrok\I18n\View\Helper\NumberFormat',
            'translatePlural' => 'Vrok\I18n\View\Helper\TranslatePlural',

            'formDecorator'        => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'formDurationSelect'   => 'Vrok\Form\View\Helper\FormDurationSelect',
            'formInterval'         => 'Vrok\Form\View\Helper\FormInterval',
        ],
        'factories' => [
            'Vrok\View\Helper\FlashMessenger' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\View\Helper\HighlightText'  => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\View\Helper\TexEscape'      => 'Zend\ServiceManager\Factory\InvokableFactory',

            'Vrok\I18n\View\Helper\CurrencyFormat'  => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\I18n\View\Helper\DurationFormat'  => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\I18n\View\Helper\NumberFormat'    => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\I18n\View\Helper\TranslatePlural' => 'Zend\ServiceManager\Factory\InvokableFactory',

            'Vrok\Form\View\Helper\FormDecorator'        => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\Form\View\Helper\FormElementDecorator' => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\Form\View\Helper\FormDurationSelect'   => 'Zend\ServiceManager\Factory\InvokableFactory',
            'Vrok\Form\View\Helper\FormInterval'         => 'Zend\ServiceManager\Factory\InvokableFactory',
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_manager">
    'view_manager' => [
        'template_path_stack' => [
            __DIR__.'/../view',
        ],
    ],
// </editor-fold>
];
