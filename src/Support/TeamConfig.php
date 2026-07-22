<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Support;

use InvalidArgumentException;

/**
 * Project-level overrides from an `architect.json` file committed at the
 * application root, so a team can version its architecture conventions
 * without publishing the package config:
 *
 *     {
 *         "generation": {
 *             "default_architecture": "actions",
 *             "default_ui": "api",
 *             "namespaces": {
 *                 "service": "App\\Domain\\{module}\\Services"
 *             }
 *         }
 *     }
 */
final class TeamConfig
{
    public static function apply(): void
    {
        $path = base_path('architect.json');

        if (! is_file($path)) {
            return;
        }

        $overrides = json_decode((string) file_get_contents($path), true);

        if (! is_array($overrides)) {
            throw new InvalidArgumentException('architect.json must contain a valid JSON object.');
        }

        config([
            'lara-architect' => ConfigMerger::merge((array) config('lara-architect', []), $overrides),
        ]);
    }
}
