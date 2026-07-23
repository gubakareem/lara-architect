<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

final readonly class VerificationCheck
{
    public function __construct(
        public string $name,
        public string $id,
        public VerificationCheckStatus $status = VerificationCheckStatus::Pending,
        public ?string $detail = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value,
            'detail' => $this->detail,
        ];
    }
}
