<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projects conversation decisions into a decision-only history (onboarding surface).
 */
final class ArchitectureDecisionHistoryService
{
    public function __construct(
        private readonly ArchitectureConversationService $conversations = new ArchitectureConversationService,
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureCollaborationService $collaboration = new ArchitectureCollaborationService,
    ) {}

    public function forArea(string $projectRoot, string $area = '', int $limit = 30): ArchitectureDecisionHistory
    {
        $report = $area !== ''
            ? $this->conversations->forSubject($projectRoot, $area, null, 100)
            : $this->conversations->forContext($projectRoot, null, 100);

        $sessionCounts = $this->sessionCountsByContext($projectRoot);
        $decisions = [];

        foreach ($report->conversations as $conversation) {
            $outcome = $conversation->outcome;
            if ($outcome === null) {
                continue;
            }
            // Include recorded/accepted and explicit no-decision (intentionally deferred).
            if (! in_array($outcome->lifecycle, [
                DecisionLifecycle::Recorded,
                DecisionLifecycle::Accepted,
                DecisionLifecycle::Referenced,
                DecisionLifecycle::NoDecision,
            ], true)) {
                continue;
            }

            $reason = $this->reasonFor($projectRoot, $conversation, $outcome);
            $refs = array_values(array_unique(array_filter([
                $conversation->context,
                $conversation->subjectKey,
            ])));
            $improvements = 0;
            foreach ($refs as $ctx) {
                $improvements += $sessionCounts[strtolower($ctx)] ?? 0;
            }

            $occurred = $conversation->closedAt !== '' ? $conversation->closedAt : $conversation->startedAt;
            $decisions[] = new ArchitectureDecisionRecord(
                area: $conversation->subjectKey !== '' ? $conversation->subjectKey : $conversation->context,
                period: $this->periodLabel($occurred),
                decision: $outcome->decision,
                reason: $reason,
                evidenceImprovements: $improvements,
                referencedContexts: $refs,
                lifecycle: $outcome->lifecycle,
                alternatives: $outcome->alternatives,
                conversationId: $conversation->id,
                rationaleId: $outcome->rationaleId,
                occurredAt: $occurred,
            );
        }

        usort(
            $decisions,
            static fn (ArchitectureDecisionRecord $a, ArchitectureDecisionRecord $b): int => strcmp($b->occurredAt, $a->occurredAt),
        );
        $decisions = array_slice($decisions, 0, $limit);

        $label = $area !== '' ? $area : 'this codebase';
        $summary = $decisions === []
            ? 'No architectural decisions recorded yet.'
            : sprintf(
                '%d decision%s for %s — including intentional deferrals.',
                count($decisions),
                count($decisions) === 1 ? '' : 's',
                $label,
            );

        return new ArchitectureDecisionHistory(
            question: 'What architectural decisions shape this system?',
            summary: $summary,
            decisions: $decisions,
            area: $area,
        );
    }

    private function reasonFor(
        string $projectRoot,
        ArchitectureConversation $conversation,
        DecisionOutcome $outcome,
    ): string {
        if ($outcome->lifecycle === DecisionLifecycle::NoDecision) {
            foreach (array_reverse($conversation->entries) as $entry) {
                if ($entry->type === ConversationEntryType::Opinion || $entry->type === ConversationEntryType::Evidence) {
                    return $entry->content;
                }
            }

            return 'Intentionally deferred — no architectural change recorded.';
        }

        if ($outcome->rationaleId !== null) {
            $collab = $this->collaboration->forContext($projectRoot, $conversation->context, 40);
            foreach ($collab->rationales as $rationale) {
                if ($rationale->id === $outcome->rationaleId) {
                    return $rationale->reason;
                }
            }
        }

        foreach (array_reverse($conversation->entries) as $entry) {
            if ($entry->type === ConversationEntryType::Rationale) {
                return $entry->content;
            }
        }

        return $conversation->topic;
    }

    /**
     * @return array<string, int>
     */
    private function sessionCountsByContext(string $projectRoot): array
    {
        $counts = [];
        foreach ($this->memory->allEvents($projectRoot, 3000) as $event) {
            if ($event->type !== ArchitectureEventType::SessionCompleted) {
                continue;
            }
            $key = strtolower($event->context);
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    private function periodLabel(string $iso): string
    {
        if ($iso === '') {
            return gmdate('Y-m');
        }
        $ts = strtotime($iso);

        return $ts !== false ? gmdate('Y-m', $ts) : gmdate('Y-m');
    }
}
