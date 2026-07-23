<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Guidance Decision Memory — architecture learning, not product telemetry.
 * Opportunity can end with “not now” and that is still a valuable fact.
 */
enum GuidanceDecision: string
{
    case Viewed = 'viewed';
    case Accepted = 'accepted';
    case Dismissed = 'dismissed';
}
