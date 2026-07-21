<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;

/**
 * Loads and renders stub templates. Applications can override any stub by
 * publishing it to stubs/lara-architect/ in their base path.
 */
final class StubRenderer
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * @param  array<string, string>  $replacements  keys without braces, e.g. ['model' => 'Product']
     */
    public function render(string $stub, array $replacements): string
    {
        $contents = $this->files->get($this->resolvePath($stub));

        foreach ($replacements as $key => $value) {
            $contents = str_replace(
                ['{{ '.$key.' }}', '{{'.$key.'}}'],
                $value,
                $contents,
            );
        }

        return $contents;
    }

    public function resolvePath(string $stub): string
    {
        $candidates = [
            base_path('stubs/lara-architect/'.$stub.'.stub'),
            self::packageStubPath($stub),
        ];

        foreach ($candidates as $path) {
            if ($this->files->exists($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException("Stub [{$stub}] not found.");
    }

    public static function packageStubPath(string $stub = ''): string
    {
        $base = dirname(__DIR__, 2).'/stubs';

        return $stub === '' ? $base : $base.'/'.$stub.'.stub';
    }
}
