<?php
/**
 * Vrok-Lib config
 */
return array(
// <editor-fold defaultstate="collapsed" desc="asset_manager">
    'asset_manager' => array(
        'resolvers' => array(
            'Vrok\Asset\ViewScriptResolver' => 3001,
        ),
        'resolver_configs' => array(
            'paths' => array(
                __DIR__ . '/../public',
            ),
            'map' => array(
                        ),
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
        'Vrok\Service\UserManager',
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
// <editor-fold defaultstate="collapsed" desc="service_manager">
    'service_manager' => array(

        // classes that have no dependencies or are ServiceLocatorAware
        'invokables' => array(
            'Vrok\Authentication\Adapter\Doctrine' => 'Vrok\Authentication\Adapter\Doctrine',
            'Vrok\Authentication\Storage\Doctrine' => 'Vrok\Authentication\Storage\Doctrine',
            'Vrok\Client\Info' => 'Vrok\Client\Info',
            'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver' => 'Vrok\Doctrine\ORM\Mapping\EntityListenerResolver',
            'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy' => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
        ),

        'factories' => array(
            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
            'Vrok\Service\FieldHistory' => 'Vrok\Service\FieldHistoryServiceFactory',
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="slm_queue">
    'slm_queue' => array(
        'job_manager' => array(
            'invokables' => array(
                'Vrok\SlmQueue\Job\CheckTodos' => 'Vrok\SlmQueue\Job\CheckTodos',
                'Vrok\SlmQueue\Job\ExitWorker' => 'Vrok\SlmQueue\Job\ExitWorker',
                'Vrok\SlmQueue\Job\PurgeValidations' => 'Vrok\SlmQueue\Job\PurgeValidations',
            ),
        ),
    ),
// </editor-fold>
// <editor-fold defaultstate="collapsed" desc="view_helpers">
    'view_helpers' => array(
        'invokables' => array(
            'flashMessenger' => 'Vrok\View\Helper\FlashMessenger',

            'currencyFormat' => '\Vrok\I18n\View\Helper\CurrencyFormat',
            'durationFormat' => '\Vrok\I18n\View\Helper\DurationFormat',
            'numberFormat' => '\Vrok\I18n\View\Helper\NumberFormat',
            'translatePlural' => '\Vrok\I18n\View\Helper\TranslatePlural',

            'formDecorator' => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'formDurationSelect' => 'Vrok\Form\View\Helper\FormDurationSelect',
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
