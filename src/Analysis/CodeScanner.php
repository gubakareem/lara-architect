<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis;

use Illuminate\Filesystem\Filesystem;

class CodeScanner
{
    public function __construct(
        private readonly Filesystem $files,
    ) {}

    /**
     * Scan the given paths (relative to the application root) for PHP files.
     *
     * @param  list<string>  $paths
     * @return list<ScannedFile>
     */
    public function scan(array $paths): array
    {
        $scanned = [];

        foreach ($paths as $path) {
            $absolute = base_path($path);

            if (! $this->files->isDirectory($absolute)) {
                continue;
            }

            foreach ($this->files->allFiles($absolute) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $scanned[] = new ScannedFile(
                    $file->getPathname(),
                    $this->files->get($file->getPathname()),
                );
            }
        }

        return $scanned;
    }
}
