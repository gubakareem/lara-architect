<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 13 — structured knowledge query (not AI, not a chatbot).
 * Understand only — never mutates architecture (Guidance / Proposal / Controlled Change do that).
 */
final readonly class ArchitectureQuestion
{
    public function __construct(
        public string $raw,
        public ArchitectureQuestionType $type,
        public string $subject,
        public string $normalized = '',
        public bool $isChangeRequest = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'type' => $this->type->value,
            'kind' => $this->type->value,
            'kind_label' => $this->type->label(),
            'type_label' => $this->type->label(),
            'subject' => $this->subject,
            'normalized' => $this->normalized !== '' ? $this->normalized : $this->type->label(),
            'routes_to' => $this->type->routesTo(),
            'is_change_request' => $this->isChangeRequest,
            'mutates' => false,
        ];
    }
}
