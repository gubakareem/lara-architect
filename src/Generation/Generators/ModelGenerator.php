<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

class ModelGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $imports = ['use Illuminate\\Database\\Eloquent\\Model;'];
        $traits = [];

        if ($blueprint->hasPattern('factory')) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;';
            $traits[] = 'HasFactory';
        }

        if ($blueprint->hasPattern('filter')) {
            $imports[] = 'use KarimAshraf\\LaraArchitect\\Database\\Concerns\\Filterable;';
            $traits[] = 'Filterable';
        }

        if ($blueprint->usesUuid) {
            $imports[] = 'use KarimAshraf\\LaraArchitect\\Database\\Concerns\\HasUuid;';
            $traits[] = 'HasUuid';
        }

        if ($blueprint->usesSoftDeletes) {
            $imports[] = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
            $traits[] = 'SoftDeletes';
        }

        if ($blueprint->usesEnums()) {
            foreach ($blueprint->enumFields() as $field) {
                $imports[] = 'use '.$blueprint->enumClass($field).';';
            }
        }

        sort($imports);
        sort($traits);

        $fillable = array_map(
            static fn ($field) => "'{$field->name}',",
            $blueprint->fillableFields(),
        );

        $casts = [];

        foreach ($blueprint->fields as $field) {
            if ($field->isEnum() && $blueprint->usesEnums()) {
                $casts[] = "'{$field->name}' => {$blueprint->enumClassName($field)}::class,";

                continue;
            }

            if ($cast = $field->cast()) {
                $casts[] = "'{$field->name}' => '{$cast}',";
            }
        }

        $contents = $this->stubs->render('model', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('model'),
            'imports' => implode("\n", $imports),
            'traits' => $traits === [] ? '' : '    use '.implode(', ', $traits).";\n",
            'fillable' => $this->block($fillable),
            'casts' => $this->block($casts, 12),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('model'),
                $blueprint->model(),
                $contents,
                'Model',
            ),
        ];
    }
}
