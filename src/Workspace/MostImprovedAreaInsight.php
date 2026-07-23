<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Domain insight: area with strongest recorded health gains.
 */
final readonly class MostImprovedAreaInsight implements ExplainableInsight
{
    public function __construct(
        public string $context,
        public ?int $healthBefore,
        public ?int $healthAfter,
        public int $healthDelta,
        public int $improvements,
        public ArchitectureConcept $mainImprovement,
        public string $observed,
        public string $whyItMatters,
        public InsightEvidence $evidence,
        public InsightOverTime $overTime,
        public IntelligenceConfidence $confidenceDetail,
    ) {}

    public function kind(): string
    {
        return 'most_improved';
    }

    public function insight(): string
    {
        return 'Most improved: '.$this->context;
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
            'evidence' => $this->evidence->toArray(),
            'confidence' => $this->confidence(),
            'confidence_detail' => $this->confidenceDetail->toArray(),
            'over_time' => $this->overTime->toArray(),
            'context' => $this->context,
            'health_before' => $this->healthBefore,
            'health_after' => $this->healthAfter,
            'health_delta' => ($this->healthDelta >= 0 ? '+' : '').$this->healthDelta,
            'improvements' => $this->improvements,
            'main_improvement' => $this->mainImprovement->toArray(),
        ];
    }
}
