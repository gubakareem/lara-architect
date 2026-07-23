<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Product intelligence — opening ≠ reviewing. Captures intentional review.
 */
final readonly class ProposalReviewed
{
    public function __construct(
        public FixProposalId $proposalId,
        public string $reviewedAt,
        public string $confidenceAtReview,
        public ?float $durationSeconds = null,
    ) {}

    public static function capture(
        FixProposal $proposal,
        ?float $durationSeconds = null,
        ?string $reviewedAt = null,
    ): self {
        return new self(
            proposalId: $proposal->id,
            reviewedAt: $reviewedAt ?? gmdate('c'),
            confidenceAtReview: $proposal->confidence->level,
            durationSeconds: $durationSeconds,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'proposal_id' => (string) $this->proposalId,
            'reviewed_at' => $this->reviewedAt,
            'confidence_at_review' => $this->confidenceAtReview,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
