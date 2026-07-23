<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 6 — memory-derived recommendation (Opportunity, not Action).
 *
 * Tone: "Based on what happened before, this might be worth looking at."
 * Not: "You should do this."
 */
final readonly class ArchitectureGuidance
{
    /**
     * @param  list<string>  $why
     */
    public function __construct(
        public string $area,
        public ArchitectureConcept $concept,
        public string $headline,
        public array $why,
        public GuidanceEvidence $evidence,
        public GuidanceConfidence $confidence,
        public string $opportunity,
        /** Linked open issue id when Guidance can bridge to Proposal — still human-gated. */
        public ?string $relatedIssueId = null,
        public ?string $relatedIssueTitle = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'recommendation' => [
                'area' => $this->area,
                'concept' => $this->concept->label,
                'concept_id' => $this->concept->id,
            ],
            'headline' => $this->headline,
            'why' => $this->why,
            'evidence' => $this->evidence->toArray(),
            'confidence' => $this->confidence->level,
            'confidence_detail' => $this->confidence->toArray(),
            'opportunity' => $this->opportunity,
            'related_issue_id' => $this->relatedIssueId,
            'related_issue_title' => $this->relatedIssueTitle,
            // Soft aliases for older consumers
            'area' => $this->area,
            'concept' => $this->concept->toArray(),
            'recommendation_text' => $this->headline,
            'reasons' => $this->why,
        ];
    }
}
