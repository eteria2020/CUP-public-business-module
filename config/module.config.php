<?php

namespace CUPPublicBusinessModule;

$translator = new \Zend\I18n\Translator\Translator();
return [
    'console' => [
        'router' => [
            'routes' => [
                'check-groups-limits' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'business check groups limits',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleGroups',
                            'action' => 'check-groups-limits'
                        ]
                    ]
                ],
                'business-pay-invoice' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'business pay invoice',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'Console',
                            'action' => 'business-pay-invoice'
                        ]
                    ]
                ],
                'make-business-pay' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'make business pay <businessCode>',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'Console',
                            'action' => 'make-business-pay'
                        ]
                    ]
                ],
                'generate-business-invoices' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'generate business invoices <businessCode>',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'Console',
                            'action' => 'generate-business-invoices'
                        ]
                    ]
                ],
                //overwrite routes
                'account-compute' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'account compute [--dry-run|-d]',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleAccountCompute',
                            'action' => 'account-compute'
                        ]
                    ]
                ],
                'account-trips' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'account trips [--dry-run|-d]',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleAccountCompute',
                            'action' => 'account-trips'
                        ]
                    ]
                ],
                'account-trip' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'account trip <tripId> [--dry-run|-d]',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleAccountCompute',
                            'action' => 'account-trip'
                        ]
                    ]
                ],
                'compute-trips-cost' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'compute trips cost [--dry-run|-d]',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleAccountCompute',
                            'action' => 'compute-trips-cost'
                        ]
                    ]
                ],
                'compute-trip-cost' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'compute trip cost <tripId> [--dry-run|-d]',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsoleAccountCompute',
                            'action' => 'compute-trip-cost'
                        ]
                    ]
                ],
//                'export-registries' => [
//                    'type' => 'simple',
//                    'options' => [
//                        'route' => 'export registries [--dry-run|-d] [--no-customers|-c] [--no-businesses|-b] [--no-invoices|-i] [--all|-a] [--no-ftp|-f] [--test-name|-t] [--date=] [--fleet=]',
//                        'defaults' => [
//                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
//                            'controller' => 'ExportRegistries',
//                            'action' => 'export-registries'
//                        ]
//                    ]
//                ],
            ]
        ],
    ],
    'router' => [
        'router_class' => 'Zend\Mvc\Router\Http\TranslatorAwareTreeRouteStack',
        'routes' => [
            'area-utente' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/{area-utente}',
                    'defaults' => [
                        '__NAMESPACE__' => 'Application\Controller',
                        'action' => 'index',
                        'controller' => 'UserArea',
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'associate' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/{associa}[/:mobile]',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessAssociation',
                                'action' => 'business-association',
                            ],
                            'constraints' => [
                                'mobile' => 'mobile'
                            ]
                        ],
                    ],
                    //overwrite default routes
                    'pin' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/{pin}[/:mobile]',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessUserArea',
                                'action' => 'pin',
                            ],
                            'constraints' => [
                                'mobile' => 'mobile'
                            ]
                        ]
                    ],
                    'rents' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/{corse}[/:mobile]',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessUserArea',
                                'action' => 'rents',
                            ],
                            'constraints' => [
                                'mobile' => 'mobile'
                            ]
                        ]
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'CUPPublicBusinessModule\Controller\BusinessAssociation' => 'CUPPublicBusinessModule\Controller\BusinessAssociationControllerFactory',
            'CUPPublicBusinessModule\Controller\BusinessUserArea' => 'CUPPublicBusinessModule\Controller\BusinessUserAreaControllerFactory',
            'CUPPublicBusinessModule\Controller\Console' => 'CUPPublicBusinessModule\Controller\ConsoleControllerFactory',
            'CUPPublicBusinessModule\Controller\ConsoleAccountCompute' => 'CUPPublicBusinessModule\Controller\ConsoleAccountComputeControllerFactory',
            'CUPPublicBusinessModule\Controller\ConsoleGroups' => 'CUPPublicBusinessModule\Controller\ConsoleGroupsControllerFactory',
            'CUPPublicBusinessModule\Controller\ExportRegistries' => 'CUPPublicBusinessModule\Controller\ExportRegistriesControllerFactory'
        ]
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'service_manager' => [
        'invokables' => [
            'CUPPublicBusinessModule\Form\AssociationCodeForm' => 'CUPPublicBusinessModule\Form\AssociationCodeForm'
        ],
        'factories' => [
            'CUPPublicBusinessModule\Listener\NewEmployeeAssociatedListener' => 'CUPPublicBusinessModule\Listener\NewEmployeeAssociatedListenerFactory',
            'CUPPublicBusinessModule\Service\EmployeeService' => 'CUPPublicBusinessModule\Service\EmployeeServiceFactory',
            'CUPPublicBusinessModule\Service\BusinessTripCostService' => 'CUPPublicBusinessModule\Service\BusinessTripCostServiceFactory',
            'CUPPublicBusinessModule\Service\AccountBusinessTripsService' => 'CUPPublicBusinessModule\Service\AccountBusinessTripsServiceFactory'
        ],
    ],
    'bjyauthorize' => [
        'guards' => [
            'BjyAuthorize\Guard\Controller' => [
                ['controller' =>  'CUPPublicBusinessModule\Controller\BusinessAssociation', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\Console', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\ConsoleGroups', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\ConsoleAccountCompute', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\BusinessUserArea', 'roles' => ['user']],
                ['controller' =>  'CUPPublicBusinessModule\Controller\ExportRegistries', 'roles' => []],
            ],
        ],
    ],
    'navigation' => [
        'default' => [
            [
                'label' => $translator->translate("Azienda"),
                'route' => 'area-utente/associate',
                'icon' => 'fa fa-briefcase',
            ]
        ]
    ],
    'asset_manager' => [
        'resolver_configs' => [
            'paths' => [
                __DIR__ . '/../public/',
            ],
        ],
    ],
];
