<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Explicit change set for a FixProposal — proposal owns meaning (file/line counts).
 */
final readonly class ChangeSet
{
    /**
     * @param  list<FileChange>  $files
     */
    public function __construct(
        public array $files,
        public ChangeSetSummary $summary,
    ) {}

    /**
     * @param  list<FileChange>  $files
     */
    public static function of(array $files): self
    {
        return new self($files, ChangeSetSummary::fromFiles($files));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'files' => array_map(
                static fn (FileChange $file): array => $file->toArray(),
                $this->files,
            ),
            'summary' => $this->summary->toArray(),
        ];
    }
}
