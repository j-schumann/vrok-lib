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
        'invokables' => array(
            'ClientInfo' => 'Vrok\Client\Info',
            'Vrok\Service\Email' => 'Vrok\Service\Email',
        ),
        'factories' => array(
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
