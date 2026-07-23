<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 14 — Decision Lifecycle.
 * Not every discussion becomes architecture; "no decision" is also history.
 */
enum DecisionLifecycle: string
{
    case Open = 'open';
    case Discussing = 'discussing';
    case Proposed = 'proposed';
    case Accepted = 'accepted';
    case Recorded = 'recorded';
    case Referenced = 'referenced';
    case NoDecision = 'no_decision';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Discussing => 'Discussing',
            self::Proposed => 'Proposed',
            self::Accepted => 'Accepted',
            self::Recorded => 'Recorded',
            self::Referenced => 'Referenced',
            self::NoDecision => 'No decision made',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::Recorded
            || $this === self::Referenced
            || $this === self::NoDecision;
    }
}
