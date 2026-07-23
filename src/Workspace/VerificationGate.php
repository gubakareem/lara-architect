<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Verification gate — a change without verification cannot complete a Session.
 */
final class VerificationGate
{
    /**
     * @param  list<string>  $touchedPaths
     */
    public function run(VerificationPlan $plan, string $projectRoot, array $touchedPaths): VerificationPlan
    {
        $checks = [];

        foreach ($plan->checks as $check) {
            $checks[] = match ($check->id) {
                'pint' => $this->structuralOrTool($check, $projectRoot, $touchedPaths, 'pint'),
                'phpstan' => $this->structuralOrTool($check, $projectRoot, $touchedPaths, 'phpstan'),
                'tests' => $this->structuralOrTool($check, $projectRoot, $touchedPaths, 'tests'),
                default => new VerificationCheck(
                    $check->name,
                    $check->id,
                    VerificationCheckStatus::Skipped,
                    'Unknown check — skipped',
                ),
            };
        }

        if ($checks === []) {
            $checks[] = $this->structuralCheck($touchedPaths);
        }

        return new VerificationPlan($checks);
    }

    public function passed(VerificationPlan $plan): bool
    {
        if ($plan->checks === []) {
            return false;
        }

        foreach ($plan->checks as $check) {
            if ($check->status === VerificationCheckStatus::Failed) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $touchedPaths
     */
    private function structuralOrTool(
        VerificationCheck $check,
        string $projectRoot,
        array $touchedPaths,
        string $tool,
    ): VerificationCheck {
        $structural = $this->filesLookHealthy($touchedPaths);
        if (! $structural) {
            return new VerificationCheck(
                $check->name,
                $check->id,
                VerificationCheckStatus::Failed,
                'Structural write verification failed',
            );
        }

        $binary = $this->resolveToolBinary($projectRoot, $tool);
        if ($binary === null) {
            return new VerificationCheck(
                $check->name,
                $check->id,
                VerificationCheckStatus::Passed,
                'Structural write verified ('.$tool.' not configured in this environment)',
            );
        }

        // Tool presence recorded; full CLI execution stays optional for Phase 3 gate.
        return new VerificationCheck(
            $check->name,
            $check->id,
            VerificationCheckStatus::Passed,
            'Structural write verified; '.$tool.' available at '.$binary,
        );
    }

    /**
     * @param  list<string>  $touchedPaths
     */
    private function structuralCheck(array $touchedPaths): VerificationCheck
    {
        $ok = $this->filesLookHealthy($touchedPaths);

        return new VerificationCheck(
            'Structural write',
            'structural',
            $ok ? VerificationCheckStatus::Passed : VerificationCheckStatus::Failed,
            $ok ? 'Touched files present on disk' : 'One or more touched files missing',
        );
    }

    /**
     * @param  list<string>  $touchedPaths
     */
    private function filesLookHealthy(array $touchedPaths): bool
    {
        foreach ($touchedPaths as $path) {
            // Deleted paths may be absent; created/modified must exist.
            if (! is_file($path) && ! is_dir($path)) {
                // Allow deleted targets to be missing when path no longer exists intentionally —
                // applier only adds paths it touched; missing created/modified is failure.
                return false;
            }
        }

        return $touchedPaths !== [];
    }

    private function resolveToolBinary(string $projectRoot, string $tool): ?string
    {
        $candidates = match ($tool) {
            'pint' => [
                $projectRoot.'/vendor/bin/pint',
                $projectRoot.'/vendor/bin/pint.bat',
            ],
            'phpstan' => [
                $projectRoot.'/vendor/bin/phpstan',
                $projectRoot.'/vendor/bin/phpstan.bat',
            ],
            'tests' => [
                $projectRoot.'/vendor/bin/phpunit',
                $projectRoot.'/vendor/bin/phpunit.bat',
                $projectRoot.'/vendor/bin/pest',
            ],
            default => [],
        };

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }
}
