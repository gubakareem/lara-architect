<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * "After this change, I will verify…" — a fix is not complete until checks succeed.
 */
final readonly class VerificationPlan
{
    /**
     * @param  list<VerificationCheck>  $checks
     */
    public function __construct(
        public array $checks,
    ) {}

    public static function defaultPlan(): self
    {
        return new self([
            new VerificationCheck('Laravel Pint', 'pint'),
            new VerificationCheck('PHPStan', 'phpstan'),
            new VerificationCheck('Tests', 'tests'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'checks' => array_map(
                static fn (VerificationCheck $check): array => $check->toArray(),
                $this->checks,
            ),
        ];
    }
}
