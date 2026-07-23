<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Correlation chain for Replay:
 * Finding → Issue → Proposal → Execution → Session → File
 */
final readonly class EventCorrelation
{
    public function __construct(
        public ?string $findingId = null,
        public ?string $issueId = null,
        public ?string $proposalId = null,
        public ?string $executionId = null,
        public ?string $sessionId = null,
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function with(
        ?string $findingId = null,
        ?string $issueId = null,
        ?string $proposalId = null,
        ?string $executionId = null,
        ?string $sessionId = null,
    ): self {
        return new self(
            findingId: $findingId ?? $this->findingId,
            issueId: $issueId ?? $this->issueId,
            proposalId: $proposalId ?? $this->proposalId,
            executionId: $executionId ?? $this->executionId,
            sessionId: $sessionId ?? $this->sessionId,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            findingId: isset($data['finding_id']) ? (string) $data['finding_id'] : null,
            issueId: isset($data['issue_id']) ? (string) $data['issue_id'] : null,
            proposalId: isset($data['proposal_id']) ? (string) $data['proposal_id'] : null,
            executionId: isset($data['execution_id']) ? (string) $data['execution_id'] : null,
            sessionId: isset($data['session_id']) ? (string) $data['session_id'] : null,
        );
    }

    /**
     * Merge IDs found in a payload (backward-compatible stream reads).
     *
     * @param  array<string, mixed>  $payload
     */
    public function mergePayload(array $payload): self
    {
        return $this->with(
            findingId: isset($payload['finding_id']) ? (string) $payload['finding_id'] : null,
            issueId: isset($payload['issue_id']) ? (string) $payload['issue_id'] : null,
            proposalId: isset($payload['proposal_id']) ? (string) $payload['proposal_id'] : null,
            executionId: isset($payload['execution_id']) ? (string) $payload['execution_id'] : null,
            sessionId: isset($payload['session_id']) ? (string) $payload['session_id'] : null,
        );
    }

    public function chainKey(): string
    {
        return $this->sessionId
            ?? $this->executionId
            ?? $this->proposalId
            ?? $this->issueId
            ?? $this->findingId
            ?? 'orphan';
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'finding_id' => $this->findingId,
            'issue_id' => $this->issueId,
            'proposal_id' => $this->proposalId,
            'execution_id' => $this->executionId,
            'session_id' => $this->sessionId,
        ];
    }
}
