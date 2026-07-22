<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis\Rules;

use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Analysis\Violation;
use KarimAshraf\LaraArchitect\Contracts\LintRule;

/**
 * Dependency direction: the HTTP layer and services may depend on models,
 * never the other way around. A model importing controllers, requests or
 * services signals inverted coupling.
 */
class ModelsDoNotDependOnHttpRule implements LintRule
{
    private const FORBIDDEN_SEGMENTS = ['\\Http\\Controllers\\', '\\Http\\Requests\\', '\\Services\\'];

    public function name(): string
    {
        return 'models-do-not-depend-on-http';
    }

    public function check(ScannedFile $file): array
    {
        if (! $file->isModel()) {
            return [];
        }

        $violations = [];

        foreach ($file->imports as $import) {
            foreach (self::FORBIDDEN_SEGMENTS as $segment) {
                if (str_contains('\\'.$import, $segment)) {
                    $violations[] = new Violation(
                        $this->name(),
                        $file->path,
                        $file->firstLineOf($import),
                        sprintf('Model imports [%s]; models must not depend on the HTTP or service layer.', $import),
                    );

                    break;
                }
            }
        }

        return $violations;
    }
}
