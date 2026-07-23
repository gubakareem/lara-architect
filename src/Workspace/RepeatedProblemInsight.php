<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Domain insight: same architectural problem recurring across memory.
 * Evidence is occurrences / resolved / remaining / contexts — not a generic bag.
 */
final readonly class RepeatedProblemInsight implements ExplainableInsight
{
    public function __construct(
        public string $title,
        public ArchitectureConcept $concept,
        public int $occurrences,
        public int $resolved,
        public int $remaining,
        public int $contextCount,
        public string $observed,
        public string $whyItMatters,
        public InsightEvidence $evidence,
        public InsightOverTime $overTime,
        public IntelligenceConfidence $confidenceDetail,
    ) {}

    public function kind(): string
    {
        return 'repeated_problem';
    }

    public function insight(): string
    {
        return 'Repeated pattern: '.$this->concept->label;
    }

    public function observed(): string
    {
        return $this->observed;
    }

    public function whyItMatters(): string
    {
        return $this->whyItMatters;
    }

    public function evidence(): InsightEvidence
    {
        return $this->evidence;
    }

    public function confidence(): string
    {
        return $this->confidenceDetail->level;
    }

    public function overTime(): InsightOverTime
    {
        return $this->overTime;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => $this->kind(),
            'insight' => $this->insight(),
            'observed' => $this->observed,
            'why_it_matters' => $this->whyItMatters,
            'evidence' => array_merge($this->evidence->toArray(), [
                'occurrences' => $this->occurrences,
                'resolved' => $this->resolved,
                'remaining' => $this->remaining,
                'contexts' => $this->contextCount,
            ]),
            'confidence' => $this->confidence(),
            'confidence_detail' => $this->confidenceDetail->toArray(),
            'over_time' => $this->overTime->toArray(),
            'title' => $this->title,
            'concept' => $this->concept->toArray(),
            'occurrences' => $this->occurrences,
            'resolved' => $this->resolved,
            'remaining' => $this->remaining,
            'contexts' => $this->contextCount,
        ];
    }
}
