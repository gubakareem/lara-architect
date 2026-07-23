<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

enum VerificationCheckStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Passed = 'passed';
    case Failed = 'failed';
    case Skipped = 'skipped';
}
