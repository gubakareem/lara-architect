<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class FactoryGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $imports = [];
        $definitions = [];

        foreach ($blueprint->fields as $field) {
            if ($field->isEnum() && $blueprint->usesEnums()) {
                $enum = $blueprint->enumClassName($field);
                $imports[] = 'use '.$blueprint->enumClass($field).';';
                $definitions[] = "'{$field->name}' => fake()->randomElement({$enum}::cases()),";

                continue;
            }

            $definitions[] = "'{$field->name}' => {$field->factoryDefinition()},";
        }

        sort($imports);

        $contents = $this->stubs->render('factory', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('factory'),
            'imports' => $imports === [] ? '' : implode("\n", $imports)."\n",
            'definitions' => $this->block($definitions, 12),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('factory'),
                $blueprint->model().'Factory',
                $contents,
                'Factory',
            ),
        ];
    }
}
