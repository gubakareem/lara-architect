<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Packs;

use KarimAshraf\LaraArchitect\Architecture\Contracts\ArchitectureRule;
use KarimAshraf\LaraArchitect\Architecture\Contracts\RulePack;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\Rules\LayerDependencyRule;

/**
 * Built-in Laravel service-repository conventions expressed as
 * declarative layer rules. Override layers/rules via architect.json
 * or config — this pack is the default when none are configured.
 */
final class LaravelRulePack implements RulePack
{
    public function name(): string
    {
        return 'laravel';
    }

    public function layers(): LayerRegistry
    {
        return new LayerRegistry([
            'Controller' => ['App\\Http\\Controllers'],
            'Request' => ['App\\Http\\Requests'],
            'Resource' => ['App\\Http\\Resources'],
            'Service' => ['App\\Services'],
            'Repository' => ['App\\Repositories'],
            'Action' => ['App\\Actions'],
            'Model' => ['App\\Models'],
            'Infrastructure' => [
                'Illuminate\\Support\\Facades\\DB',
                'Illuminate\\Database',
            ],
            'Validation' => [
                'Illuminate\\Validation',
                'Illuminate\\Support\\Facades\\Validator',
            ],
        ]);
    }

    /**
     * @return list<ArchitectureRule>
     */
    public function rules(): array
    {
        return [
            new LayerDependencyRule(
                from: 'Controller',
                deny: ['Model', 'Repository', 'Infrastructure', 'Validation'],
            ),
            new LayerDependencyRule(
                from: 'Model',
                deny: ['Controller', 'Request', 'Service', 'Repository'],
            ),
        ];
    }
}
