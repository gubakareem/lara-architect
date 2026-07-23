<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Events → Intelligence Projectors → typed Read Models.
 * Facts stay immutable; explainable interpretations can evolve.
 */
final class ArchitectureIntelligenceService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureBaselineStore $baselines = new ArchitectureBaselineStore,
        private readonly ArchitectureVocabulary $vocabulary = new ArchitectureVocabulary,
    ) {}

    public function analyze(string $projectRoot, int $days = 30): ArchitectureIntelligence
    {
        $events = $this->memory->allEvents($projectRoot, 2000);
        $timeRange = $days.'_days';
        $cutoff = time() - ($days * 86400);
        $from = gmdate('c', $cutoff);
        $to = gmdate('c');

        /** @var array<string, array{delta: int, before: ?int, after: ?int, sessions: int, contexts: array<string, true>, events: int, main: string}> $healthByContext */
        $healthByContext = [];
        /** @var array<string, array{count: int, resolved: int, contexts: array<string, true>, events: int}> $issueStats */
        $issueStats = [];
        /** @var array<string, string> $issueIdTitles */
        $issueIdTitles = [];
        /** @var array<string, array{count: int, contexts: array<string, true>, events: int, health_sum: int, successes: int}> $patterns */
        $patterns = [];
        /** @var array<string, int> $failedByContext */
        $failedByContext = [];
        /** @var array<string, array<string, int>> $openIssues */
        $openIssues = [];
        $serviceExtractions = 0;
        $directModelRecent = 0;
        $totalEventsInRange = 0;

        foreach ($events as $event) {
            $ts = strtotime($event->occurredAt) ?: 0;
            $recent = $ts >= $cutoff;
            if ($recent) {
                $totalEventsInRange++;
            }
            $context = $event->context !== '' ? $event->context : 'unknown';
            $corr = $event->correlation->mergePayload($event->payload);

            match ($event->type) {
                ArchitectureEventType::IssueDetected => (function () use (
                    $event,
                    $context,
                    &$issueStats,
                    &$openIssues,
                    &$issueIdTitles,
                    &$directModelRecent,
                    $recent,
                    $corr,
                ): void {
                    $title = (string) ($event->payload['title'] ?? 'Unknown issue');
                    $canonical = $this->vocabulary->canonicalize($title)->label;
                    $issueStats[$canonical] ??= ['count' => 0, 'resolved' => 0, 'contexts' => [], 'events' => 0];
                    $issueStats[$canonical]['count']++;
                    $issueStats[$canonical]['events']++;
                    $issueStats[$canonical]['contexts'][$context] = true;
                    if ($corr->issueId !== null) {
                        $issueIdTitles[$corr->issueId] = $canonical;
                    }
                    if ($recent) {
                        $openIssues[$context][$canonical] = ($openIssues[$context][$canonical] ?? 0) + 1;
                        if ($this->vocabulary->is($title, ArchitectureVocabulary::DIRECT_MODEL_USAGE)
                            || $this->vocabulary->is($title, ArchitectureVocabulary::CONTROLLER_DEPENDENCY)) {
                            $directModelRecent++;
                        }
                    }
                })(),
                ArchitectureEventType::SessionCompleted => (function () use (
                    $event,
                    $context,
                    &$healthByContext,
                    &$patterns,
                    &$openIssues,
                    &$issueStats,
                    &$issueIdTitles,
                    &$serviceExtractions,
                    $recent,
                    $corr,
                ): void {
                    if (! $recent) {
                        return;
                    }
                    $before = (int) ($event->payload['health_before'] ?? 0);
                    $after = (int) ($event->payload['health_after'] ?? $before);
                    $delta = $after - $before;
                    $goal = (string) ($event->payload['goal'] ?? 'Improvement');
                    $concept = $this->vocabulary->canonicalize($goal);
                    $pattern = $concept->label;

                    $healthByContext[$context] ??= [
                        'delta' => 0,
                        'before' => $before,
                        'after' => $after,
                        'sessions' => 0,
                        'contexts' => [],
                        'events' => 0,
                        'main' => $pattern,
                    ];
                    $healthByContext[$context]['delta'] += $delta;
                    $healthByContext[$context]['sessions']++;
                    $healthByContext[$context]['events']++;
                    $healthByContext[$context]['contexts'][$context] = true;
                    $healthByContext[$context]['before'] = min((int) $healthByContext[$context]['before'], $before);
                    $healthByContext[$context]['after'] = max((int) $healthByContext[$context]['after'], $after);
                    if ($delta > 0) {
                        $healthByContext[$context]['main'] = $pattern;
                    }

                    $patterns[$pattern] ??= [
                        'count' => 0,
                        'contexts' => [],
                        'events' => 0,
                        'health_sum' => 0,
                        'successes' => 0,
                    ];
                    $patterns[$pattern]['count']++;
                    $patterns[$pattern]['events']++;
                    $patterns[$pattern]['contexts'][$context] = true;
                    $patterns[$pattern]['health_sum'] += $delta;
                    if ($delta > 0) {
                        $patterns[$pattern]['successes']++;
                    }

                    if ($concept->id === ArchitectureVocabulary::SERVICE_EXTRACTION) {
                        $serviceExtractions++;
                    }

                    if ($corr->issueId !== null && isset($issueIdTitles[$corr->issueId])) {
                        $title = $issueIdTitles[$corr->issueId];
                        $issueStats[$title]['resolved'] = ($issueStats[$title]['resolved'] ?? 0) + 1;
                    } else {
                        foreach (array_keys($issueStats) as $title) {
                            if ($this->titlesAlign($title, $goal)) {
                                $issueStats[$title]['resolved']++;

                                break;
                            }
                        }
                    }

                    unset($openIssues[$context]);
                })(),
                ArchitectureEventType::VerificationFailed => (function () use ($context, &$failedByContext, $recent): void {
                    if ($recent) {
                        $failedByContext[$context] = ($failedByContext[$context] ?? 0) + 1;
                    }
                })(),
                ArchitectureEventType::ProposalCreated => (function () use ($event, $context, &$patterns, $recent): void {
                    if (! $recent) {
                        return;
                    }
                    $title = (string) ($event->payload['title'] ?? '');
                    if ($title === '') {
                        return;
                    }
                    $pattern = $this->vocabulary->canonicalize($title)->label;
                    $patterns[$pattern] ??= [
                        'count' => 0,
                        'contexts' => [],
                        'events' => 0,
                        'health_sum' => 0,
                        'successes' => 0,
                    ];
                    $patterns[$pattern]['count']++;
                    $patterns[$pattern]['events']++;
                    $patterns[$pattern]['contexts'][$context] = true;
                })(),
                default => null,
            };
        }

        $mostImproved = $this->projectMostImproved($healthByContext, $timeRange, $from, $to);
        $repeated = $this->projectRepeated($issueStats, $timeRange, $from, $to);
        $drift = $this->projectDrift(
            $failedByContext,
            $openIssues,
            $serviceExtractions,
            $directModelRecent,
            $this->baselines->latest($projectRoot),
            $timeRange,
            $from,
            $to,
            $totalEventsInRange,
        );
        $common = $this->projectPatterns($patterns, $timeRange, $from, $to);
        $baseline = $this->baselines->latest($projectRoot);

        return new ArchitectureIntelligence(
            mostImprovedAreas: $mostImproved,
            repeatedProblems: $repeated,
            driftSignals: $drift,
            commonPatterns: $common,
            summary: $this->summarize($mostImproved, $repeated, $drift, $baseline),
        );
    }

    /**
     * @param  array<string, array{delta: int, before: ?int, after: ?int, sessions: int, contexts: array<string, true>, events: int, main: string}>  $healthByContext
     * @return list<MostImprovedAreaInsight>
     */
    private function projectMostImproved(array $healthByContext, string $timeRange, string $from, string $to): array
    {
        uasort($healthByContext, static fn (array $a, array $b): int => $b['delta'] <=> $a['delta']);
        $out = [];

        foreach (array_slice($healthByContext, 0, 5, true) as $context => $row) {
            if ($row['delta'] <= 0) {
                continue;
            }
            $evidence = new InsightEvidence(max(1, $row['events']), max(1, count($row['contexts'])), $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence, supportingSignals: $row['sessions']);
            $concept = $this->vocabulary->canonicalize($row['main']);
            $beforeLabel = $row['before'] !== null ? (string) $row['before'] : 'unknown';
            $afterLabel = $row['after'] !== null ? (string) $row['after'] : 'unknown';

            $out[] = new MostImprovedAreaInsight(
                context: $context,
                healthBefore: $row['before'],
                healthAfter: $row['after'],
                healthDelta: $row['delta'],
                improvements: $row['sessions'],
                mainImprovement: $concept,
                observed: sprintf(
                    '%s health moved %s → %s across %d completed improvement%s.',
                    $context,
                    $beforeLabel,
                    $afterLabel,
                    $row['sessions'],
                    $row['sessions'] === 1 ? '' : 's',
                ),
                whyItMatters: 'Concentrated gains show where architectural investment is paying off.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: 'Health '.$beforeLabel,
                    after: 'Health '.$afterLabel.' via '.$concept->label,
                    summary: 'Main improvement: '.$concept->label,
                ),
                confidenceDetail: $confidence,
            );
        }

        return $out;
    }

    /**
     * @param  array<string, array{count: int, resolved: int, contexts: array<string, true>, events: int}>  $issueStats
     * @return list<RepeatedProblemInsight>
     */
    private function projectRepeated(array $issueStats, string $timeRange, string $from, string $to): array
    {
        uasort($issueStats, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $out = [];

        foreach (array_slice($issueStats, 0, 5, true) as $title => $row) {
            if ($row['count'] < 2) {
                continue;
            }
            $resolved = min($row['resolved'], $row['count']);
            $remaining = max(0, $row['count'] - $resolved);
            $contextCount = count($row['contexts']);
            $concept = $this->vocabulary->canonicalize($title);
            $evidence = new InsightEvidence($row['events'], max(1, $contextCount), $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence, supportingSignals: $remaining > 0 ? 1 : 0);

            $out[] = new RepeatedProblemInsight(
                title: $title,
                concept: $concept,
                occurrences: $row['count'],
                resolved: $resolved,
                remaining: $remaining,
                contextCount: $contextCount,
                observed: sprintf(
                    '%s appeared %d time%s across %d context%s (%d resolved, %d remaining).',
                    $concept->label,
                    (int) $row['count'],
                    (int) $row['count'] === 1 ? '' : 's',
                    max(1, $contextCount),
                    $contextCount === 1 ? '' : 's',
                    $resolved,
                    $remaining,
                ),
                whyItMatters: $remaining > 0
                    ? 'This is a team learning signal — the same architectural mistake keeps returning.'
                    : 'This pattern was common historically; memory shows it has been cleared in recorded sessions.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: $row['count'].' detections',
                    after: $resolved.' resolved · '.$remaining.' remaining',
                ),
                confidenceDetail: $confidence,
            );
        }

        return $out;
    }

    /**
     * @param  array<string, int>  $failedByContext
     * @param  array<string, array<string, int>>  $openIssues
     * @return list<ArchitectureDriftInsight>
     */
    private function projectDrift(
        array $failedByContext,
        array $openIssues,
        int $serviceExtractions,
        int $directModelRecent,
        ?ArchitectureBaseline $baseline,
        string $timeRange,
        string $from,
        string $to,
        int $totalEventsInRange,
    ): array {
        $out = [];

        if ($serviceExtractions > 0 && $directModelRecent > 0) {
            $evidence = new InsightEvidence(
                $serviceExtractions + $directModelRecent,
                max(1, count($openIssues)),
                $timeRange,
                $from,
                $to,
            );
            $confidence = IntelligenceConfidence::derive($evidence, supportingSignals: $baseline !== null ? 1 : 0);
            $out[] = new ArchitectureDriftInsight(
                driftKind: 'boundary_weakening',
                signal: 'Service boundary weakening detected',
                baseline: $baseline,
                currentState: 'Controller → Model pressure returning alongside services',
                direction: 'regressing',
                relatedEvents: [
                    $serviceExtractions.' service extractions',
                    $directModelRecent.' direct-model style issues again',
                ],
                observed: sprintf(
                    'Memory recorded %d service-boundary improvement%s, then %d direct-model style issue%s again.',
                    $serviceExtractions,
                    $serviceExtractions === 1 ? '' : 's',
                    $directModelRecent,
                    $directModelRecent === 1 ? '' : 's',
                ),
                whyItMatters: 'A healthy system can regress — Memory detects movement, not only static violations.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: $baseline !== null
                        ? sprintf('Baseline health %d%% · %d issues', $baseline->health, $baseline->violations)
                        : 'Service boundaries improved in memory',
                    after: 'Direct model usage reappearing',
                ),
                confidenceDetail: $confidence,
            );
        }

        foreach ($failedByContext as $context => $count) {
            $evidence = new InsightEvidence($count, 1, $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence);
            $out[] = new ArchitectureDriftInsight(
                driftKind: 'verification_friction',
                signal: 'Verification friction: '.$context,
                baseline: $baseline,
                currentState: 'Verification failing under change',
                direction: 'unstable',
                relatedEvents: [$count.' verification_failed events'],
                observed: sprintf('%s failed verification %d time%s recently.', $context, $count, $count === 1 ? '' : 's'),
                whyItMatters: 'Failed gates mean intended improvements are not sticking.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: 'Improvements attempted',
                    after: 'Verification failed '.$count.' time(s)',
                ),
                confidenceDetail: $confidence,
                context: $context,
            );
        }

        foreach ($openIssues as $context => $titles) {
            $top = array_key_first($titles);
            if ($top === null) {
                continue;
            }
            $hits = (int) $titles[$top];
            $evidence = new InsightEvidence(max(1, $hits), 1, $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence);
            $out[] = new ArchitectureDriftInsight(
                driftKind: 'unresolved_issue',
                signal: 'Unresolved: '.$top,
                baseline: $baseline,
                currentState: $top.' still open in '.$context,
                direction: 'unresolved',
                relatedEvents: ['issue_detected without session_completed'],
                observed: sprintf('%s still shows unresolved “%s” in recent memory.', $context, $top),
                whyItMatters: 'Open architectural debt without a completed session is drift relative to intended improvements.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: $baseline !== null ? 'Baseline remembered '.$baseline->violations.' issues' : 'Issue detected',
                    after: 'Still open in '.$context,
                ),
                confidenceDetail: $confidence,
                context: $context,
            );
        }

        if ($out === [] && $baseline !== null && $totalEventsInRange === 0) {
            $evidence = new InsightEvidence(1, 1, $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence);
            $out[] = new ArchitectureDriftInsight(
                driftKind: 'quiet_window',
                signal: 'Quiet window after baseline',
                baseline: $baseline,
                currentState: 'No recent improvement events',
                direction: 'unknown',
                relatedEvents: [],
                observed: sprintf(
                    'Baseline remembered health %d%% with %d issues; no recent improvement events in this window.',
                    $baseline->health,
                    $baseline->violations,
                ),
                whyItMatters: 'Absence of memory may mean stability — or that improvements are not being recorded.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: 'Baseline captured',
                    after: 'No SessionCompleted in range',
                ),
                confidenceDetail: $confidence,
            );
        }

        return array_slice($out, 0, 5);
    }

    /**
     * @param  array<string, array{count: int, contexts: array<string, true>, events: int, health_sum: int, successes: int}>  $patterns
     * @return list<ImprovementPatternInsight>
     */
    private function projectPatterns(array $patterns, string $timeRange, string $from, string $to): array
    {
        uasort($patterns, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $out = [];

        foreach (array_slice($patterns, 0, 5, true) as $pattern => $row) {
            $contextCount = count($row['contexts']);
            $concept = $this->vocabulary->canonicalize($pattern);
            $evidence = new InsightEvidence($row['events'], max(1, $contextCount), $timeRange, $from, $to);
            $confidence = IntelligenceConfidence::derive($evidence);
            $successRate = $row['count'] > 0 ? $row['successes'] / $row['count'] : 0.0;
            $avgImpact = $row['count'] > 0 ? $row['health_sum'] / $row['count'] : 0.0;

            $out[] = new ImprovementPatternInsight(
                concept: $concept,
                frequency: $row['count'],
                successRate: round($successRate, 2),
                averageHealthImpact: round($avgImpact, 1),
                contextCount: $contextCount,
                observed: sprintf(
                    '“%s” appeared %d time%s across %d context%s (success rate %.0f%%, avg health %+0.1f).',
                    $concept->label,
                    $row['count'],
                    $row['count'] === 1 ? '' : 's',
                    max(1, $contextCount),
                    $contextCount === 1 ? '' : 's',
                    $successRate * 100,
                    $avgImpact,
                ),
                whyItMatters: 'Common improvements become the foundation for guidance, presets, and team standards later.',
                evidence: $evidence,
                overTime: new InsightOverTime(
                    before: 'Pattern infrequent or absent',
                    after: $row['count'].' recorded uses · avg health '.(($avgImpact >= 0 ? '+' : '').$avgImpact),
                ),
                confidenceDetail: $confidence,
            );
        }

        return $out;
    }

    private function titlesAlign(string $issueTitle, string $goal): bool
    {
        $issue = $this->vocabulary->canonicalize($issueTitle);
        $improvement = $this->vocabulary->canonicalize($goal);

        return ($issue->id === ArchitectureVocabulary::DIRECT_MODEL_USAGE
                && $improvement->id === ArchitectureVocabulary::SERVICE_EXTRACTION)
            || ($issue->id === ArchitectureVocabulary::CONTROLLER_DEPENDENCY
                && $improvement->id === ArchitectureVocabulary::SERVICE_EXTRACTION)
            || str_contains(strtolower($goal), strtolower(explode(' ', $issueTitle)[0] ?? ''));
    }

    /**
     * @param  list<MostImprovedAreaInsight>  $mostImproved
     * @param  list<RepeatedProblemInsight>  $repeated
     * @param  list<ArchitectureDriftInsight>  $drift
     */
    private function summarize(
        array $mostImproved,
        array $repeated,
        array $drift,
        ?ArchitectureBaseline $baseline,
    ): string {
        if ($mostImproved !== []) {
            $top = $mostImproved[0];

            return $top->insight().' — '.(($top->healthDelta >= 0 ? '+' : '').$top->healthDelta)
                .' health (confidence: '.$top->confidence().').';
        }

        if ($drift !== []) {
            return $drift[0]->insight().' (confidence: '.$drift[0]->confidence().').';
        }

        if ($repeated !== []) {
            return $repeated[0]->insight().' (confidence: '.$repeated[0]->confidence().').';
        }

        if ($baseline !== null) {
            return sprintf(
                'Baseline remembered: health %d%% with %d issues — improvements will accumulate here.',
                $baseline->health,
                $baseline->violations,
            );
        }

        return 'Not enough memory yet for strong architecture intelligence.';
    }
}
