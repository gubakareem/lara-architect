<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture Session — recorded only after verification succeeds.
 * Schema is Replay-first: before / changes / verification / after.
 */
final readonly class ArchitectureSession
{
    public const SCHEMA_VERSION = '1.0';

    /**
     * @param  list<string>  $changes
     * @param  array<string, string>  $verificationSummary  check id => status
     */
    public function __construct(
        public SessionId $id,
        public FixProposalId $proposalId,
        public ChangeExecutionId $executionId,
        public string $context,
        public string $goal,
        public int $healthBefore,
        public int $healthAfter,
        public array $changes,
        public array $verificationSummary,
        public VerificationPlan $verification,
        public ArchitectureTimeline $timeline,
        public string $completedAt,
        public string $status = 'completed',
        public ?ImprovementConfidence $confidence = null,
        public ?SessionConfidence $sessionConfidence = null,
        public string $schemaVersion = self::SCHEMA_VERSION,
    ) {}

    /**
     * @param  list<string>  $changes
     */
    public static function fromSuccessfulChange(
        FixProposal $proposal,
        ChangeExecution $execution,
        string $contextName,
        int $healthBefore,
        int $healthAfter,
        array $changes,
        ArchitectureTimeline $timeline,
        ?bool $developerAccepted = null,
    ): self {
        $summary = [];
        foreach ($execution->verificationPlan->checks as $check) {
            $summary[$check->id] = $check->status->value;
        }

        $session = new self(
            id: SessionId::of('session_'.substr(md5((string) $execution->id), 0, 12)),
            proposalId: $proposal->id,
            executionId: $execution->id,
            context: $contextName,
            goal: $proposal->summary->intent !== '' ? $proposal->summary->intent : $proposal->title,
            healthBefore: $healthBefore,
            healthAfter: $healthAfter,
            changes: $changes,
            verificationSummary: $summary,
            verification: $execution->verificationPlan,
            timeline: $timeline,
            completedAt: gmdate('c'),
            status: 'completed',
        );

        return new self(
            $session->id,
            $session->proposalId,
            $session->executionId,
            $session->context,
            $session->goal,
            $session->healthBefore,
            $session->healthAfter,
            $session->changes,
            $session->verificationSummary,
            $session->verification,
            $session->timeline,
            $session->completedAt,
            $session->status,
            null,
            SessionConfidence::derive($session, $developerAccepted),
            $session->schemaVersion,
        );
    }

    public function withConfidence(ImprovementConfidence $confidence): self
    {
        $base = new self(
            $this->id,
            $this->proposalId,
            $this->executionId,
            $this->context,
            $this->goal,
            $this->healthBefore,
            $this->healthAfter,
            $this->changes,
            $this->verificationSummary,
            $this->verification,
            $this->timeline->append(TimelineEventType::ConfidenceRecorded, $confidence->toArray()),
            $this->completedAt,
            $this->status,
            $confidence,
            null,
            $this->schemaVersion,
        );

        return new self(
            $base->id,
            $base->proposalId,
            $base->executionId,
            $base->context,
            $base->goal,
            $base->healthBefore,
            $base->healthAfter,
            $base->changes,
            $base->verificationSummary,
            $base->verification,
            $base->timeline,
            $base->completedAt,
            $base->status,
            $confidence,
            SessionConfidence::derive($base, $confidence->helped),
            $base->schemaVersion,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => (string) $this->id,
            'schema_version' => $this->schemaVersion,
            'proposal_id' => (string) $this->proposalId,
            'execution_id' => (string) $this->executionId,
            'context' => $this->context,
            'goal' => $this->goal,
            'title' => $this->context.' Improvement',
            'before' => [
                'health' => $this->healthBefore,
            ],
            'changes' => $this->changes,
            'verification' => $this->verificationSummary,
            'verification_plan' => $this->verification->toArray(),
            'after' => [
                'health' => $this->healthAfter,
            ],
            'context_name' => $this->context,
            'completed' => $this->changes,
            'health' => [
                'before' => $this->healthBefore,
                'after' => $this->healthAfter,
            ],
            'timeline' => $this->timeline->toArray(),
            'confidence' => $this->confidence?->toArray(),
            'session_confidence' => $this->sessionConfidence?->toArray(),
            'completed_at' => $this->completedAt,
            'status' => $this->status,
        ];
    }
}
