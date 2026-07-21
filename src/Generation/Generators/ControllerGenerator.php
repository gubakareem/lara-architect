<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates the API controller. The controller style follows the module's
 * patterns: it delegates to a service when one is generated, to actions when
 * those are generated, and falls back to plain Eloquent otherwise.
 */
class ControllerGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        if (! $blueprint->hasPattern('resource')) {
            throw new InvalidArgumentException(
                'The [controller] pattern requires the [resource] pattern. Add "resource" to the pattern list.',
            );
        }

        $stub = match (true) {
            $blueprint->hasPattern('service') => 'controllers/service',
            $blueprint->hasPattern('actions') => 'controllers/actions',
            default => 'controllers/plain',
        };

        $requests = $this->requestReplacements($blueprint);

        $contents = $this->stubs->render($stub, [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('controller'),
            ...$requests,
            ...$this->collaboratorReplacements($blueprint, $requests),
            ...$this->indexReplacements($blueprint),
            'resourceClass' => $blueprint->namespaceFor('resource').'\\'.$blueprint->model().'Resource',
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('controller'),
                $blueprint->model().'Controller',
                $contents,
                'Controller',
            ),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function requestReplacements(ModuleBlueprint $blueprint): array
    {
        $model = $blueprint->model();

        if (! $blueprint->hasPattern('requests')) {
            // No form requests generated: accept the base request and pass everything through.
            return [
                'requestImports' => "use Illuminate\\Http\\Request;\n",
                'storeRequest' => 'Request',
                'updateRequest' => 'Request',
                'storeData' => '$request->all()',
                'updateData' => '$request->all()',
            ];
        }

        $namespace = $blueprint->namespaceFor('request').'\\'.$blueprint->pluralModel();

        return [
            'requestImports' => sprintf(
                "use %s\\Store%sRequest;\nuse %s\\Update%sRequest;\n",
                $namespace, $model, $namespace, $model,
            ),
            'storeRequest' => 'Store'.$model.'Request',
            'updateRequest' => 'Update'.$model.'Request',
            'storeData' => '$request->validated()',
            'updateData' => '$request->validated()',
        ];
    }

    /**
     * The index() signature and query change when the module has a filter:
     * the generated QueryFilter is injected and applied to the listing.
     *
     * @return array<string, string>
     */
    private function indexReplacements(ModuleBlueprint $blueprint): array
    {
        $usesService = $blueprint->hasPattern('service');
        $perPage = "(int) request()->integer('per_page', 15)";

        if (! $blueprint->hasPattern('filter')) {
            return [
                'filterImport' => '',
                'indexSignature' => '',
                'indexQuery' => $usesService
                    ? sprintf('$this->%sService->paginate(%s)', $blueprint->modelVariable(), $perPage)
                    : sprintf('%s::latest()->paginate(%s)', $blueprint->model(), $perPage),
            ];
        }

        $filterClass = $blueprint->model().'Filter';

        return [
            'filterImport' => 'use '.$blueprint->namespaceFor('filter').'\\'.$filterClass.";\n",
            'indexSignature' => $filterClass.' $filter',
            'indexQuery' => $usesService
                ? sprintf('$this->%sService->filter($filter, %s)', $blueprint->modelVariable(), $perPage)
                : sprintf('%s::filter($filter)->latest()->paginate(%s)', $blueprint->model(), $perPage),
        ];
    }

    /**
     * @param  array<string, string>  $requests
     * @return array<string, string>
     */
    private function collaboratorReplacements(ModuleBlueprint $blueprint, array $requests): array
    {
        $model = $blueprint->model();

        if ($blueprint->hasPattern('service')) {
            return [
                'serviceClass' => $blueprint->namespaceFor('service').'\\'.$model.'Service',
            ];
        }

        if ($blueprint->hasPattern('actions')) {
            $actionNamespace = $blueprint->namespaceFor('action').'\\'.$blueprint->pluralModel();
            $usesDto = $blueprint->hasPattern('dto');
            $dtoClass = $blueprint->namespaceFor('dto').'\\'.$model.'Data';

            return [
                'actionImports' => sprintf(
                    "use %s\\Create%s;\nuse %s\\Delete%s;\nuse %s\\Update%s;\n%s",
                    $actionNamespace, $model,
                    $actionNamespace, $model,
                    $actionNamespace, $model,
                    $usesDto ? "use {$dtoClass};\n" : '',
                ),
                'storeArgument' => $usesDto ? $model.'Data::fromRequest($request)' : $requests['storeData'],
                'updateArgument' => $usesDto ? $model.'Data::fromRequest($request)' : $requests['updateData'],
            ];
        }

        return [];
    }
}
