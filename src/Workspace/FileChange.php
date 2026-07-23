<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One file in a ChangeSet. Owns line counts so clients never recalculate.
 */
final readonly class FileChange
{
    public function __construct(
        public string $path,
        public FileChangeType $type,
        public ?string $before = null,
        public ?string $after = null,
        public int $linesAdded = 0,
        public int $linesRemoved = 0,
    ) {}

    public static function make(
        string $path,
        FileChangeType $type,
        ?string $before = null,
        ?string $after = null,
    ): self {
        [$added, $removed] = self::countLines($before, $after, $type);

        return new self($path, $type, $before, $after, $added, $removed);
    }

    /**
     * @return array{0: int, 1: int}
     */
    public static function countLines(?string $before, ?string $after, FileChangeType $type): array
    {
        $beforeLines = self::lines($before);
        $afterLines = self::lines($after);

        return match ($type) {
            FileChangeType::Created => [count($afterLines), 0],
            FileChangeType::Deleted => [0, count($beforeLines)],
            FileChangeType::Modified => [
                count(array_values(array_diff($afterLines, $beforeLines))),
                count(array_values(array_diff($beforeLines, $afterLines))),
            ],
        };
    }

    /**
     * @return list<string>
     */
    private static function lines(?string $content): array
    {
        if ($content === null || $content === '') {
            return [];
        }

        /** @var list<string> $parts */
        $parts = preg_split('/\R/', $content) ?: [];

        return $parts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'type' => $this->type->value,
            'lines_added' => $this->linesAdded,
            'lines_removed' => $this->linesRemoved,
            'before' => $this->before,
            'after' => $this->after,
        ];
    }
}
