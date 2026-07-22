<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates a database seeder. Uses the model factory when the factory
 * pattern is part of the module, otherwise leaves an empty run() body.
 */
class SeederGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $body = $blueprint->hasPattern('factory')
            ? $blueprint->model().'::factory()->count(10)->create();'
            : '// Seed '.$blueprint->table().' here.';

        $contents = $this->stubs->render('seeder', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('seeder'),
            'body' => $body,
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('seeder'),
                $blueprint->model().'Seeder',
                $contents,
                'Seeder',
            ),
        ];
    }
}
