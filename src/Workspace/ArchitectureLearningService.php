<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 10 — Architecture Learning projectors.
 * Given everything we know, which paths historically worked best?
 * LearningEvidence justifies every claim — not ML, not AI.
 */
final class ArchitectureLearningService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureVocabulary $vocabulary = new ArchitectureVocabulary,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureEvolutionService $evolution = new ArchitectureEvolutionService,
    ) {}

    public function learn(string $projectRoot, int $days = 180): ArchitectureLearning
    {
        $intel = $this->intelligence->analyze($projectRoot, min(90, $days));
        $evo = $this->evolution->evolve($projectRoot, $days);
        $events = $this->memory->allEvents($projectRoot, 3000);

        $patterns = $this->successfulPatterns($intel, $events);
        $risks = $this->evolutionRisks($evo, $intel);
        $paths = $this->preferredPaths($intel, $events);
        $intents = $this->recentIntents($events);

        $summary = $this->summarize($patterns, $risks, $paths);

        return new ArchitectureLearning(
            question: 'What has this system learned about itself?',
            summary: $summary,
            successfulPatterns: $patterns,
            risks: $risks,
            preferredPaths: $paths,
            recentIntents: $intents,
        );
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     * @return list<SuccessfulEvolutionPattern>
     */
    private function successfulPatterns(ArchitectureIntelligence $intel, array $events): array
    {
        $contextsByConcept = $this->contextsByConcept($events);
        $out = [];
        foreach ($intel->commonPatterns as $pattern) {
            if ($pattern->frequency < 1) {
                continue;
            }
            $successful = (int) round($pattern->frequency * max(0.0, $pattern->successRate));
            $contexts = $contextsByConcept[$pattern->concept->id] ?? [];
            $evidence = new LearningEvidence(
                attempts: $pattern->frequency,
                successful: max($successful, $pattern->averageHealthImpact > 0 ? $pattern->frequency : $successful),
                contexts: $contexts,
                averageHealthDelta: $pattern->averageHealthImpact,
            );
            $out[] = new SuccessfulEvolutionPattern(
                concept: $pattern->concept,
                applied: $pattern->frequency,
                successRate: $pattern->successRate,
                averageHealthImpact: $pattern->averageHealthImpact,
                summary: sprintf(
                    'Most successful improvement: %s — applied %d time%s · success %.0f%% · avg health %+0.0f',
                    $pattern->concept->label,
                    $pattern->frequency,
                    $pattern->frequency === 1 ? '' : 's',
                    $pattern->successRate * 100,
                    $pattern->averageHealthImpact,
                ),
                evidence: $evidence,
            );
        }

        usort(
            $out,
            static fn (SuccessfulEvolutionPattern $a, SuccessfulEvolutionPattern $b): int => ($b->successRate * $b->applied) <=> ($a->successRate * $a->applied),
        );

        return array_slice($out, 0, 5);
    }

    /**
     * @return list<EvolutionRisk>
     */
    private function evolutionRisks(ArchitectureEvolution $evo, ArchitectureIntelligence $intel): array
    {
        $risks = [];
        foreach ($evo->regressions as $regression) {
            $count = max(1, count($regression->evidence));
            $risks[] = new EvolutionRisk(
                risk: $regression->signal,
                previousRegressions: $count,
                evidenceLines: array_merge([$regression->observed], $regression->evidence),
                relatedConcept: $regression->relatedConcept,
                learningEvidence: new LearningEvidence(
                    attempts: $count,
                    successful: 0,
                    contexts: [],
                    averageHealthDelta: 0.0,
                ),
            );
        }

        foreach ($intel->repeatedProblems as $problem) {
            if ($problem->remaining <= 0 || $problem->resolved < 1) {
                continue;
            }
            $risks[] = new EvolutionRisk(
                risk: sprintf('%s tends to return after prior fixes', $problem->concept->label),
                previousRegressions: $problem->remaining,
                evidenceLines: [
                    sprintf('%d occurrences', $problem->occurrences),
                    sprintf('%d resolved', $problem->resolved),
                    sprintf('%d remaining', $problem->remaining),
                ],
                relatedConcept: $problem->concept,
                learningEvidence: new LearningEvidence(
                    attempts: $problem->occurrences,
                    successful: $problem->resolved,
                    contexts: [],
                    averageHealthDelta: 0.0,
                ),
            );
        }

        return array_slice($risks, 0, 5);
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     * @return list<PreferredPath>
     */
    private function preferredPaths(ArchitectureIntelligence $intel, array $events): array
    {
        /** @var array<string, array{solution: string, problem: string, count: int, success: int, contexts: array<string, true>, health_sum: int}> $pairs */
        $pairs = [];

        foreach ($events as $event) {
            if ($event->type !== ArchitectureEventType::SessionCompleted) {
                continue;
            }
            $corr = $event->correlation->mergePayload($event->payload);
            $goal = (string) ($event->payload['goal'] ?? '');
            if ($goal === '') {
                continue;
            }
            $solution = $this->vocabulary->canonicalize($goal);
            $issueTitle = 'Architecture issue';
            if ($corr->issueId !== null) {
                foreach ($events as $candidate) {
                    if ($candidate->type !== ArchitectureEventType::IssueDetected) {
                        continue;
                    }
                    $c = $candidate->correlation->mergePayload($candidate->payload);
                    if ($c->issueId === $corr->issueId) {
                        $issueTitle = (string) ($candidate->payload['title'] ?? $issueTitle);

                        break;
                    }
                }
            }
            $problem = $this->vocabulary->canonicalize($issueTitle);
            $key = $problem->id.'|'.$solution->id;
            $context = $event->context !== '' ? $event->context : 'unknown';
            $pairs[$key] ??= [
                'solution' => $solution->id,
                'problem' => $problem->label,
                'count' => 0,
                'success' => 0,
                'contexts' => [],
                'health_sum' => 0,
            ];
            $pairs[$key]['count']++;
            $pairs[$key]['contexts'][$context] = true;
            $before = (int) ($event->payload['health_before'] ?? 0);
            $after = (int) ($event->payload['health_after'] ?? $before);
            $pairs[$key]['health_sum'] += ($after - $before);
            if ($after >= $before) {
                $pairs[$key]['success']++;
            }
        }

        if ($pairs === []) {
            $paths = [];
            foreach ($intel->repeatedProblems as $problem) {
                $solution = match ($problem->concept->id) {
                    ArchitectureVocabulary::DIRECT_MODEL_USAGE,
                    ArchitectureVocabulary::CONTROLLER_DEPENDENCY => $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
                        ?? $this->vocabulary->canonicalize('Service Extraction'),
                    default => $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
                        ?? $problem->concept,
                };
                $rate = $problem->occurrences > 0 ? $problem->resolved / $problem->occurrences : 0.0;
                $paths[] = new PreferredPath(
                    whenIssue: $problem->concept->label,
                    preferredSolution: $solution,
                    timesChosen: max(1, $problem->resolved),
                    successRate: round($rate, 2),
                    evidence: new LearningEvidence(
                        attempts: $problem->occurrences,
                        successful: $problem->resolved,
                        contexts: [],
                        averageHealthDelta: 0.0,
                    ),
                    insteadOf: 'ad-hoc local fixes',
                );
            }

            return array_slice($paths, 0, 5);
        }

        $paths = [];
        foreach ($pairs as $row) {
            $solution = $this->vocabulary->concept($row['solution'])
                ?? $this->vocabulary->canonicalize($row['solution']);
            $rate = $row['count'] > 0 ? $row['success'] / $row['count'] : 0.0;
            $avg = $row['count'] > 0 ? $row['health_sum'] / $row['count'] : 0.0;
            $contexts = array_keys($row['contexts']);
            sort($contexts);
            $paths[] = new PreferredPath(
                whenIssue: (string) $row['problem'],
                preferredSolution: $solution,
                timesChosen: $row['count'],
                successRate: round($rate, 2),
                evidence: new LearningEvidence(
                    attempts: $row['count'],
                    successful: $row['success'],
                    contexts: $contexts,
                    averageHealthDelta: round($avg, 1),
                ),
                insteadOf: $solution->id === ArchitectureVocabulary::SERVICE_EXTRACTION
                    ? 'Repository introduction'
                    : 'unstructured refactor',
            );
        }

        usort(
            $paths,
            static fn (PreferredPath $a, PreferredPath $b): int => ($b->timesChosen * $b->successRate) <=> ($a->timesChosen * $a->successRate),
        );

        return array_slice($paths, 0, 5);
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     * @return array<string, list<string>>
     */
    private function contextsByConcept(array $events): array
    {
        /** @var array<string, array<string, true>> $map */
        $map = [];
        foreach ($events as $event) {
            if ($event->type !== ArchitectureEventType::SessionCompleted
                && $event->type !== ArchitectureEventType::ProposalCreated) {
                continue;
            }
            $text = (string) ($event->payload['goal'] ?? $event->payload['title'] ?? '');
            if ($text === '') {
                continue;
            }
            $id = $this->vocabulary->canonicalize($text)->id;
            $context = $event->context !== '' ? $event->context : 'unknown';
            $map[$id][$context] = true;
        }
        $out = [];
        foreach ($map as $id => $contexts) {
            $list = array_keys($contexts);
            sort($list);
            $out[$id] = $list;
        }

        return $out;
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     * @return list<ArchitectureChangeIntent>
     */
    private function recentIntents(array $events): array
    {
        $intents = [];
        foreach (array_reverse($events) as $event) {
            if ($event->type !== ArchitectureEventType::ChangeIntentRecorded) {
                continue;
            }
            $intents[] = ArchitectureChangeIntent::fromPayload($event->payload, $event->occurredAt);
            if (count($intents) >= 5) {
                break;
            }
        }

        return $intents;
    }

    /**
     * @param  list<SuccessfulEvolutionPattern>  $patterns
     * @param  list<EvolutionRisk>  $risks
     * @param  list<PreferredPath>  $paths
     */
    private function summarize(array $patterns, array $risks, array $paths): string
    {
        if ($patterns !== []) {
            $top = $patterns[0];

            return sprintf(
                'This codebase has learned that %s works best (%.0f%% success across %d applications).',
                $top->concept->label,
                $top->successRate * 100,
                $top->applied,
            );
        }
        if ($paths !== []) {
            return $paths[0]->summary;
        }
        if ($risks !== []) {
            return 'Learning signals include evolution risks — reinforce boundaries that previously slipped.';
        }

        return 'Not enough history yet for strong architecture learning.';
    }
}
