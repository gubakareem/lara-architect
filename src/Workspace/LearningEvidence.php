<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Why did we learn this? — evidence behind Learning projections.
 * System learning from evidence, not machine learning.
 */
final readonly class LearningEvidence
{
    /**
     * @param  list<string>  $contexts
     */
    public function __construct(
        public int $attempts,
        public int $successful,
        public array $contexts,
        public float $averageHealthDelta,
    ) {}

    /**
     * @return list<string>
     */
    public function trustSignals(): array
    {
        $signals = [];
        if ($this->attempts > 0) {
            $signals[] = sprintf('%d attempt%s', $this->attempts, $this->attempts === 1 ? '' : 's');
        }
        if ($this->successful > 0) {
            $signals[] = sprintf('%d successful', $this->successful);
        }
        if ($this->contexts !== []) {
            $signals[] = sprintf('across %d context%s', count($this->contexts), count($this->contexts) === 1 ? '' : 's');
        }
        if ($this->averageHealthDelta != 0.0) {
            $signals[] = sprintf('avg health %+0.1f', $this->averageHealthDelta);
        }

        return $signals;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempts' => $this->attempts,
            'successful' => $this->successful,
            'contexts' => $this->contexts,
            'average_health_delta' => $this->averageHealthDelta,
            'trust_signals' => $this->trustSignals(),
        ];
    }
}
