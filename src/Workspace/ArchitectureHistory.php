<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Workspace;

/**
 * Architecture History read model for a context.
 * Memory · Story · Decision · Intelligence · Guidance.
 */
final readonly class ArchitectureHistory
{
    /**
     * @param  list<HistoryImprovement>  $recentImprovements
     * @param  list<ReplayEntry>  $replay
     * @param  list<ImprovementStory>  $stories
     * @param  list<ArchitectureDecision>  $decisions
     */
    public function __construct(
        public string $context,
        public ?int $currentHealth,
        public array $recentImprovements,
        public array $replay,
        public ?ArchitectureBaseline $baseline = null,
        public ?SessionConfidence $latestConfidence = null,
        public array $stories = [],
        public ?ArchitectureTrend $trend = null,
        public ?ImprovementStory $latestStory = null,
        public array $decisions = [],
        public ?ArchitectureIntelligence $intelligence = null,
        public ?ArchitectureGuidance $guidance = null,
        public ?GuidedImprovementJourney $journey = null,
        public ?ArchitectureGovernance $governance = null,
        public ?ArchitectureEvolution $evolution = null,
        public ?ArchitectureLearning $learning = null,
        public ?ArchitectureCollaboration $collaboration = null,
        public ?ArchitectureKnowledgeTransfer $knowledgeTransfer = null,
        public ?ArchitectureAnswer $latestAnswer = null,
        public ?ArchitectureConversations $conversations = null,
        public ?ArchitectureDecisionHistory $decisionHistory = null,
        public ?ArchitectureIdentity $identity = null,
        public ?ArchitectureCommunication $communication = null,
        public ?ArchitectureContext $architectureContext = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'context' => $this->context,
            'current_health' => $this->currentHealth,
            'baseline' => $this->baseline?->toArray(),
            'recent_improvements' => array_map(
                static fn (HistoryImprovement $item): array => $item->toArray(),
                $this->recentImprovements,
            ),
            'replay' => array_map(
                static fn (ReplayEntry $entry): array => $entry->toArray(),
                $this->replay,
            ),
            'latest_confidence' => $this->latestConfidence?->toArray(),
            'stories' => array_map(
                static fn (ImprovementStory $story): array => $story->toArray(),
                $this->stories,
            ),
            'latest_story' => $this->latestStory?->toArray(),
            'trend' => $this->trend?->toArray(),
            'decisions' => array_map(
                static fn (ArchitectureDecision $decision): array => $decision->toArray(),
                $this->decisions,
            ),
            'intelligence' => $this->intelligence?->toArray(),
            'guidance' => $this->guidance?->toArray(),
            'journey' => $this->journey?->toArray(),
            'governance' => $this->governance?->toArray(),
            'evolution' => $this->evolution?->toArray(),
            'learning' => $this->learning?->toArray(),
            'collaboration' => $this->collaboration?->toArray(),
            'knowledge_transfer' => $this->knowledgeTransfer?->toArray(),
            'latest_answer' => $this->latestAnswer?->toArray(),
            'conversations' => $this->conversations?->toArray(),
            'decision_history' => $this->decisionHistory?->toArray(),
            'identity' => $this->identity?->toArray(),
            'communication' => $this->communication?->toArray(),
            'architecture_context' => $this->architectureContext?->toArray(),
        ];
    }
}
