<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projection: why trust this Architecture Standard?
 * Not a new engine — credibility from Memory.
 */
final readonly class StandardEvidence
{
    /**
     * @param  list<string>  $contexts
     */
    public function __construct(
        public int $successfulImprovements,
        public array $contexts,
        public float $averageHealthDelta,
        public int $verificationPassed = 0,
        public int $guidanceAccepted = 0,
        public int $guidanceDismissed = 0,
    ) {}

    /**
     * @return list<string>
     */
    public function trustSignals(): array
    {
        $signals = [];
        if ($this->successfulImprovements > 0) {
            $signals[] = sprintf(
                'Applied successfully %d time%s',
                $this->successfulImprovements,
                $this->successfulImprovements === 1 ? '' : 's',
            );
        }
        if ($this->contexts !== []) {
            $signals[] = sprintf(
                'Across %d context%s',
                count($this->contexts),
                count($this->contexts) === 1 ? '' : 's',
            );
        }
        if ($this->verificationPassed > 0) {
            $signals[] = sprintf('Verification passed %d time%s', $this->verificationPassed, $this->verificationPassed === 1 ? '' : 's');
        }
        if ($this->averageHealthDelta > 0) {
            $signals[] = sprintf('Average health %+0.0f', $this->averageHealthDelta);
        }

        return $signals;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'successful_improvements' => $this->successfulImprovements,
            'contexts' => $this->contexts,
            'average_health_delta' => $this->averageHealthDelta,
            'verification_passed' => $this->verificationPassed,
            'guidance_accepted' => $this->guidanceAccepted,
            'guidance_dismissed' => $this->guidanceDismissed,
            'trust_signals' => $this->trustSignals(),
        ];
    }
}
