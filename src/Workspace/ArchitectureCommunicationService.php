<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 16 — Architecture Communication.
 * Living Architecture Brief — transferable identity, not static documentation.
 * Audience shapes presentation; knowledge stays the same.
 */
final class ArchitectureCommunicationService
{
    public function __construct(
        private readonly ArchitectureIdentityService $identity = new ArchitectureIdentityService,
        private readonly ArchitectureDecisionHistoryService $decisions = new ArchitectureDecisionHistoryService,
        private readonly ArchitectureKnowledgeTransferService $transfer = new ArchitectureKnowledgeTransferService,
        private readonly ArchitectureEvolutionService $evolution = new ArchitectureEvolutionService,
    ) {}

    public function communicate(
        string $projectRoot,
        string $context = 'architecture',
        int $days = 180,
        CommunicationAudience|string $audience = CommunicationAudience::Contributor,
    ): ArchitectureCommunication {
        $audience = $this->resolveAudience($audience);
        $identity = $this->identity->identify($projectRoot, $days);
        $area = $this->areaName($context);
        $history = $this->decisions->forArea(
            $projectRoot,
            $context !== '' && $context !== 'architecture' ? $area : '',
            8,
        );
        $onboarding = $this->transfer->onboarding($projectRoot, $area, null, $days);
        $evolution = $this->evolution->evolve($projectRoot, $days);
        $brief = $this->composeBrief($identity, $history, $onboarding, $evolution, $audience);

        $highlights = $this->highlightsForAudience($brief, $audience);
        $readFirst = $this->readFirstForAudience($brief, $identity, $audience);

        return new ArchitectureCommunication(
            question: $audience->question(),
            headline: sprintf('Architecture Brief · %s', $identity->snapshot->styleName),
            summary: $brief->summary,
            identity: $identity->snapshot,
            highlights: $highlights,
            readFirst: $readFirst,
            brief: $brief,
            audience: $audience,
        );
    }

    public function brief(
        string $projectRoot,
        string $context = 'architecture',
        int $days = 180,
        CommunicationAudience|string $audience = CommunicationAudience::Contributor,
    ): ArchitectureBrief {
        return $this->communicate($projectRoot, $context, $days, $audience)->brief
            ?? new ArchitectureBrief(
                identityStyle: 'Evolving Laravel architecture',
                identityConfidence: 'low',
                currentDirection: 'Still forming',
                principles: [],
                recentEvolution: [],
                importantDecisions: [],
                growthAreas: [],
                whereToStart: [],
                audience: $this->resolveAudience($audience),
            );
    }

    private function composeBrief(
        ArchitectureIdentity $identity,
        ArchitectureDecisionHistory $history,
        ArchitectureOnboarding $onboarding,
        ArchitectureEvolution $evolution,
        CommunicationAudience $audience,
    ): ArchitectureBrief {
        $principles = array_map(
            static fn (IdentityPrinciple $p): string => $p->name,
            array_slice($identity->snapshot->principles, 0, 5),
        );

        $recent = [];
        if ($evolution->direction !== null) {
            $recent[] = $evolution->direction->statement;
        }
        foreach (array_slice($onboarding->recentEvolution, 0, 3) as $line) {
            $recent[] = $line;
        }
        foreach (array_slice($identity->history, -2) as $entry) {
            $recent[] = $entry->period.': became '.$entry->style;
        }

        $decisions = [];
        foreach (array_slice($history->decisions, 0, 5) as $decision) {
            $line = $decision->decision;
            if ($decision->alternatives !== []) {
                $line .= ' (not '.$decision->alternatives[0]->option.')';
            }
            $decisions[] = $line;
        }
        foreach (array_slice($onboarding->importantDecisions, 0, 3) as $line) {
            $decisions[] = $line;
        }

        $growth = array_map(
            static fn (IdentityGrowthArea $g): string => $g->area.': '.$g->evidence,
            array_slice($identity->snapshot->growthAreas, 0, 4),
        );
        foreach (array_slice($onboarding->knownRisks, 0, 2) as $risk) {
            $growth[] = $risk;
        }

        $whereToStart = match ($audience) {
            CommunicationAudience::Developer => array_values(array_unique(array_filter([
                'Read important decisions for this area before changing behavior.',
                $growth[0] ?? 'Watch growth areas listed below.',
                'Use Guidance → Proposal for changes — Questions stay read-only.',
            ]))),
            CommunicationAudience::Architect => array_values(array_unique(array_filter([
                'Direction: '.($evolution->direction !== null ? $evolution->direction->statement : 'still forming'),
                'Identity confidence: '.$identity->snapshot->styleConfidence,
                $recent[0] ?? 'Review recent evolution and decision history.',
            ]))),
            CommunicationAudience::Contributor => array_values(array_unique(array_filter([
                'Start with identity: '.$identity->snapshot->styleName,
                $principles[0] ?? 'Follow verified improvement paths.',
                $decisions[0] ?? 'Ask: why does this exist?',
            ]))),
        };

        $summary = sprintf(
            'Architecture Brief for %s — %s (confidence %s). Living understanding from identity, decisions, and evidence — not documentation.',
            $audience->label(),
            $identity->snapshot->styleName,
            $identity->snapshot->styleConfidence,
        );

        return new ArchitectureBrief(
            identityStyle: $identity->snapshot->styleName,
            identityConfidence: $identity->snapshot->styleConfidence,
            currentDirection: $evolution->direction !== null
                ? $evolution->direction->statement
                : ($onboarding->currentDirection !== '' ? $onboarding->currentDirection : 'Still forming'),
            principles: array_values(array_unique(array_slice($principles, 0, 5))),
            recentEvolution: array_values(array_unique(array_slice($recent, 0, 5))),
            importantDecisions: array_values(array_unique(array_slice($decisions, 0, 6))),
            growthAreas: array_values(array_unique(array_slice($growth, 0, 4))),
            whereToStart: $whereToStart,
            audience: $audience,
            summary: $summary,
        );
    }

    /**
     * @return list<string>
     */
    private function highlightsForAudience(ArchitectureBrief $brief, CommunicationAudience $audience): array
    {
        return match ($audience) {
            CommunicationAudience::Developer => array_values(array_filter([
                'Identity: '.$brief->identityStyle,
                $brief->importantDecisions[0] ?? null,
                $brief->growthAreas[0] ?? null,
                $brief->whereToStart[0] ?? null,
            ])),
            CommunicationAudience::Architect => array_values(array_filter([
                'Direction: '.$brief->currentDirection,
                'Confidence: '.$brief->identityConfidence,
                $brief->recentEvolution[0] ?? null,
                $brief->principles[0] ?? null,
            ])),
            CommunicationAudience::Contributor => array_values(array_filter([
                'Style: '.$brief->identityStyle.' ('.$brief->identityConfidence.')',
                $brief->principles[0] ?? null,
                $brief->importantDecisions[0] ?? null,
                $brief->whereToStart[0] ?? null,
            ])),
        };
    }

    /**
     * @return list<string>
     */
    private function readFirstForAudience(
        ArchitectureBrief $brief,
        ArchitectureIdentity $identity,
        CommunicationAudience $audience,
    ): array {
        $lines = match ($audience) {
            CommunicationAudience::Developer => [
                ...array_slice($brief->importantDecisions, 0, 3),
                ...array_slice($brief->growthAreas, 0, 2),
                ...array_slice($brief->whereToStart, 0, 2),
            ],
            CommunicationAudience::Architect => [
                $brief->currentDirection,
                ...array_slice($brief->recentEvolution, 0, 3),
                ...array_slice($brief->principles, 0, 2),
            ],
            CommunicationAudience::Contributor => [
                ...$brief->whereToStart,
                ...array_slice($brief->principles, 0, 2),
                ...array_slice($brief->importantDecisions, 0, 2),
            ],
        };

        if ($identity->history !== []) {
            $latest = $identity->history[count($identity->history) - 1];
            $lines[] = 'Became '.$latest->style.': '.$latest->reason;
        }

        return array_values(array_unique(array_slice(array_filter($lines), 0, 8)));
    }

    private function resolveAudience(CommunicationAudience|string $audience): CommunicationAudience
    {
        if ($audience instanceof CommunicationAudience) {
            return $audience;
        }

        return CommunicationAudience::tryFrom($audience) ?? CommunicationAudience::Contributor;
    }

    private function areaName(string $context): string
    {
        $base = basename(str_replace('\\', '/', $context));
        $base = preg_replace('/\.php$/i', '', $base) ?? $base;

        return $base !== '' ? $base : 'architecture';
    }
}
