<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * Pipeline pattern: a named pipeline plus Validation and Persist pipes.
 */
class PipelineGenerator extends BaseGenerator
{
    public function generate(ModuleBlueprint $blueprint): array
    {
        $namespace = $blueprint->namespaceFor('pipeline').'\\'.$blueprint->pluralModel();
        $replacements = [
            ...$this->baseReplacements($blueprint),
            'namespace' => $namespace,
        ];

        return [
            $this->classFile(
                $namespace,
                $blueprint->model().'Pipeline',
                $this->stubs->render('pipeline/pipeline', $replacements),
                'Pipeline',
            ),
            $this->classFile(
                $namespace,
                'Validate'.$blueprint->model().'Pipe',
                $this->stubs->render('pipeline/validate', $replacements),
                'Validation pipe',
            ),
            $this->classFile(
                $namespace,
                'Persist'.$blueprint->model().'Pipe',
                $this->stubs->render('pipeline/persist', $replacements),
                'Persist pipe',
            ),
        ];
    }
}
