<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class ResourceGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $fields = [];

        if ($blueprint->usesUuid) {
            $fields[] = "'uuid' => \$this->uuid,";
        }

        foreach ($blueprint->fields as $field) {
            $fields[] = "'{$field->name}' => \$this->{$field->name},";
        }

        $contents = $this->stubs->render('resource', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('resource'),
            'fields' => $this->block($fields, 12),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('resource'),
                $blueprint->model().'Resource',
                $contents,
                'API resource',
            ),
        ];
    }
}
