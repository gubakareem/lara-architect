<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Generation\FieldParser;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;
use KarimAshraf\LaraArchitect\Generation\ModuleGenerator;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name : The module name, e.g. Product}
        {--a|architecture= : Architecture preset (run architect:patterns to list them)}
        {--p|patterns= : Comma-separated pattern list, overrides the preset}
        {--fields= : Field definitions, e.g. "name:string, price:decimal, sku:string:unique, notes:text:nullable"}
        {--no-uuid : Skip the uuid column and HasUuid trait}
        {--no-soft-deletes : Skip soft deletes}
        {--force : Overwrite existing files}
        {--dry-run : Preview the files without writing anything}';

    protected $description = 'Generate a module using a configurable architecture preset';

    public function handle(ModuleGenerator $generator): int
    {
        try {
            $blueprint = $this->makeBlueprint();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Generating module [%s] with architecture [%s].',
            $blueprint->model(),
            $blueprint->architecture,
        ));

        $this->components->twoColumnDetail('Patterns', implode(', ', $blueprint->patterns));

        if ($this->option('dry-run')) {
            return $this->preview($generator, $blueprint);
        }

        try {
            $result = $generator->generate($blueprint, force: (bool) $this->option('force'));
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($result['written'] as $file) {
            $this->components->task($file->description.': '.$this->relativePath($file->path));
        }

        foreach ($result['skipped'] as $file) {
            $this->components->warn('Skipped (already exists): '.$this->relativePath($file->path).' — use --force to overwrite.');
        }

        $this->newLine();
        $this->components->info('Module generated. Next steps:');
        $this->line('  1. Review the generated migration, then run: php artisan migrate');

        if ($blueprint->hasPattern('controller')) {
            $this->line(sprintf(
                "  2. Register routes: Route::apiResource('%s', \\%s\\%sController::class);",
                $blueprint->routeName(),
                $blueprint->namespaceFor('controller'),
                $blueprint->model(),
            ));
        }

        return self::SUCCESS;
    }

    private function makeBlueprint(): ModuleBlueprint
    {
        $architecture = $this->option('architecture')
            ?: config('lara-architect.generation.default_architecture', 'service-repository');

        $patterns = $this->option('patterns')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('patterns')))))
            : ModuleGenerator::patternsForArchitecture($architecture);

        return new ModuleBlueprint(
            name: (string) $this->argument('name'),
            fields: FieldParser::parse($this->option('fields')),
            architecture: $this->option('patterns') ? 'custom' : $architecture,
            patterns: $patterns,
            namespaces: config('lara-architect.generation.namespaces', []),
            usesUuid: ! $this->option('no-uuid') && (bool) config('lara-architect.models.uuids', true),
            usesSoftDeletes: ! $this->option('no-soft-deletes') && (bool) config('lara-architect.models.soft_deletes', true),
        );
    }

    private function preview(ModuleGenerator $generator, ModuleBlueprint $blueprint): int
    {
        try {
            $files = $generator->collectFiles($blueprint);
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();

        foreach ($files as $file) {
            $this->components->twoColumnDetail($file->description, $this->relativePath($file->path));
        }

        $this->newLine();
        $this->components->info(sprintf('%d file(s) would be generated. Re-run without --dry-run to write them.', count($files)));

        return self::SUCCESS;
    }

    private function relativePath(string $path): string
    {
        return str_replace([base_path().DIRECTORY_SEPARATOR, base_path().'/'], '', $path);
    }
}
