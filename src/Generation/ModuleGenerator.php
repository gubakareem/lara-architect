<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Contracts\Generator;

/**
 * Orchestrates module generation: resolves the generator for every pattern
 * in the blueprint, collects the files they produce and writes them to disk.
 */
final class ModuleGenerator
{
    public function __construct(
        private readonly Container $container,
        private readonly Filesystem $files,
    ) {}

    /**
     * @return array{written: list<GeneratedFile>, skipped: list<GeneratedFile>}
     */
    public function generate(ModuleBlueprint $blueprint, bool $force = false): array
    {
        $written = [];
        $skipped = [];

        foreach ($this->collectFiles($blueprint) as $file) {
            if (! $force && $this->files->exists($file->path)) {
                $skipped[] = $file;

                continue;
            }

            $this->files->ensureDirectoryExists(dirname($file->path));
            $this->files->put($file->path, $file->contents);

            $written[] = $file;
        }

        return ['written' => $written, 'skipped' => $skipped];
    }

    /**
     * @return list<GeneratedFile>
     */
    public function collectFiles(ModuleBlueprint $blueprint): array
    {
        $registry = config('lara-architect.generation.generators', []);
        $files = [];

        foreach ($blueprint->patterns as $pattern) {
            if (! isset($registry[$pattern])) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown pattern [%s]. Available patterns: %s.',
                    $pattern,
                    implode(', ', array_keys($registry)),
                ));
            }

            $generator = $this->container->make($registry[$pattern]);

            if (! $generator instanceof Generator) {
                throw new InvalidArgumentException(sprintf(
                    'Generator for pattern [%s] must implement %s.',
                    $pattern,
                    Generator::class,
                ));
            }

            $files = array_merge($files, $generator->generate($blueprint));
        }

        return $files;
    }

    /**
     * Patterns for a named architecture preset.
     *
     * @return list<string>
     */
    public static function patternsForArchitecture(string $architecture): array
    {
        $architectures = config('lara-architect.generation.architectures', []);

        if (! isset($architectures[$architecture])) {
            throw new InvalidArgumentException(sprintf(
                'Unknown architecture [%s]. Available: %s.',
                $architecture,
                implode(', ', array_keys($architectures)),
            ));
        }

        return $architectures[$architecture];
    }
}
