<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * CQRS write-side: Create/Update/Delete command classes (ArchitectAction).
 */
class CommandGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $usesDto = $blueprint->hasPattern('dto');
        $namespace = $blueprint->namespaceFor('command').'\\'.$blueprint->pluralModel();
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
                ucfirst($operation).$model.'Command',
                $this->stubs->render('commands/'.$operation, $replacements),
                ucfirst($operation).' command',
            );
        }

        return $files;
    }
}
