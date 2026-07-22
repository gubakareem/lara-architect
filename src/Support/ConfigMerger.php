<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Support;

/**
 * Deep-merges associative config arrays. Lists (architectures pattern
 * lists, feature_extras, …) are replaced wholesale; nested maps
 * (generators, namespaces, layers) merge key-by-key so an outdated
 * published config cannot wipe generators added in a newer package version.
 */
final class ConfigMerger
{
    /**
     * @param  array<array-key, mixed>  $base
     * @param  array<array-key, mixed>  $overrides
     * @return array<array-key, mixed>
     */
    public static function merge(array $base, array $overrides): array
    {
        foreach ($overrides as $key => $value) {
            $base[$key] = is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && ! array_is_list($value)
                && ! array_is_list($base[$key])
                ? self::merge($base[$key], $value)
                : $value;
        }

        return $base;
    }
}
