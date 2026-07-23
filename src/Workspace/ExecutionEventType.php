<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Append-only execution ledger events — never rewrite history.
 */
enum ExecutionEventType: string
{
    case ExecutionStarted = 'execution_started';
    case FilesChanged = 'files_changed';
    case VerificationStarted = 'verification_started';
    case VerificationPassed = 'verification_passed';
    case VerificationFailed = 'verification_failed';
    case SessionCompleted = 'session_completed';
    case ExecutionFailed = 'execution_failed';
}
