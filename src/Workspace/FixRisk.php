<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Aligns with Safe / Assisted / Manual — drives Preview vs Apply policy.
 */
enum FixRisk: string
{
    case Safe = 'safe';
    case Assisted = 'assisted';
    case Design = 'design';

    public function allowsApply(): bool
    {
        return $this === self::Safe;
    }

    public function requiresPreview(): bool
    {
        return $this !== self::Design;
    }

    public function label(): string
    {
        return match ($this) {
            self::Safe => 'Safe',
            self::Assisted => 'Assisted',
            self::Design => 'Design decision',
        };
    }
}
