<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Phase 13 — Architecture Questions.
 *
 * Question → Classify intent → Retrieve evidence → Compose answer → Show sources.
 * Read-only: never mutates. Change belongs to Guidance / Proposal / Controlled Change.
 */
final class ArchitectureQuestionService
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureCollaborationService $collaboration = new ArchitectureCollaborationService,
        private readonly ArchitectureOwnershipService $ownership = new ArchitectureOwnershipService,
        private readonly ArchitectureDecisionMemory $decisions = new ArchitectureDecisionMemory,
        private readonly ArchitectureStandardsService $standards = new ArchitectureStandardsService,
        private readonly ArchitectureLearningService $learning = new ArchitectureLearningService,
        private readonly ArchitectureKnowledgeTransferService $transfer = new ArchitectureKnowledgeTransferService,
        private readonly ArchitectureHistoryService $history = new ArchitectureHistoryService,
        private readonly ArchitectureStoryProjector $stories = new ArchitectureStoryProjector,
    ) {}

    public function parse(string $raw, string $fallbackSubject = ''): ArchitectureQuestion
    {
        $trimmed = trim(preg_replace('/\s+/', ' ', $raw) ?? $raw);
        $lower = strtolower($trimmed);
        $isChange = $this->looksLikeChangeRequest($lower);
        $type = $isChange ? ArchitectureQuestionType::Unknown : $this->detectType($lower);
        $subject = $this->extractSubject($trimmed, $lower, $fallbackSubject);

        return new ArchitectureQuestion(
            raw: $trimmed,
            type: $type,
            subject: $subject,
            normalized: $type->label().($subject !== '' ? ' — '.$subject : ''),
            isChangeRequest: $isChange,
        );
    }

    public function ask(string $projectRoot, string $raw, string $fallbackSubject = ''): ArchitectureAnswer
    {
        $question = $this->parse($raw, $fallbackSubject);

        if ($question->isChangeRequest) {
            return $this->answerChangeBoundary($question);
        }

        return match ($question->type) {
            ArchitectureQuestionType::WhyExists => $this->answerWhy($projectRoot, $question),
            ArchitectureQuestionType::WhatChanged => $this->answerChanged($projectRoot, $question),
            ArchitectureQuestionType::WhoOwns => $this->answerOwns($projectRoot, $question),
            ArchitectureQuestionType::WhatToFollow => $this->answerFollow($projectRoot, $question),
            ArchitectureQuestionType::WhatWorked => $this->answerWorked($projectRoot, $question),
            ArchitectureQuestionType::Unknown => $this->answerUnknown($projectRoot, $question),
        };
    }

    private function looksLikeChangeRequest(string $lower): bool
    {
        return preg_match(
            '/\b(fix|refactor|apply|generate|delete|remove\s+file|mutate|rewrite|implement\s+for\s+me|create\s+a\s+pr)\b/',
            $lower,
        ) === 1;
    }

    private function detectType(string $lower): ArchitectureQuestionType
    {
        if (preg_match('/\b(who\s+owns|ownership|maintained\s+by|owner)\b/', $lower) === 1) {
            return ArchitectureQuestionType::WhoOwns;
        }
        if (preg_match('/\b(what\s+(should\s+i\s+)?follow|which\s+standard|standards?|principle)\b/', $lower) === 1) {
            return ArchitectureQuestionType::WhatToFollow;
        }
        if (preg_match('/\b(what\s+worked|worked\s+before|preferred\s+path|successful\s+pattern|learn(?:ed|ing)?)\b/', $lower) === 1) {
            return ArchitectureQuestionType::WhatWorked;
        }
        if (preg_match('/\b(what\s+changed|history|replay|recent\s+change|evolution)\b/', $lower) === 1) {
            return ArchitectureQuestionType::WhatChanged;
        }
        if (preg_match('/\b(why|rationale|reason|exists?|created|purpose)\b/', $lower) === 1) {
            return ArchitectureQuestionType::WhyExists;
        }

        return ArchitectureQuestionType::Unknown;
    }

    private function extractSubject(string $raw, string $lower, string $fallback): string
    {
        if (preg_match('/\b(?:why|about|for|in|of)\s+([A-Za-z][A-Za-z0-9_\\\\\/.-]+)/i', $raw, $matches) === 1) {
            $candidate = rtrim($matches[1], '?.!,');
            $stop = ['does', 'this', 'the', 'a', 'an', 'it', 'our', 'my', 'here', 'exist', 'exists', 'existed'];
            if (! in_array(strtolower($candidate), $stop, true)) {
                return $candidate;
            }
        }

        if (preg_match('/\b([A-Z][A-Za-z0-9]+(?:Service|Controller|Repository|Request|Module)?)\b/', $raw, $matches) === 1) {
            return $matches[1];
        }

        if (preg_match('/["\']([^"\']+)["\']/', $raw, $matches) === 1) {
            return trim($matches[1]);
        }

        return $fallback !== '' ? $fallback : $this->guessSubjectFromTokens($lower);
    }

    private function guessSubjectFromTokens(string $lower): string
    {
        $tokens = preg_split('/\s+/', $lower) ?: [];
        $skip = ['why', 'does', 'what', 'who', 'should', 'i', 'follow', 'changed', 'here', 'own', 'owns', 'the', 'a', 'an', 'exist', 'exists', 'worked', 'before', 'about', 'for', 'this', 'direction', 'standard', 'standards', 'fix', 'refactor'];
        foreach ($tokens as $token) {
            $token = trim($token, '?.!,');
            if ($token === '' || in_array($token, $skip, true)) {
                continue;
            }
            if (preg_match('/^[a-z0-9_\\\\\/.-]+$/i', $token) === 1) {
                return $token;
            }
        }

        return 'architecture';
    }

    private function answerChangeBoundary(ArchitectureQuestion $question): ArchitectureAnswer
    {
        return new ArchitectureAnswer(
            question: $question,
            reason: 'Architecture Questions are read-only. They explain knowledge — they do not change code.',
            evidence: [
                'Use Guidance to see what might be worth improving.',
                'Use Fix Proposal / Controlled Change to preview and apply improvements.',
                'Use architect:ask only to understand: why / what changed / who owns / what to follow / what worked.',
            ],
            decision: 'Understand ≠ Change',
            sources: [
                new ArchitectureAnswerSource(ArchitectureSourceType::KnowledgeTransfer, 'Questions boundary · understand only'),
            ],
            confidence: 'high',
            summary: 'Change requests belong to the improvement workflow, not architect:ask.',
        );
    }

    private function answerWhy(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $subject = $question->subject;
        $collab = $this->collaboration->forContext($projectRoot, $subject, 40);
        $fileDecisions = $this->decisions->forFile($projectRoot, $this->fileNeedle($subject), $subject);
        $contextDecisions = $this->decisions->forContext($projectRoot, $subject);
        $events = $this->memory->eventsForContext($projectRoot, $subject, 200);
        $storyList = $this->stories->stories($events, $subject);
        $brief = $this->transfer->brief($projectRoot, $subject);

        $evidence = [];
        $reason = '';
        $decision = '';
        /** @var list<ArchitectureAnswerSource> $sources */
        $sources = [];

        foreach ($collab->rationales as $rationale) {
            $reason = $rationale->reason;
            $decision = $rationale->question;
            if ($rationale->tradeoff !== '') {
                $evidence[] = 'Tradeoff: '.$rationale->tradeoff;
            }
            $evidence[] = 'Rationale by '.$rationale->author.' · '.$rationale->createdAt;
            $sources[] = new ArchitectureAnswerSource(
                ArchitectureSourceType::Rationale,
                'Rationale: '.($rationale->question !== '' ? $rationale->question : 'decision'),
                $rationale->id,
            );
            break;
        }

        if ($reason === '' && $fileDecisions !== []) {
            $d = $fileDecisions[0];
            $reason = $d->answer;
            $decision = $d->decision;
            $evidence[] = 'Decision memory for '.$d->file;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Decision, 'Decision: '.$d->decision, $d->file);
        }

        if ($reason === '' && $contextDecisions !== []) {
            $d = $contextDecisions[0];
            $reason = $d->answer;
            $decision = $d->decision;
            $evidence[] = 'Context decision · '.$d->occurredAt;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Decision, 'Decision: '.$d->decision);
        }

        if ($reason === '' && $storyList !== []) {
            $story = $storyList[0];
            $reason = sprintf('Created during %s improvement.', $story->context);
            $decision = $story->decision;
            $evidence[] = 'Story: '.$story->decision;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Story, 'Story: '.$story->decision);
        }

        if ($reason === '') {
            $reason = $brief->whyItExists;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::ContextBrief, 'Context brief: '.$subject);
        }

        $proposals = 0;
        $sessions = 0;
        $healthDelta = 0;
        foreach ($events as $event) {
            if ($event->type === ArchitectureEventType::ProposalCreated) {
                $proposals++;
                $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Event, 'Proposal event', (string) ($event->payload['proposal_id'] ?? ''));
            }
            if ($event->type === ArchitectureEventType::SessionCompleted) {
                $sessions++;
                $before = (int) ($event->payload['health_before'] ?? 0);
                $after = (int) ($event->payload['health_after'] ?? $before);
                $healthDelta += ($after - $before);
                $sources[] = new ArchitectureAnswerSource(
                    ArchitectureSourceType::Session,
                    'Session: '.(string) ($event->payload['goal'] ?? 'improvement'),
                    (string) ($event->payload['session_id'] ?? ''),
                );
            }
        }
        if ($proposals > 0) {
            $evidence[] = $proposals.' related proposal'.($proposals === 1 ? '' : 's');
        }
        if ($sessions > 0) {
            $evidence[] = $sessions.' completed session'.($sessions === 1 ? '' : 's');
            if ($healthDelta !== 0) {
                $evidence[] = sprintf('%+d health impact', $healthDelta);
            }
        }
        foreach ($collab->notes as $note) {
            $evidence[] = 'Note: '.$note->body;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Note, 'Note: '.$note->subjectKey, $note->id);
            if (count($evidence) >= 8) {
                break;
            }
        }

        $standard = $this->standards->forConcept($projectRoot, 'service_extraction');
        if ($standard !== null && $standard->successfulImprovements() > 0) {
            $sources[] = new ArchitectureAnswerSource(
                ArchitectureSourceType::Standard,
                'Standard: '.$standard->concept->label.' v'.$standard->version,
                $standard->concept->id,
            );
            $evidence[] = 'Standard: '.$standard->concept->label.' v'.$standard->version;
        }

        $sources = $this->uniqueSources($sources !== [] ? $sources : [
            new ArchitectureAnswerSource(ArchitectureSourceType::KnowledgeTransfer, 'Knowledge transfer'),
        ]);

        return new ArchitectureAnswer(
            question: $question,
            reason: $reason !== '' ? $reason : 'No recorded reason yet — attach a rationale or complete an improvement session.',
            evidence: array_values(array_unique($evidence)),
            decision: $decision,
            sources: $sources,
            confidence: $this->confidenceFromSources($evidence, $sources),
            summary: $decision !== '' ? $decision.' — '.$reason : $reason,
        );
    }

    private function answerChanged(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $history = $this->history->forContext($projectRoot, $question->subject);
        $evidence = [];
        $sources = [
            new ArchitectureAnswerSource(ArchitectureSourceType::Replay, 'Replay for '.$question->subject),
            new ArchitectureAnswerSource(ArchitectureSourceType::History, 'History for '.$question->subject),
        ];
        foreach (array_slice($history->recentImprovements, 0, 5) as $item) {
            $line = $item->title;
            if ($item->healthBefore !== null && $item->healthAfter !== null) {
                $line .= sprintf(' · health %d → %d', $item->healthBefore, $item->healthAfter);
            }
            $evidence[] = $line;
            $sources[] = new ArchitectureAnswerSource(
                ArchitectureSourceType::Session,
                'Session: '.$item->title,
                $item->sessionId ?? '',
            );
        }
        foreach (array_slice($history->replay, 0, 6) as $entry) {
            if (in_array($entry->type, ['session_completed', 'files_changed', 'rationale_recorded', 'note_added'], true)) {
                $evidence[] = $entry->label;
                $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Event, $entry->label);
            }
            if (count($evidence) >= 8) {
                break;
            }
        }

        $reason = $evidence !== []
            ? sprintf('%d recorded change%s around %s.', count($history->recentImprovements), count($history->recentImprovements) === 1 ? '' : 's', $question->subject)
            : 'No replay history for this subject yet.';

        return new ArchitectureAnswer(
            question: $question,
            reason: $reason,
            evidence: array_values(array_unique($evidence)),
            decision: $history->latestStory?->decision ?? '',
            sources: $this->uniqueSources($sources),
            confidence: $evidence !== [] ? 'medium' : 'low',
            summary: $reason,
        );
    }

    private function answerOwns(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $ownership = $this->ownership->forArea($projectRoot, $question->subject);
        if ($ownership === null) {
            return new ArchitectureAnswer(
                question: $question,
                reason: 'Ownership not recorded yet for '.$question->subject.'.',
                evidence: ['Record ownership via collaboration (kind=ownership).'],
                sources: [
                    new ArchitectureAnswerSource(ArchitectureSourceType::Ownership, 'Ownership lookup · none recorded'),
                ],
                confidence: 'low',
                summary: 'No ownership recorded.',
            );
        }

        $evidence = [
            'Owned by: '.$ownership->ownedBy,
        ];
        if ($ownership->maintainedBy !== '') {
            $evidence[] = 'Maintained by: '.$ownership->maintainedBy;
        }
        if ($ownership->recordedAt !== '') {
            $evidence[] = 'Recorded: '.$ownership->recordedAt;
        }

        return new ArchitectureAnswer(
            question: $question,
            reason: sprintf('%s is owned by %s.', $ownership->area, $ownership->ownedBy),
            evidence: $evidence,
            decision: 'Knowledge responsibility for '.$ownership->area,
            sources: [
                new ArchitectureAnswerSource(
                    ArchitectureSourceType::Ownership,
                    'Ownership: '.$ownership->area.' → '.$ownership->ownedBy,
                    $ownership->area,
                ),
            ],
            confidence: 'high',
            summary: $ownership->ownedBy.($ownership->maintainedBy !== '' ? ' · '.$ownership->maintainedBy : ''),
        );
    }

    private function answerFollow(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $standards = $this->standards->all($projectRoot);
        $evidence = [];
        $sources = [];
        $top = $standards[0] ?? null;
        foreach (array_slice($standards, 0, 4) as $standard) {
            if ($standard->successfulImprovements() < 1 && $top !== $standard) {
                continue;
            }
            $evidence[] = sprintf(
                '%s — %s (v%s · %d successful)',
                $standard->concept->label,
                $standard->principle,
                $standard->version,
                $standard->successfulImprovements(),
            );
            $sources[] = new ArchitectureAnswerSource(
                ArchitectureSourceType::Standard,
                'Standard: '.$standard->concept->label.' v'.$standard->version,
                $standard->concept->id,
            );
        }

        $reason = $top !== null
            ? sprintf('Follow %s: %s', $top->concept->label, $top->principle)
            : 'No standards projected yet from vocabulary + evidence.';

        return new ArchitectureAnswer(
            question: $question,
            reason: $reason,
            evidence: $evidence,
            decision: $top?->concept->label ?? '',
            sources: $this->uniqueSources($sources !== [] ? $sources : [
                new ArchitectureAnswerSource(ArchitectureSourceType::Standard, 'Standards catalog'),
            ]),
            confidence: $evidence !== [] ? 'medium' : 'low',
            summary: $reason,
        );
    }

    private function answerWorked(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $learning = $this->learning->learn($projectRoot);
        $evidence = [];
        $sources = [
            new ArchitectureAnswerSource(ArchitectureSourceType::Learning, 'Architecture Learning'),
        ];
        $reason = $learning->summary;
        $decision = '';

        if ($learning->preferredPaths !== []) {
            $path = $learning->preferredPaths[0];
            $decision = $path->preferredSolution->label;
            $reason = $path->summary;
            $evidence[] = sprintf(
                'Preferred path: %s · chosen %d× · success %.0f%%',
                $path->preferredSolution->label,
                $path->timesChosen,
                $path->successRate * 100,
            );
            foreach ($path->evidence->trustSignals() as $signal) {
                $evidence[] = $signal;
            }
        }
        if ($learning->successfulPatterns !== []) {
            $pattern = $learning->successfulPatterns[0];
            $evidence[] = $pattern->summary;
            foreach (array_slice($pattern->evidence->trustSignals(), 0, 3) as $signal) {
                $evidence[] = $signal;
            }
        }
        foreach (array_slice($learning->risks, 0, 2) as $risk) {
            $evidence[] = 'Risk: '.$risk->risk;
            $sources[] = new ArchitectureAnswerSource(ArchitectureSourceType::Regression, 'Risk: '.$risk->risk);
        }

        return new ArchitectureAnswer(
            question: $question,
            reason: $reason,
            evidence: array_values(array_unique($evidence)),
            decision: $decision,
            sources: $this->uniqueSources($sources),
            confidence: $evidence !== [] ? 'medium' : 'low',
            summary: $reason,
        );
    }

    private function answerUnknown(string $projectRoot, ArchitectureQuestion $question): ArchitectureAnswer
    {
        $transfer = $this->transfer->transfer($projectRoot, $question->subject);
        $evidence = [];
        if ($transfer->onboarding !== null) {
            $evidence[] = 'Direction: '.$transfer->onboarding->currentDirection;
            foreach (array_slice($transfer->onboarding->importantDecisions, 0, 3) as $decision) {
                $evidence[] = $decision;
            }
        }
        if ($transfer->brief !== null) {
            $evidence[] = $transfer->brief->whyItExists;
        }

        return new ArchitectureAnswer(
            question: $question,
            reason: $transfer->summary,
            evidence: $evidence,
            sources: [
                new ArchitectureAnswerSource(ArchitectureSourceType::KnowledgeTransfer, 'Knowledge transfer for '.$question->subject),
            ],
            confidence: $evidence !== [] ? 'medium' : 'low',
            summary: $transfer->summary,
        );
    }

    /**
     * @param  list<string>  $evidence
     * @param  list<ArchitectureAnswerSource>  $sources
     */
    private function confidenceFromSources(array $evidence, array $sources): string
    {
        $types = array_map(static fn (ArchitectureAnswerSource $s): string => $s->type->value, $sources);
        $score = count($evidence)
            + (in_array(ArchitectureSourceType::Rationale->value, $types, true) ? 2 : 0)
            + (in_array(ArchitectureSourceType::Decision->value, $types, true) ? 1 : 0)
            + (in_array(ArchitectureSourceType::Session->value, $types, true) ? 1 : 0);
        if ($score >= 5) {
            return 'high';
        }
        if ($score >= 2) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @param  list<ArchitectureAnswerSource>  $sources
     * @return list<ArchitectureAnswerSource>
     */
    private function uniqueSources(array $sources): array
    {
        $seen = [];
        $out = [];
        foreach ($sources as $source) {
            $key = $source->type->value.'|'.$source->label.'|'.$source->ref;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $source;
        }

        return $out;
    }

    private function fileNeedle(string $subject): string
    {
        return str_ends_with(strtolower($subject), '.php') ? $subject : $subject.'.php';
    }
}
