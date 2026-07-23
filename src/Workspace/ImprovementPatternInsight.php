<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Domain insight: how teams usually improve in this codebase.
 * Evidence: frequency, success rate, average health impact.
 */
final readonly class ImprovementPatternInsight implements ExplainableInsight
{
    public function __construct(
        public ArchitectureConcept $concept,
        public int $frequency,
        public float $successRate,
        public float $averageHealthImpact,
        public int $contextCount,
        public string $observed,
        public string $whyItMatters,
        public InsightEvidence $evidence,
        public InsightOverTime $overTime,
        public IntelligenceConfidence $confidenceDetail,
    ) {}

    public function kind(): string
    {
        return 'common_pattern';
    }

    public function insight(): string
    {
        return $this->concept->label;
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
                'frequency' => $this->frequency,
                'success_rate' => $this->successRate,
                'average_health_impact' => $this->averageHealthImpact,
            ]),
            'confidence' => $this->confidence(),
            'confidence_detail' => $this->confidenceDetail->toArray(),
            'over_time' => $this->overTime->toArray(),
            'concept' => $this->concept->toArray(),
            'pattern' => $this->concept->label,
            'count' => $this->frequency,
            'frequency' => $this->frequency,
            'success_rate' => $this->successRate,
            'average_health_impact' => $this->averageHealthImpact,
            'contexts' => $this->contextCount,
        ];
    }
}
