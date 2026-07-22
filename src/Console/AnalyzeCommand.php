<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Architecture\EngineFactory;
use KarimAshraf\LaraArchitect\Support\TeamConfig;
use Throwable;

class AnalyzeCommand extends Command
{
    protected $signature = 'architect:analyze
        {--path=* : Paths to scan, relative to the project root}
        {--format=console : Output format: console|json (sarif reserved)}';

    protected $description = 'Report architecture layers, violations and hotspots';

    public function handle(): int
    {
        TeamConfig::apply();

        try {
            $engine = EngineFactory::engine([
                'layers' => (array) config('lara-architect.lint.layers', []),
                'dependencies' => (array) config('lara-architect.lint.dependencies', []),
                'thresholds' => (array) config('lara-architect.lint.thresholds', []),
                'pack' => (string) config('lara-architect.lint.pack', 'laravel'),
            ]);

            $paths = $this->option('path') ?: config('lara-architect.lint.paths', ['app']);
            $result = $engine->analyze(base_path(), array_values((array) $paths));
            $format = (string) $this->option('format');
            $renderer = EngineFactory::renderer($format);

            if ($format === 'json') {
                $this->line(rtrim($renderer->render($result, base_path())));

                return self::SUCCESS;
            }

            $this->components->info(sprintf(
                'Scanned %d class(es) in [%s].',
                $result->filesScanned,
                implode(', ', (array) $paths),
            ));

            foreach ($result->layerCounts as $layer => $count) {
                $this->components->twoColumnDetail($layer, (string) $count);
            }

            if ($result->violations !== []) {
                $this->newLine();
                $this->components->warn(sprintf('%d architecture violation(s):', count($result->violations)));

                foreach ($result->violations as $violation) {
                    $this->components->twoColumnDetail(
                        sprintf('%s:%d', $this->relative($violation->path), $violation->line),
                        $violation->message,
                    );
                }
            }

            $this->newLine();

            if ($result->hotspots === []) {
                $this->components->info('No hotspots detected.');
            } else {
                $this->components->warn(sprintf('%d hotspot(s) worth a look:', count($result->hotspots)));

                foreach ($result->hotspots as $hotspot) {
                    $this->components->twoColumnDetail($this->relative($hotspot->path), $hotspot->message);
                }
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function relative(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, base_path().'/'], '', $path);
    }
}
