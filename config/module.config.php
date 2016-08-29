<?php

namespace CUPPublicBusinessModule;

$translator = new \Zend\I18n\Translator\Translator();
return [
    'console' => [
        'router' => [
            'routes' => [
                'make-business-pay' => [
                    'type' => 'simple',
                    'options' => [
                        'route' => 'make business pay <businessCode>',
                        'defaults' => [
                            '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                            'controller' => 'ConsolePayments',
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
                            'controller' => 'ConsolePayments',
                            'action' => 'generate-business-invoices'
                        ]
                    ]
                ]
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
                            'route' => '/{associa}',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessAssociation',
                                'action' => 'business-association',
                            ],
                        ],
                    ],
                    //overwrite default routes
                    'pin' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/{pin}',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessUserArea',
                                'action' => 'pin',
                            ]
                        ]
                    ],
                    'rents' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/{corse}',
                            'defaults' => [
                                '__NAMESPACE__' => 'CUPPublicBusinessModule\Controller',
                                'controller' => 'BusinessUserArea',
                                'action' => 'rents',
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
            'CUPPublicBusinessModule\Controller\ConsolePayments' => 'CUPPublicBusinessModule\Controller\ConsolePaymentsControllerFactory'
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
            'CUPPublicBusinessModule\Service\EmployeeService' => 'CUPPublicBusinessModule\Service\EmployeeServiceFactory'
        ],
    ],
    'bjyauthorize' => [
        'guards' => [
            'BjyAuthorize\Guard\Controller' => [
                ['controller' =>  'CUPPublicBusinessModule\Controller\BusinessAssociation', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\ConsolePayments', 'roles' => []],
                ['controller' =>  'CUPPublicBusinessModule\Controller\BusinessUserArea', 'roles' => ['user']],
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
