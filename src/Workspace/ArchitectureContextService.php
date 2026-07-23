<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 17 — Architecture Context.
 * Unifies what you need before touching a file/module/feature.
 * ArchitectureContextEnvelope is the stable external contract for AI / adapters.
 */
final class ArchitectureContextService
{
    public function __construct(
        private readonly ArchitectureKnowledgeTransferService $transfer = new ArchitectureKnowledgeTransferService,
        private readonly ArchitectureIdentityService $identity = new ArchitectureIdentityService,
        private readonly ArchitectureDecisionHistoryService $decisions = new ArchitectureDecisionHistoryService,
        private readonly ArchitectureEvolutionService $evolution = new ArchitectureEvolutionService,
        private readonly ArchitectureGuidanceService $guidance = new ArchitectureGuidanceService,
        private readonly ArchitectureLearningService $learning = new ArchitectureLearningService,
        private readonly ArchitectureCommunicationService $communication = new ArchitectureCommunicationService,
    ) {}

    public function forSubject(
        string $projectRoot,
        string $subject,
        ?int $currentHealth = null,
        int $days = 180,
        CommunicationAudience|string $audience = CommunicationAudience::Developer,
    ): ArchitectureContext {
        return $this->compose($projectRoot, $subject, $currentHealth, $days, $audience)['context'];
    }

    /**
     * Stable boundary for lara-architect-ai · VS Code · GitHub · external tools.
     * AI may explain this envelope — never replace the analyzer.
     */
    public function envelope(
        string $projectRoot,
        string $subject,
        ?int $currentHealth = null,
        int $days = 180,
        CommunicationAudience|string $audience = CommunicationAudience::Developer,
    ): ArchitectureContextEnvelope {
        $parts = $this->compose($projectRoot, $subject, $currentHealth, $days, $audience);
        $context = $parts['context'];
        $identity = $parts['identity'];
        $decisionHistory = $parts['decisions'];
        $guidance = $parts['guidance'];
        $architectureBrief = $parts['brief'];

        $decisionLines = $context->importantDecisions;
        $decisionRecords = [];
        foreach (array_slice($decisionHistory->decisions, 0, 5) as $record) {
            $decisionRecords[] = [
                'decision' => $record->decision,
                'reason' => $record->reason,
                'lifecycle' => $record->lifecycle->value,
                'period' => $record->period,
                'alternatives' => array_map(
                    static fn (DecisionAlternative $alt): array => [
                        'option' => $alt->option,
                        'status' => $alt->status->value,
                        'reason' => $alt->reason,
                    ],
                    $record->alternatives,
                ),
            ];
        }

        return new ArchitectureContextEnvelope(
            context: [
                'target' => $context->subject,
                'question' => $context->question,
                'purpose' => [
                    'reason' => $context->purpose,
                    'created_because' => $context->createdBecause,
                ],
                'principles' => $context->principles,
                'audience' => $context->audience->value,
                'summary' => $context->summary,
            ],
            identity: [
                'style' => [
                    'name' => $identity->snapshot->styleName,
                    'confidence' => $identity->snapshot->styleConfidence,
                ],
                'principles' => array_map(
                    static fn (IdentityPrinciple $p): array => $p->toArray(),
                    array_slice($identity->snapshot->principles, 0, 5),
                ),
                'growth_areas' => array_map(
                    static fn (IdentityGrowthArea $g): array => $g->toArray(),
                    array_slice($identity->snapshot->growthAreas, 0, 3),
                ),
            ],
            evidence: [
                'watch' => $context->watch,
                'improvement_count' => $context->brief !== null ? $context->brief->improvementCount : 0,
                'sources' => [
                    'memory',
                    'identity',
                    'decisions',
                    'evolution',
                    'guidance',
                ],
            ],
            decisions: [
                'important' => $decisionLines,
                'records' => $decisionRecords,
            ],
            history: [
                'recent_evolution' => $context->recentEvolution,
                'identity_history' => array_map(
                    static fn (IdentityHistoryEntry $entry): array => $entry->toArray(),
                    array_slice($identity->history, -3),
                ),
            ],
            guidance: [
                'hints' => array_values(array_filter([
                    $context->guidanceHint,
                    ...array_slice($context->watch, 0, 2),
                ])),
                'opportunity' => $guidance?->opportunity,
                'concept' => $guidance?->concept->toArray(),
            ],
            allowedQuestions: [
                ArchitectureQuestionType::WhyExists->value,
                ArchitectureQuestionType::WhatChanged->value,
                ArchitectureQuestionType::WhatToFollow->value,
                ArchitectureQuestionType::WhatWorked->value,
                ArchitectureQuestionType::WhoOwns->value,
            ],
            boundary: [
                'can_explain' => true,
                'can_modify' => false,
                'role' => 'language_layer',
                'authority' => 'lara-architect',
                'may' => [
                    'explain',
                    'summarize',
                    'translate',
                    'navigate',
                    'onboard',
                ],
                'must_not' => [
                    'new_rules',
                    'new_findings',
                    'new_architecture_decisions',
                    'bypass_evidence',
                    'direct_code_scan',
                    'autonomous_refactor',
                    'mutate_code',
                ],
                'consume' => [
                    'ArchitectureContext',
                    'ArchitectureBrief',
                    'ArchitectureIdentitySnapshot',
                ],
            ],
            brief: $architectureBrief->toArray(),
        );
    }

    /**
     * @return array{
     *     context: ArchitectureContext,
     *     identity: ArchitectureIdentity,
     *     decisions: ArchitectureDecisionHistory,
     *     guidance: ?ArchitectureGuidance,
     *     brief: ArchitectureBrief
     * }
     */
    private function compose(
        string $projectRoot,
        string $subject,
        ?int $currentHealth,
        int $days,
        CommunicationAudience|string $audience,
    ): array {
        $audience = $audience instanceof CommunicationAudience
            ? $audience
            : (CommunicationAudience::tryFrom($audience) ?? CommunicationAudience::Developer);

        $brief = $this->transfer->brief($projectRoot, $subject);
        $identity = $this->identity->identify($projectRoot, $days);
        $area = $this->areaName($subject);
        $decisionHistory = $this->decisions->forArea($projectRoot, $area, 8);
        $evolution = $this->evolution->evolve($projectRoot, $days);
        $learning = $this->learning->learn($projectRoot, $days);
        $guidance = $this->guidance->recommend($projectRoot, $subject, $currentHealth);
        $architectureBrief = $this->communication->brief($projectRoot, $subject, $days, $audience);

        $purpose = $this->inferPurpose($subject, $brief, $identity);
        $createdBecause = $brief->whyItExists;

        $importantDecisions = [];
        foreach (array_slice($decisionHistory->decisions, 0, 4) as $decision) {
            $line = $decision->decision;
            if ($decision->alternatives !== []) {
                $line .= ' — not '.$decision->alternatives[0]->option;
            }
            $importantDecisions[] = $line;
        }
        foreach (array_slice($brief->importantDecisions, 0, 4) as $line) {
            $importantDecisions[] = $line;
        }
        $importantDecisions = array_values(array_unique(array_slice($importantDecisions, 0, 5)));

        $recent = [];
        foreach (array_slice($brief->recentChanges, 0, 3) as $change) {
            $recent[] = $change;
        }
        if ($evolution->trajectories !== []) {
            $recent[] = $evolution->trajectories[0]->summary;
        }
        if ($brief->improvementCount > 0) {
            $recent[] = sprintf('%d successful improvement%s in this context', $brief->improvementCount, $brief->improvementCount === 1 ? '' : 's');
        }
        $recent = array_values(array_unique(array_slice($recent, 0, 5)));

        $watch = [];
        foreach (array_slice($learning->risks, 0, 3) as $risk) {
            $watch[] = $risk->risk;
        }
        foreach (array_slice($identity->snapshot->growthAreas, 0, 2) as $growth) {
            $watch[] = $growth->area;
        }
        $watch = array_values(array_unique(array_slice($watch, 0, 4)));

        $principles = array_slice($architectureBrief->principles, 0, 4);
        $guidanceHint = $guidance !== null
            ? $guidance->opportunity
            : 'No open guidance — ask why this exists, then follow preferred paths.';

        $summary = sprintf(
            'Before touching %s: %s. Identity: %s. %d decision%s · %d recent change%s · watch %d signal%s.',
            $subject,
            $purpose,
            $identity->snapshot->styleName,
            count($importantDecisions),
            count($importantDecisions) === 1 ? '' : 's',
            count($recent),
            count($recent) === 1 ? '' : 's',
            count($watch),
            count($watch) === 1 ? '' : 's',
        );

        return [
            'context' => new ArchitectureContext(
                question: 'What should I know about this exact thing before I touch it?',
                subject: $subject,
                purpose: $purpose,
                createdBecause: $createdBecause,
                importantDecisions: $importantDecisions,
                recentEvolution: $recent,
                watch: $watch,
                principles: $principles,
                identityStyle: $identity->snapshot->styleName,
                guidanceHint: $guidanceHint,
                summary: $summary,
                brief: $brief,
                audience: $audience,
            ),
            'identity' => $identity,
            'decisions' => $decisionHistory,
            'guidance' => $guidance,
            'brief' => $architectureBrief,
        ];
    }

    private function inferPurpose(string $subject, ContextBrief $brief, ArchitectureIdentity $identity): string
    {
        $base = basename(str_replace('\\', '/', $subject));
        if (str_ends_with(strtolower($base), 'service.php') || str_ends_with(strtolower($base), 'service')) {
            return 'Own business rules for this area — keep controllers as orchestrators.';
        }
        if (str_ends_with(strtolower($base), 'controller.php') || str_ends_with(strtolower($base), 'controller')) {
            return 'HTTP orchestration only — align with '.$identity->snapshot->styleName.'.';
        }
        if (str_ends_with(strtolower($base), 'request.php') || str_ends_with(strtolower($base), 'request')) {
            return 'Validation boundary — keep rules out of controllers.';
        }

        return $brief->whyItExists !== '' && ! str_contains(strtolower($brief->whyItExists), 'no recorded')
            ? $brief->whyItExists
            : 'Part of '.$identity->snapshot->styleName.' — verify purpose before changing behavior.';
    }

    private function areaName(string $context): string
    {
        $base = basename(str_replace('\\', '/', $context));
        $base = preg_replace('/\.php$/i', '', $base) ?? $base;
        $base = preg_replace('/(Controller|Service|Repository|Request)$/i', '', $base) ?? $base;

        return $base !== '' ? $base : $context;
    }
}
