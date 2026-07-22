<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Analysis\CodeScanner;
use KarimAshraf\LaraArchitect\Analysis\ScannedFile;
use KarimAshraf\LaraArchitect\Support\TeamConfig;

/**
 * Read-only report on the application's architectural shape: how many
 * classes exist per layer, and which classes are drifting (too many public
 * methods, too many constructor dependencies, oversized files).
 */
class AnalyzeCommand extends Command
{
    protected $signature = 'architect:analyze
        {--path=* : Paths to scan, relative to the project root (defaults to lint.paths config)}';

    protected $description = 'Report architecture metrics for the application';

    public function handle(CodeScanner $scanner): int
    {
        TeamConfig::apply();

        /** @var list<string> $paths */
        $paths = $this->option('path') ?: config('lara-architect.lint.paths', ['app']);

        $files = $scanner->scan($paths);

        $layers = [
            'Controllers' => static fn (ScannedFile $file): bool => $file->isController(),
            'Models' => static fn (ScannedFile $file): bool => $file->isModel(),
            'Services' => static fn (ScannedFile $file): bool => $file->isService(),
            'Repositories' => static fn (ScannedFile $file): bool => str_contains($file->namespace, '\\Repositories'),
            'Actions' => static fn (ScannedFile $file): bool => str_contains($file->namespace, '\\Actions'),
            'Form requests' => static fn (ScannedFile $file): bool => str_contains($file->namespace, '\\Http\\Requests'),
        ];

        $this->components->info(sprintf('Scanned %d PHP file(s) in [%s].', count($files), implode(', ', $paths)));

        foreach ($layers as $label => $matcher) {
            $this->components->twoColumnDetail($label, (string) count(array_filter($files, $matcher)));
        }

        $warnings = $this->collectWarnings($files);

        $this->newLine();

        if ($warnings === []) {
            $this->components->info('No hotspots detected.');

            return self::SUCCESS;
        }

        $this->components->warn(sprintf('%d hotspot(s) worth a look:', count($warnings)));

        foreach ($warnings as [$path, $message]) {
            $this->components->twoColumnDetail($this->relativePath($path), $message);
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<ScannedFile>  $files
     * @return list<array{string, string}>
     */
    private function collectWarnings(array $files): array
    {
        $maxPublicMethods = (int) config('lara-architect.lint.thresholds.public_methods', 8);
        $maxDependencies = (int) config('lara-architect.lint.thresholds.constructor_dependencies', 5);
        $maxLines = (int) config('lara-architect.lint.thresholds.file_lines', 300);

        $warnings = [];

        foreach ($files as $file) {
            $publicMethods = count($file->linesMatching('/^\s*(?:final\s+)?public\s+(?:static\s+)?function\s+(?!__)/'));

            if ($publicMethods > $maxPublicMethods) {
                $warnings[] = [$file->path, sprintf('%d public methods (max %d) — consider splitting responsibilities.', $publicMethods, $maxPublicMethods)];
            }

            if (preg_match('/function\s+__construct\s*\(([^)]*)\)/s', $file->contents, $matches) === 1) {
                $dependencies = substr_count($matches[1], '$');

                if ($dependencies > $maxDependencies) {
                    $warnings[] = [$file->path, sprintf('%d constructor dependencies (max %d) — the class may be doing too much.', $dependencies, $maxDependencies)];
                }
            }

            if (count($file->lines) > $maxLines) {
                $warnings[] = [$file->path, sprintf('%d lines (max %d) — consider extracting collaborators.', count($file->lines), $maxLines)];
            }
        }

        return $warnings;
    }

    private function relativePath(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, base_path().'/'], '', $path);
    }
}
