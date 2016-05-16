<?php

namespace CUPPublicBusinessModule;

return [
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
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'CUPPublicBusinessModule\Controller\BusinessAssociation' => 'CUPPublicBusinessModule\Controller\BusinessAssociationControllerFactory'
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
        ]
    ],
    'bjyauthorize' => [
        'guards' => [
            'BjyAuthorize\Guard\Controller' => [
                [
                    'controller' =>  'CUPPublicBusinessModule\Controller\BusinessAssociation', 'roles' => []
                ],
            ],
        ],
    ],
];
