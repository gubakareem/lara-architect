<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Where an ArchitectureChangeIntent originated.
 */
enum ChangeIntentSource: string
{
    case Guidance = 'guidance';
    case Proposal = 'proposal';
    case Manual = 'manual';
    case Session = 'session';
}
