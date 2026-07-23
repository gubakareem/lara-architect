<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Projects Standards + Learning contexts + human notes/rationales into relationships.
 */
final class ArchitectureKnowledgeMapService
{
    public function __construct(
        private readonly ArchitectureStandardsService $standards = new ArchitectureStandardsService,
        private readonly ArchitectureCollaborationService $collaboration = new ArchitectureCollaborationService,
        private readonly ArchitectureLearningService $learning = new ArchitectureLearningService,
    ) {}

    public function map(string $projectRoot, int $days = 180): ArchitectureKnowledgeMap
    {
        $standards = $this->standards->all($projectRoot, min(90, $days));
        $learning = $this->learning->learn($projectRoot, $days);
        $collab = $this->collaboration->forContext($projectRoot, null, 200);

        $contextsByConcept = [];
        foreach ($learning->successfulPatterns as $pattern) {
            $contextsByConcept[$pattern->concept->id] = $pattern->evidence->contexts;
        }

        $entries = [];
        foreach ($standards as $standard) {
            $usedBy = $contextsByConcept[$standard->concept->id] ?? $standard->evidence->contexts;
            $usedBy = array_values(array_unique(array_filter($usedBy)));
            sort($usedBy);

            if ($standard->successfulImprovements() < 1 && $usedBy === []) {
                continue;
            }

            [$noteCount, $rationaleCount] = $this->documentCounts($standard, $usedBy, $collab);

            $entries[] = new KnowledgeMapEntry(
                concept: $standard->concept,
                usedBy: array_slice($usedBy, 0, 12),
                rationaleCount: $rationaleCount,
                noteCount: $noteCount,
            );
        }

        usort(
            $entries,
            static fn (KnowledgeMapEntry $a, KnowledgeMapEntry $b): int => (count($b->usedBy) + $b->rationaleCount + $b->noteCount)
                <=> (count($a->usedBy) + $a->rationaleCount + $a->noteCount),
        );

        $entries = array_slice($entries, 0, 8);
        $summary = $entries === []
            ? 'No connected knowledge yet — standards, improvements, and human notes will appear here.'
            : sprintf(
                '%d standard%s connected to improvements and human documentation.',
                count($entries),
                count($entries) === 1 ? '' : 's',
            );

        return new ArchitectureKnowledgeMap(
            question: 'How do standards, improvements, and human knowledge connect?',
            summary: $summary,
            entries: $entries,
        );
    }

    /**
     * @param  list<string>  $usedBy
     * @return array{0: int, 1: int}
     */
    private function documentCounts(ArchitectureStandard $standard, array $usedBy, ArchitectureCollaboration $collab): array
    {
        $noteCount = 0;
        $rationaleCount = 0;
        $id = strtolower($standard->concept->id);
        $label = strtolower($standard->concept->label);
        $contextSet = array_fill_keys(array_map('strtolower', $usedBy), true);

        foreach ($collab->notes as $note) {
            $key = strtolower($note->subjectKey !== '' ? $note->subjectKey : $note->context);
            if ($this->matches($key, $id, $label, $contextSet) || $note->subjectType === CollaborationSubject::Concept) {
                $noteCount++;
            }
        }
        foreach ($collab->rationales as $rationale) {
            $key = strtolower($rationale->subjectKey !== '' ? $rationale->subjectKey : $rationale->context);
            if ($this->matches($key, $id, $label, $contextSet) || $rationale->subjectType === CollaborationSubject::Concept) {
                $rationaleCount++;
            }
        }

        return [$noteCount, $rationaleCount];
    }

    /**
     * @param  array<string, true>  $contextSet
     */
    private function matches(string $key, string $id, string $label, array $contextSet): bool
    {
        if ($key === '') {
            return false;
        }
        if (isset($contextSet[$key])) {
            return true;
        }
        if (str_contains($key, $id) || str_contains($key, $label)) {
            return true;
        }
        foreach (array_keys($contextSet) as $context) {
            if ($context !== '' && (str_contains($key, $context) || str_contains($context, $key))) {
                return true;
            }
        }

        return false;
    }
}
