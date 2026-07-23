<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Look up architectural decisions for a file or context from the Event Stream.
 * Fact layer only — interpretation stays in projections.
 */
final class ArchitectureDecisionMemory
{
    public function __construct(
        private readonly ArchitectureMemory $memory = new ArchitectureMemory,
        private readonly ArchitectureStoryProjector $stories = new ArchitectureStoryProjector,
    ) {}

    /**
     * @return list<ArchitectureDecision>
     */
    public function forFile(string $projectRoot, string $pathOrBasename, ?string $contextHint = null): array
    {
        $needle = $this->normalizeNeedle($pathOrBasename);
        $events = $contextHint !== null && $contextHint !== ''
            ? $this->memory->eventsForContext($projectRoot, $contextHint)
            : $this->memory->allEvents($projectRoot, 500);

        $anchors = [];
        foreach ($events as $event) {
            if ($event->type !== ArchitectureEventType::FilesChanged
                && $event->type !== ArchitectureEventType::SessionCompleted) {
                continue;
            }

            if (! $this->eventTouchesFile($event, $needle)) {
                continue;
            }

            $anchors[] = $event;
        }

        if ($anchors === []) {
            return [];
        }

        $decisions = [];
        $seen = [];

        foreach ($anchors as $anchor) {
            $context = $anchor->context !== '' ? $anchor->context : ($contextHint ?? 'unknown');
            $related = array_values(array_filter(
                $events,
                fn (ArchitectureEvent $candidate): bool => $this->sameJourney($candidate, $anchor)
                    || (strcasecmp($candidate->context, $context) === 0 && $this->sharesCorrelation($candidate, $anchor)),
            ));

            $storyList = $this->stories->stories(
                array_values(array_filter($events, static fn (ArchitectureEvent $e): bool => strcasecmp($e->context, $context) === 0)),
                $context,
            );

            $story = $this->bestStoryForFile($storyList, $related, $needle);
            if ($story === null) {
                continue;
            }

            $key = $story->correlation->chainKey().'|'.$needle;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $basename = basename(str_replace('\\', '/', $pathOrBasename));
            $decisions[] = new ArchitectureDecision(
                file: $basename,
                question: $this->questionFor($basename),
                answer: sprintf(
                    'Created during %s Improvement. Decision: %s. %s',
                    $story->context,
                    $story->decision,
                    $story->result,
                ),
                context: $story->context,
                decision: $story->decision,
                occurredAt: (string) ($story->occurredAt ?? $anchor->occurredAt),
                verification: $story->proof,
                correlation: $story->correlation,
                principle: null,
            );
        }

        return $decisions;
    }

    /**
     * @return list<ArchitectureDecision>
     */
    public function forContext(string $projectRoot, string $context): array
    {
        $events = $this->memory->eventsForContext($projectRoot, $context);
        $storyList = $this->stories->stories($events, $context);
        $decisions = [];

        foreach ($storyList as $story) {
            $decisions[] = new ArchitectureDecision(
                file: $context,
                question: 'Why did we change '.$context.'?',
                answer: sprintf('%s → %s. %s', $story->problem, $story->decision, $story->result),
                context: $story->context,
                decision: $story->decision,
                occurredAt: (string) ($story->occurredAt ?? gmdate('c')),
                verification: $story->proof,
                correlation: $story->correlation,
            );
        }

        return $decisions;
    }

    private function normalizeNeedle(string $pathOrBasename): string
    {
        $normalized = str_replace('\\', '/', $pathOrBasename);

        return strtolower(basename($normalized));
    }

    private function eventTouchesFile(ArchitectureEvent $event, string $needle): bool
    {
        $paths = $event->payload['paths'] ?? [];
        if (is_array($paths)) {
            foreach ($paths as $path) {
                if (str_contains(strtolower(str_replace('\\', '/', (string) $path)), $needle)) {
                    return true;
                }
            }
        }

        $changes = $event->payload['changes'] ?? [];
        if (is_array($changes)) {
            foreach ($changes as $change) {
                if (str_contains(strtolower((string) $change), $needle)) {
                    return true;
                }
            }
        }

        $goal = strtolower((string) ($event->payload['goal'] ?? ''));
        $title = strtolower((string) ($event->payload['title'] ?? ''));

        return str_contains($goal, pathinfo($needle, PATHINFO_FILENAME))
            || str_contains($title, pathinfo($needle, PATHINFO_FILENAME));
    }

    /**
     * @param  list<ImprovementStory>  $stories
     * @param  list<ArchitectureEvent>  $related
     */
    private function bestStoryForFile(array $stories, array $related, string $needle): ?ImprovementStory
    {
        foreach ($stories as $story) {
            if (str_contains(strtolower($story->change), pathinfo($needle, PATHINFO_FILENAME))
                || str_contains(strtolower($story->decision), pathinfo($needle, PATHINFO_FILENAME))) {
                return $story;
            }
        }

        return $stories[0] ?? null;
    }

    private function sameJourney(ArchitectureEvent $candidate, ArchitectureEvent $anchor): bool
    {
        $a = $anchor->correlation->mergePayload($anchor->payload);
        $c = $candidate->correlation->mergePayload($candidate->payload);

        return ($a->sessionId !== null && $c->sessionId !== null && $a->sessionId === $c->sessionId)
            || ($a->executionId !== null && $c->executionId !== null && $a->executionId === $c->executionId)
            || ($a->proposalId !== null && $c->proposalId !== null && $a->proposalId === $c->proposalId)
            || ($a->issueId !== null && $c->issueId !== null && $a->issueId === $c->issueId);
    }

    private function sharesCorrelation(ArchitectureEvent $candidate, ArchitectureEvent $anchor): bool
    {
        return $this->sameJourney($candidate, $anchor);
    }

    private function questionFor(string $basename): string
    {
        if (str_ends_with(strtolower($basename), 'service.php')) {
            return 'Why was this service created?';
        }
        if (str_ends_with(strtolower($basename), 'request.php')) {
            return 'Why was this form request introduced?';
        }
        if (str_ends_with(strtolower($basename), 'repository.php')) {
            return 'Why was this repository introduced?';
        }

        return 'Why does this file look like this now?';
    }
}
