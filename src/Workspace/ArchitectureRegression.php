<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture regression — learning, not failure.
 * Evidence-first: what returned after what previously worked.
 */
final readonly class ArchitectureRegression
{
    /**
     * @param  list<string>  $evidence
     */
    public function __construct(
        public string $signal,
        public string $observed,
        public string $previousPattern,
        public array $evidence,
        public ?ArchitectureConcept $relatedConcept = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'signal' => $this->signal,
            'observed' => $this->observed,
            'previous_pattern' => $this->previousPattern,
            'evidence' => $this->evidence,
            'related_concept' => $this->relatedConcept?->toArray(),
        ];
    }
}
