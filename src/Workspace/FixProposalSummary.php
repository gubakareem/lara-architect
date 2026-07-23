<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Presentation-neutral proposal summary for CLI · React · GitHub · Debugbar.
 */
final readonly class FixProposalSummary
{
    public function __construct(
        public string $title,
        public string $intent,
        public string $expectedOutcome,
        public int $affectedFilesCount,
        public int $verificationCount,
    ) {}

    public static function make(
        string $title,
        string $intent,
        string $expectedOutcome,
        ChangeSet $changeSet,
        VerificationPlan $verification,
    ): self {
        return new self(
            title: $title,
            intent: $intent,
            expectedOutcome: $expectedOutcome,
            affectedFilesCount: $changeSet->summary->filesChanged,
            verificationCount: count($verification->checks),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'intent' => $this->intent,
            'expected_outcome' => $this->expectedOutcome,
            'affected_files_count' => $this->affectedFilesCount,
            'verification_count' => $this->verificationCount,
        ];
    }
}
