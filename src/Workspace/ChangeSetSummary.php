<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Aggregate stats for a ChangeSet — computed in core, never in the UI.
 */
final readonly class ChangeSetSummary
{
    public function __construct(
        public int $filesChanged,
        public int $linesAdded,
        public int $linesRemoved,
    ) {}

    /**
     * @param  list<FileChange>  $files
     */
    public static function fromFiles(array $files): self
    {
        $added = 0;
        $removed = 0;

        foreach ($files as $file) {
            $added += $file->linesAdded;
            $removed += $file->linesRemoved;
        }

        return new self(count($files), $added, $removed);
    }

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'files_changed' => $this->filesChanged,
            'lines_added' => $this->linesAdded,
            'lines_removed' => $this->linesRemoved,
        ];
    }
}
