<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Controlled Change orchestrator + Architecture Memory recording.
 *
 * Preview → Accept → Prepare → Apply → Verify (gate) → Session
 * Events are written to the Architecture Event Stream (source of truth).
 */
final class ControlledChangeService
{
    public function __construct(
        private readonly ChangeSetApplier $applier = new ChangeSetApplier,
        private readonly VerificationGate $verification = new VerificationGate,
        private readonly ImprovementMetricsStore $metrics = new ImprovementMetricsStore,
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
    ) {}

    public function review(
        FixProposal $proposal,
        ?float $durationSeconds = null,
        string $projectRoot = '',
        string $context = '',
    ): ProposalReviewed {
        $reviewed = ProposalReviewed::capture($proposal, $durationSeconds);

        if ($projectRoot !== '' && $context !== '') {
            $this->memory->record(
                $projectRoot,
                ArchitectureEventType::ProposalReviewed,
                $context,
                $reviewed->toArray(),
                EventCorrelation::empty()->with(proposalId: (string) $proposal->id, issueId: (string) $proposal->issueId),
            );
        }

        return $reviewed;
    }

    public function improve(
        FixProposal $proposal,
        string $projectRoot,
        string $contextName,
        int $healthBefore = 0,
        string $approvedBy = 'developer',
        ?float $reviewDurationSeconds = null,
    ): ControlledChangeResult {
        $reviewed = ProposalReviewed::capture($proposal, $reviewDurationSeconds);
        $proposal = $proposal->markReviewed()->markAccepted();

        $metrics = $this->metrics->load($projectRoot)->recordStarted();
        $this->metrics->save($projectRoot, $metrics);

        if (! $proposal->applyEnabled || ! $proposal->risk->allowsApply()) {
            $execution = ChangeExecution::start($proposal, $approvedBy)
                ->append(
                    ExecutionEventType::ExecutionFailed,
                    ['reason' => 'not_executable'],
                    'This proposal is not executable. Safe Controlled Change only — Assisted/Design stay gated.',
                );

            $timeline = ArchitectureTimeline::forControlledChange($proposal, $reviewed, $execution, false);
            $this->memory->recordFromExecution(
                $projectRoot,
                $contextName,
                $reviewed,
                $execution,
                null,
                EventCorrelation::empty()->with(
                    proposalId: (string) $proposal->id,
                    issueId: (string) $proposal->issueId,
                    executionId: (string) $execution->id,
                ),
            );

            return new ControlledChangeResult($proposal, $reviewed, $execution, null, false, $timeline);
        }

        $execution = ChangeExecution::start($proposal, $approvedBy);

        try {
            $touched = $this->applier->apply($proposal->changeSet, $projectRoot);
            $execution = $execution->append(ExecutionEventType::FilesChanged, [
                'paths' => array_map(
                    static fn (string $path): string => str_replace('\\', '/', $path),
                    $touched,
                ),
                'files_changed' => $proposal->changeSet->summary->filesChanged,
            ]);
        } catch (\Throwable $e) {
            $execution = $execution->append(
                ExecutionEventType::ExecutionFailed,
                ['reason' => $e->getMessage()],
                $e->getMessage(),
            );
            $timeline = ArchitectureTimeline::forControlledChange($proposal, $reviewed, $execution, false);
            $this->memory->recordFromExecution(
                $projectRoot,
                $contextName,
                $reviewed,
                $execution,
                null,
                $this->correlationFor($proposal, $execution),
            );

            return new ControlledChangeResult($proposal, $reviewed, $execution, null, false, $timeline);
        }

        $execution = $execution->append(ExecutionEventType::VerificationStarted);
        $verifiedPlan = $this->verification->run($proposal->verification, $projectRoot, $touched);
        $execution = $execution->withVerificationPlan($verifiedPlan);

        if (! $this->verification->passed($verifiedPlan)) {
            $execution = $execution->append(
                ExecutionEventType::VerificationFailed,
                ['verification' => $verifiedPlan->toArray()],
                'Verification gate failed — Session not recorded.',
            );
            $timeline = ArchitectureTimeline::forControlledChange($proposal, $reviewed, $execution, false);
            $this->memory->recordFromExecution(
                $projectRoot,
                $contextName,
                $reviewed,
                $execution,
                null,
                $this->correlationFor($proposal, $execution),
            );

            return new ControlledChangeResult($proposal, $reviewed, $execution, null, true, $timeline);
        }

        $execution = $execution->append(
            ExecutionEventType::VerificationPassed,
            ['verification' => $verifiedPlan->toArray()],
        );

        $completed = $proposal->architectureImpact->results;
        if ($completed === []) {
            $completed = $proposal->reasoning->benefits !== []
                ? $proposal->reasoning->benefits
                : [$proposal->title];
        }

        $healthAfter = min(100, $healthBefore + max(1, count($completed)));
        $proposal = $proposal->markVerified()->markCompleted();

        $sessionId = SessionId::of('session_'.substr(md5((string) $execution->id), 0, 12));
        $execution = $execution->append(ExecutionEventType::SessionCompleted, [
            'session_id' => (string) $sessionId,
            'goal' => $proposal->summary->intent !== '' ? $proposal->summary->intent : $proposal->title,
            'health_before' => $healthBefore,
            'health_after' => $healthAfter,
            'changes' => $completed,
            'proposal_id' => (string) $proposal->id,
            'execution_id' => (string) $execution->id,
        ]);

        $timeline = ArchitectureTimeline::forControlledChange($proposal, $reviewed, $execution, true);

        $session = ArchitectureSession::fromSuccessfulChange(
            proposal: $proposal,
            execution: $execution,
            contextName: $contextName,
            healthBefore: $healthBefore,
            healthAfter: $healthAfter,
            changes: $completed,
            timeline: $timeline,
        );

        $this->persistSession($session, $projectRoot);
        $this->metrics->save($projectRoot, $metrics->recordCompletedSession());
        $this->memory->recordFromExecution(
            $projectRoot,
            $contextName,
            $reviewed,
            $execution,
            $session,
            $this->correlationFor($proposal, $execution, $session),
        );

        return new ControlledChangeResult(
            $proposal,
            $reviewed,
            $execution,
            $session,
            true,
            $timeline,
        );
    }

    private function correlationFor(
        FixProposal $proposal,
        ChangeExecution $execution,
        ?ArchitectureSession $session = null,
    ): EventCorrelation {
        return EventCorrelation::empty()->with(
            proposalId: (string) $proposal->id,
            issueId: (string) $proposal->issueId,
            executionId: (string) $execution->id,
            sessionId: $session !== null ? (string) $session->id : null,
        );
    }

    public function recordConfidence(
        string $projectRoot,
        string $sessionId,
        bool $helped,
        ?string $note = null,
    ): ?ArchitectureSession {
        $path = $this->sessionPath($projectRoot, $sessionId);
        if ($path === null || ! is_file($path)) {
            return null;
        }

        /** @var array<string, mixed> $raw */
        $raw = json_decode((string) file_get_contents($path), true) ?: [];
        if ($raw === []) {
            return null;
        }

        $confidence = ImprovementConfidence::record(
            SessionId::of((string) ($raw['id'] ?? $sessionId)),
            $helped,
            $note,
        );

        $raw['confidence'] = $confidence->toArray();

        $context = (string) ($raw['context'] ?? $raw['context_name'] ?? 'unknown');
        $before = (int) ($raw['before']['health'] ?? $raw['health']['before'] ?? 0);
        $after = (int) ($raw['after']['health'] ?? $raw['health']['after'] ?? $before);
        $changes = is_array($raw['changes'] ?? null) ? array_values(array_map('strval', $raw['changes'])) : [];
        $verificationSummary = is_array($raw['verification'] ?? null) ? $raw['verification'] : [];

        $synthetic = new ArchitectureSession(
            id: SessionId::of((string) ($raw['id'] ?? $sessionId)),
            proposalId: FixProposalId::of((string) ($raw['proposal_id'] ?? 'fix:unknown')),
            executionId: ChangeExecutionId::of((string) ($raw['execution_id'] ?? 'exec:unknown')),
            context: $context,
            goal: (string) ($raw['goal'] ?? 'Architecture improvement'),
            healthBefore: $before,
            healthAfter: $after,
            changes: $changes,
            verificationSummary: array_map('strval', $verificationSummary),
            verification: VerificationPlan::defaultPlan(),
            timeline: ArchitectureTimeline::empty(),
            completedAt: (string) ($raw['completed_at'] ?? gmdate('c')),
            confidence: $confidence,
        );
        $derived = SessionConfidence::derive($synthetic, $helped);
        $raw['session_confidence'] = $derived->toArray();

        $timeline = $raw['timeline']['events'] ?? [];
        if (! is_array($timeline)) {
            $timeline = [];
        }
        $timeline[] = TimelineEvent::of(TimelineEventType::ConfidenceRecorded, $confidence->toArray())->toArray();
        $raw['timeline'] = ['events' => $timeline];

        file_put_contents($path, json_encode($raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $metrics = $this->metrics->load($projectRoot)->recordConfidence($helped);
        $this->metrics->save($projectRoot, $metrics);

        $this->memory->record($projectRoot, ArchitectureEventType::ConfidenceRecorded, $context, [
            ...$confidence->toArray(),
            'session_confidence' => $derived->toArray(),
            'session_id' => (string) ($raw['id'] ?? $sessionId),
            'proposal_id' => (string) ($raw['proposal_id'] ?? ''),
            'execution_id' => (string) ($raw['execution_id'] ?? ''),
        ], EventCorrelation::empty()->with(
            sessionId: (string) ($raw['id'] ?? $sessionId),
            proposalId: isset($raw['proposal_id']) ? (string) $raw['proposal_id'] : null,
            executionId: isset($raw['execution_id']) ? (string) $raw['execution_id'] : null,
        ));

        return null;
    }

    private function persistSession(ArchitectureSession $session, string $projectRoot): void
    {
        $dir = rtrim(str_replace('\\', '/', $projectRoot), '/').'/storage/architect/sessions';
        if (! is_dir($dir) && ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
            return;
        }

        $safeId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) $session->id) ?? 'session';
        file_put_contents(
            $dir.'/'.$safeId.'.json',
            json_encode($session->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );
    }

    private function sessionPath(string $projectRoot, string $sessionId): ?string
    {
        $dir = rtrim(str_replace('\\', '/', $projectRoot), '/').'/storage/architect/sessions';
        $safeId = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $sessionId) ?? '';
        if ($safeId === '') {
            return null;
        }

        $path = $dir.'/'.$safeId.'.json';

        return is_file($path) ? $path : null;
    }
}
