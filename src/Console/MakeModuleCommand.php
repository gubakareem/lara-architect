<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Generation\FieldParser;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;
use KarimAshraf\LaraArchitect\Generation\ModuleGenerator;
use KarimAshraf\LaraArchitect\Support\TeamConfig;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name? : The module name, e.g. Product (prompted if omitted)}
        {--a|architecture= : Architecture preset (run architect:patterns to list them)}
        {--p|patterns= : Comma-separated pattern list, overrides the preset}
        {--ui= : Presentation layer: api (JsonResource + Api controller) or web (Blade views)}
        {--fields= : Field definitions, e.g. "name:string, status:enum:int, price:decimal:nullable"}
        {--no-uuid : Skip the uuid column and HasUuid trait}
        {--no-soft-deletes : Skip soft deletes}
        {--force : Overwrite existing files}
        {--dry-run : Preview the files without writing anything}';

    protected $description = 'Generate a module using a configurable architecture preset';

    public function handle(ModuleGenerator $generator): int
    {
        try {
            TeamConfig::apply();

            $blueprint = $this->makeBlueprint();
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Generating module [%s] with architecture [%s] (%s).',
            $blueprint->model(),
            $blueprint->architecture,
            $blueprint->presentation,
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
            $routeHelper = $blueprint->isApi() ? 'apiResource' : 'resource';

            $this->line(sprintf(
                "  2. Register routes: Route::%s('%s', \\%s\\%sController::class);",
                $routeHelper,
                $blueprint->routeName(),
                $blueprint->namespaceFor('controller'),
                $blueprint->model(),
            ));
        }

        return self::SUCCESS;
    }

    /**
     * Extra patterns appended on top of the preset — overridden by
     * architect:feature to complete the module with policy/seeder/test.
     *
     * @return list<string>
     */
    protected function extraPatterns(): array
    {
        return [];
    }

    protected function makeBlueprint(): ModuleBlueprint
    {
        $architecture = $this->option('architecture')
            ?: config('lara-architect.generation.default_architecture', 'service-repository');

        $presentation = $this->resolvePresentation();

        $patterns = $this->option('patterns')
            ? array_values(array_filter(array_map('trim', explode(',', (string) $this->option('patterns')))))
            : ModuleGenerator::patternsForArchitecture($architecture);

        $patterns = array_values(array_unique(array_merge($patterns, $this->extraPatterns())));
        $patterns = $this->applyPresentation($patterns, $presentation);
        $namespaces = $this->resolveNamespaces($presentation, $architecture);

        return new ModuleBlueprint(
            name: $this->resolveName(),
            fields: FieldParser::parse($this->option('fields')),
            architecture: $this->option('patterns') ? 'custom' : $architecture,
            patterns: $patterns,
            namespaces: $namespaces,
            usesUuid: ! $this->option('no-uuid') && (bool) config('lara-architect.models.uuids', true),
            usesSoftDeletes: ! $this->option('no-soft-deletes') && (bool) config('lara-architect.models.soft_deletes', true),
            presentation: $presentation,
        );
    }

    private function resolveName(): string
    {
        $name = $this->argument('name');

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        if (! $this->input->isInteractive()) {
            throw new InvalidArgumentException(
                'Missing module name. Pass it as the first argument, or run `php artisan architect:new`.',
            );
        }

        return (string) $this->ask('What should the module be called?', 'Product');
    }

    private function resolvePresentation(): string
    {
        $ui = strtolower((string) ($this->option('ui')
            ?: config('lara-architect.generation.default_ui', ModuleBlueprint::PRESENTATION_API)));

        if (! in_array($ui, [ModuleBlueprint::PRESENTATION_API, ModuleBlueprint::PRESENTATION_WEB], true)) {
            throw new InvalidArgumentException(
                'Invalid --ui value. Use "api" (JsonResource + Api controllers) or "web" (Blade views).',
            );
        }

        return $ui;
    }

    /**
     * @param  list<string>  $patterns
     * @return list<string>
     */
    private function applyPresentation(array $patterns, string $presentation): array
    {
        if ($presentation === ModuleBlueprint::PRESENTATION_WEB) {
            $patterns = array_values(array_filter($patterns, static fn (string $p) => $p !== 'resource'));

            if (in_array('controller', $patterns, true) && ! in_array('views', $patterns, true)) {
                $index = array_search('controller', $patterns, true);
                array_splice($patterns, (int) $index, 0, ['views']);
            }

            return $patterns;
        }

        $patterns = array_values(array_filter($patterns, static fn (string $p) => $p !== 'views'));

        if (in_array('controller', $patterns, true) && ! in_array('resource', $patterns, true)) {
            $index = array_search('controller', $patterns, true);
            array_splice($patterns, (int) $index, 0, ['resource']);
        }

        return $patterns;
    }

    /**
     * @return array<string, string>
     */
    private function resolveNamespaces(string $presentation, string $architecture): array
    {
        /** @var array<string, string> $namespaces */
        $namespaces = config('lara-architect.generation.namespaces', []);

        /** @var array<string, array<string, string>> $overlays */
        $overlays = config('lara-architect.generation.architecture_namespaces', []);

        if (isset($overlays[$architecture]) && is_array($overlays[$architecture])) {
            $namespaces = array_merge($namespaces, $overlays[$architecture]);
        }

        if ($presentation === ModuleBlueprint::PRESENTATION_API) {
            $namespaces['controller'] = $namespaces['controller_api']
                ?? (($namespaces['controller'] ?? 'App\\Http\\Controllers').'\\Api');
        }

        return $namespaces;
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
