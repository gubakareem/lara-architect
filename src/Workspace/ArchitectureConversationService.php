<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 14 — Architecture Conversations.
 *
 * Event-based reasoning over what we know. Not a chatbot.
 * Conversation → Decision → Rationale (durable). Conversations never replace rationales.
 */
final class ArchitectureConversationService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureCollaborationService $collaboration = new ArchitectureCollaborationService,
    ) {}

    public function start(
        string $projectRoot,
        string $topic,
        string $context,
        ConversationSubject $subjectType = ConversationSubject::Decision,
        string $subjectKey = '',
        string $author = 'developer',
        string $openingQuestion = '',
    ): ArchitectureConversation {
        $id = 'conv_'.bin2hex(random_bytes(6));
        $startedAt = gmdate('c');
        $key = $subjectKey !== '' ? $subjectKey : $context;

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::ConversationStarted,
            $context !== '' ? $context : $key,
            [
                'conversation_id' => $id,
                'topic' => trim($topic),
                'context' => $context,
                'subject_type' => $subjectType->value,
                'subject_key' => $key,
                'status' => DecisionLifecycle::Open->value,
                'author' => $author !== '' ? $author : 'developer',
                'started_at' => $startedAt,
            ],
        );

        $entries = [];
        if (trim($openingQuestion) !== '') {
            $entries[] = $this->addEntry(
                projectRoot: $projectRoot,
                conversationId: $id,
                context: $context !== '' ? $context : $key,
                type: ConversationEntryType::Question,
                content: trim($openingQuestion),
                author: $author,
            );
        }

        return new ArchitectureConversation(
            id: $id,
            context: $context !== '' ? $context : $key,
            topic: trim($topic),
            status: $entries !== [] ? DecisionLifecycle::Discussing : DecisionLifecycle::Open,
            subjectType: $subjectType,
            subjectKey: $key,
            entries: $entries,
            startedAt: $startedAt,
        );
    }

    public function addEntry(
        string $projectRoot,
        string $conversationId,
        string $context,
        ConversationEntryType $type,
        string $content,
        string $author = 'developer',
    ): ConversationEntry {
        $entry = new ConversationEntry(
            id: 'centry_'.bin2hex(random_bytes(5)),
            type: $type,
            content: trim($content),
            author: $author !== '' ? $author : 'developer',
            createdAt: gmdate('c'),
            conversationId: $conversationId,
        );

        $status = match ($type) {
            ConversationEntryType::Decision => DecisionLifecycle::Proposed,
            ConversationEntryType::Rationale => DecisionLifecycle::Accepted,
            default => DecisionLifecycle::Discussing,
        };

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::ConversationEntryAdded,
            $context,
            [
                ...$entry->toArray(),
                'status' => $status->value,
            ],
        );

        return $entry;
    }

    /**
     * Resolve conversation: create DecisionOutcome and optionally a durable Rationale.
     *
     * @param  list<DecisionAlternative|array<string, mixed>>  $alternatives
     */
    public function reachDecision(
        string $projectRoot,
        string $conversationId,
        string $context,
        string $decision,
        string $rationaleReason = '',
        string $rationaleQuestion = '',
        string $tradeoff = '',
        string $author = 'developer',
        bool $createRationale = true,
        bool $futureReference = true,
        array $alternatives = [],
    ): DecisionOutcome {
        $rationaleId = null;
        $parsedAlternatives = $this->parseAlternatives($alternatives);

        if (trim($decision) === '') {
            $outcome = new DecisionOutcome(
                decision: 'No decision made',
                result: 'no_decision',
                futureReference: false,
                lifecycle: DecisionLifecycle::NoDecision,
                alternatives: $parsedAlternatives,
            );
        } elseif ($createRationale && trim($rationaleReason) !== '') {
            $rationale = $this->collaboration->addRationale(
                projectRoot: $projectRoot,
                question: $rationaleQuestion !== '' ? $rationaleQuestion : $decision,
                reason: $rationaleReason,
                author: $author,
                subjectKey: $context,
                subjectType: CollaborationSubject::Decision,
                context: $context,
                tradeoff: $tradeoff,
            );
            $rationaleId = $rationale->id;
            $outcome = new DecisionOutcome(
                decision: trim($decision),
                result: 'rationale_created',
                futureReference: $futureReference,
                rationaleId: $rationaleId,
                lifecycle: DecisionLifecycle::Recorded,
                alternatives: $parsedAlternatives,
            );
        } else {
            $outcome = new DecisionOutcome(
                decision: trim($decision),
                result: 'decision_accepted',
                futureReference: $futureReference,
                lifecycle: DecisionLifecycle::Accepted,
                alternatives: $parsedAlternatives,
            );
        }

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::ConversationDecisionReached,
            $context,
            [
                'conversation_id' => $conversationId,
                ...$outcome->toArray(),
                'author' => $author !== '' ? $author : 'developer',
            ],
        );

        $this->addEntry(
            projectRoot: $projectRoot,
            conversationId: $conversationId,
            context: $context,
            type: ConversationEntryType::Decision,
            content: $outcome->decision,
            author: $author,
        );

        if ($rationaleId !== null) {
            $this->addEntry(
                projectRoot: $projectRoot,
                conversationId: $conversationId,
                context: $context,
                type: ConversationEntryType::Rationale,
                content: $rationaleReason,
                author: $author,
            );
        }

        return $outcome;
    }

    public function close(
        string $projectRoot,
        string $conversationId,
        string $context,
        DecisionLifecycle $finalStatus = DecisionLifecycle::Recorded,
        string $author = 'developer',
    ): void {
        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::ConversationClosed,
            $context,
            [
                'conversation_id' => $conversationId,
                'status' => $finalStatus->value,
                'closed_at' => gmdate('c'),
                'author' => $author !== '' ? $author : 'developer',
            ],
        );
    }

    /**
     * Close without architecture decision — still valuable history.
     */
    public function closeWithoutDecision(
        string $projectRoot,
        string $conversationId,
        string $context,
        string $author = 'developer',
    ): DecisionOutcome {
        $outcome = new DecisionOutcome(
            decision: 'No decision made',
            result: 'no_decision',
            futureReference: false,
            lifecycle: DecisionLifecycle::NoDecision,
        );

        $this->memory->record(
            $projectRoot,
            ArchitectureEventType::ConversationDecisionReached,
            $context,
            [
                'conversation_id' => $conversationId,
                ...$outcome->toArray(),
                'author' => $author !== '' ? $author : 'developer',
            ],
        );

        $this->close($projectRoot, $conversationId, $context, DecisionLifecycle::NoDecision, $author);

        return $outcome;
    }

    public function forSubject(
        string $projectRoot,
        string $subjectKey,
        ?ConversationSubject $subjectType = null,
        int $limit = 20,
    ): ArchitectureConversations {
        $all = $this->project($projectRoot);
        $filtered = array_values(array_filter(
            $all,
            static function (ArchitectureConversation $c) use ($subjectKey, $subjectType): bool {
                if (strcasecmp($c->subjectKey, $subjectKey) !== 0 && strcasecmp($c->context, $subjectKey) !== 0) {
                    return false;
                }
                if ($subjectType !== null && $c->subjectType !== $subjectType) {
                    return false;
                }

                return true;
            },
        ));

        $filtered = array_slice($filtered, 0, $limit);
        $summary = $filtered === []
            ? 'No conversations yet — discuss standards, improvements, and rationales without turning this into a chat product.'
            : sprintf(
                '%d conversation%s attached to %s.',
                count($filtered),
                count($filtered) === 1 ? '' : 's',
                $subjectKey,
            );

        return new ArchitectureConversations(
            question: 'What do we think about what we know?',
            summary: $summary,
            conversations: $filtered,
            subjectKey: $subjectKey,
        );
    }

    public function forContext(string $projectRoot, ?string $context = null, int $limit = 20): ArchitectureConversations
    {
        if ($context !== null && $context !== '') {
            return $this->forSubject($projectRoot, $context, null, $limit);
        }

        $all = array_slice($this->project($projectRoot), 0, $limit);
        $summary = $all === []
            ? 'No architecture conversations yet.'
            : sprintf('%d architecture conversation%s.', count($all), count($all) === 1 ? '' : 's');

        return new ArchitectureConversations(
            question: 'What do we think about what we know?',
            summary: $summary,
            conversations: $all,
        );
    }

    public function find(string $projectRoot, string $conversationId): ?ArchitectureConversation
    {
        foreach ($this->project($projectRoot) as $conversation) {
            if ($conversation->id === $conversationId) {
                return $conversation;
            }
        }

        return null;
    }

    /**
     * @return list<ArchitectureConversation>
     */
    private function project(string $projectRoot): array
    {
        $events = $this->memory->allEvents($projectRoot, 4000);
        /** @var array<string, array{meta: array<string, mixed>, entries: list<ConversationEntry>, outcome: ?DecisionOutcome, closed: bool}> $bags */
        $bags = [];

        foreach ($events as $event) {
            $payload = $event->payload;
            $id = (string) ($payload['conversation_id'] ?? '');
            if ($id === '') {
                continue;
            }

            $bags[$id] ??= [
                'meta' => [],
                'entries' => [],
                'outcome' => null,
                'closed' => false,
            ];

            if ($event->type === ArchitectureEventType::ConversationStarted) {
                $bags[$id]['meta'] = [
                    ...$payload,
                    'context' => (string) ($payload['context'] ?? $event->context),
                ];
            }

            if ($event->type === ArchitectureEventType::ConversationEntryAdded) {
                $bags[$id]['entries'][] = ConversationEntry::fromPayload($payload, $event->occurredAt);
                if (($bags[$id]['meta']['status'] ?? '') === DecisionLifecycle::Open->value) {
                    $bags[$id]['meta']['status'] = DecisionLifecycle::Discussing->value;
                }
                if (isset($payload['status'])) {
                    $bags[$id]['meta']['status'] = (string) $payload['status'];
                }
            }

            if ($event->type === ArchitectureEventType::ConversationDecisionReached) {
                $bags[$id]['outcome'] = DecisionOutcome::fromPayload($payload);
                $bags[$id]['meta']['status'] = $bags[$id]['outcome']->lifecycle->value;
            }

            if ($event->type === ArchitectureEventType::ConversationClosed) {
                $bags[$id]['closed'] = true;
                $bags[$id]['meta']['closed_at'] = (string) ($payload['closed_at'] ?? $event->occurredAt);
                if (isset($payload['status'])) {
                    $bags[$id]['meta']['status'] = (string) $payload['status'];
                }
            }
        }

        $out = [];
        foreach ($bags as $id => $bag) {
            $meta = $bag['meta'];
            if ($meta === []) {
                continue;
            }
            $status = DecisionLifecycle::tryFrom((string) ($meta['status'] ?? 'open')) ?? DecisionLifecycle::Open;
            $out[] = new ArchitectureConversation(
                id: $id,
                context: (string) ($meta['context'] ?? ''),
                topic: (string) ($meta['topic'] ?? 'Architecture discussion'),
                status: $status,
                subjectType: ConversationSubject::tryFrom((string) ($meta['subject_type'] ?? 'decision'))
                    ?? ConversationSubject::Decision,
                subjectKey: (string) ($meta['subject_key'] ?? $meta['context'] ?? ''),
                entries: $bag['entries'],
                outcome: $bag['outcome'],
                startedAt: (string) ($meta['started_at'] ?? ''),
                closedAt: (string) ($meta['closed_at'] ?? ''),
            );
        }

        usort(
            $out,
            static fn (ArchitectureConversation $a, ArchitectureConversation $b): int => strcmp($b->startedAt, $a->startedAt),
        );

        return $out;
    }

    /**
     * @param  list<DecisionAlternative|array<string, mixed>>  $alternatives
     * @return list<DecisionAlternative>
     */
    private function parseAlternatives(array $alternatives): array
    {
        $out = [];
        foreach ($alternatives as $row) {
            if ($row instanceof DecisionAlternative) {
                $out[] = $row;
                continue;
            }
            if (is_array($row) && (string) ($row['option'] ?? '') !== '') {
                $out[] = DecisionAlternative::fromPayload($row);
            }
        }

        return $out;
    }
}
