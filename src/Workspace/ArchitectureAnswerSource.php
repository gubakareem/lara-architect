<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One traced source behind an Architecture Answer.
 */
final readonly class ArchitectureAnswerSource
{
    public function __construct(
        public ArchitectureSourceType $type,
        public string $label,
        public string $ref = '',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'label' => $this->label,
            'ref' => $this->ref,
        ];
    }

    public function display(): string
    {
        return $this->label !== '' ? $this->label : $this->type->value;
    }
}
