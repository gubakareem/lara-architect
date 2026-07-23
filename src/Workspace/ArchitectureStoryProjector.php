<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projects Architecture Events → ImprovementStory + ArchitectureTrend.
 * Read models only — no new engine concepts.
 */
final class ArchitectureStoryProjector
{
    /**
     * @param  list<ArchitectureEvent>  $events
     * @return list<ImprovementStory>
     */
    public function stories(array $events, string $context): array
    {
        $stories = [];
        $usedSessionIds = [];

        foreach ($events as $event) {
            if ($event->type !== ArchitectureEventType::SessionCompleted) {
                continue;
            }

            $sessionId = $event->correlation->sessionId ?? (isset($event->payload['session_id']) ? (string) $event->payload['session_id'] : null);
            if ($sessionId !== null && isset($usedSessionIds[$sessionId])) {
                continue;
            }
            if ($sessionId !== null) {
                $usedSessionIds[$sessionId] = true;
            }

            $chain = array_values(array_filter(
                $events,
                fn (ArchitectureEvent $candidate): bool => $this->sameJourney($candidate, $event),
            ));

            $story = $this->storyFromChain($chain, $context);
            if ($story !== null) {
                $stories[] = $story;
            }
        }

        usort(
            $stories,
            static fn (ImprovementStory $a, ImprovementStory $b): int => strcmp(
                (string) $b->occurredAt,
                (string) $a->occurredAt,
            ),
        );

        return $stories;
    }

    private function sameJourney(ArchitectureEvent $candidate, ArchitectureEvent $anchor): bool
    {
        $a = $anchor->correlation->mergePayload($anchor->payload);
        $c = $candidate->correlation->mergePayload($candidate->payload);

        if ($a->sessionId !== null && $c->sessionId !== null && $a->sessionId === $c->sessionId) {
            return true;
        }
        if ($a->executionId !== null && $c->executionId !== null && $a->executionId === $c->executionId) {
            return true;
        }
        if ($a->proposalId !== null && $c->proposalId !== null && $a->proposalId === $c->proposalId) {
            return true;
        }
        if ($a->issueId !== null && $c->issueId !== null && $a->issueId === $c->issueId) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     */
    public function trend(array $events, string $period = 'last_30_days', int $days = 30): ArchitectureTrend
    {
        $cutoff = time() - ($days * 86400);
        $improvements = 0;
        $healthDelta = 0;
        $resolved = 0;
        $viewed = 0;
        $failed = 0;

        foreach ($events as $event) {
            $ts = strtotime($event->occurredAt) ?: 0;
            if ($ts < $cutoff) {
                continue;
            }

            match ($event->type) {
                ArchitectureEventType::SessionCompleted => (function () use ($event, &$improvements, &$healthDelta, &$resolved): void {
                    $improvements++;
                    $before = (int) ($event->payload['health_before'] ?? 0);
                    $after = (int) ($event->payload['health_after'] ?? $before);
                    $healthDelta += ($after - $before);
                    $changes = $event->payload['changes'] ?? [];
                    $resolved += is_array($changes) ? count($changes) : 1;
                })(),
                ArchitectureEventType::ProposalViewed => $viewed++,
                ArchitectureEventType::VerificationFailed => $failed++,
                default => null,
            };
        }

        return new ArchitectureTrend(
            period: $period,
            improvements: $improvements,
            healthDelta: $healthDelta,
            resolvedIssues: $resolved,
            proposalsViewed: $viewed,
            failedVerifications: $failed,
        );
    }

    /**
     * @param  list<ArchitectureEvent>  $chain
     */
    private function storyFromChain(array $chain, string $context): ?ImprovementStory
    {
        $problem = null;
        $decision = null;
        $change = null;
        $proof = null;
        $result = null;
        $correlation = EventCorrelation::empty();
        $occurredAt = null;
        $healthBefore = null;
        $healthAfter = null;
        $completed = false;

        foreach ($chain as $event) {
            $correlation = $event->correlation->mergePayload($event->payload);
            $occurredAt = $event->occurredAt;

            match ($event->type) {
                ArchitectureEventType::IssueDetected => $problem = (string) ($event->payload['title'] ?? 'Architecture issue detected'),
                ArchitectureEventType::ProposalCreated => $decision = (string) ($event->payload['title'] ?? 'Proposed improvement'),
                ArchitectureEventType::FilesChanged => $change = $this->describeChange($event->payload),
                ArchitectureEventType::VerificationPassed => $proof = 'Verification passed (Pint · PHPStan · Tests)',
                ArchitectureEventType::VerificationFailed => $proof = 'Verification failed — Session not completed',
                ArchitectureEventType::SessionCompleted => (function () use ($event, &$result, &$healthBefore, &$healthAfter, &$completed, &$change, &$decision): void {
                    $completed = true;
                    $healthBefore = isset($event->payload['health_before']) ? (int) $event->payload['health_before'] : null;
                    $healthAfter = isset($event->payload['health_after']) ? (int) $event->payload['health_after'] : null;
                    $goal = (string) ($event->payload['goal'] ?? '');
                    if ($decision === null && $goal !== '') {
                        $decision = $goal;
                    }
                    $changes = $event->payload['changes'] ?? [];
                    if ($change === null) {
                        $change = is_array($changes) && $changes !== []
                            ? implode('; ', array_map('strval', $changes))
                            : ($goal !== '' ? $goal : 'Architecture files updated');
                    }
                    $delta = ($healthBefore !== null && $healthAfter !== null)
                        ? ($healthAfter - $healthBefore)
                        : null;
                    $result = $delta !== null
                        ? 'Architecture improved (health '.($delta >= 0 ? '+' : '').$delta.')'
                        : 'Improvement recorded';
                })(),
                default => null,
            };
        }

        if (! $completed && $proof === null && $decision === null) {
            return null;
        }

        // Incomplete chains (viewed/reviewed only) skip story — stories need a completed arc or clear decision+proof.
        if (! $completed && $proof === null) {
            return null;
        }

        return new ImprovementStory(
            context: $context,
            problem: $problem ?? 'Architecture needed improvement',
            decision: $decision ?? 'Proceed with Controlled Change',
            change: $change ?? 'Code updated under Controlled Change',
            proof: $proof ?? ($completed ? 'Session completed' : 'Pending verification'),
            result: $result ?? ($completed ? 'Architecture session recorded' : 'In progress'),
            correlation: $correlation,
            occurredAt: $occurredAt,
            healthBefore: $healthBefore,
            healthAfter: $healthAfter,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function describeChange(array $payload): string
    {
        $count = (int) ($payload['files_changed'] ?? 0);
        if ($count > 0) {
            return $count.' file'.($count === 1 ? '' : 's').' changed';
        }

        $paths = $payload['paths'] ?? [];
        if (is_array($paths) && $paths !== []) {
            $names = array_map(
                static fn (mixed $path): string => basename(str_replace('\\', '/', (string) $path)),
                $paths,
            );

            return 'Updated '.implode(', ', array_slice($names, 0, 3));
        }

        return 'Architecture files updated';
    }
}
