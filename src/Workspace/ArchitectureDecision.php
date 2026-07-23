<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Decision Memory — expose existing Story facts for a file/context.
 * Answers: "Why was this service created?" without inventing new engine concepts.
 */
final readonly class ArchitectureDecision
{
    public function __construct(
        public string $file,
        public string $question,
        public string $answer,
        public string $context,
        public string $decision,
        public string $occurredAt,
        public string $verification,
        public EventCorrelation $correlation,
        public ?string $principle = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file' => $this->file,
            'question' => $this->question,
            'answer' => $this->answer,
            'context' => $this->context,
            'decision' => $this->decision,
            'principle' => $this->principle,
            'occurred_at' => $this->occurredAt,
            'verification' => $this->verification,
            'correlation' => $this->correlation->toArray(),
        ];
    }
}
