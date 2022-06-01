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
        'output' => app_path('DTO\Output'),
        'input' => app_path('DTO\Input'),
        'service' => app_path('Services'),
        'controller' => app_path('Http\Controllers\API'),
        'request' => app_path('Http\Requests'),
        'resource' => app_path('Http\Resources'),
        'rootPaths' => [
            'repository' => app_path('Repositories\Interfaces'),
            'service' => app_path('Services\Interfaces'),
            'dto' => [
                'input' => app_path('DTO\Input\Interfaces'),
                'output' => app_path('DTO\Output\Interfaces')
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
    | Namespaces
    |--------------------------------------------------------------------------
    |
    | This namespaces by component's
    |
    */

    'namespaces' => [
        'repository' => 'App\Repositories',
        'output' => 'App\DTO\Output',
        'input' => 'App\DTO\Input',
        'service' => 'App\Services',
        'controller' => 'App\Http\Controllers\API',
        'request' => 'App\Http\Requests',
        'resource' => 'App\Http\Resources',
        'interface' => [
            'repository' => 'App\Repositories\Interfaces',
            'service' => 'App\Services\Interfaces',
            'dto' => [
                'input' => 'App\DTO\Input\Interfaces',
                'output' => 'App\DTO\Output\Interfaces'
            ]
        ],
        'base' => [
            'dto' => 'App\DTO',
            'controller' => 'App\Http\Controllers\API',
            'repository' => 'App\Repositories',
            'service' => 'App\Services'
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Base files
    |--------------------------------------------------------------------------
    |
    | This base file names
    |
    */

    'baseFile' => [
        'dto' => 'BaseDTO',
        'controller' => 'BaseController',
        'repository' => 'BaseRepository',
        'service' => 'BaseService'
    ],

    /*
    |--------------------------------------------------------------------------
    | path to route file
    |--------------------------------------------------------------------------
    |
    | This path to route file where generate controller
    |
    */
    'route_path' => base_path('routes\v1\api.php'),
];
