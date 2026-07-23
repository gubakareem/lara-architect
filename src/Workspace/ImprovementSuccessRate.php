<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Improvement Success Rate:
 * (Accepted + Passed Verification + Completed Session) / Started Improvements
 */
final readonly class ImprovementSuccessRate
{
    public function __construct(
        public int $started,
        public int $completedSessions,
        public int $confidenceHelped = 0,
        public int $confidenceResponses = 0,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0);
    }

    public function recordStarted(): self
    {
        return new self($this->started + 1, $this->completedSessions, $this->confidenceHelped, $this->confidenceResponses);
    }

    public function recordCompletedSession(): self
    {
        return new self($this->started, $this->completedSessions + 1, $this->confidenceHelped, $this->confidenceResponses);
    }

    public function recordConfidence(bool $helped): self
    {
        return new self(
            $this->started,
            $this->completedSessions,
            $this->confidenceHelped + ($helped ? 1 : 0),
            $this->confidenceResponses + 1,
        );
    }

    public function rate(): ?float
    {
        if ($this->started === 0) {
            return null;
        }

        return round($this->completedSessions / $this->started, 4);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'started_improvements' => $this->started,
            'completed_sessions' => $this->completedSessions,
            'success_rate' => $this->rate(),
            'confidence_helped' => $this->confidenceHelped,
            'confidence_responses' => $this->confidenceResponses,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['started_improvements'] ?? 0),
            (int) ($data['completed_sessions'] ?? 0),
            (int) ($data['confidence_helped'] ?? 0),
            (int) ($data['confidence_responses'] ?? 0),
        );
    }
}
