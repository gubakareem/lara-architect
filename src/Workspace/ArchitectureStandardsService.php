<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 7 — Architecture Standards catalog (vocabulary → principle).
 * Evidence projection gives standards credibility; principles stay explicit.
 * Standards guide; Rules enforce — keep them separate.
 */
final class ArchitectureStandardsService
{
    /** @var array<string, array{principle: string, version: string}> */
    private const DEFINITIONS = [
        ArchitectureVocabulary::SERVICE_EXTRACTION => [
            'principle' => 'Controllers orchestrate; services own business logic.',
            'version' => '1.0',
        ],
        ArchitectureVocabulary::REQUEST_VALIDATION => [
            'principle' => 'Validation belongs in Form Requests, not controllers.',
            'version' => '1.0',
        ],
        ArchitectureVocabulary::REPOSITORY_PORT => [
            'principle' => 'Persist behind a repository port; keep Eloquent at the edge.',
            'version' => '1.0',
        ],
        ArchitectureVocabulary::CONTROLLER_DEPENDENCY => [
            'principle' => 'Controllers should not own deep domain dependencies.',
            'version' => '1.0',
        ],
        ArchitectureVocabulary::DIRECT_MODEL_USAGE => [
            'principle' => 'Avoid direct model usage in controllers; prefer an application boundary.',
            'version' => '1.0',
        ],
    ];

    public function __construct(
        private readonly ArchitectureVocabulary $vocabulary = new ArchitectureVocabulary,
        private readonly ArchitectureIntelligenceService $intelligence = new ArchitectureIntelligenceService,
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
    ) {}

    /**
     * @return list<ArchitectureStandard>
     */
    public function all(string $projectRoot, int $days = 90): array
    {
        $intel = $this->intelligence->analyze($projectRoot, $days);
        $byConcept = [];
        foreach ($intel->commonPatterns as $pattern) {
            $byConcept[$pattern->concept->id] = $pattern;
        }

        $evidenceByConcept = $this->projectEvidence($projectRoot, $days, $byConcept);
        $standards = [];

        foreach ($this->vocabulary->all() as $concept) {
            $definition = self::DEFINITIONS[$concept->id] ?? [
                'principle' => 'Improve architecture toward clearer boundaries for '.$concept->label.'.',
                'version' => '1.0',
            ];
            $evidence = $evidenceByConcept[$concept->id] ?? new StandardEvidence(0, [], 0.0);
            $standards[] = new ArchitectureStandard(
                concept: $concept,
                principle: $definition['principle'],
                evidence: $evidence,
                summary: $evidence->successfulImprovements > 0
                    ? sprintf(
                        '%s — %d successful improvements · avg health %+0.0f',
                        $definition['principle'],
                        $evidence->successfulImprovements,
                        $evidence->averageHealthDelta,
                    )
                    : $definition['principle'],
                version: $definition['version'],
            );
        }

        usort(
            $standards,
            static fn (ArchitectureStandard $a, ArchitectureStandard $b): int => $b->successfulImprovements() <=> $a->successfulImprovements(),
        );

        return $standards;
    }

    public function forConcept(string $projectRoot, string $conceptIdOrPhrase, int $days = 90): ?ArchitectureStandard
    {
        $concept = $this->vocabulary->canonicalize($conceptIdOrPhrase);
        foreach ($this->all($projectRoot, $days) as $standard) {
            if ($standard->concept->id === $concept->id) {
                return $standard;
            }
        }

        return null;
    }

    /**
     * @param  array<string, ImprovementPatternInsight>  $byConcept
     * @return array<string, StandardEvidence>
     */
    private function projectEvidence(string $projectRoot, int $days, array $byConcept): array
    {
        $cutoff = time() - ($days * 86400);
        $events = $this->memory->allEvents($projectRoot, 2000);

        /** @var array<string, array{contexts: array<string, true>, verifications: int, accepted: int, dismissed: int}> $acc */
        $acc = [];

        foreach ($events as $event) {
            $ts = strtotime($event->occurredAt) ?: 0;
            if ($ts < $cutoff) {
                continue;
            }
            $context = $event->context !== '' ? $event->context : 'unknown';

            if ($event->type === ArchitectureEventType::SessionCompleted
                || $event->type === ArchitectureEventType::ProposalCreated) {
                $text = (string) ($event->payload['goal'] ?? $event->payload['title'] ?? '');
                if ($text === '') {
                    continue;
                }
                $id = $this->vocabulary->canonicalize($text)->id;
                $acc[$id] ??= ['contexts' => [], 'verifications' => 0, 'accepted' => 0, 'dismissed' => 0];
                $acc[$id]['contexts'][$context] = true;
            }

            if ($event->type === ArchitectureEventType::VerificationPassed) {
                // Attribute to concept via correlated proposal/session when possible; else skip.
                $text = (string) ($event->payload['goal'] ?? $event->payload['title'] ?? '');
                if ($text === '') {
                    continue;
                }
                $id = $this->vocabulary->canonicalize($text)->id;
                $acc[$id] ??= ['contexts' => [], 'verifications' => 0, 'accepted' => 0, 'dismissed' => 0];
                $acc[$id]['verifications']++;
            }

            if ($event->type === ArchitectureEventType::GuidanceAccepted
                || $event->type === ArchitectureEventType::GuidanceDismissed) {
                $id = (string) ($event->payload['concept_id'] ?? '');
                if ($id === '') {
                    $id = $this->vocabulary->canonicalize((string) ($event->payload['concept'] ?? ''))->id;
                }
                $acc[$id] ??= ['contexts' => [], 'verifications' => 0, 'accepted' => 0, 'dismissed' => 0];
                if ($event->type === ArchitectureEventType::GuidanceAccepted) {
                    $acc[$id]['accepted']++;
                } else {
                    $acc[$id]['dismissed']++;
                }
            }
        }

        // Also collect contexts from pattern insights when event scan is thin.
        foreach ($byConcept as $id => $pattern) {
            $acc[$id] ??= ['contexts' => [], 'verifications' => 0, 'accepted' => 0, 'dismissed' => 0];
        }

        $out = [];
        foreach ($this->vocabulary->all() as $concept) {
            $pattern = $byConcept[$concept->id] ?? null;
            $row = $acc[$concept->id] ?? ['contexts' => [], 'verifications' => 0, 'accepted' => 0, 'dismissed' => 0];
            $successes = 0;
            $avg = 0.0;
            if ($pattern !== null) {
                $successes = (int) round($pattern->frequency * max(0.0, $pattern->successRate));
                if ($successes === 0 && $pattern->frequency > 0 && $pattern->averageHealthImpact > 0) {
                    $successes = $pattern->frequency;
                }
                $avg = $pattern->averageHealthImpact;
            }

            $contexts = array_keys($row['contexts']);
            sort($contexts);

            $out[$concept->id] = new StandardEvidence(
                successfulImprovements: $successes,
                contexts: array_values($contexts),
                averageHealthDelta: $avg,
                verificationPassed: max($row['verifications'], $successes > 0 ? $successes : 0),
                guidanceAccepted: $row['accepted'],
                guidanceDismissed: $row['dismissed'],
            );
        }

        return $out;
    }
}
