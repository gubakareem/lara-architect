<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates a string-backed enum for every `enum` field in the module
 * (e.g. a `status:enum` field on Product produces App\Enums\ProductStatus).
 * Other generators wire the enum into model casts, validation rules,
 * factories and DTOs automatically.
 */
class EnumGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $files = [];

        foreach ($blueprint->enumFields() as $field) {
            $class = $blueprint->enumClassName($field);

            $contents = $this->stubs->render('enum', [
                ...$this->baseReplacements($blueprint),
                'namespace' => $blueprint->namespaceFor('enum'),
                'class' => $class,
            ]);

            $files[] = $this->classFile(
                $blueprint->namespaceFor('enum'),
                $class,
                $contents,
                'Enum',
            );
        }

        return $files;
    }
}
