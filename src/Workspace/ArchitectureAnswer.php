<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 13 — deterministic answer from living architecture knowledge.
 * AI later may improve wording; evidence and routing stay factual.
 */
final readonly class ArchitectureAnswer
{
    /**
     * @param  list<string>  $evidence
     * @param  list<ArchitectureAnswerSource>  $sources
     */
    public function __construct(
        public ArchitectureQuestion $question,
        public string $reason,
        public array $evidence,
        public string $decision = '',
        public array $sources = [],
        public string $confidence = 'low',
        public string $summary = '',
    ) {}

    /**
     * @return list<string>
     */
    public function sourceLabels(): array
    {
        return array_values(array_map(
            static fn (ArchitectureAnswerSource $source): string => $source->display(),
            $this->sources,
        ));
    }

    /**
     * @return array<string, int>
     */
    public function sourceCounts(): array
    {
        $counts = [];
        foreach ($this->sources as $source) {
            $key = $source->type->value;
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question' => $this->question->toArray(),
            'reason' => $this->reason,
            'evidence' => $this->evidence,
            'decision' => $this->decision,
            'confidence' => $this->confidence,
            'sources' => array_map(
                static fn (ArchitectureAnswerSource $source): array => $source->toArray(),
                $this->sources,
            ),
            'source_labels' => $this->sourceLabels(),
            'source_counts' => $this->sourceCounts(),
            'summary' => $this->summary !== '' ? $this->summary : $this->reason,
            'mutates' => false,
        ];
    }
}
