<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 13 — Question Intent (typed, not NLP/AI).
 * Same semantic contract for CLI · Workspace · future VS Code · GitHub.
 */
enum ArchitectureQuestionType: string
{
    case WhyExists = 'why_exists';
    case WhatChanged = 'what_changed';
    case WhoOwns = 'who_owns';
    case WhatToFollow = 'what_to_follow';
    case WhatWorked = 'what_worked';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::WhyExists => 'Why does this exist?',
            self::WhatChanged => 'What changed here?',
            self::WhoOwns => 'Who owns this direction?',
            self::WhatToFollow => 'What should I follow?',
            self::WhatWorked => 'What worked before?',
            self::Unknown => 'Architecture question',
        };
    }

    /**
     * Primary knowledge lane this intent routes to.
     */
    public function routesTo(): string
    {
        return match ($this) {
            self::WhyExists => 'rationale',
            self::WhatChanged => 'replay',
            self::WhoOwns => 'ownership',
            self::WhatToFollow => 'standards',
            self::WhatWorked => 'learning',
            self::Unknown => 'knowledge_transfer',
        };
    }
}
