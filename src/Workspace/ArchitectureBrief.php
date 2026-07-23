<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 16 — Architecture Brief.
 * Living explanation — never call this documentation.
 */
final readonly class ArchitectureBrief
{
    /**
     * @param  list<string>  $principles
     * @param  list<string>  $recentEvolution
     * @param  list<string>  $importantDecisions
     * @param  list<string>  $growthAreas
     * @param  list<string>  $whereToStart
     */
    public function __construct(
        public string $identityStyle,
        public string $identityConfidence,
        public string $currentDirection,
        public array $principles,
        public array $recentEvolution,
        public array $importantDecisions,
        public array $growthAreas,
        public array $whereToStart,
        public CommunicationAudience $audience = CommunicationAudience::Contributor,
        public string $summary = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'kind' => 'architecture_brief',
            'audience' => $this->audience->value,
            'audience_label' => $this->audience->label(),
            'audience_question' => $this->audience->question(),
            'architecture_identity' => [
                'style' => $this->identityStyle,
                'confidence' => $this->identityConfidence,
            ],
            'current_direction' => $this->currentDirection,
            'important_principles' => $this->principles,
            'recent_evolution' => $this->recentEvolution,
            'important_decisions' => $this->importantDecisions,
            'known_growth_areas' => $this->growthAreas,
            'where_to_start' => $this->whereToStart,
            'summary' => $this->summary,
        ];
    }
}
