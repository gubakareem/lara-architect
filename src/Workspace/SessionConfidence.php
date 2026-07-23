<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Derived confidence for a completed Session — not only a developer vote.
 *
 * Improvement confidence is High when verification + health + violation removal align.
 */
final readonly class SessionConfidence
{
    /**
     * @param  array<string, bool|string>  $signals
     * @param  list<string>  $reasons
     */
    public function __construct(
        public bool $success,
        public string $level,
        public array $signals,
        public array $reasons,
        public ?bool $developerAccepted = null,
    ) {}

    public static function derive(
        ArchitectureSession $session,
        ?bool $developerAccepted = null,
    ): self {
        $healthDelta = $session->healthAfter - $session->healthBefore;
        $verificationOk = $session->verificationSummary !== []
            && ! in_array('failed', $session->verificationSummary, true);
        $violationRemoved = $session->changes !== [];
        $healthImproved = $healthDelta > 0;

        $signals = [
            'verification' => $verificationOk,
            'health_change' => ($healthDelta >= 0 ? '+' : '').$healthDelta,
            'violation_removed' => $violationRemoved,
            'developer_accepted' => $developerAccepted === true,
        ];

        $reasons = [];
        if ($verificationOk) {
            $reasons[] = 'verification passed';
        }
        if ($violationRemoved) {
            $reasons[] = 'issue / architecture boundary improved';
        }
        if ($healthImproved) {
            $reasons[] = 'architecture metric improved';
        }
        if ($developerAccepted === true) {
            $reasons[] = 'developer confirmed it helped';
        }
        if ($developerAccepted === false) {
            $reasons[] = 'developer did not confirm help';
        }

        $score = (int) $verificationOk + (int) $violationRemoved + (int) $healthImproved + (int) ($developerAccepted === true);
        $level = match (true) {
            $score >= 3 => 'high',
            $score === 2 => 'medium',
            default => 'low',
        };

        return new self(
            success: $verificationOk && $violationRemoved,
            level: $level,
            signals: $signals,
            reasons: $reasons,
            developerAccepted: $developerAccepted,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'level' => $this->level,
            'signals' => $this->signals,
            'reasons' => $this->reasons,
            'developer_accepted' => $this->developerAccepted,
        ];
    }
}
