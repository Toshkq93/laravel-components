<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | These paths by component's show where the files will be created
    |
    */

    'paths' => [
        'repository' => app_path('Repositories'),
        'output' => app_path('DTO/Output'),
        'input' => app_path('DTO/Input'),
        'service' => app_path('Services'),
        'controller' => app_path('Http/Controllers/API'),
        'request' => app_path('Http/Requests'),
        'resource' => app_path('Http/Resources'),
        'interface' => [
            'repository' => app_path('Repositories/Interfaces'),
            'service' => app_path('Services/Interfaces'),
            'dto' => [
                'input' => app_path('DTO/Input/Interfaces'),
                'output' => app_path('DTO/Output/Interfaces')
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | ignore properties
    |--------------------------------------------------------------------------
    |
    | This properties ignored, when generate dto, requests and resources
    |
    */

    'ignore_properties' => [
        'updated_at',
        'deleted_at'
    ],

    /*
    |--------------------------------------------------------------------------
    | prefixes
    |--------------------------------------------------------------------------
    |
    | Prefixes by class name
    |
    */

    'prefix' => [
        'service' => 'Service',
        'repository' => 'Repository',
        'request' => 'Request',
        'controller' => 'Controller',
        'resource' => [
            'resource' => 'Resource',
            'collection' => 'Collection'
        ],
        'interface' => 'Interface',
        'dto' => [
            'base' => 'DTO',
            'input' => 'InputDTO',
            'output' => 'OutputDTO'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Base file
    |--------------------------------------------------------------------------
    |
    | This base file name by DTO, Service, Repository, Controller
    |
    */

    'base_name' => 'Base',

    /*
    |--------------------------------------------------------------------------
    | path to route file
    |--------------------------------------------------------------------------
    |
    | This path to route file where generate controller
    |
    */
    'route_path' => base_path('routes/v1/api.php'),
];
