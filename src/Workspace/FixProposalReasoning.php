<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture reasoning for a FixProposal — deterministic, not AI.
 * Powers learning, PR comments, and docs later.
 */
final readonly class FixProposalReasoning
{
    /**
     * @param  list<string>  $benefits
     */
    public function __construct(
        public string $rule,
        public string $principle,
        public array $benefits = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule' => $this->rule,
            'principle' => $this->principle,
            'benefits' => $this->benefits,
        ];
    }
}
