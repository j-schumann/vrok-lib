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

    'service_manager' => array(
        // add some short names that hopefully don't conflict
        'aliases' => array(
            'ClientInfo'        => 'Vrok\Client\Info',
            'OwnerService'      => 'Vrok\Owner\OwnerService',
            'UserManager'       => 'Vrok\User\Manager',
            'ValidationManager' => 'Vrok\Validation\Manager',
        ),
        // classes that have no dependencies or are ServiceLocatorAware
        'invokables' => array(
            'Vrok\Authentication\Adapter\Doctrine' => 'Vrok\Authentication\Adapter\Doctrine',
            'Vrok\Client\Info'                     => 'Vrok\Client\Info',
            'Vrok\Service\Email'                   => 'Vrok\Service\Email',
        ),
        'factories' => array(
            'Vrok\Owner\OwnerService' => function($sm) {
                $config = $sm->get('Config');
                $service = new \Vrok\Owner\OwnerService();
                if (!empty($config['owner_service']['allowed_owners'])) {
                    $allowedOwners = $config['owner_service']['allowed_owners'];
                    $service->setAllowedOwners($allowedOwners);
                }
                return $service;
            },
            'Vrok\User\Manager' => function($sm) {
                $config = $sm->get('Config');
                $manager = new \Vrok\User\Manager();
                if (!empty($config['user_manager'])) {
                    $manager->setConfig($config['user_manager']);
                }
                return $manager;
            },
            'Vrok\Validation\Manager' => function($sm) {
                $config = $sm->get('Config');
                $manager = new \Vrok\Validation\Manager();
                if (!empty($config['validation_manager']['timeouts'])) {
                    $manager->setTimeouts($config['validation_manager']['timeouts']);
                }
                return $manager;
            },
            // replace the default translator with our custom implementation
            'Zend\I18n\Translator\TranslatorInterface'
                => 'Vrok\I18n\Translator\TranslatorServiceFactory',
        ),
    ),

    'view_helpers' => array(
        'invokables' => array(
            'alternativeUrl'       => 'Vrok\View\Helper\AlternativeUrl',
            'formDecorator'        => 'Vrok\Form\View\Helper\FormDecorator',
            'formElementDecorator' => 'Vrok\Form\View\Helper\FormElementDecorator',
            'formMultiText'        => 'Vrok\Form\View\Helper\FormMultiText',
            'fullUrl'              => 'Vrok\View\Helper\FullUrl',
            'flashMessenger'       => 'Vrok\View\Helper\FlashMessenger',
            'translatePlural'      => '\Vrok\I18n\Translator\View\Helper\TranslatePlural',
        ),
    ),
);
