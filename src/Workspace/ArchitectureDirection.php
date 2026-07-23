<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architectural trajectory — where the system is heading (not a product roadmap).
 * from → to makes the direction explicit for future changes.
 */
final readonly class ArchitectureDirection
{
    /**
     * @param  list<string>  $reasons
     * @param  list<string>  $expectedOutcomes
     */
    public function __construct(
        public ArchitectureConcept $concept,
        public string $statement,
        public int $supportingImprovements,
        public array $expectedOutcomes,
        public string $standardVersion = '1.0',
        public string $from = '',
        public string $to = '',
        public array $reasons = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'concept' => $this->concept->toArray(),
            'current_direction' => [
                'from' => $this->from !== '' ? $this->from : 'current_architecture_debt',
                'to' => $this->to !== '' ? $this->to : $this->concept->id,
            ],
            'statement' => $this->statement,
            'reason' => $this->reasons !== [] ? $this->reasons : $this->expectedOutcomes,
            'supporting_improvements' => $this->supportingImprovements,
            'expected_outcomes' => $this->expectedOutcomes,
            'standard_version' => $this->standardVersion,
        ];
    }
}
