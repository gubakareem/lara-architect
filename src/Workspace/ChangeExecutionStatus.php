<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

enum ChangeExecutionStatus: string
{
    case Prepared = 'prepared';
    case Started = 'execution_started';
    case Verifying = 'verifying';
    case Verified = 'verified';
    case Failed = 'failed';
    case Completed = 'completed';
}
