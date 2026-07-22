<?php

declare(strict_types=1);

use KarimAshraf\LaraArchitect\Generation\Generators\ActionsGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\ControllerGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\DtoGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\EnumGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\FactoryGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\FilterGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\MigrationGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\ModelGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\RepositoryGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\RequestsGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\ResourceGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\ServiceGenerator;
use KarimAshraf\LaraArchitect\Generation\Generators\ViewsGenerator;

return [

    /*
    |--------------------------------------------------------------------------
    | Code Generation
    |--------------------------------------------------------------------------
    |
    | Everything the `make:module` command produces is driven by this section.
    | An "architecture" is simply a named list of patterns; a "pattern" is a
    | generator class. Add your own generator class and architecture preset
    | here and it becomes available to the command instantly.
    |
    */

    'generation' => [

        'default_architecture' => env('LARA_ARCHITECT_ARCHITECTURE', 'service-repository'),

        /*
        | Default presentation layer for make:module:
        | - api: JsonResource + controller under Http\Controllers\Api
        | - web: Blade views + controller under Http\Controllers
        | Override per command with --ui=api|web.
        */
        'default_ui' => env('LARA_ARCHITECT_UI', 'api'),

        /*
        | Architecture presets. Each preset maps to the list of patterns that
        | will be generated for a module. Feel free to add your own preset,
        | e.g. 'cqrs' => ['model', 'migration', 'query', 'command', ...].
        | The --ui option swaps "resource" ↔ "views" automatically.
        */
        'architectures' => [
            'service-repository' => [
                'model', 'migration', 'factory', 'enum', 'repository', 'service',
                'filter', 'requests', 'resource', 'controller',
            ],
            'actions' => [
                'model', 'migration', 'factory', 'enum', 'dto', 'actions',
                'filter', 'requests', 'resource', 'controller',
            ],
            'lean' => [
                'model', 'migration', 'requests', 'resource', 'controller',
            ],
        ],

        /*
        | Pattern registry. Every entry must implement
        | KarimAshraf\LaraArchitect\Contracts\Generator. Register your own
        | generators here to extend the toolkit with new patterns.
        */
        'generators' => [
            'model' => ModelGenerator::class,
            'migration' => MigrationGenerator::class,
            'factory' => FactoryGenerator::class,
            'enum' => EnumGenerator::class,
            'repository' => RepositoryGenerator::class,
            'service' => ServiceGenerator::class,
            'dto' => DtoGenerator::class,
            'actions' => ActionsGenerator::class,
            'filter' => FilterGenerator::class,
            'requests' => RequestsGenerator::class,
            'resource' => ResourceGenerator::class,
            'views' => ViewsGenerator::class,
            'controller' => ControllerGenerator::class,
        ],

        /*
        | Target namespaces per generated class type. Paths are derived from
        | these namespaces (App\ => app/, Database\ => database/).
        | "controller_api" is used when --ui=api (falls back to controller\Api).
        */
        'namespaces' => [
            'model' => 'App\\Models',
            'repository' => 'App\\Repositories',
            'service' => 'App\\Services',
            'action' => 'App\\Actions',
            'dto' => 'App\\DTOs',
            'enum' => 'App\\Enums',
            'filter' => 'App\\Http\\Filters',
            'controller' => 'App\\Http\\Controllers',
            'controller_api' => 'App\\Http\\Controllers\\Api',
            'request' => 'App\\Http\\Requests',
            'resource' => 'App\\Http\\Resources',
            'factory' => 'Database\\Factories',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enums
    |--------------------------------------------------------------------------
    |
    | Locales for which lang/{locale}/enums.php translation maps are generated
    | alongside enum classes. Labels resolve via EnumHelpers::label().
    |
    */

    'enums' => [
        'locales' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('LARA_ARCHITECT_ENUM_LOCALES', 'en,ar')),
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Generated Models
    |--------------------------------------------------------------------------
    */

    'models' => [
        // Adds a unique `uuid` column, the HasUuid trait and a resource field.
        'uuids' => true,

        // Adds soft deletes to generated models and migrations.
        'soft_deletes' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Runtime Behaviour
    |--------------------------------------------------------------------------
    */

    'services' => [
        // Wrap write operations of ArchitectService in a database transaction.
        'transactions' => true,
    ],

    'actions' => [
        // Wrap ArchitectAction::execute() in a database transaction by default.
        'transactions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Response Envelope
    |--------------------------------------------------------------------------
    |
    | Keys used by the RespondsWithJson trait and ArchitectFormRequest when
    | building JSON responses, so the envelope matches your API style.
    |
    */

    'responses' => [
        'keys' => [
            'status' => 'status',
            'message' => 'message',
            'data' => 'data',
            'errors' => 'errors',
        ],
    ],
];
