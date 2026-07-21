<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates Create/Update/Delete actions. When the blueprint also includes
 * the dto pattern, actions type-hint the generated data object; otherwise
 * they accept plain arrays.
 */
class ActionsGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $usesDto = $blueprint->hasPattern('dto');

        $namespace = $blueprint->namespaceFor('action').'\\'.$blueprint->pluralModel();
        $model = $blueprint->model();

        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
            'dtoImport' => $usesDto
                ? 'use '.$blueprint->namespaceFor('dto').'\\'.$model."Data;\n"
                : '',
            'dataParameter' => $usesDto ? $model.'Data $data' : 'array $data',
            'dataPayload' => $usesDto ? '$data->toArray()' : '$data',
            'updatePayload' => $usesDto ? '$data->toFilteredArray()' : '$data',
        ];

        $files = [];

        foreach (['create', 'update', 'delete'] as $operation) {
            $files[] = $this->classFile(
                $namespace,
                ucfirst($operation).$model,
                $this->stubs->render('actions/'.$operation, $replacements),
                ucfirst($operation).' action',
            );
        }

        return $files;
    }
}
