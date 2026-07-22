<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis\Rules;

use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Analysis\Violation;
use KarimAshraf\LaraArchitect\Contracts\LintRule;

/**
 * Repositories belong behind services (or actions); controllers that inject
 * them skip the transactional layer and duplicate orchestration logic.
 */
class NoRepositoriesInControllersRule implements LintRule
{
    public function name(): string
    {
        return 'no-repositories-in-controllers';
    }

    public function check(ScannedFile $file): array
    {
        if (! $file->isController()) {
            return [];
        }

        $violations = [];

        foreach ($file->imports as $import) {
            if (str_contains($import, '\\Repositories\\') || str_ends_with($import, 'Repository')) {
                $violations[] = new Violation(
                    $this->name(),
                    $file->path,
                    $file->firstLineOf($import),
                    sprintf('Controller depends on repository [%s]; inject a service (or action) instead.', $import),
                );
            }
        }

        return $violations;
    }
}
