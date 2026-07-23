<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * One dependency edge in an Architecture Impact graph.
 */
final readonly class ArchitectureEdge
{
    public function __construct(
        public string $from,
        public string $to,
    ) {}

    /**
     * @return array{from: string, to: string}
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    public function label(): string
    {
        return $this->from.' → '.$this->to;
    }
}
