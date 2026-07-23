<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Timeline — append-only improvement history for a context/session.
 * Feeds Replay without requiring a full analytics product yet.
 */
final readonly class ArchitectureTimeline
{
    /**
     * @param  list<TimelineEvent>  $events
     */
    public function __construct(
        public array $events,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function append(TimelineEventType $type, array $payload = []): self
    {
        return new self([...$this->events, TimelineEvent::of($type, $payload)]);
    }

    public static function forControlledChange(
        FixProposal $proposal,
        ProposalReviewed $reviewed,
        ChangeExecution $execution,
        bool $sessionCompleted,
    ): self {
        $timeline = self::empty()
            ->append(TimelineEventType::ProposalCreated, [
                'proposal_id' => (string) $proposal->id,
                'title' => $proposal->title,
            ])
            ->append(TimelineEventType::ProposalReviewed, $reviewed->toArray())
            ->append(TimelineEventType::ImprovementStarted, [
                'execution_id' => (string) $execution->id,
            ]);

        foreach ($execution->events as $event) {
            $mapped = match ($event->type) {
                ExecutionEventType::FilesChanged => TimelineEventType::FilesChanged,
                ExecutionEventType::VerificationPassed => TimelineEventType::VerificationPassed,
                ExecutionEventType::VerificationFailed => TimelineEventType::VerificationFailed,
                ExecutionEventType::SessionCompleted => TimelineEventType::SessionCompleted,
                default => null,
            };

            if ($mapped !== null) {
                $timeline = $timeline->append($mapped, $event->payload);
            }
        }

        if ($sessionCompleted && ! $timeline->has(TimelineEventType::SessionCompleted)) {
            $timeline = $timeline->append(TimelineEventType::SessionCompleted, [
                'execution_id' => (string) $execution->id,
            ]);
        }

        return $timeline;
    }

    public function has(TimelineEventType $type): bool
    {
        foreach ($this->events as $event) {
            if ($event->type === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'events' => array_map(
                static fn (TimelineEvent $event): array => $event->toArray(),
                $this->events,
            ),
        ];
    }
}
