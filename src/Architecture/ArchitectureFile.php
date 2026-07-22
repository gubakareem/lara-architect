<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture;

/**
 * A scanned PHP source file. Extractors read this; architecture rules
 * never do — they only see the graph built from it.
 */
final class ArchitectureFile
{
    /** @var list<string> */
    public readonly array $lines;

    public readonly string $namespace;

    /** @var list<string> */
    public readonly array $imports;

    /** @var array<string, string> short alias => fqcn */
    public readonly array $importAliases;

    public readonly string $className;

    public function __construct(
        public readonly string $path,
        public readonly string $contents,
    ) {
        $this->lines = preg_split('/\r\n|\r|\n/', $contents) ?: [];

        $this->namespace = preg_match('/^namespace\s+([^;]+);/m', $contents, $matches)
            ? trim($matches[1])
            : '';

        preg_match_all('/^use\s+(?!function\s|const\s)([^;]+);/m', $contents, $matches);

        $imports = [];
        $aliases = [];

        foreach ($matches[1] as $statement) {
            $class = trim(preg_replace('/\s+as\s+.+$/i', '', $statement) ?? $statement);
            $imports[] = $class;

            $alias = preg_match('/\s+as\s+(\w+)\s*$/i', $statement, $aliasMatch)
                ? $aliasMatch[1]
                : substr((string) strrchr('\\'.$class, '\\'), 1);

            $aliases[$alias] = $class;
        }

        $this->imports = $imports;
        $this->importAliases = $aliases;

        $this->className = preg_match(
            '/^(?:final\s+|abstract\s+|readonly\s+)*(?:class|interface|trait|enum)\s+(\w+)/m',
            $contents,
            $matches,
        ) ? $matches[1] : '';
    }

    public function nodeId(): ?NodeId
    {
        if ($this->className === '') {
            return null;
        }

        $fqcn = $this->namespace === '' ? $this->className : $this->namespace.'\\'.$this->className;

        return NodeId::fromClass($fqcn);
    }

    public function resolveClass(string $reference): NodeId
    {
        $reference = trim($reference);

        if (str_starts_with($reference, '\\')) {
            return NodeId::fromClass($reference);
        }

        $head = str_contains($reference, '\\')
            ? substr($reference, 0, (int) strpos($reference, '\\'))
            : $reference;

        if (isset($this->importAliases[$head])) {
            $resolved = $this->importAliases[$head];

            return NodeId::fromClass(
                str_contains($reference, '\\')
                    ? $resolved.substr($reference, strlen($head))
                    : $resolved,
            );
        }

        return NodeId::fromClass(
            $this->namespace === '' ? $reference : $this->namespace.'\\'.$reference,
        );
    }

    /**
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
