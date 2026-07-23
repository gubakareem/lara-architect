<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Human-first health: band for developers, optional score for managers/trends.
 */
final readonly class WorkspaceHealth
{
    public function __construct(
        public string $band,
        public ?float $score = null,
        public string $label = '',
    ) {}

    /**
     * @param  list<WorkspaceIssue>  $issues
     */
    public static function fromIssues(array $issues): self
    {
        $count = count($issues);

        [$band, $score] = match (true) {
            $count === 0 => ['Excellent', 98.0],
            $count <= 2 => ['Good', 91.0],
            $count <= 6 => ['Needs Attention', 78.0],
            default => ['Critical', 55.0],
        };

        return new self($band, $score, $band);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'band' => $this->band,
            'label' => $this->label !== '' ? $this->label : $this->band,
            'score' => $this->score,
        ];
    }
}
