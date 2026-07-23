<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 17 — Architecture Context.
 * What should I know about this exact thing before I touch it?
 * Unifies file/module context + identity + decisions + evolution + guidance.
 */
final readonly class ArchitectureContext
{
    /**
     * @param  list<string>  $importantDecisions
     * @param  list<string>  $recentEvolution
     * @param  list<string>  $watch
     * @param  list<string>  $principles
     */
    public function __construct(
        public string $question,
        public string $subject,
        public string $purpose,
        public string $createdBecause,
        public array $importantDecisions,
        public array $recentEvolution,
        public array $watch,
        public array $principles,
        public string $identityStyle,
        public string $guidanceHint,
        public string $summary,
        public ?ContextBrief $brief = null,
        public CommunicationAudience $audience = CommunicationAudience::Developer,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'subject' => $this->subject,
            'purpose' => $this->purpose,
            'created_because' => $this->createdBecause,
            'important_decisions' => $this->importantDecisions,
            'recent_evolution' => $this->recentEvolution,
            'watch' => $this->watch,
            'principles' => $this->principles,
            'identity_style' => $this->identityStyle,
            'guidance_hint' => $this->guidanceHint,
            'summary' => $this->summary,
            'brief' => $this->brief?->toArray(),
            'audience' => $this->audience->value,
            'kind' => 'architecture_context',
        ];
    }
}
