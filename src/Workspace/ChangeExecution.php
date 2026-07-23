<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Controlled Change execution — immutable after start; history is appended as events.
 *
 * Replay reads events, never mutated fields.
 */
final readonly class ChangeExecution
{
    /**
     * @param  list<ExecutionEvent>  $events
     */
    public function __construct(
        public ChangeExecutionId $id,
        public FixProposalId $proposalId,
        public string $approvedBy,
        public string $startedAt,
        public ChangeSet $changes,
        public VerificationPlan $verificationPlan,
        public array $events,
        public ?string $failureReason = null,
    ) {}

    public static function start(
        FixProposal $proposal,
        string $approvedBy = 'developer',
    ): self {
        $id = ChangeExecutionId::of('exec:'.md5((string) $proposal->id.'|'.microtime(true)));

        return new self(
            id: $id,
            proposalId: $proposal->id,
            approvedBy: $approvedBy,
            startedAt: gmdate('c'),
            changes: $proposal->changeSet,
            verificationPlan: $proposal->verification,
            events: [
                ExecutionEvent::of(ExecutionEventType::ExecutionStarted, [
                    'proposal_id' => (string) $proposal->id,
                    'approved_by' => $approvedBy,
                ]),
            ],
        );
    }

    /**
     * Append an event — never rewrite prior history.
     *
     * @param  array<string, mixed>  $payload
     */
    public function append(ExecutionEventType $type, array $payload = [], ?string $failureReason = null): self
    {
        return new self(
            $this->id,
            $this->proposalId,
            $this->approvedBy,
            $this->startedAt,
            $this->changes,
            $this->verificationPlan,
            [...$this->events, ExecutionEvent::of($type, $payload)],
            $failureReason ?? $this->failureReason,
        );
    }

    public function withVerificationPlan(VerificationPlan $plan): self
    {
        return new self(
            $this->id,
            $this->proposalId,
            $this->approvedBy,
            $this->startedAt,
            $this->changes,
            $plan,
            $this->events,
            $this->failureReason,
        );
    }

    public function status(): ChangeExecutionStatus
    {
        $last = $this->events === [] ? null : $this->events[array_key_last($this->events)]->type;

        return match ($last) {
            ExecutionEventType::SessionCompleted => ChangeExecutionStatus::Completed,
            ExecutionEventType::VerificationPassed => ChangeExecutionStatus::Verified,
            ExecutionEventType::VerificationStarted => ChangeExecutionStatus::Verifying,
            ExecutionEventType::VerificationFailed,
            ExecutionEventType::ExecutionFailed => ChangeExecutionStatus::Failed,
            ExecutionEventType::FilesChanged => ChangeExecutionStatus::Started,
            ExecutionEventType::ExecutionStarted => ChangeExecutionStatus::Started,
            default => ChangeExecutionStatus::Prepared,
        };
    }

    public function finishedAt(): ?string
    {
        foreach (array_reverse($this->events) as $event) {
            if (in_array($event->type, [
                ExecutionEventType::SessionCompleted,
                ExecutionEventType::VerificationFailed,
                ExecutionEventType::ExecutionFailed,
            ], true)) {
                return $event->occurredAt;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'proposal_id' => (string) $this->proposalId,
            'approved_by' => $this->approvedBy,
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt(),
            'status' => $this->status()->value,
            'failure_reason' => $this->failureReason,
            'changes' => $this->changes->toArray(),
            'verification_plan' => $this->verificationPlan->toArray(),
            'events' => array_map(
                static fn (ExecutionEvent $event): array => $event->toArray(),
                $this->events,
            ),
        ];
    }
}
