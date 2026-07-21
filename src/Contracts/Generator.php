<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Contracts;

use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;

/**
 * A pattern generator produces one or more files for a module blueprint.
 * Register implementations in config('lara-architect.generation.generators')
 * to make them available to `make:module` and architecture presets.
 */
interface Generator
{
    /**
     * @return list<GeneratedFile>
     */
    public function generate(ModuleBlueprint $blueprint): array;
}
