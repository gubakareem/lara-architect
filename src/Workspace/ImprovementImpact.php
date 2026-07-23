<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Multi-dimension impact so developers can prioritize (not every violation is urgent).
 */
final readonly class ImprovementImpact
{
    public function __construct(
        public string $overall,
        public string $architecture = 'medium',
        public string $testing = 'medium',
        public string $complexity = 'medium',
    ) {}

    public static function high(string $architecture = 'high', string $testing = 'high', string $complexity = 'medium'): self
    {
        return new self('high', $architecture, $testing, $complexity);
    }

    public static function medium(string $architecture = 'medium', string $testing = 'medium', string $complexity = 'medium'): self
    {
        return new self('medium', $architecture, $testing, $complexity);
    }

    public static function low(string $architecture = 'low', string $testing = 'low', string $complexity = 'medium'): self
    {
        return new self('low', $architecture, $testing, $complexity);
    }

    public function sortWeight(): int
    {
        return match (strtolower($this->overall)) {
            'high' => 3,
            'medium' => 2,
            default => 1,
        };
    }

    /**
     * @return array{overall: string, architecture: string, testing: string, complexity: string}
     */
    public function toArray(): array
    {
        return [
            'overall' => $this->overall,
            'architecture' => $this->architecture,
            'testing' => $this->testing,
            'complexity' => $this->complexity,
        ];
    }
}
