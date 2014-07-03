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
                'purge-validations' => array(
                    'options' => array(
                        'route' => 'purge-validations',
                        'defaults' => array(
                            'controller' => 'Vrok\Controller\Validation',
                            'action'     => 'purge',
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Vrok\Controller\Validation'  => 'Vrok\Controller\ValidationController',
        ),
    ),

    'controller_plugins' => array(
        'invokables' => array(
            'translate' => 'Vrok\Mvc\Controller\Plugin\Translate',
        ),
    ),

    'doctrine' => array(
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

    'owner_service' => array(
        'allowed_owners' => array(
            'Vrok\Entity\Validation' => array(
                'Vrok\Entity\User',
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
            'Vrok\Authentication\Adapter\Doctrine'         => 'Vrok\Authentication\Adapter\Doctrine',
            'Vrok\Client\Info'                             => 'Vrok\Client\Info',
            'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy' => 'Vrok\Mvc\View\Http\AuthorizeRedirectStrategy',
        ),

        'factories' => array(
            // replace the default translator with our custom implementation
            'Zend\I18n\Translator\TranslatorInterface'
                => 'Vrok\I18n\Translator\TranslatorServiceFactory',

            'Vrok\Service\ActionLogger' => 'Vrok\Service\ActionLoggerServiceFactory',
        ),
    ),

    'slm_queue' => array(
        'job_manager' => array(
            'invokables' => array(
                'Vrok\SlmQueue\Job\ExitWorker' => 'Vrok\SlmQueue\Job\ExitWorker',
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
);
