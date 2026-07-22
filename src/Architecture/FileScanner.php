<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * Scans PHP files under a project root. No Laravel dependency —
 * uses native filesystem functions so ArchitectureEngine can run
 * outside the framework.
 */
final class FileScanner
{
    public function __construct(
        private readonly string $root,
    ) {}

    /**
     * @param  list<string>  $paths  Absolute or root-relative directories.
     * @return list<ArchitectureFile>
     */
    public function scan(array $paths): array
    {
        $files = [];

        foreach ($paths as $path) {
            $absolute = $this->absolute($path);

            if (! is_dir($absolute)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absolute, \FilesystemIterator::SKIP_DOTS),
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                    $contents = file_get_contents($file->getPathname());

                    if ($contents === false) {
                        continue;
                    }

                    $files[] = new ArchitectureFile($file->getPathname(), $contents);
                }
            }
        }

        return $files;
    }

    private function absolute(string $path): string
    {
        if ($this->isAbsolute($path)) {
            return $path;
        }

        return rtrim($this->root, '/\\').DIRECTORY_SEPARATOR.ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), '/\\');
    }

    private function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/'));
    }
}
