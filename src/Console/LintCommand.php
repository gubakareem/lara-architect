<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Architecture\Baseline;
use KarimAshraf\LaraArchitect\Architecture\EngineFactory;
use KarimAshraf\LaraArchitect\Architecture\Violation;
use KarimAshraf\LaraArchitect\Support\TeamConfig;
use Throwable;

class LintCommand extends Command
{
    protected $signature = 'architect:lint
        {--path=* : Paths to scan, relative to the project root}
        {--format=console : Output format: console|json (sarif reserved)}
        {--baseline= : Baseline file path (default: architect-baseline.json)}
        {--update-baseline : Write the current violations to the baseline file}
        {--ignore-baseline : Report every violation, including baselined ones}';

    protected $description = 'Lint the application against LaraArchitect architecture rules';

    public function handle(): int
    {
        TeamConfig::apply();

        try {
            $engine = EngineFactory::engine($this->engineConfig());
            $paths = $this->option('path') ?: config('lara-architect.lint.paths', ['app']);
            $result = $engine->lint(base_path(), array_values((array) $paths));

            $baselinePath = (string) ($this->option('baseline') ?: base_path('architect-baseline.json'));
            $baseline = new Baseline($baselinePath, base_path());

            if ($this->option('update-baseline')) {
                $baseline->write($result->violations);
                $this->components->info(sprintf(
                    'Wrote %d violation(s) to baseline [%s].',
                    count($result->violations),
                    $this->relative($baselinePath),
                ));

                return self::SUCCESS;
            }

            $new = $result->violations;
            $baselined = [];

            if (! $this->option('ignore-baseline') && $baseline->exists()) {
                [$new, $baselined] = $baseline->partition($result->violations);
                $result = $result->withViolations($new);
            }

            $format = (string) $this->option('format');
            $renderer = EngineFactory::renderer($format);

            if ($format === 'json') {
                $this->line(rtrim($renderer->render($result, base_path())));
            } else {
                $this->renderConsole($result->violations, count($result->graph->nodes()), count($baselined));
            }

            return $new === [] ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @param  list<Violation>  $violations
     */
    private function renderConsole(array $violations, int $files, int $baselined): void
    {
        if ($violations === []) {
            $message = sprintf('No architecture violations in %d class(es).', $files);

            if ($baselined > 0) {
                $message .= sprintf(' (%d baselined)', $baselined);
            }

            $this->components->info($message);

            return;
        }

        foreach ($violations as $violation) {
            $this->line(sprintf(
                '  <fg=red>%s</>:%d [%s] %s',
                $this->relative($violation->path),
                $violation->line,
                $violation->rule,
                $violation->message,
            ));
        }

        $this->newLine();
        $suffix = $baselined > 0 ? sprintf(' (%d baselined)', $baselined) : '';
        $this->components->error(sprintf('%d violation(s) found.%s', count($violations), $suffix));
    }

    /**
     * @return array<string, mixed>
     */
    private function engineConfig(): array
    {
        return [
            'layers' => (array) config('lara-architect.lint.layers', []),
            'dependencies' => (array) config('lara-architect.lint.dependencies', []),
            'thresholds' => (array) config('lara-architect.lint.thresholds', []),
            'pack' => (string) config('lara-architect.lint.pack', 'laravel'),
        ];
    }

    private function relative(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, base_path().'/'], '', $path);
    }
}
