<?php

return [
    'paths' => [
        'repository' => app_path('Repositories'),
        'output' => app_path('DTO\\Output'),
        'input' => app_path('DTO\\Input'),
        'service' => app_path('Services'),
        'controller' => app_path('Http\\Controllers\\API'),
        'request' => app_path('Http\\Requests'),
        'resource' => app_path('Http\\Resources'),
        'rootPaths' => [
            'repository' => app_path('Contracts\\Repositories'),
            'service' => app_path('Contracts\\Services'),
            'dto' => app_path('DTO')
        ],
    ],
    'namespaces' => [
        'repository' => 'App\\Repositories',
        'output' => 'App\\DTO\\Output',
        'input' => 'App\\DTO\\Input',
        'service' => 'App\\Services',
        'controller' => 'App\\Http\\Controllers\\API',
        'request' => 'App\\Http\\Requests',
        'resource' => 'App\\Http\\Resources',
        'contracts' => [
            'repository' => 'App\\Contracts\\Repositories',
            'service' => 'App\\Contracts\\Services'
        ],
        'base' => [
            'dto' => 'App\\DTO',
            'controller' => 'App\\Http\\Controllers\\API'
        ]
    ],
    'baseFile' => [
        'dto' => 'BaseDTO',
        'controller' => 'BaseController'
    ]
];
