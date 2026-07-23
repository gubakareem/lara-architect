<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 11 — share architectural knowledge inside the Workspace.
 * Notes (contextual) and Rationales (permanent) stay distinct — do not merge.
 */
final class ArchitectureCollaborationService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureOwnershipService $ownership = new ArchitectureOwnershipService,
    ) {}

    public function addNote(
        string $projectRoot,
        CollaborationSubject $subjectType,
        string $subjectKey,
        string $body,
        string $author = 'developer',
        string $context = '',
    ): ArchitectureNote {
        $note = new ArchitectureNote(
            id: 'note_'.bin2hex(random_bytes(6)),
            subjectType: $subjectType,
            subjectKey: $subjectKey,
            body: trim($body),
            author: $author !== '' ? $author : 'developer',
            createdAt: gmdate('c'),
            context: $context,
            lifecycle: KnowledgeLifecycle::Contextual,
        );

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::NoteAdded,
            $context !== '' ? $context : $subjectKey,
            $note->toArray(),
        );

        return $note;
    }

    public function addRationale(
        string $projectRoot,
        string $question,
        string $reason,
        string $author = 'developer',
        string $subjectKey = '',
        CollaborationSubject $subjectType = CollaborationSubject::Decision,
        string $context = '',
        string $tradeoff = '',
    ): ArchitectureRationale {
        $rationale = new ArchitectureRationale(
            id: 'rationale_'.bin2hex(random_bytes(6)),
            question: trim($question),
            reason: trim($reason),
            author: $author !== '' ? $author : 'developer',
            createdAt: gmdate('c'),
            subjectKey: $subjectKey,
            subjectType: $subjectType,
            context: $context,
            tradeoff: trim($tradeoff),
            lifecycle: KnowledgeLifecycle::Permanent,
        );

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::RationaleRecorded,
            $context !== '' ? $context : ($subjectKey !== '' ? $subjectKey : 'architecture'),
            $rationale->toArray(),
        );

        return $rationale;
    }

    public function forContext(string $projectRoot, ?string $context = null, int $limit = 40): ArchitectureCollaboration
    {
        $events = $context !== null && $context !== ''
            ? $this->memory->eventsForContext($projectRoot, $context, 500)
            : $this->memory->allEvents($projectRoot, 2000);

        $notes = [];
        $rationales = [];

        foreach (array_reverse($events) as $event) {
            if ($event->type === ArchitectureEventType::NoteAdded) {
                $notes[] = $this->noteFromPayload($event->payload, $event->occurredAt);
                if (count($notes) >= $limit) {
                    break;
                }
            }
        }

        foreach (array_reverse($events) as $event) {
            if ($event->type === ArchitectureEventType::RationaleRecorded) {
                $rationales[] = $this->rationaleFromPayload($event->payload, $event->occurredAt);
                if (count($rationales) >= $limit) {
                    break;
                }
            }
        }

        $ownership = $context !== null && $context !== ''
            ? $this->ownership->forArea($projectRoot, $context)
            : null;

        $summary = match (true) {
            $notes !== [] || $rationales !== [] => sprintf(
                '%d contextual note%s · %d permanent rationale%s — human architectural knowledge preserved.',
                count($notes),
                count($notes) === 1 ? '' : 's',
                count($rationales),
                count($rationales) === 1 ? '' : 's',
            ),
            default => 'No shared notes yet — attach explanations to standards, decisions, and improvements.',
        };

        return new ArchitectureCollaboration(
            question: 'How can developers share architectural knowledge?',
            summary: $summary,
            notes: $notes,
            rationales: $rationales,
            ownership: $ownership,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function noteFromPayload(array $payload, string $occurredAt): ArchitectureNote
    {
        $type = CollaborationSubject::tryFrom((string) ($payload['subject_type'] ?? 'decision'))
            ?? CollaborationSubject::Decision;

        return new ArchitectureNote(
            id: (string) ($payload['id'] ?? 'note_unknown'),
            subjectType: $type,
            subjectKey: (string) ($payload['subject_key'] ?? ''),
            body: (string) ($payload['body'] ?? ''),
            author: (string) ($payload['author'] ?? 'developer'),
            createdAt: (string) ($payload['created_at'] ?? $occurredAt),
            context: (string) ($payload['context'] ?? ''),
            lifecycle: KnowledgeLifecycle::tryFrom((string) ($payload['lifecycle'] ?? 'contextual'))
                ?? KnowledgeLifecycle::Contextual,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function rationaleFromPayload(array $payload, string $occurredAt): ArchitectureRationale
    {
        $type = CollaborationSubject::tryFrom((string) ($payload['subject_type'] ?? 'decision'))
            ?? CollaborationSubject::Decision;

        return new ArchitectureRationale(
            id: (string) ($payload['id'] ?? 'rationale_unknown'),
            question: (string) ($payload['question'] ?? ''),
            reason: (string) ($payload['reason'] ?? ''),
            author: (string) ($payload['author'] ?? 'developer'),
            createdAt: (string) ($payload['created_at'] ?? $occurredAt),
            subjectKey: (string) ($payload['subject_key'] ?? ''),
            subjectType: $type,
            context: (string) ($payload['context'] ?? ''),
            tradeoff: (string) ($payload['tradeoff'] ?? ''),
            lifecycle: KnowledgeLifecycle::tryFrom((string) ($payload['lifecycle'] ?? 'permanent'))
                ?? KnowledgeLifecycle::Permanent,
        );
    }
}
