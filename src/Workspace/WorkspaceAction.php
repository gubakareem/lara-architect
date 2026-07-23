<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * First-class Workspace action. UI buttons dispatch these — not ad-hoc controllers.
 */
final readonly class WorkspaceAction
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public ActionId $id,
        public string $label,
        public WorkspaceActionState $state = WorkspaceActionState::Available,
        public bool $available = true,
        public array $payload = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(
        string $name,
        string $label,
        WorkspaceActionState $state = WorkspaceActionState::Available,
        bool $available = true,
        array $payload = [],
    ): self {
        return new self(ActionId::of($name), $label, $state, $available, $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => (string) $this->id,
            'label' => $this->label,
            'state' => $this->state->value,
            'available' => $this->available,
            'payload' => $this->payload,
        ];
    }
}
