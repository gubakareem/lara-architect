<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Single source of truth for "which layer does this class belong to".
 * Longest matching namespace prefix wins.
 */
final class LayerRegistry
{
    /** @var array<string, list<string>> */
    private readonly array $layers;

    /**
     * @param  array<string, string|list<string>>  $layers
     */
    public function __construct(array $layers)
    {
        $normalized = [];

        foreach ($layers as $name => $prefixes) {
            $normalized[$name] = array_map(
                static fn (string $prefix): string => trim($prefix, '\\'),
                is_array($prefixes) ? array_values($prefixes) : [$prefixes],
            );
        }

        $this->layers = $normalized;
    }

    /**
     * @return list<LayerId>
     */
    public function ids(): array
    {
        return array_map(static fn (string $name): LayerId => LayerId::of($name), array_keys($this->layers));
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->layers);
    }

    public function isEmpty(): bool
    {
        return $this->layers === [];
    }

    public function layerFor(NodeId|string $class): ?LayerId
    {
        $fqcn = $class instanceof NodeId ? $class->fqcn : ltrim($class, '\\');
        $bestLayer = null;
        $bestLength = -1;

        foreach ($this->layers as $name => $prefixes) {
            foreach ($prefixes as $prefix) {
                $matches = $fqcn === $prefix || str_starts_with($fqcn, $prefix.'\\');

                if ($matches && strlen($prefix) > $bestLength) {
                    $bestLayer = $name;
                    $bestLength = strlen($prefix);
                }
            }
        }

        return $bestLayer === null ? null : LayerId::of($bestLayer);
    }
}
