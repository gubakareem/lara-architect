<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 9 — Architecture Evolution projectors.
 * Trajectory · Momentum · Regressions — evidence first, learning not blame.
 */
final class ArchitectureEvolutionService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureVocabulary $vocabulary = new ArchitectureVocabulary,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureGovernanceService $governance = new ArchitectureGovernanceService,
    ) {}

    public function evolve(string $projectRoot, int $days = 180): ArchitectureEvolution
    {
        $intel = $this->intelligence->analyze($projectRoot, min(90, $days));
        $gov = $this->governance->assess($projectRoot, min(90, $days));
        $events = $this->memory->allEvents($projectRoot, 3000);

        $direction = $this->projectDirection($gov, $intel);
        $momentum = $this->projectMomentum($gov, $intel);
        $trajectories = $this->projectTrajectories($events, $gov, $days);
        $regressions = $this->projectRegressions($intel);

        $summary = $this->summarize($direction, $momentum, $regressions);

        return new ArchitectureEvolution(
            direction: $direction,
            momentum: $momentum,
            trajectories: $trajectories,
            regressions: $regressions,
            summary: $summary,
        );
    }

    private function projectDirection(
        ArchitectureGovernance $gov,
        ArchitectureIntelligence $intel,
    ): ?ArchitectureDirection {
        $top = $gov->alignments[0] ?? null;
        if ($top === null && $intel->commonPatterns !== []) {
            $pattern = $intel->commonPatterns[0];
            [$from, $to, $reasons] = $this->directionShape($pattern->concept->id);

            return new ArchitectureDirection(
                concept: $pattern->concept,
                statement: 'Current direction: strengthen '.$pattern->concept->label.' where history already proved value.',
                supportingImprovements: $pattern->frequency,
                expectedOutcomes: [
                    'Clearer ownership boundaries',
                    'Lower coupling',
                    'Higher testability',
                ],
                standardVersion: '1.0',
                from: $from,
                to: $to,
                reasons: $reasons,
            );
        }

        if ($top === null) {
            return null;
        }

        $outcomes = match ($top->standard->concept->id) {
            ArchitectureVocabulary::SERVICE_EXTRACTION => [
                'Move business logic from controllers into services',
                'Lower controller coupling',
                'Higher testability of domain behavior',
            ],
            ArchitectureVocabulary::REQUEST_VALIDATION => [
                'Keep validation at the HTTP edge',
                'Thinner controllers',
            ],
            default => [
                'Preserve '.$top->standard->concept->label,
                'Reduce architectural drift',
            ],
        };
        [$from, $to, $reasons] = $this->directionShape($top->standard->concept->id);

        return new ArchitectureDirection(
            concept: $top->standard->concept,
            statement: sprintf(
                'Current direction: %s (Standard v%s).',
                $top->standard->principle,
                $top->standard->version,
            ),
            supportingImprovements: $top->improvementsCompleted,
            expectedOutcomes: $outcomes,
            standardVersion: $top->standard->version,
            from: $from,
            to: $to,
            reasons: $reasons,
        );
    }

    /**
     * @return array{0: string, 1: string, 2: list<string>}
     */
    private function directionShape(string $conceptId): array
    {
        return match ($conceptId) {
            ArchitectureVocabulary::SERVICE_EXTRACTION => [
                'controller_owned_logic',
                'service_owned_logic',
                ['reduce coupling', 'improve test boundaries'],
            ],
            ArchitectureVocabulary::REQUEST_VALIDATION => [
                'controller_validation',
                'form_request_validation',
                ['keep HTTP edge thin', 'centralize validation'],
            ],
            ArchitectureVocabulary::REPOSITORY_PORT => [
                'direct_eloquent_access',
                'repository_port',
                ['isolate persistence', 'swap storage adapters safely'],
            ],
            default => [
                'architectural_debt',
                $conceptId,
                ['preserve valued boundaries', 'reduce drift'],
            ],
        };
    }

    private function projectMomentum(
        ArchitectureGovernance $gov,
        ArchitectureIntelligence $intel,
    ): ArchitectureMomentum {
        $completed = 0;
        $drift = 0;
        foreach ($gov->alignments as $alignment) {
            $completed += $alignment->improvementsCompleted;
            $drift += $alignment->remainingDrift;
        }
        if ($drift === 0) {
            $drift = count($intel->driftSignals);
        }

        $level = match (true) {
            $completed > $drift && $completed > 0 => 'positive',
            $drift > $completed => 'negative',
            $completed === 0 && $drift === 0 => 'neutral',
            default => 'neutral',
        };

        $reason = match ($level) {
            'positive' => sprintf(
                'More improvements completed (%d) than drift introduced (%d).',
                $completed,
                $drift,
            ),
            'negative' => sprintf(
                'Drift signals (%d) outpace completed improvements (%d).',
                $drift,
                $completed,
            ),
            default => 'Improvements and drift are roughly balanced — direction is forming.',
        };

        return new ArchitectureMomentum($level, $reason, $completed, $drift);
    }

    /**
     * @param  list<ArchitectureEvent>  $events
     * @return list<ArchitectureTrajectory>
     */
    private function projectTrajectories(array $events, ArchitectureGovernance $gov, int $days): array
    {
        $cutoff = time() - ($days * 86400);
        /** @var array<string, array<string, array{improvements: int, health_sum: int}>> $byConceptPeriod */
        $byConceptPeriod = [];

        foreach ($events as $event) {
            if ($event->type !== ArchitectureEventType::SessionCompleted) {
                continue;
            }
            $ts = strtotime($event->occurredAt) ?: 0;
            if ($ts < $cutoff) {
                continue;
            }
            $goal = (string) ($event->payload['goal'] ?? '');
            if ($goal === '') {
                continue;
            }
            $concept = $this->vocabulary->canonicalize($goal);
            $period = gmdate('Y-m', $ts);
            $before = (int) ($event->payload['health_before'] ?? 0);
            $after = (int) ($event->payload['health_after'] ?? $before);
            $byConceptPeriod[$concept->id][$period] ??= ['improvements' => 0, 'health_sum' => 0];
            $byConceptPeriod[$concept->id][$period]['improvements']++;
            $byConceptPeriod[$concept->id][$period]['health_sum'] += ($after - $before);
        }

        $trajectories = [];
        $focusIds = array_map(
            static fn (StandardAlignment $a): string => $a->standard->concept->id,
            array_slice($gov->alignments, 0, 3),
        );
        if ($focusIds === []) {
            $focusIds = array_slice(array_keys($byConceptPeriod), 0, 2);
        }

        foreach ($focusIds as $conceptId) {
            $periods = $byConceptPeriod[$conceptId] ?? [];
            if ($periods === []) {
                continue;
            }
            ksort($periods);
            $concept = $this->vocabulary->concept($conceptId)
                ?? $this->vocabulary->canonicalize($conceptId);
            $points = [];
            $running = 50;
            foreach ($periods as $period => $row) {
                $delta = $row['health_sum'];
                $running = max(0, min(100, $running + max(1, $delta) + $row['improvements']));
                $points[] = [
                    'period' => $period,
                    'alignment' => $running,
                    'improvements' => $row['improvements'],
                ];
            }

            $first = $points[0]['alignment'] ?? 0;
            $last = $points[array_key_last($points)]['alignment'] ?? 0;
            $trajectories[] = new ArchitectureTrajectory(
                concept: $concept,
                points: $points,
                summary: sprintf(
                    '%s · %d → %d across %d period%s',
                    $concept->label,
                    $first,
                    $last,
                    count($points),
                    count($points) === 1 ? '' : 's',
                ),
            );
        }

        return array_slice($trajectories, 0, 3);
    }

    /**
     * @return list<ArchitectureRegression>
     */
    private function projectRegressions(ArchitectureIntelligence $intel): array
    {
        $regressions = [];

        foreach ($intel->driftSignals as $drift) {
            if ($drift->driftKind !== 'boundary_weakening') {
                continue;
            }
            $concept = $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
                ?? $this->vocabulary->canonicalize('Service Extraction');
            $regressions[] = new ArchitectureRegression(
                signal: 'Service boundary regression',
                observed: $drift->observed(),
                previousPattern: 'Resolved previously by introducing a service boundary',
                evidence: $drift->relatedEvents,
                relatedConcept: $concept,
            );
        }

        foreach ($intel->repeatedProblems as $problem) {
            if ($problem->remaining <= 0 || $problem->resolved <= 0) {
                continue;
            }
            $regressions[] = new ArchitectureRegression(
                signal: 'Recurring problem after prior fixes',
                observed: sprintf(
                    '%s: %d resolved earlier, %d remaining',
                    $problem->concept->label,
                    $problem->resolved,
                    $problem->remaining,
                ),
                previousPattern: 'Previously addressed via '.$this->vocabulary->canonicalize(
                    match ($problem->concept->id) {
                        ArchitectureVocabulary::DIRECT_MODEL_USAGE,
                        ArchitectureVocabulary::CONTROLLER_DEPENDENCY => 'Service Extraction',
                        default => $problem->concept->label,
                    },
                )->label,
                evidence: [
                    sprintf('%d occurrences', $problem->occurrences),
                    sprintf('%d resolved', $problem->resolved),
                    sprintf('%d remaining', $problem->remaining),
                ],
                relatedConcept: $problem->concept,
            );
        }

        return array_slice($regressions, 0, 5);
    }

    /**
     * @param  list<ArchitectureRegression>  $regressions
     */
    private function summarize(
        ?ArchitectureDirection $direction,
        ArchitectureMomentum $momentum,
        array $regressions,
    ): string {
        if ($direction !== null && $momentum->level === 'positive') {
            return sprintf(
                'Evolving toward %s with positive momentum (%s).',
                $direction->concept->label,
                $momentum->reason,
            );
        }
        if ($regressions !== []) {
            return 'Evolution shows learning signals — regressions detected with prior successful patterns to reuse.';
        }
        if ($direction !== null) {
            return $direction->statement;
        }

        return 'Not enough memory yet to describe architectural evolution.';
    }
}
