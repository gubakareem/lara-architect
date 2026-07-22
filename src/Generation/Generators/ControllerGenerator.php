<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates the module controller. Style follows the collaborator patterns
 * (service / actions / plain) and the presentation layer (api → JsonResource
 * + RespondsWithJson; web → Blade views + redirects).
 */
class ControllerGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        if ($blueprint->isApi() && ! $blueprint->hasPattern('resource')) {
            throw new InvalidArgumentException(
                'API controllers require the [resource] pattern. Add "resource" or use --ui=web.',
            );
        }

        if ($blueprint->isWeb() && ! $blueprint->hasPattern('views')) {
            throw new InvalidArgumentException(
                'Web controllers require the [views] pattern. Add "views" or use --ui=api.',
            );
        }

        $stub = $this->resolveStub($blueprint);
        $requests = $this->requestReplacements($blueprint);

        $contents = $this->stubs->render($stub, [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('controller'),
            'viewPath' => $blueprint->viewPath(),
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

    private function resolveStub(ModuleBlueprint $blueprint): string
    {
        $style = match (true) {
            $blueprint->hasPattern('service') => 'service',
            $blueprint->hasPattern('actions') => 'actions',
            default => 'plain',
        };

        return $blueprint->isWeb()
            ? 'controllers/web/'.$style
            : 'controllers/'.$style;
    }

    /**
     * @return array<string, string>
     */
    private function requestReplacements(ModuleBlueprint $blueprint): array
    {
        $model = $blueprint->model();

        if (! $blueprint->hasPattern('requests')) {
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
