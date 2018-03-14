<?php
return [
    'doctrine' => [
        'connection' => [
            'orm_default' => [
                'driverClass' => 'Doctrine\DBAL\Driver\PDOSqlite\Driver',
                'params'      => [
                    'memory' => true,
                ],
            ],
        ],
        'configuration' => [
            'orm_default' => [
            ],
        ],
        'driver' => [
            /*'ref_entities' => [
                'class' => 'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => [__DIR__.'/RefHelperTest/Entity'],
            ],*/
            'orm_default' => [
                'drivers' => [
                    //'RefHelperTest\Entity' => 'ref_entities',
                ],
            ],
        ],
    ],
    'modules' => [
        'Zend\Router',
        'DoctrineModule',
        'DoctrineORMModule',
        'SlmQueue',
        'Vrok',
    ],
    'module_listener_options' => [
        'config_glob_paths'    => [
            //'../../../config/autoload/{,*.}{global,local}.php',
            __DIR__.'/TestConfig.php',
            __DIR__.'/TestConfig.local.php',
        ],
        'module_paths' => [
            'module',
            'vendor',
        ],
    ],
    'router' => [
        'routes' => [
            'user' => [
                'type'    => 'Literal',
                'options' => [
                    'route'    => '/test-user/',
                    'defaults' => [
                        'controller' => 'Test\Controller\User',
                        'action'     => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes'  => [
                    'edit' => [
                        'type'    => 'segment',
                        'options' => [
                            'route'       => 'edit/[:id][/]',
                            'constraints' => [
                                'id' => '[0-9]+',
                            ],
                            'defaults' => [
                                'action' => 'edit',
                            ],
                        ],
                    ],
                    'search' => [
                        'type'    => 'Segment',
                        'options' => [
                            'route'    => 'search[/]',
                            'defaults' => [
                                'action' => 'search',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
