<?php
return [
    'service_manager' => [
        'factories' => [
            \Task\V1\Auth\TokenAuthenticationAdapter::class => \Task\V1\Auth\TokenAuthenticationAdapterFactory::class,
            \Laminas\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener::class => \Task\V1\Auth\AuthorizationPostListenerFactory::class,
            \Task\V1\Auth\AuthorizationPostListener::class => \Task\V1\Auth\AuthorizationPostListenerFactory::class,
        ],
        'delegators' => [
            \Laminas\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener::class => [
                \Task\V1\Auth\AttachTokenAdapterDelegatorFactory::class,
            ],
        ],
    ],
    'router' => [
        'routes' => [
            'task.rest.tasks' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/tasks[/:tasks_id]',
                    'defaults' => [
                        'controller' => 'Task\\V1\\Rest\\Tasks\\Controller',
                    ],
                ],
            ],
            'task.rest.users' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/users[/:users_id]',
                    'defaults' => [
                        'controller' => 'Task\\V1\\Rest\\Users\\Controller',
                    ],
                ],
            ],
            'task.rpc.login' => [
                'type' => 'Segment',
                'options' => [
                    'route' => '/login',
                    'defaults' => [
                        'controller' => 'Task\\V1\\Rpc\\Login\\Controller',
                        'action' => 'login',
                    ],
                ],
            ],
        ],
    ],
    'api-tools-versioning' => [
        'uri' => [
            0 => 'task.rest.tasks',
            1 => 'task.rest.users',
            2 => 'task.rpc.login',
        ],
    ],
    'api-tools-rest' => [
        'Task\\V1\\Rest\\Tasks\\Controller' => [
            'listener' => 'Task\\V1\\Rest\\Tasks\\TasksResource',
            'route_name' => 'task.rest.tasks',
            'route_identifier_name' => 'tasks_id',
            'collection_name' => 'tasks',
            'entity_http_methods' => [
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ],
            'collection_http_methods' => [
                0 => 'GET',
                1 => 'POST',
            ],
            'collection_query_whitelist' => [],
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => \Task\V1\Rest\Tasks\TasksEntity::class,
            'collection_class' => \Task\V1\Rest\Tasks\TasksCollection::class,
            'service_name' => 'tasks',
        ],
        'Task\\V1\\Rest\\Users\\Controller' => [
            'listener' => 'Task\\V1\\Rest\\Users\\UsersResource',
            'route_name' => 'task.rest.users',
            'route_identifier_name' => 'users_id',
            'collection_name' => 'users',
            'entity_http_methods' => [
                0 => 'GET',
                1 => 'PATCH',
                2 => 'PUT',
                3 => 'DELETE',
            ],
            'collection_http_methods' => [
                0 => 'GET',
                1 => 'POST',
            ],
            'collection_query_whitelist' => [],
            'page_size' => 25,
            'page_size_param' => null,
            'entity_class' => \Task\V1\Rest\Users\UsersEntity::class,
            'collection_class' => \Task\V1\Rest\Users\UsersCollection::class,
            'service_name' => 'users',
        ],
    ],
    'api-tools-content-negotiation' => [
        'controllers' => [
            'Task\\V1\\Rest\\Tasks\\Controller' => 'HalJson',
            'Task\\V1\\Rest\\Users\\Controller' => 'HalJson',
            'Task\\V1\\Rpc\\Login\\Controller' => 'Json',
        ],
        'accept_whitelist' => [
            'Task\\V1\\Rest\\Tasks\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
            'Task\\V1\\Rest\\Users\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/hal+json',
                2 => 'application/json',
            ],
            'Task\\V1\\Rpc\\Login\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/json',
                2 => 'application/*+json',
            ],
        ],
        'content_type_whitelist' => [
            'Task\\V1\\Rest\\Tasks\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/json',
            ],
            'Task\\V1\\Rest\\Users\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/json',
            ],
            'Task\\V1\\Rpc\\Login\\Controller' => [
                0 => 'application/vnd.task.v1+json',
                1 => 'application/json',
            ],
        ],
    ],
    'api-tools-hal' => [
        'metadata_map' => [
            \Task\V1\Rest\Tasks\TasksEntity::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.tasks',
                'route_identifier_name' => 'tasks_id',
                'hydrator' => \Laminas\Hydrator\ArraySerializableHydrator::class,
            ],
            \Task\V1\Rest\Tasks\TasksCollection::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.tasks',
                'route_identifier_name' => 'tasks_id',
                'is_collection' => true,
            ],
            \Task\V1\Rest\Users\UsersEntity::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.users',
                'route_identifier_name' => 'users_id',
                'hydrator' => \Laminas\Hydrator\ArraySerializableHydrator::class,
            ],
            \Task\V1\Rest\Users\UsersCollection::class => [
                'entity_identifier_name' => 'id',
                'route_name' => 'task.rest.users',
                'route_identifier_name' => 'users_id',
                'is_collection' => true,
            ],
        ],
    ],
    'api-tools' => [
        'db-connected' => [
            'Task\\V1\\Rest\\Tasks\\TasksResource' => [
                'adapter_name' => 'MySqlAdapter',
                'table_name' => 'tasks',
                'hydrator_name' => \Laminas\Hydrator\ArraySerializableHydrator::class,
                // Didático (Dia 6): usamos um Resource customizado para:
                // - pegar o user_id a partir do token (não do payload do cliente)
                // - preencher created_at no servidor
                // - garantir que o CRUD de /tasks só enxergue as tasks do usuário logado
                'resource_class' => \Task\V1\Rest\Tasks\TasksResource::class,
                'controller_service_name' => 'Task\\V1\\Rest\\Tasks\\Controller',
                'entity_identifier_name' => 'id',
            ],
            'Task\\V1\\Rest\\Users\\UsersResource' => [
                'adapter_name' => 'MySqlAdapter',
                'table_name' => 'users',
                'hydrator_name' => \Laminas\Hydrator\ArraySerializableHydrator::class,
                'controller_service_name' => 'Task\\V1\\Rest\\Users\\Controller',
                'entity_identifier_name' => 'id',
            ],
        ],
    ],
    'api-tools-content-validation' => [
        'Task\\V1\\Rest\\Tasks\\Controller' => [
            'input_filter' => 'Task\\V1\\Rest\\Tasks\\Validator',
        ],
        'Task\\V1\\Rest\\Users\\Controller' => [
            'input_filter' => 'Task\\V1\\Rest\\Users\\Validator',
        ],
    ],
    'input_filter_specs' => [
        'Task\\V1\\Rest\\Tasks\\Validator' => [
            0 => [
                'name' => 'user_id',
                // Dia 6: o cliente NÃO envia user_id.
                // Quem define user_id é o backend, com base no token (AuthN).
                'required' => false,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\Digits::class,
                    ],
                ],
                // Sem validators aqui: o server injeta user_id e o próprio FK do banco
                // garante integridade (e evita o cliente tentar “se passar por outro user”).
                'validators' => [],
            ],
            1 => [
                'name' => 'title',
                'required' => true,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StringTrim::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                ],
                'validators' => [
                    0 => [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 150,
                        ],
                    ],
                ],
            ],
            2 => [
                'name' => 'description',
                'required' => false,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StringTrim::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                ],
                'validators' => [
                    0 => [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 65535,
                        ],
                    ],
                ],
            ],
            3 => [
                'name' => 'status',
                'required' => true,
                'filters' => [],
                'validators' => [],
            ],
            4 => [
                'name' => 'created_at',
                // Dia 6: created_at é responsabilidade do servidor (não do cliente).
                // O server coloca um valor; se o banco já tiver DEFAULT, melhor ainda.
                'required' => false,
                'filters' => [],
                'validators' => [],
            ],
        ],
        'Task\\V1\\Rest\\Users\\Validator' => [
            0 => [
                'name' => 'name',
                'required' => true,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StringTrim::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                ],
                'validators' => [
                    0 => [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 100,
                        ],
                    ],
                ],
            ],
            1 => [
                'name' => 'email',
                'required' => true,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StringTrim::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                ],
                'validators' => [
                    0 => [
                        'name' => 'Laminas\\ApiTools\\ContentValidation\\Validator\\DbNoRecordExists',
                        'options' => [
                            'adapter' => 'MySqlAdapter',
                            'table' => 'users',
                            'field' => 'email',
                        ],
                    ],
                    1 => [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 150,
                        ],
                    ],
                ],
            ],
            2 => [
                'name' => 'password',
                'required' => true,
                'filters' => [
                    0 => [
                        'name' => \Laminas\Filter\StringTrim::class,
                    ],
                    1 => [
                        'name' => \Laminas\Filter\StripTags::class,
                    ],
                ],
                'validators' => [
                    0 => [
                        'name' => \Laminas\Validator\StringLength::class,
                        'options' => [
                            'min' => 1,
                            'max' => 255,
                        ],
                    ],
                ],
            ],
            3 => [
                'name' => 'created_at',
                'required' => false,
                'filters' => [],
                'validators' => [],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            'Task\\V1\\Rpc\\Login\\Controller' => \Task\V1\Rpc\Login\LoginControllerFactory::class,
        ],
    ],
    'api-tools-rpc' => [
        'Task\\V1\\Rpc\\Login\\Controller' => [
            'service_name' => 'login',
            'http_methods' => [
                0 => 'POST',
            ],
            'route_name' => 'task.rpc.login',
        ],
    ],
    'api-tools-mvc-auth' => [
        'authentication' => [
            'map' => [
                'Task\\V1' => 'token',
            ],
            'types' => [
                'token',
            ],
        ],
        'authorization' => [
            'Task\\V1\\Rpc\\Login\\Controller' => [
                'actions' => [
                    'login' => [
                        'GET' => false,
                        'POST' => false,
                        'PUT' => false,
                        'PATCH' => false,
                        'DELETE' => false,
                    ],
                ],
            ],
            'Task\\V1\\Rest\\Tasks\\Controller' => [
                'collection' => [
                    'GET' => true,
                    'POST' => true,
                    'PUT' => true,
                    'PATCH' => true,
                    'DELETE' => true,
                ],
                'entity' => [
                    'GET' => true,
                    'POST' => true,
                    'PUT' => true,
                    'PATCH' => true,
                    'DELETE' => true,
                ],
            ],
        ],
    ],
];
