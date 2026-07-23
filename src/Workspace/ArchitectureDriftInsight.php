<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Domain insight: architecture movement / regression vs intended boundaries.
 * Evidence: baseline, current state, direction change, related events.
 */
final readonly class ArchitectureDriftInsight implements ExplainableInsight
{
    /**
     * @param  list<string>  $relatedEvents
     */
    public function __construct(
        public string $driftKind,
        public string $signal,
        public ?ArchitectureBaseline $baseline,
        public string $currentState,
        public string $direction,
        public array $relatedEvents,
        public string $observed,
        public string $whyItMatters,
        public InsightEvidence $evidence,
        public InsightOverTime $overTime,
        public IntelligenceConfidence $confidenceDetail,
        public ?string $context = null,
    ) {}

    public function kind(): string
    {
        return 'architecture_drift';
    }

    public function insight(): string
    {
        return $this->signal;
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
                'baseline' => $this->baseline?->toArray(),
                'current_state' => $this->currentState,
                'direction' => $this->direction,
                'related_events' => $this->relatedEvents,
            ]),
            'confidence' => $this->confidence(),
            'confidence_detail' => $this->confidenceDetail->toArray(),
            'over_time' => $this->overTime->toArray(),
            'drift_kind' => $this->driftKind,
            'context' => $this->context,
            'baseline' => $this->baseline?->toArray(),
            'current_state' => $this->currentState,
            'direction' => $this->direction,
            'related_events' => $this->relatedEvents,
        ];
    }
}
