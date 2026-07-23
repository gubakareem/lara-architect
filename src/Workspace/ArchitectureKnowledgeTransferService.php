<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 12 — Architecture Knowledge Transfer.
 * Living history a new developer can learn from — not generated docs.
 */
final class ArchitectureKnowledgeTransferService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureCollaborationService $collaboration = new ArchitectureCollaborationService,
        private readonly ArchitectureLearningService $learning = new ArchitectureLearningService,
        private readonly ArchitectureEvolutionService $evolution = new ArchitectureEvolutionService,
        private readonly ArchitectureDecisionMemory $decisions = new ArchitectureDecisionMemory,
        private readonly ArchitectureOwnershipService $ownership = new ArchitectureOwnershipService,
        private readonly ArchitectureKnowledgeMapService $knowledgeMap = new ArchitectureKnowledgeMapService,
        private readonly ArchitectureStoryProjector $stories = new ArchitectureStoryProjector,
    ) {}

    public function transfer(string $projectRoot, string $context, int $days = 180): ArchitectureKnowledgeTransfer
    {
        $area = $this->areaName($context);
        $ownership = $this->ownership->forArea($projectRoot, $area)
            ?? $this->ownership->forArea($projectRoot, $context);
        $onboarding = $this->onboarding($projectRoot, $area, $ownership, $days);
        $brief = $this->brief($projectRoot, $context);
        $map = $this->knowledgeMap->map($projectRoot, $days);

        $summary = match (true) {
            $onboarding->importantDecisions !== [] || $brief->importantDecisions !== [] => sprintf(
                'Living knowledge for %s — %d decision%s · %d recent change%s.',
                $area,
                count($onboarding->importantDecisions),
                count($onboarding->importantDecisions) === 1 ? '' : 's',
                $brief->improvementCount,
                $brief->improvementCount === 1 ? '' : 's',
            ),
            default => 'Architecture knowledge will accumulate as improvements, notes, and rationales are recorded.',
        };

        return new ArchitectureKnowledgeTransfer(
            question: 'How can a new developer understand this codebase faster?',
            summary: $summary,
            onboarding: $onboarding,
            brief: $brief,
            knowledgeMap: $map,
            ownership: $ownership,
        );
    }

    public function onboarding(
        string $projectRoot,
        string $area,
        ?ArchitectureOwnership $ownership = null,
        int $days = 180,
    ): ArchitectureOnboarding {
        $ownership ??= $this->ownership->forArea($projectRoot, $area);
        $learning = $this->learning->learn($projectRoot, $days);
        $evolution = $this->evolution->evolve($projectRoot, $days);
        $collab = $this->collaboration->forContext($projectRoot, $area, 40);

        $direction = $evolution->direction !== null
            ? $evolution->direction->statement
            : ($learning->preferredPaths[0]->preferredSolution->label ?? 'Keep improving toward clearer boundaries');

        $decisions = [];
        foreach ($collab->rationales as $rationale) {
            $line = trim($rationale->question) !== ''
                ? $rationale->question.': '.$rationale->reason
                : $rationale->reason;
            if ($rationale->tradeoff !== '') {
                $line .= ' (tradeoff: '.$rationale->tradeoff.')';
            }
            $decisions[] = $line;
            if (count($decisions) >= 5) {
                break;
            }
        }
        foreach ($collab->notes as $note) {
            if (count($decisions) >= 5) {
                break;
            }
            $decisions[] = $note->body;
        }

        $recent = [];
        if ($evolution->trajectories !== []) {
            $recent[] = $evolution->trajectories[0]->summary;
        }
        foreach ($learning->successfulPatterns as $pattern) {
            $recent[] = $pattern->summary;
            if (count($recent) >= 3) {
                break;
            }
        }

        $risks = [];
        foreach ($learning->risks as $risk) {
            $risks[] = $risk->risk;
            if (count($risks) >= 3) {
                break;
            }
        }

        $summary = $ownership !== null
            ? sprintf('Owned by %s%s.', $ownership->ownedBy, $ownership->maintainedBy !== '' ? ' · maintained by '.$ownership->maintainedBy : '')
            : 'Ownership not recorded yet — knowledge still accumulates from history.';

        return new ArchitectureOnboarding(
            area: $area,
            welcome: sprintf('Welcome to %s', $area),
            currentDirection: $direction,
            importantDecisions: $decisions,
            recentEvolution: $recent,
            knownRisks: $risks,
            ownership: $ownership,
            summary: $summary,
        );
    }

    public function brief(string $projectRoot, string $context): ContextBrief
    {
        $events = $this->memory->eventsForContext($projectRoot, $context, 200);
        $storyList = $this->stories->stories($events, $context);
        $decisionList = $this->decisions->forContext($projectRoot, $context);
        $fileNeedle = str_ends_with(strtolower($context), '.php') ? $context : $context.'.php';
        $fileDecisions = $this->decisions->forFile($projectRoot, $fileNeedle, $context);
        $collab = $this->collaboration->forContext($projectRoot, $context, 20);

        $why = 'No recorded origin yet — open an Architecture Session or attach a rationale.';
        if ($storyList !== []) {
            $story = $storyList[0];
            $why = sprintf(
                'Created during %s improvement. Decision: %s.',
                $story->context,
                $story->decision,
            );
        } elseif ($fileDecisions !== []) {
            $why = $fileDecisions[0]->answer;
        }

        $important = [];
        foreach ([...$fileDecisions, ...$decisionList] as $decision) {
            $important[] = $decision->answer;
            if (count($important) >= 4) {
                break;
            }
        }
        foreach ($collab->rationales as $rationale) {
            if (count($important) >= 5) {
                break;
            }
            $important[] = $rationale->question.': '.$rationale->reason;
        }
        foreach ($collab->notes as $note) {
            if (count($important) >= 5) {
                break;
            }
            $important[] = $note->body;
        }

        $recent = [];
        $improvements = 0;
        foreach (array_reverse($events) as $event) {
            if ($event->type === ArchitectureEventType::SessionCompleted) {
                $improvements++;
                $recent[] = (string) ($event->payload['goal'] ?? 'Architecture improvement');
                if (count($recent) >= 3) {
                    break;
                }
            }
        }

        return new ContextBrief(
            context: $context,
            whyItExists: $why,
            importantDecisions: array_values(array_unique($important)),
            recentChanges: $recent,
            improvementCount: $improvements > 0 ? $improvements : count($recent),
            summary: sprintf(
                '%s — %d important decision%s · %d recent improvement%s.',
                $context,
                count($important),
                count($important) === 1 ? '' : 's',
                count($recent),
                count($recent) === 1 ? '' : 's',
            ),
        );
    }

    private function areaName(string $context): string
    {
        $base = basename(str_replace('\\', '/', $context));
        $base = preg_replace('/\.php$/i', '', $base) ?? $base;
        $base = preg_replace('/(Controller|Service|Repository|Request)$/i', '', $base) ?? $base;

        return $base !== '' ? $base : $context;
    }
}
