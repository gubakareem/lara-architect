<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 3.1 — measurable signal after a successful Session (not a survey).
 */
final readonly class ImprovementConfidence
{
    public function __construct(
        public SessionId $sessionId,
        public bool $helped,
        public string $recordedAt,
        public ?string $note = null,
    ) {}

    public static function record(SessionId $sessionId, bool $helped, ?string $note = null): self
    {
        return new self($sessionId, $helped, gmdate('c'), $note);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'session_id' => (string) $this->sessionId,
            'helped' => $this->helped,
            'recorded_at' => $this->recordedAt,
            'note' => $this->note,
        ];
    }
}
