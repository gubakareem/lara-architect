<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Analysis;

/**
 * A parsed PHP source file with just enough structure for lint rules and
 * metrics: namespace, imports and line access. Intentionally regex-based —
 * fast, dependency-free and good enough for convention checks.
 */
final class ScannedFile
{
    /** @var list<string> */
    public readonly array $lines;

    public readonly string $namespace;

    /** @var list<string> Fully-qualified imports from `use` statements. */
    public readonly array $imports;

    public function __construct(
        public readonly string $path,
        public readonly string $contents,
    ) {
        $this->lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $this->namespace = preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)
            ? trim($matches[1])
            : '';

        preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

        $this->imports = array_values(array_map(
            static fn (string $import): string => trim(preg_replace('/\s+as\s+.+$/i', '', $import) ?? $import),
            $matches[1],
        ));
    }

    public function isController(): bool
    {
        return str_contains($this->namespace, '\\Http\\Controllers');
    }

    public function isModel(): bool
    {
        return str_ends_with($this->namespace, '\\Models') || str_contains($this->namespace, '\\Models\\');
    }

    public function isService(): bool
    {
        return str_ends_with($this->namespace, '\\Services') || str_contains($this->namespace, '\\Services\\');
    }

    /**
     * Imports whose namespace starts with the given prefix.
     *
     * @return list<string>
     */
    public function importsStartingWith(string $prefix): array
    {
        return array_values(array_filter(
            $this->imports,
            static fn (string $import): bool => str_starts_with($import, $prefix),
        ));
    }

    public function importsClass(string $class): bool
    {
        return in_array($class, $this->imports, true);
    }

    /**
     * 1-based line numbers whose content matches the pattern.
     *
     * @return list<int>
     */
    public function linesMatching(string $pattern): array
    {
        $numbers = [];

        foreach ($this->lines as $index => $line) {
            if (preg_match($pattern, $line) === 1) {
                $numbers[] = $index + 1;
            }
        }

        return $numbers;
    }

    public function firstLineOf(string $needle): int
    {
        foreach ($this->lines as $index => $line) {
            if (str_contains($line, $needle)) {
                return $index + 1;
            }
        }

        return 1;
    }
}
