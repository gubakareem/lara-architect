<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Confidence of a guidance recommendation — evidence quality, not event count alone.
 */
final readonly class GuidanceConfidence
{
    /**
     * @param  list<string>  $qualitySignals  e.g. "multiple contexts", "successful previous improvements"
     */
    public function __construct(
        public string $level,
        public array $qualitySignals,
        public GuidanceEvidence $evidence,
    ) {}

    public static function derive(GuidanceEvidence $evidence): self
    {
        $signals = [];

        if ($evidence->contexts >= 2) {
            $signals[] = 'multiple contexts';
        }
        if ($evidence->remainingIssues > 0 || $evidence->resolvedIssues >= 2) {
            $signals[] = 'repeated pattern';
        }
        if ($evidence->similarImprovements > 0 || $evidence->resolvedIssues > 0) {
            $signals[] = 'successful previous improvements';
        }
        if ($evidence->recent) {
            $signals[] = 'recent evidence';
        }
        if ($evidence->healthDeltaAverage > 0) {
            $signals[] = 'positive average health impact';
        }

        $score = count($signals);
        // Quantity alone never forces high — quality must support it.
        if ($evidence->events >= 20 && $score < 2) {
            $score = 2; // thin quality with volume → medium at best via match below
        }

        $level = match (true) {
            $score >= 4 => 'high',
            $score >= 2 => 'medium',
            default => 'low',
        };

        // Cap: lots of events without quality stays medium.
        if ($level === 'high' && $score < 3) {
            $level = 'medium';
        }

        return new self($level, $signals, $evidence);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'quality_signals' => $this->qualitySignals,
            'evidence' => $this->evidence->toArray(),
        ];
    }
}
