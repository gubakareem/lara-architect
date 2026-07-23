<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Optional reason when guidance is dismissed (or accepted with nuance).
 */
enum GuidanceDismissReason: string
{
    case NotNow = 'not_now';
    case WrongContext = 'wrong_context';
    case AlreadyAddressed = 'already_addressed';
    case Disagreed = 'disagreed';
    case Other = 'other';
}
