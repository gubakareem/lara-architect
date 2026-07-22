<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis\Rules;

use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Analysis\Violation;
use KarimAshraf\LaraArchitect\Contracts\LintRule;

/**
 * Controllers should delegate persistence to services, actions or
 * repositories instead of calling Eloquent write/query methods or the DB
 * facade directly.
 */
class NoEloquentInControllersRule implements LintRule
{
    public function name(): string
    {
        return 'no-eloquent-in-controllers';
    }

    public function check(ScannedFile $file): array
    {
        if (! $file->isController()) {
            return [];
        }

        $violations = [];

        if ($file->importsClass('Illuminate\Support\Facades\DB')) {
            foreach ($file->linesMatching('/\bDB::/') as $line) {
                $violations[] = new Violation(
                    $this->name(),
                    $file->path,
                    $line,
                    'Controller uses the DB facade directly; move the query into a service, action or repository.',
                );
            }
        }

        foreach ($file->importsStartingWith('App\\Models\\') as $model) {
            $short = substr((string) strrchr('\\'.$model, '\\'), 1);
            $pattern = '/\b'.preg_quote($short, '/').'::(create|update|delete|forceDelete|insert|where|query|firstOrCreate|updateOrCreate)\s*\(/';

            foreach ($file->linesMatching($pattern) as $line) {
                $violations[] = new Violation(
                    $this->name(),
                    $file->path,
                    $line,
                    sprintf('Controller calls %s Eloquent methods directly; delegate to a service, action or repository.', $short),
                );
            }
        }

        return $violations;
    }
}
