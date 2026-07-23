<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 6 — Architecture Guidance (Evidence → Guidance, not AI → Suggestions).
 * Recommendation tone: worth considering — never a command.
 */
final class ArchitectureGuidanceService
{
    public function __construct(
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureVocabulary $vocabulary = new ArchitectureVocabulary,
        private readonly ArchitectureBaselineStore $baselines = new ArchitectureBaselineStore,
    ) {}

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     */
    public function recommend(
        string $projectRoot,
        ?string $context = null,
        ?int $currentHealth = null,
        array $openIssues = [],
        int $days = 90,
    ): ?ArchitectureGuidance {
        $intel = $this->intelligence->analyze($projectRoot, $days);
        $baseline = $this->baselines->latest($projectRoot);
        $projectAverage = $baseline?->health;

        foreach ($intel->repeatedProblems as $repeated) {
            if ($repeated->remaining <= 0) {
                continue;
            }

            $matchesOpen = $this->matchesOpenIssue($repeated, $openIssues, $context);
            $related = $this->findRelatedIssue($repeated, $openIssues);

            if ($matchesOpen || $openIssues === [] || ($context !== null && $this->contextInRepeated($repeated, $context))) {
                return $this->fromRepeated(
                    $repeated,
                    $intel,
                    $currentHealth,
                    $projectAverage,
                    $related,
                );
            }
        }

        if ($intel->repeatedProblems !== [] && $intel->repeatedProblems[0]->remaining > 0) {
            $repeated = $intel->repeatedProblems[0];

            return $this->fromRepeated(
                $repeated,
                $intel,
                $currentHealth,
                $projectAverage,
                $this->findRelatedIssue($repeated, $openIssues),
            );
        }

        foreach ($intel->driftSignals as $drift) {
            if ($drift->driftKind === 'boundary_weakening') {
                return $this->fromDrift($drift, $intel, $context, $currentHealth, $projectAverage, $openIssues);
            }
        }

        if ($intel->commonPatterns !== [] && $openIssues !== []) {
            return $this->fromPattern($intel->commonPatterns[0], $openIssues[0], $context, $currentHealth, $projectAverage);
        }

        return null;
    }

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     */
    private function fromRepeated(
        RepeatedProblemInsight $repeated,
        ArchitectureIntelligence $intel,
        ?int $currentHealth,
        ?int $projectAverage,
        ?array $relatedIssue,
    ): ArchitectureGuidance {
        $improvement = $this->suggestedConceptForProblem($repeated->concept);
        $avgHealth = $this->averageHealthForConcept($intel, $improvement);
        $evidence = new GuidanceEvidence(
            similarImprovements: max($repeated->resolved, $this->patternFrequency($intel, $improvement)),
            resolvedIssues: $repeated->resolved,
            healthDeltaAverage: $avgHealth,
            contexts: $repeated->contextCount,
            remainingIssues: $repeated->remaining,
            events: $repeated->evidence()->events,
            recent: true,
        );
        $confidence = GuidanceConfidence::derive($evidence);

        $why = [
            sprintf('%d similar issues appeared', $repeated->occurrences),
        ];
        if ($repeated->resolved > 0) {
            $why[] = sprintf('%d previous fixes succeeded', $repeated->resolved);
        }
        if ($avgHealth > 0) {
            $why[] = sprintf('Average improvement: %+0.0f health points', $avgHealth);
        }
        if ($currentHealth !== null && $projectAverage !== null && $currentHealth < $projectAverage) {
            $why[] = sprintf('Health below project average (%d%% vs %d%%)', $currentHealth, $projectAverage);
        }

        $area = $relatedIssue['context'] ?? $repeated->title;

        return new ArchitectureGuidance(
            area: (string) $area,
            concept: $improvement,
            headline: sprintf(
                'Based on what happened before, %s might be worth looking at for “%s”.',
                $improvement->label,
                $repeated->concept->label,
            ),
            why: $why,
            evidence: $evidence,
            confidence: $confidence,
            opportunity: sprintf(
                '%s has recurring %s — consider %s when you are ready.',
                $area,
                $repeated->concept->label,
                $improvement->label,
            ),
            relatedIssueId: isset($relatedIssue['id']) ? (string) $relatedIssue['id'] : null,
            relatedIssueTitle: isset($relatedIssue['title']) ? (string) $relatedIssue['title'] : null,
        );
    }

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     */
    private function fromDrift(
        ArchitectureDriftInsight $drift,
        ArchitectureIntelligence $intel,
        ?string $context,
        ?int $currentHealth,
        ?int $projectAverage,
        array $openIssues,
    ): ArchitectureGuidance {
        $concept = $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
            ?? $this->vocabulary->canonicalize('Service Extraction');
        $avgHealth = $this->averageHealthForConcept($intel, $concept);
        $patternFreq = $this->patternFrequency($intel, $concept);
        $evidence = new GuidanceEvidence(
            similarImprovements: $patternFreq,
            resolvedIssues: $patternFreq,
            healthDeltaAverage: $avgHealth,
            contexts: $drift->evidence()->contexts,
            remainingIssues: 1,
            events: $drift->evidence()->events,
            recent: true,
        );
        $confidence = GuidanceConfidence::derive($evidence);
        $why = [
            $drift->observed(),
            'Previous boundary improvements exist in memory',
        ];
        if ($avgHealth > 0) {
            $why[] = sprintf('Average improvement: %+0.0f health points', $avgHealth);
        }
        if ($currentHealth !== null && $projectAverage !== null && $currentHealth < $projectAverage) {
            $why[] = 'Current health below project baseline';
        }

        $related = $openIssues[0] ?? null;

        return new ArchitectureGuidance(
            area: $context ?: ($drift->context ?? 'project'),
            concept: $concept,
            headline: sprintf(
                'Based on architecture movement in memory, restoring %s might be worth looking at.',
                $concept->label,
            ),
            why: $why,
            evidence: $evidence,
            confidence: $confidence,
            opportunity: 'Service boundary weakening detected — an opportunity to re-establish intended layers.',
            relatedIssueId: isset($related['id']) ? (string) $related['id'] : null,
            relatedIssueTitle: isset($related['title']) ? (string) $related['title'] : null,
        );
    }

    /**
     * @param  array{id?: string, title?: string, context?: string}  $issue
     */
    private function fromPattern(
        ImprovementPatternInsight $pattern,
        array $issue,
        ?string $context,
        ?int $currentHealth,
        ?int $projectAverage,
    ): ArchitectureGuidance {
        $area = (string) ($issue['context'] ?? $context ?? 'current context');
        $evidence = new GuidanceEvidence(
            similarImprovements: $pattern->frequency,
            resolvedIssues: (int) round($pattern->frequency * $pattern->successRate),
            healthDeltaAverage: $pattern->averageHealthImpact,
            contexts: $pattern->contextCount,
            remainingIssues: 1,
            events: $pattern->evidence()->events,
            recent: true,
        );
        $confidence = GuidanceConfidence::derive($evidence);
        $why = [
            sprintf('%d similar improvements recorded previously', $pattern->frequency),
            sprintf('Historical success rate %.0f%%', $pattern->successRate * 100),
        ];
        if ($pattern->averageHealthImpact > 0) {
            $why[] = sprintf('Average improvement: %+0.0f health points', $pattern->averageHealthImpact);
        }
        if ($currentHealth !== null && $projectAverage !== null && $currentHealth < $projectAverage) {
            $why[] = 'Current health below project average';
        }

        return new ArchitectureGuidance(
            area: $area,
            concept: $pattern->concept,
            headline: sprintf(
                'Based on what happened before, %s might be worth looking at for %s.',
                $pattern->concept->label,
                (string) ($issue['title'] ?? $area),
            ),
            why: $why,
            evidence: $evidence,
            confidence: $confidence,
            opportunity: sprintf('Open issue in %s aligns with a common improvement pattern.', $area),
            relatedIssueId: isset($issue['id']) ? (string) $issue['id'] : null,
            relatedIssueTitle: isset($issue['title']) ? (string) $issue['title'] : null,
        );
    }

    private function averageHealthForConcept(ArchitectureIntelligence $intel, ArchitectureConcept $concept): float
    {
        foreach ($intel->commonPatterns as $pattern) {
            if ($pattern->concept->id === $concept->id) {
                return $pattern->averageHealthImpact;
            }
        }

        return 0.0;
    }

    private function patternFrequency(ArchitectureIntelligence $intel, ArchitectureConcept $concept): int
    {
        foreach ($intel->commonPatterns as $pattern) {
            if ($pattern->concept->id === $concept->id) {
                return $pattern->frequency;
            }
        }

        return 0;
    }

    private function suggestedConceptForProblem(ArchitectureConcept $problem): ArchitectureConcept
    {
        return match ($problem->id) {
            ArchitectureVocabulary::DIRECT_MODEL_USAGE,
            ArchitectureVocabulary::CONTROLLER_DEPENDENCY => $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
                ?? $this->vocabulary->canonicalize('Service Extraction'),
            default => $this->vocabulary->concept(ArchitectureVocabulary::SERVICE_EXTRACTION)
                ?? $problem,
        };
    }

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     * @return array{id?: string, title?: string, context?: string}|null
     */
    private function findRelatedIssue(RepeatedProblemInsight $repeated, array $openIssues): ?array
    {
        foreach ($openIssues as $issue) {
            $title = (string) ($issue['title'] ?? '');
            if ($title === '') {
                continue;
            }
            if (strcasecmp($title, $repeated->title) === 0
                || $this->vocabulary->canonicalize($title)->id === $repeated->concept->id) {
                return $issue;
            }
        }

        return $openIssues[0] ?? null;
    }

    /**
     * @param  list<array{id?: string, title?: string, context?: string}>  $openIssues
     */
    private function matchesOpenIssue(RepeatedProblemInsight $repeated, array $openIssues, ?string $context): bool
    {
        foreach ($openIssues as $issue) {
            $title = (string) ($issue['title'] ?? '');
            $issueContext = (string) ($issue['context'] ?? '');
            $sameTitle = $title !== '' && (
                strcasecmp($title, $repeated->title) === 0
                || $this->vocabulary->canonicalize($title)->id === $repeated->concept->id
            );
            $sameContext = $context === null || $context === '' || strcasecmp($issueContext, $context) === 0 || $issueContext === '';

            if ($sameTitle && $sameContext) {
                return true;
            }
        }

        return false;
    }

    private function contextInRepeated(RepeatedProblemInsight $repeated, string $context): bool
    {
        return str_contains(strtolower($repeated->title), strtolower($context))
            || str_contains(strtolower($context), strtolower($repeated->concept->label));
    }
}
