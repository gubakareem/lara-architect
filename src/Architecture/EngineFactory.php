<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Architecture\Contracts\ArchitectureRule;
use KarimAshraf\LaraArchitect\Architecture\Contracts\Renderer;
use KarimAshraf\LaraArchitect\Architecture\Contracts\RulePack;
use KarimAshraf\LaraArchitect\Architecture\Packs\LaravelRulePack;
use KarimAshraf\LaraArchitect\Architecture\Rendering\ConsoleRenderer;
use KarimAshraf\LaraArchitect\Architecture\Rendering\JsonRenderer;
use KarimAshraf\LaraArchitect\Architecture\Rendering\SarifRenderer;
use KarimAshraf\LaraArchitect\Architecture\Rules\LayerDependencyRule;

/**
 * Builds engine pieces from plain arrays (config / architect.json)
 * without touching the Laravel container.
 */
final class EngineFactory
{
    /**
     * @param  array{
     *     layers?: array<string, string|list<string>>,
     *     dependencies?: list<array{from: string, allow?: list<string>, deny?: list<string>}>,
     *     thresholds?: array{public_methods?: int, constructor_dependencies?: int, file_lines?: int},
     *     pack?: string
     * }  $config
     */
    public static function engine(array $config = []): ArchitectureEngine
    {
        $pack = self::pack($config);
        $layers = isset($config['layers']) && $config['layers'] !== []
            ? new LayerRegistry($config['layers'])
            : $pack->layers();

        $rules = isset($config['dependencies']) && $config['dependencies'] !== []
            ? self::rulesFromConfig($config['dependencies'])
            : $pack->rules();

        return ArchitectureEngine::create(
            layers: $layers,
            rules: $rules,
            thresholds: $config['thresholds'] ?? [],
            pack: $pack,
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function pack(array $config = []): RulePack
    {
        $name = $config['pack'] ?? 'laravel';

        return match ($name) {
            'laravel' => new LaravelRulePack,
            default => throw new InvalidArgumentException("Unknown architecture rule pack [{$name}]."),
        };
    }

    /**
     * @param  list<array{from: string, allow?: list<string>, deny?: list<string>}>  $definitions
     * @return list<ArchitectureRule>
     */
    public static function rulesFromConfig(array $definitions): array
    {
        return array_map(
            static fn (array $definition): ArchitectureRule => new LayerDependencyRule(
                from: $definition['from'],
                allow: $definition['allow'] ?? [],
                deny: $definition['deny'] ?? [],
            ),
            $definitions,
        );
    }

    public static function renderer(string $format): Renderer
    {
        return match (strtolower($format)) {
            'json' => new JsonRenderer,
            'console', 'text' => new ConsoleRenderer,
            'sarif' => new SarifRenderer,
            default => throw new InvalidArgumentException(
                "Unknown format [{$format}]. Supported: console, json (sarif reserved).",
            ),
        };
    }
}
