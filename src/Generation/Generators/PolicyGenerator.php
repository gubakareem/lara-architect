<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates a model policy with the standard ability methods.
 */
class PolicyGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $contents = $this->stubs->render('policy', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('policy'),
            'userClass' => (string) config('lara-architect.generation.user_model', 'App\\Models\\User'),
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('policy'),
                $blueprint->model().'Policy',
                $contents,
                'Policy',
            ),
        ];
    }
}
