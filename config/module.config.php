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
        'invokables' => [
            'translate' => 'Vrok\Mvc\Controller\Plugin\Translate',
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="doctrine">
    'doctrine' => [
        'configuration' => [
            'orm_default' => [
                'entity_listener_resolver' => 'Vrok\Doctrine\Orm\Mapping\EntityListenerResolver',
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
        // classes that have no dependencies
        'invokables' => [
            'Vrok\Client\Info' => 'Vrok\Client\Info',
        ],

        'factories' => [
            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
            'Vrok\Service\FieldHistory' => 'Vrok\Service\FieldHistoryServiceFactory',
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="slm_queue">
    'slm_queue' => [
        'job_manager' => [
            'invokables' => [
                'Vrok\SlmQueue\Job\CheckTodos'       => 'Vrok\SlmQueue\Job\CheckTodos',
                'Vrok\SlmQueue\Job\ExitWorker'       => 'Vrok\SlmQueue\Job\ExitWorker',
                'Vrok\SlmQueue\Job\PurgeValidations' => 'Vrok\SlmQueue\Job\PurgeValidations',
            ],
        ],
    ],
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_helpers">
    'view_helpers' => [
        'invokables' => [
            'flashMessenger' => 'Vrok\View\Helper\FlashMessenger',

            'currencyFormat'  => '\Vrok\I18n\View\Helper\CurrencyFormat',
            'durationFormat'  => '\Vrok\I18n\View\Helper\DurationFormat',
            'numberFormat'    => '\Vrok\I18n\View\Helper\NumberFormat',
            'translatePlural' => '\Vrok\I18n\View\Helper\TranslatePlural',

            'formDecorator'        => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'formDurationSelect'   => 'Vrok\Form\View\Helper\FormDurationSelect',
            'formInterval'         => 'Vrok\Form\View\Helper\FormInterval',
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
