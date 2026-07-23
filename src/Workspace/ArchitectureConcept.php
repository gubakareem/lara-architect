<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Canonical architecture concept + aliases (internal Vocabulary — not Team Language yet).
 */
final readonly class ArchitectureConcept
{
    /**
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $id,
        public string $label,
        public array $aliases = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'aliases' => $this->aliases,
        ];
    }
}
