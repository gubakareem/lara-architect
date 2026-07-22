<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis\Rules;

use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Analysis\Violation;
use KarimAshraf\LaraArchitect\Contracts\LintRule;

/**
 * Validation rules belong in form requests where they are reusable and
 * testable, not inline in controller methods.
 */
class NoInlineValidationInControllersRule implements LintRule
{
    public function name(): string
    {
        return 'no-inline-validation-in-controllers';
    }

    public function check(ScannedFile $file): array
    {
        if (! $file->isController()) {
            return [];
        }

        $violations = [];

        foreach ($file->linesMatching('/->validate(WithBag)?\s*\(|\bValidator::make\s*\(/') as $line) {
            $violations[] = new Violation(
                $this->name(),
                $file->path,
                $line,
                'Controller validates inline; extract the rules into a FormRequest.',
            );
        }

        return $violations;
    }
}
