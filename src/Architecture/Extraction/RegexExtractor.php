<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Architecture\Extraction;

use KarimAshraf\LaraArchitect\Architecture\ArchitectureFile;
use KarimAshraf\LaraArchitect\Architecture\Contracts\DependencyExtractor;
use KarimAshraf\LaraArchitect\Architecture\Dependency;
use KarimAshraf\LaraArchitect\Architecture\EdgeType;
use KarimAshraf\LaraArchitect\Architecture\NodeId;

/**
 * Regex-based dependency extraction. Fast and dependency-free.
 * Emits Import, Extends, Implements, UsesTrait, StaticCall, Instantiates,
 * and MethodCall (for ->validate) edges.
 */
final class RegexExtractor implements DependencyExtractor
{
    private const KEYWORDS = ['self', 'static', 'parent'];

    /** Synthetic target for inline `$request->validate(...)` calls. */
    public const INLINE_VALIDATION = 'Illuminate\\Validation\\Validator';

    public function extract(ArchitectureFile $file): array
    {
        $source = $file->nodeId();

        if ($source === null) {
            return [];
        }

        return [
            ...$this->imports($file, $source),
            ...$this->inheritance($file, $source),
            ...$this->traitUses($file, $source),
            ...$this->calls($file, $source),
            ...$this->inlineValidation($file, $source),
        ];
    }

    /**
     * @return list<Dependency>
     */
    private function imports(ArchitectureFile $file, NodeId $source): array
    {
        $edges = [];

        foreach ($file->imports as $import) {
            $edges[] = new Dependency(
                $source,
                NodeId::fromClass($import),
                EdgeType::Import,
                $file->firstLineOf($import),
            );
        }

        return $edges;
    }

    /**
     * @return list<Dependency>
     */
    private function inheritance(ArchitectureFile $file, NodeId $source): array
    {
        $edges = [];

        if (preg_match(
            '/(?:class|interface|enum)\s+\w+(?:\s+extends\s+([\w\\\\,\s]+?))?(?:\s+implements\s+([\w\\\\,\s]+?))?\s*(?:\{|$)/m',
            $file->contents,
            $matches,
        ) === 1) {
            foreach ($this->splitClassList($matches[1] ?? '') as $parent) {
                $edges[] = new Dependency(
                    $source,
                    $file->resolveClass($parent),
                    EdgeType::Extends,
                    $file->firstLineOf('extends'),
                );
            }

            foreach ($this->splitClassList($matches[2] ?? '') as $interface) {
                $edges[] = new Dependency(
                    $source,
                    $file->resolveClass($interface),
                    EdgeType::Implements,
                    $file->firstLineOf('implements'),
                );
            }
        }

        return $edges;
    }

    /**
     * @return list<Dependency>
     */
    private function traitUses(ArchitectureFile $file, NodeId $source): array
    {
        $edges = [];

        foreach ($file->lines as $index => $line) {
            if (preg_match('/^\s+use\s+([\w\\\\]+(?:\s*,\s*[\w\\\\]+)*)\s*(?:;|\{)/', $line, $matches) !== 1) {
                continue;
            }

            foreach ($this->splitClassList($matches[1]) as $trait) {
                $edges[] = new Dependency(
                    $source,
                    $file->resolveClass($trait),
                    EdgeType::UsesTrait,
                    $index + 1,
                );
            }
        }

        return $edges;
    }

    /**
     * @return list<Dependency>
     */
    private function calls(ArchitectureFile $file, NodeId $source): array
    {
        $edges = [];

        foreach ($file->lines as $index => $line) {
            if (preg_match_all('/(\\\\?[A-Z][\w\\\\]*)::(?!class\b)\w+\s*\(/', $line, $matches) > 0) {
                foreach ($matches[1] as $subject) {
                    if (in_array(strtolower($subject), self::KEYWORDS, true)) {
                        continue;
                    }

                    $edges[] = new Dependency(
                        $source,
                        $file->resolveClass($subject),
                        EdgeType::StaticCall,
                        $index + 1,
                    );
                }
            }

            if (preg_match_all('/\bnew\s+(\\\\?[A-Z][\w\\\\]*)\s*[(;,)]/', $line, $matches) > 0) {
                foreach ($matches[1] as $subject) {
                    $edges[] = new Dependency(
                        $source,
                        $file->resolveClass($subject),
                        EdgeType::Instantiates,
                        $index + 1,
                    );
                }
            }
        }

        return $edges;
    }

    /**
     * @return list<Dependency>
     */
    private function inlineValidation(ArchitectureFile $file, NodeId $source): array
    {
        $edges = [];

        foreach ($file->linesMatching('/->validate(WithBag)?\s*\(/') as $line) {
            $edges[] = new Dependency(
                $source,
                NodeId::fromClass(self::INLINE_VALIDATION),
                EdgeType::MethodCall,
                $line,
            );
        }

        return $edges;
    }

    /**
     * @return list<string>
     */
    private function splitClassList(string $list): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $list))));
    }
}
