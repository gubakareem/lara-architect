<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Before/after architecture movement — what makes Preview different from a git diff.
 */
final readonly class ArchitectureImpact
{
    /**
     * @param  list<string>  $beforeNodes
     * @param  list<ArchitectureEdge>  $beforeEdges
     * @param  list<string>  $afterNodes
     * @param  list<ArchitectureEdge>  $afterEdges
     * @param  list<string>  $removed
     * @param  list<string>  $added
     * @param  list<string>  $results
     */
    public function __construct(
        public array $beforeNodes,
        public array $beforeEdges,
        public array $afterNodes,
        public array $afterEdges,
        public array $removed,
        public array $added,
        public array $results,
        public string $verificationReason = '',
    ) {}

    /**
     * @param  list<string>  $beforeNodes
     * @param  list<ArchitectureEdge>  $beforeEdges
     * @param  list<string>  $afterNodes
     * @param  list<ArchitectureEdge>  $afterEdges
     * @param  list<string>  $results
     */
    public static function graph(
        array $beforeNodes,
        array $beforeEdges,
        array $afterNodes,
        array $afterEdges,
        array $results,
        string $verificationReason = '',
    ): self {
        $beforeLabels = array_map(static fn (ArchitectureEdge $e): string => $e->label(), $beforeEdges);
        $afterLabels = array_map(static fn (ArchitectureEdge $e): string => $e->label(), $afterEdges);

        return new self(
            beforeNodes: $beforeNodes,
            beforeEdges: $beforeEdges,
            afterNodes: $afterNodes,
            afterEdges: $afterEdges,
            removed: array_values(array_diff($beforeLabels, $afterLabels)),
            added: array_values(array_diff($afterLabels, $beforeLabels)),
            results: $results,
            verificationReason: $verificationReason,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'before' => [
                'nodes' => $this->beforeNodes,
                'edges' => array_map(
                    static fn (ArchitectureEdge $edge): array => $edge->toArray(),
                    $this->beforeEdges,
                ),
            ],
            'after' => [
                'nodes' => $this->afterNodes,
                'edges' => array_map(
                    static fn (ArchitectureEdge $edge): array => $edge->toArray(),
                    $this->afterEdges,
                ),
            ],
            'removed' => $this->removed,
            'added' => $this->added,
            'results' => $this->results,
            'verification_reason' => $this->verificationReason,
        ];
    }
}
