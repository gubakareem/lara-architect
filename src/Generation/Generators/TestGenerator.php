<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Generates a feature test exercising the model through its factory so the
 * module ships with a passing test out of the box.
 */
class TestGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $contents = $this->stubs->render('test', [
            ...$this->baseReplacements($blueprint),
            'namespace' => $blueprint->namespaceFor('test'),
            'deleteAssertion' => $blueprint->usesSoftDeletes
                ? '$this->assertSoftDeleted($'.$blueprint->modelVariable().');'
                : '$this->assertModelMissing($'.$blueprint->modelVariable().');',
        ]);

        return [
            $this->classFile(
                $blueprint->namespaceFor('test'),
                $blueprint->model().'ModuleTest',
                $contents,
                'Test',
            ),
        ];
    }
}
