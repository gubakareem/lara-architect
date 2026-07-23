<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Confidence of an Architecture Intelligence insight (not of the code).
 * Derived from evidence volume + consistency — ready for later AI grounding.
 */
final readonly class IntelligenceConfidence
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public string $level,
        public InsightEvidence $evidence,
        public array $reasons = [],
    ) {}

    public static function derive(InsightEvidence $evidence, int $supportingSignals = 0): self
    {
        $reasons = [];
        if ($evidence->events >= 10) {
            $reasons[] = 'strong event sample ('.$evidence->events.')';
        } elseif ($evidence->events >= 3) {
            $reasons[] = 'moderate event sample ('.$evidence->events.')';
        } else {
            $reasons[] = 'thin event sample ('.$evidence->events.')';
        }

        if ($evidence->contexts >= 5) {
            $reasons[] = 'appears across '.$evidence->contexts.' contexts';
        } elseif ($evidence->contexts >= 2) {
            $reasons[] = 'appears in '.$evidence->contexts.' contexts';
        } else {
            $reasons[] = 'single-context signal';
        }

        if ($supportingSignals > 0) {
            $reasons[] = $supportingSignals.' supporting signal'.($supportingSignals === 1 ? '' : 's');
        }

        $score = 0;
        $score += $evidence->events >= 10 ? 2 : ($evidence->events >= 3 ? 1 : 0);
        $score += $evidence->contexts >= 5 ? 2 : ($evidence->contexts >= 2 ? 1 : 0);
        $score += min(2, $supportingSignals);

        $level = match (true) {
            $score >= 4 => 'high',
            $score >= 2 => 'medium',
            default => 'low',
        };

        return new self($level, $evidence, $reasons);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'evidence' => $this->evidence->toArray(),
            'reasons' => $this->reasons,
        ];
    }
}
