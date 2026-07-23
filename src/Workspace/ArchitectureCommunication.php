<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 16 foundation — transferable architecture understanding.
 * Living Architecture Brief — never static documentation.
 */
final readonly class ArchitectureCommunication
{
    /**
     * @param  list<string>  $highlights
     * @param  list<string>  $readFirst
     */
    public function __construct(
        public string $question,
        public string $headline,
        public string $summary,
        public ?ArchitectureIdentitySnapshot $identity,
        public array $highlights,
        public array $readFirst,
        public ?ArchitectureBrief $brief = null,
        public CommunicationAudience $audience = CommunicationAudience::Contributor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question,
            'headline' => $this->headline,
            'summary' => $this->summary,
            'identity' => $this->identity?->toArray(),
            'highlights' => $this->highlights,
            'read_first' => $this->readFirst,
            'brief' => $this->brief?->toArray(),
            'audience' => $this->audience->value,
            'audience_label' => $this->audience->label(),
            'kind' => 'architecture_brief',
        ];
    }
}
