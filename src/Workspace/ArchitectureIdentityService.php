<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 15 — Architecture Identity.
 * Discovered from Standards + Evolution + Learning + Governance + Decisions.
 * Identity has inertia: one change does not rewrite who the system is.
 */
final class ArchitectureIdentityService
{
    /** Minimum successful improvements before a style claim is "high" confidence. */
    private const STYLE_HIGH_IMPROVEMENTS = 5;

    private const STYLE_MEDIUM_IMPROVEMENTS = 2;

    private const STYLE_HIGH_DECISIONS = 2;

    public function __construct(
        private readonly ArchitectureStandardsService $standards = new ArchitectureStandardsService,
        private readonly ArchitectureEvolutionService $evolution = new ArchitectureEvolutionService,
        private readonly ArchitectureLearningService $learning = new ArchitectureLearningService,
        private readonly ArchitectureGovernanceService $governance = new ArchitectureGovernanceService,
        private readonly ArchitectureDecisionHistoryService $decisions = new ArchitectureDecisionHistoryService,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
    ) {}

    public function identify(string $projectRoot, int $days = 180): ArchitectureIdentity
    {
        $standards = $this->standards->all($projectRoot, min(90, $days));
        $evolution = $this->evolution->evolve($projectRoot, $days);
        $learning = $this->learning->learn($projectRoot, $days);
        $governance = $this->governance->assess($projectRoot, min(90, $days));
        $decisionHistory = $this->decisions->forArea($projectRoot, '', 40);
        $intel = $this->intelligence->analyze($projectRoot, min(90, $days));

        $principleModels = $this->buildPrinciples($standards);
        $principles = array_map(static fn (IdentityPrinciple $p): string => $p->name, $principleModels);

        $candidateStyle = $this->inferStyle($evolution, $learning, $standards);
        $support = $this->styleSupport($learning, $evolution, $decisionHistory);
        $confidence = $this->styleConfidence($support['improvements'], $support['decisions'], $support['contexts']);

        // Inertia: thin evidence → evolving, not a premature identity flip.
        $style = $confidence === 'low'
            ? 'Evolving Laravel architecture'
            : $candidateStyle;

        $strengths = $this->buildStrengths($governance, $learning, $intel);
        $growth = $this->buildGrowth($learning, $evolution, $intel);

        $strongLabels = array_map(static fn (IdentityStrength $s): string => $s->area, $strengths);
        $growingLabels = array_map(static fn (IdentityGrowthArea $g): string => $g->area, $growth);

        $evidence = [];
        if ($evolution->direction !== null) {
            $evidence[] = 'Direction: '.$evolution->direction->statement;
        }
        $evidence[] = sprintf('%d valued principle%s', count($principleModels), count($principleModels) === 1 ? '' : 's');
        $evidence[] = sprintf('%d recorded decision%s', count($decisionHistory->decisions), count($decisionHistory->decisions) === 1 ? '' : 's');
        $evidence[] = sprintf('Style confidence: %s (%d improvements · %d decisions)', $confidence, $support['improvements'], $support['decisions']);
        if ($learning->successfulPatterns !== []) {
            $evidence[] = 'Learning: '.$learning->successfulPatterns[0]->concept->label;
        }

        $summary = sprintf(
            '%s — values clear ownership of rules, continuous improvement, and verified change. Strong: %s. Growing: %s.',
            $style,
            $strongLabels !== [] ? implode('; ', array_slice($strongLabels, 0, 2)) : 'still forming',
            $growingLabels !== [] ? implode('; ', array_slice($growingLabels, 0, 2)) : 'watch for emerging drift',
        );

        $snapshot = new ArchitectureIdentitySnapshot(
            styleName: $style,
            styleConfidence: $confidence,
            principles: $principleModels,
            strengths: $strengths,
            growthAreas: $growth,
            updatedAt: gmdate('c'),
            summary: $summary,
        );

        $history = $this->identityHistory($projectRoot, $style, $candidateStyle, $decisionHistory, $learning);

        return new ArchitectureIdentity(
            question: 'What kind of architecture does this codebase believe in?',
            style: $style,
            principles: $principles,
            strongAreas: $strongLabels,
            growingAreas: $growingLabels,
            summary: $summary,
            snapshot: $snapshot,
            evidence: $evidence,
            history: $history,
        );
    }

    /**
     * Explicitly record a stabilized identity observation (not called from read paths).
     */
    public function observe(string $projectRoot, int $days = 180): ?ArchitectureIdentitySnapshot
    {
        $identity = $this->identify($projectRoot, $days);
        if (! in_array($identity->snapshot->styleConfidence, ['medium', 'high'], true)) {
            return null;
        }

        $last = null;
        foreach (array_reverse($this->memory->allEvents($projectRoot, 200)) as $event) {
            if ($event->type === ArchitectureEventType::IdentityObserved) {
                $last = (string) ($event->payload['style'] ?? '');

                break;
            }
        }
        if ($last === $identity->snapshot->styleName) {
            return $identity->snapshot;
        }

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::IdentityObserved,
            'architecture',
            [
                'style' => $identity->snapshot->styleName,
                'confidence' => $identity->snapshot->styleConfidence,
                'updated_at' => $identity->snapshot->updatedAt,
                'reason' => 'Identity stabilized from repeated evidence (standards · evolution · decisions).',
                'snapshot' => $identity->snapshot->toArray(),
            ],
        );

        return $identity->snapshot;
    }

    public function snapshot(string $projectRoot, int $days = 180): ArchitectureIdentitySnapshot
    {
        return $this->identify($projectRoot, $days)->snapshot;
    }

    /**
     * @param  list<ArchitectureStandard>  $standards
     * @return list<IdentityPrinciple>
     */
    private function buildPrinciples(array $standards): array
    {
        $out = [];
        foreach (array_slice($standards, 0, 5) as $standard) {
            $count = max(
                $standard->successfulImprovements(),
                count($standard->evidence->contexts),
            );
            if ($count < 1 && $out !== []) {
                continue;
            }
            $out[] = new IdentityPrinciple(
                name: $standard->principle,
                evidenceCount: max(1, $count),
            );
        }
        if ($out === []) {
            foreach (array_slice($standards, 0, 3) as $standard) {
                $out[] = new IdentityPrinciple($standard->principle, 1);
            }
        }
        $out[] = new IdentityPrinciple('Changes require verification before they become memory.', 1);

        return array_slice($out, 0, 6);
    }

    /**
     * @return list<IdentityStrength>
     */
    private function buildStrengths(
        ArchitectureGovernance $governance,
        ArchitectureLearning $learning,
        ArchitectureIntelligence $intel,
    ): array {
        $out = [];
        foreach (array_slice($governance->alignments, 0, 3) as $alignment) {
            if ($alignment->alignmentPercent >= 60 && $alignment->improvementsCompleted >= 2) {
                $out[] = new IdentityStrength(
                    area: $alignment->standard->concept->label.' boundaries',
                    evidence: sprintf('%d successful improvements', $alignment->improvementsCompleted),
                );
            }
        }
        foreach (array_slice($learning->successfulPatterns, 0, 2) as $pattern) {
            if ($pattern->applied >= self::STYLE_MEDIUM_IMPROVEMENTS) {
                $out[] = new IdentityStrength(
                    area: $pattern->concept->label,
                    evidence: sprintf('%d successful applications · %.0f%% success', $pattern->applied, $pattern->successRate * 100),
                );
            }
        }
        if ($intel->mostImprovedAreas !== [] && $out === []) {
            $out[] = new IdentityStrength(
                area: 'Recent improvement focus',
                evidence: $intel->mostImprovedAreas[0]->insight(),
            );
        }

        return array_slice($out, 0, 4);
    }

    /**
     * @return list<IdentityGrowthArea>
     */
    private function buildGrowth(
        ArchitectureLearning $learning,
        ArchitectureEvolution $evolution,
        ArchitectureIntelligence $intel,
    ): array {
        $out = [];
        foreach ($learning->risks as $risk) {
            $out[] = new IdentityGrowthArea(
                area: $risk->risk,
                evidence: sprintf('%d previous regression signal%s', max(1, $risk->previousRegressions), $risk->previousRegressions === 1 ? '' : 's'),
            );
            if (count($out) >= 3) {
                break;
            }
        }
        foreach ($evolution->regressions as $regression) {
            $out[] = new IdentityGrowthArea(
                area: $regression->signal,
                evidence: $regression->previousPattern !== '' ? $regression->previousPattern : 'recurring signal',
            );
            if (count($out) >= 4) {
                break;
            }
        }
        if ($intel->driftSignals !== []) {
            $out[] = new IdentityGrowthArea(
                area: 'Architecture drift',
                evidence: $intel->driftSignals[0]->insight(),
            );
        }

        return array_slice($out, 0, 4);
    }

    /**
     * @param  list<ArchitectureStandard>  $standards
     */
    private function inferStyle(
        ArchitectureEvolution $evolution,
        ArchitectureLearning $learning,
        array $standards,
    ): string {
        $top = $evolution->direction !== null
            ? $evolution->direction->concept
            : (($learning->successfulPatterns[0]->concept ?? null)
                ?? ($standards[0]->concept ?? null));

        if ($top === null) {
            return 'Evolving Laravel architecture';
        }

        return match ($top->id) {
            ArchitectureVocabulary::SERVICE_EXTRACTION,
            ArchitectureVocabulary::CONTROLLER_DEPENDENCY => 'Service-oriented Laravel',
            ArchitectureVocabulary::REPOSITORY_PORT => 'Port-and-adapter leaning Laravel',
            ArchitectureVocabulary::REQUEST_VALIDATION => 'Request-validated Laravel',
            ArchitectureVocabulary::DIRECT_MODEL_USAGE => 'Boundary-seeking Laravel',
            default => $top->label.'-oriented Laravel',
        };
    }

    /**
     * @return array{improvements: int, decisions: int, contexts: int}
     */
    private function styleSupport(
        ArchitectureLearning $learning,
        ArchitectureEvolution $evolution,
        ArchitectureDecisionHistory $decisionHistory,
    ): array {
        $improvements = $evolution->direction !== null
            ? $evolution->direction->supportingImprovements
            : ($learning->successfulPatterns[0]->applied ?? 0);
        $contexts = 0;
        if ($learning->successfulPatterns !== []) {
            $contexts = count($learning->successfulPatterns[0]->evidence->contexts);
        }
        $recorded = 0;
        foreach ($decisionHistory->decisions as $decision) {
            if ($decision->lifecycle !== DecisionLifecycle::NoDecision) {
                $recorded++;
            }
        }

        return [
            'improvements' => $improvements,
            'decisions' => $recorded,
            'contexts' => $contexts,
        ];
    }

    private function styleConfidence(int $improvements, int $decisions, int $contexts): string
    {
        if ($improvements >= self::STYLE_HIGH_IMPROVEMENTS && ($decisions >= self::STYLE_HIGH_DECISIONS || $contexts >= 3)) {
            return 'high';
        }
        if ($improvements >= self::STYLE_MEDIUM_IMPROVEMENTS || $decisions >= 1) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @return list<IdentityHistoryEntry>
     */
    private function identityHistory(
        string $projectRoot,
        string $currentStyle,
        string $candidateStyle,
        ArchitectureDecisionHistory $decisionHistory,
        ArchitectureLearning $learning,
    ): array {
        $entries = [];
        foreach ($this->memory->allEvents($projectRoot, 500) as $event) {
            if ($event->type !== ArchitectureEventType::IdentityObserved) {
                continue;
            }
            $entries[] = new IdentityHistoryEntry(
                period: $this->period((string) ($event->payload['updated_at'] ?? $event->occurredAt)),
                style: (string) ($event->payload['style'] ?? ''),
                reason: (string) ($event->payload['reason'] ?? 'Identity observation recorded'),
            );
        }

        if ($entries === [] && $decisionHistory->decisions !== []) {
            $oldest = $decisionHistory->decisions[count($decisionHistory->decisions) - 1];
            $entries[] = new IdentityHistoryEntry(
                period: $oldest->period,
                style: 'Evolving Laravel architecture',
                reason: 'Early decisions: '.$oldest->decision,
            );
        }

        if ($learning->successfulPatterns !== [] && $currentStyle !== 'Evolving Laravel architecture') {
            $pattern = $learning->successfulPatterns[0];
            $entries[] = new IdentityHistoryEntry(
                period: gmdate('Y'),
                style: $currentStyle,
                reason: sprintf(
                    'Repeated %s improvements (%d×) shaped identity toward %s.',
                    $pattern->concept->label,
                    $pattern->applied,
                    $candidateStyle,
                ),
            );
        }

        // Dedupe by period+style
        $seen = [];
        $unique = [];
        foreach ($entries as $entry) {
            $key = $entry->period.'|'.$entry->style;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $entry;
        }

        return array_slice($unique, 0, 6);
    }

    private function period(string $iso): string
    {
        $ts = strtotime($iso);

        return $ts !== false ? gmdate('Y', $ts) : gmdate('Y');
    }
}
