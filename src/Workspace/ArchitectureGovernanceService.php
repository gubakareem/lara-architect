<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Standards → measurable developer governance.
 * Projection only — does not enforce Rules.
 */
final class ArchitectureGovernanceService
{
    public function __construct(
        private readonly ArchitectureStandardsService $standards = new ArchitectureStandardsService,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
    ) {}

    public function assess(string $projectRoot, int $days = 90): ArchitectureGovernance
    {
        $standards = $this->standards->all($projectRoot, $days);
        $intel = $this->intelligence->analyze($projectRoot, $days);

        $remainingByConcept = $this->remainingDriftByConcept($intel);
        $alignments = [];

        foreach ($standards as $standard) {
            // Focus on standards the project has actually valued (or core boundary standards with drift).
            $completed = $standard->successfulImprovements();
            $remaining = $remainingByConcept[$standard->concept->id] ?? 0;
            if ($completed === 0 && $remaining === 0) {
                continue;
            }

            $total = $completed + $remaining;
            $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;
            $percent = max(0, min(100, $percent));

            $trend = match (true) {
                $completed > 0 && $remaining === 0 => 'improving',
                $completed > $remaining => 'improving',
                $remaining > $completed => 'regressing',
                default => 'stable',
            };

            // Drift signals of boundary_weakening pull service extraction toward regressing.
            if ($standard->concept->id === ArchitectureVocabulary::SERVICE_EXTRACTION) {
                foreach ($intel->driftSignals as $drift) {
                    if ($drift->driftKind === 'boundary_weakening') {
                        $trend = 'regressing';
                        $remaining = max($remaining, 1);
                        $total = $completed + $remaining;
                        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : $percent;

                        break;
                    }
                }
            }

            $alignments[] = new StandardAlignment(
                standard: $standard,
                alignmentPercent: $percent,
                trend: $trend,
                improvementsCompleted: $completed,
                remainingDrift: $remaining,
                summary: sprintf(
                    '%s · %d%% aligned · %s · %d improvements · %d remaining drift',
                    $standard->concept->label,
                    $percent,
                    $trend,
                    $completed,
                    $remaining,
                ),
            );
        }

        usort(
            $alignments,
            static fn (StandardAlignment $a, StandardAlignment $b): int => $b->alignmentPercent <=> $a->alignmentPercent,
        );

        $overall = $alignments === []
            ? 0
            : (int) round(array_sum(array_map(
                static fn (StandardAlignment $a): int => $a->alignmentPercent,
                $alignments,
            )) / count($alignments));

        $improving = count(array_filter($alignments, static fn (StandardAlignment $a): bool => $a->trend === 'improving'));
        $regressing = count(array_filter($alignments, static fn (StandardAlignment $a): bool => $a->trend === 'regressing'));
        $overallTrend = match (true) {
            $improving > $regressing => 'improving',
            $regressing > $improving => 'regressing',
            default => 'stable',
        };

        $summary = $alignments === []
            ? 'Not enough valued standards in memory yet — improvements will define what this system protects.'
            : sprintf(
                'Overall alignment %d%% · trend %s — %d standard%s with evidence.',
                $overall,
                $overallTrend,
                count($alignments),
                count($alignments) === 1 ? '' : 's',
            );

        $sliced = array_slice($alignments, 0, 5);
        $lastUpdated = gmdate('c');
        $snapshots = array_map(
            fn (StandardAlignment $alignment): GovernanceSnapshot => GovernanceSnapshot::fromAlignment(
                $alignment,
                $this->snapshotConfidence($alignment),
                $lastUpdated,
            ),
            $sliced,
        );

        return new ArchitectureGovernance(
            question: 'Are we moving toward the architecture we value?',
            summary: $summary,
            overallAlignment: $overall,
            overallTrend: $overallTrend,
            alignments: $sliced,
            snapshots: $snapshots,
        );
    }

    private function snapshotConfidence(StandardAlignment $alignment): string
    {
        $signals = 0;
        if ($alignment->improvementsCompleted >= 3) {
            $signals++;
        }
        if ($alignment->standard->evidence->contexts !== [] && count($alignment->standard->evidence->contexts) >= 2) {
            $signals++;
        }
        if ($alignment->standard->evidence->averageHealthDelta > 0) {
            $signals++;
        }
        if ($alignment->remainingDrift === 0 && $alignment->improvementsCompleted > 0) {
            $signals++;
        }

        return match (true) {
            $signals >= 3 => 'high',
            $signals >= 2 => 'medium',
            default => 'low',
        };
    }

    /**
     * @return array<string, int>
     */
    private function remainingDriftByConcept(ArchitectureIntelligence $intel): array
    {
        $remaining = [];

        foreach ($intel->repeatedProblems as $problem) {
            if ($problem->remaining <= 0) {
                continue;
            }
            $target = match ($problem->concept->id) {
                ArchitectureVocabulary::DIRECT_MODEL_USAGE,
                ArchitectureVocabulary::CONTROLLER_DEPENDENCY => ArchitectureVocabulary::SERVICE_EXTRACTION,
                default => $problem->concept->id,
            };
            $remaining[$target] = ($remaining[$target] ?? 0) + $problem->remaining;
        }

        foreach ($intel->driftSignals as $drift) {
            if ($drift->driftKind === 'boundary_weakening') {
                $remaining[ArchitectureVocabulary::SERVICE_EXTRACTION] =
                    ($remaining[ArchitectureVocabulary::SERVICE_EXTRACTION] ?? 0) + 1;
            }
            if ($drift->driftKind === 'unresolved_issue' && $drift->context !== null) {
                $remaining[ArchitectureVocabulary::SERVICE_EXTRACTION] =
                    ($remaining[ArchitectureVocabulary::SERVICE_EXTRACTION] ?? 0) + 1;
            }
        }

        return $remaining;
    }
}
