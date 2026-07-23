<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Result of a Controlled Change attempt.
 * Session is present only when verification gate passed.
 */
final readonly class ControlledChangeResult
{
    public function __construct(
        public FixProposal $proposal,
        public ProposalReviewed $reviewed,
        public ChangeExecution $execution,
        public ?ArchitectureSession $session,
        public bool $filesWritten,
        public ArchitectureTimeline $timeline,
    ) {}

    public function succeeded(): bool
    {
        return $this->session !== null
            && $this->execution->status() === ChangeExecutionStatus::Completed;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'succeeded' => $this->succeeded(),
            'proposal' => $this->proposal->toArray(),
            'reviewed' => $this->reviewed->toArray(),
            'execution' => $this->execution->toArray(),
            'session' => $this->session?->toArray(),
            'timeline' => $this->timeline->toArray(),
            'files_written' => $this->filesWritten,
            'message' => $this->succeeded()
                ? 'Architecture improvement recorded.'
                : ($this->execution->failureReason ?? 'Controlled change did not complete.'),
        ];
    }
}
