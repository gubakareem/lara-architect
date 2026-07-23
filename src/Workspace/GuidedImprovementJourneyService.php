<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 6.1 — Guided Improvement Journey.
 * Guidance → Explore (why now) → Related history → Create proposal → Controlled Change.
 * Does not auto-start Controlled Change; human intent remains the gate.
 */
final class GuidedImprovementJourneyService
{
    public function __construct(
        private readonly ArchitectureGuidanceService $guidance = new ArchitectureGuidanceService,
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureStoryProjector $stories = new ArchitectureStoryProjector,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureStandardsService $standards = new ArchitectureStandardsService,
    ) {}

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     */
    public function forContext(
        string $projectRoot,
        string $context,
        ?int $currentHealth = null,
        array $openIssues = [],
        int $days = 90,
    ): ?GuidedImprovementJourney {
        $guidance = $this->guidance->recommend(
            projectRoot: $projectRoot,
            context: $context,
            currentHealth: $currentHealth,
            openIssues: $openIssues,
            days: $days,
        );

        if ($guidance === null) {
            return null;
        }

        $intel = $this->intelligence->analyze($projectRoot, $days);
        $events = $this->memory->eventsForContext($projectRoot, $context, 80);
        $storyList = $this->stories->stories($events, $context);
        $standard = $this->standards->forConcept($projectRoot, $guidance->concept->id, $days);

        $currentState = $this->currentStateLines($openIssues, $guidance, $currentHealth);
        $history = $this->historicalLines($guidance, $intel);
        $direction = $this->directionLines($guidance, $standard);
        $relatedHistory = $this->relatedHistoryEntries($storyList, $events, $guidance);

        $issueId = $guidance->relatedIssueId;
        $canPropose = $issueId !== null && $issueId !== '';

        return new GuidedImprovementJourney(
            guidance: $guidance,
            currentState: $currentState,
            history: $history,
            expectedDirection: $direction,
            relatedHistory: $relatedHistory,
            canCreateProposal: $canPropose,
            proposeIssueId: $issueId,
            nextStep: $canPropose ? 'create_proposal' : 'explore',
            standard: $standard,
        );
    }

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     * @return list<string>
     */
    private function currentStateLines(array $openIssues, ArchitectureGuidance $guidance, ?int $currentHealth): array
    {
        $lines = [];
        $matching = 0;
        foreach ($openIssues as $issue) {
            if ((string) ($issue['title'] ?? '') !== '') {
                $matching++;
            }
        }
        if ($matching > 0) {
            $lines[] = sprintf(
                '%d controller/context signal%s related to this opportunity',
                $matching,
                $matching === 1 ? '' : 's',
            );
        }
        if ($guidance->relatedIssueTitle !== null) {
            $lines[] = 'Current focus: '.$guidance->relatedIssueTitle;
        }
        if ($currentHealth !== null) {
            $lines[] = 'Current health: '.$currentHealth.'%';
        }
        if ($lines === []) {
            $lines[] = $guidance->opportunity;
        }

        return $lines;
    }

    /**
     * @return list<string>
     */
    private function historicalLines(ArchitectureGuidance $guidance, ArchitectureIntelligence $intel): array
    {
        $lines = [];
        if ($guidance->evidence->similarImprovements > 0) {
            $lines[] = sprintf(
                '%d successful %s improvement%s in memory',
                $guidance->evidence->similarImprovements,
                $guidance->concept->label,
                $guidance->evidence->similarImprovements === 1 ? '' : 's',
            );
        }
        if ($guidance->evidence->resolvedIssues > 0) {
            $lines[] = sprintf('%d related issues resolved previously', $guidance->evidence->resolvedIssues);
        }
        foreach ($intel->commonPatterns as $pattern) {
            if ($pattern->concept->id === $guidance->concept->id) {
                $lines[] = sprintf(
                    'Pattern success rate %.0f%% · avg health %+0.0f',
                    $pattern->successRate * 100,
                    $pattern->averageHealthImpact,
                );
                break;
            }
        }

        return $lines !== [] ? $lines : ['Limited historical evidence — treat as a soft opportunity.'];
    }

    /**
     * @return list<string>
     */
    private function directionLines(ArchitectureGuidance $guidance, ?ArchitectureStandard $standard): array
    {
        $lines = [];
        if ($standard !== null) {
            $lines[] = $standard->principle;
        }
        $lines[] = 'Restore / strengthen '.$guidance->concept->label.' boundary';
        if ($guidance->evidence->healthDeltaAverage > 0) {
            $lines[] = sprintf(
                'Expected direction: historically about %+0.0f health on similar improvements',
                $guidance->evidence->healthDeltaAverage,
            );
        }

        return $lines;
    }

    /**
     * @param  list<ImprovementStory>  $stories
     * @param  list<ArchitectureEvent>  $events
     * @return list<array<string, mixed>>
     */
    private function relatedHistoryEntries(array $stories, array $events, ArchitectureGuidance $guidance): array
    {
        $entries = [];
        foreach (array_slice($stories, 0, 3) as $story) {
            $entries[] = [
                'type' => 'story',
                'title' => $story->decision,
                'problem' => $story->problem,
                'result' => $story->result,
                'occurred_at' => $story->occurredAt,
            ];
        }

        if ($entries === []) {
            foreach (array_reverse($events) as $event) {
                if ($event->type !== ArchitectureEventType::SessionCompleted) {
                    continue;
                }
                $entries[] = [
                    'type' => 'session',
                    'title' => (string) ($event->payload['goal'] ?? 'Improvement'),
                    'problem' => $guidance->concept->label,
                    'result' => sprintf(
                        'Health %s → %s',
                        (string) ($event->payload['health_before'] ?? '?'),
                        (string) ($event->payload['health_after'] ?? '?'),
                    ),
                    'occurred_at' => $event->occurredAt,
                ];
                if (count($entries) >= 3) {
                    break;
                }
            }
        }

        return $entries;
    }
}
