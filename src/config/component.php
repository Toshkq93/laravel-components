<?php

return [
    'paths' => [
        'repository' => app_path('Repositories'),
        'dto' => app_path('DTO'),
        'filter' => app_path('Filters'),
        'service' => app_path('Services'),
        'controller' => app_path('Http\\Controllers\\API'),
        'request' => app_path('Http\\Requests'),
        'resource' => app_path('Http\\Resources'),
    ],
    'namespaces' => [
        'repository' => 'App\\Repositories',
        'dto' => 'App\\DTO',
        'filter' => 'App\\Filters',
        'service' => 'App\\Services',
        'controller' => 'App\\Http\\Controllers\\API',
        'request' => 'App\\Http\\Requests',
        'resource' => 'App\\Http\\Resources',
        'contracts' => [
            'repository' => 'App\\Contracts\\Repositories',
            'service' => 'App\\Contracts\\Services'
        ],
        'base' => [
            'dto' => 'App\\DTO\\BaseDTO'
        ]
    ],
    'rootPaths' => [
        'repository' => app_path('Contracts\\Repositories'),
        'service' => app_path('Contracts\\Services')
    ],
    'baseFile' => [
        'dto' => 'BaseDTO'
    ]
];
