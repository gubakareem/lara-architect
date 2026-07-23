<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Who receives Architecture Communication — presentation context, not permissions.
 * Same knowledge, different emphasis.
 */
enum CommunicationAudience: string
{
    case Developer = 'developer';
    case Architect = 'architect';
    case Contributor = 'contributor';

    public function question(): string
    {
        return match ($this) {
            self::Developer => 'How do I safely change this?',
            self::Architect => 'What direction are we moving?',
            self::Contributor => 'What should I understand first?',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Developer => 'Developer',
            self::Architect => 'Architect',
            self::Contributor => 'New contributor',
        };
    }
}
