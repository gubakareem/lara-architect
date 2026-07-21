<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation\Generators;

use Illuminate\Support\Str;
use KarimAshraf\LaraArchitect\Contracts\Generator;
use KarimAshraf\LaraArchitect\Generation\GeneratedFile;
use KarimAshraf\LaraArchitect\Generation\ModuleBlueprint;
use KarimAshraf\LaraArchitect\Generation\StubRenderer;

abstract class BaseGenerator implements Generator
{
    public function __construct(
        protected readonly StubRenderer $stubs,
    ) {}

    /**
     * Replacements shared by every stub.
     *
     * @return array<string, string>
     */
    protected function baseReplacements(ModuleBlueprint $blueprint): array
    {
        return [
            'model' => $blueprint->model(),
            'modelVariable' => $blueprint->modelVariable(),
            'pluralModel' => $blueprint->pluralModel(),
            'pluralVariable' => Str::camel($blueprint->pluralModel()),
            'table' => $blueprint->table(),
            'routeName' => $blueprint->routeName(),
            'modelClass' => $blueprint->modelClass(),
        ];
    }

    protected function classFile(string $namespace, string $class, string $contents, string $description): GeneratedFile
    {
        return new GeneratedFile(
            path: $this->pathForNamespace($namespace).'/'.$class.'.php',
            contents: $contents,
            description: $description,
        );
    }

    /**
     * Map a namespace to a filesystem path following Laravel conventions
     * (App\ => app/, Database\Factories => database/factories).
     */
    protected function pathForNamespace(string $namespace): string
    {
        if ($namespace === 'App' || str_starts_with($namespace, 'App\\')) {
            $relative = str_replace('\\', '/', Str::after($namespace.'\\', 'App\\'));

            return rtrim(app_path(rtrim($relative, '/')), '/');
        }

        if (str_starts_with($namespace, 'Database\\')) {
            $segments = explode('\\', Str::after($namespace, 'Database\\'));
            $segments[0] = Str::snake($segments[0]);

            return database_path(implode('/', $segments));
        }

        return base_path(str_replace('\\', '/', $namespace));
    }

    /**
     * Indent each line of a block and join with newlines.
     *
     * @param  list<string>  $lines
     */
    protected function block(array $lines, int $indent = 8): string
    {
        $prefix = str_repeat(' ', $indent);

        return implode("\n", array_map(
            static fn (string $line) => $line === '' ? '' : $prefix.$line,
            $lines,
        ));
    }

    /**
     * Render a list of string rules as PHP array source: ['required', 'string'].
     *
     * @param  list<string>  $rules
     */
    protected function rulesArray(array $rules): string
    {
        return '['.implode(', ', array_map(
            static fn (string $rule) => "'".$rule."'",
            $rules,
        )).']';
    }
}
