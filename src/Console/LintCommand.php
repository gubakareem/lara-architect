<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Analysis\CodeScanner;
use KarimAshraf\LaraArchitect\Contracts\LintRule;
use KarimAshraf\LaraArchitect\Support\TeamConfig;

/**
 * Checks the application against the architectural conventions the package
 * scaffolds: controllers stay thin, repositories stay behind services,
 * validation lives in form requests, models do not depend on the HTTP layer.
 */
class LintCommand extends Command
{
    protected $signature = 'architect:lint
        {--path=* : Paths to scan, relative to the project root (defaults to lint.paths config)}';

    protected $description = 'Lint the application against LaraArchitect architecture conventions';

    public function handle(CodeScanner $scanner): int
    {
        TeamConfig::apply();

        /** @var list<string> $paths */
        $paths = $this->option('path') ?: config('lara-architect.lint.paths', ['app']);

        $violations = [];
        $files = $scanner->scan($paths);

        foreach ($files as $file) {
            foreach ($this->rules() as $rule) {
                $violations = [...$violations, ...$rule->check($file)];
            }
        }

        if ($violations === []) {
            $this->components->info(sprintf('No architecture violations in %d file(s).', count($files)));

            return self::SUCCESS;
        }

        foreach ($violations as $violation) {
            $this->components->twoColumnDetail(
                sprintf('<fg=red>%s</>:%d [%s]', $this->relativePath($violation->path), $violation->line, $violation->rule),
                $violation->message,
            );
        }

        $this->newLine();
        $this->components->error(sprintf('%d violation(s) found in %d file(s).', count($violations), count($files)));

        return self::FAILURE;
    }

    /**
     * @return list<LintRule>
     */
    private function rules(): array
    {
        /** @var list<class-string<LintRule>> $classes */
        $classes = config('lara-architect.lint.rules', []);

        return array_map(fn (string $class): LintRule => $this->laravel->make($class), $classes);
    }

    private function relativePath(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, base_path().'/'], '', $path);
    }
}
