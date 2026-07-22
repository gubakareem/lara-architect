<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Contracts;

use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;

/**
 * A named set of layers + rules (e.g. the built-in Laravel pack).
 * Installable packs are a v2 concern; the format is stable now.
 */
interface RulePack
{
    public function name(): string;

    public function layers(): LayerRegistry;

    /**
     * @return list<ArchitectureRule>
     */
    public function rules(): array;
}
