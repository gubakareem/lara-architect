<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * How confidently Lara Architect can automate this proposal.
 */
final readonly class FixConfidence
{
    /**
     * @param  list<string>  $reasons
     */
    public function __construct(
        public string $level,
        public array $reasons = [],
    ) {}

    /**
     * @param  list<string>  $reasons
     */
    public static function high(array $reasons = []): self
    {
        return new self('high', $reasons !== [] ? $reasons : [
            'Rule is deterministic',
            'Generated code follows preset',
            'No behavior change intended',
        ]);
    }

    /**
     * @param  list<string>  $reasons
     */
    public static function medium(array $reasons = []): self
    {
        return new self('medium', $reasons);
    }

    /**
     * @param  list<string>  $reasons
     */
    public static function low(array $reasons = []): self
    {
        return new self('low', $reasons);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'reasons' => $this->reasons,
        ];
    }
}
