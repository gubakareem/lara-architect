<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Workspace\ArchitectureBaseline;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureBaselineStore;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureEventType;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureHistoryService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureMemory;
use KarimAshraf\LaraArchitect\Workspace\EventCorrelation;
use KarimAshraf\LaraArchitect\Workspace\SessionConfidence;
use PHPUnit\Framework\TestCase;

class ArchitectureMemoryTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/lara-architect-memory-'.uniqid('', true);
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->root);
        parent::tearDown();
    }

    public function test_event_stream_projects_to_history_replay(): void
    {
        $memory = new ArchitectureMemory;
        $corr = EventCorrelation::empty()->with(issueId: 'issue:1', proposalId: 'fix:1', executionId: 'exec:1', sessionId: 'session_abc');
        $memory->record($this->root, ArchitectureEventType::IssueDetected, 'ProductController', [
            'title' => 'Direct Model Usage',
            'issue_id' => 'issue:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::ProposalCreated, 'ProductController', [
            'title' => 'Extract ProductService',
            'proposal_id' => 'fix:1',
            'issue_id' => 'issue:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::VerificationPassed, 'ProductController', [
            'proposal_id' => 'fix:1',
            'execution_id' => 'exec:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::SessionCompleted, 'ProductController', [
            'session_id' => 'session_abc',
            'proposal_id' => 'fix:1',
            'execution_id' => 'exec:1',
            'goal' => 'Extract business logic into service',
            'health_before' => 91,
            'health_after' => 94,
            'changes' => ['Layer violation resolved'],
        ], $corr);

        $history = (new ArchitectureHistoryService($memory))->forContext(
            $this->root,
            'ProductController',
            94,
        );

        $this->assertSame('ProductController', $history->context);
        $this->assertGreaterThanOrEqual(4, count($history->replay));
        $this->assertCount(1, $history->recentImprovements);
        $this->assertSame('Extract business logic into service', $history->recentImprovements[0]->title);
        $this->assertNotNull($history->latestConfidence);
        $this->assertSame('high', $history->latestConfidence->level);
        $this->assertNotNull($history->latestStory);
        $this->assertSame('Direct Model Usage', $history->latestStory->problem);
        $this->assertStringContainsString('Extract', $history->latestStory->decision);
        $this->assertStringContainsString('Verification', $history->latestStory->proof);
        $this->assertNotNull($history->trend);
        $this->assertSame(1, $history->trend->improvements);
        $this->assertSame('fix:1', $history->replay[0]->correlation?->proposalId ?? $history->latestStory->correlation->proposalId);
        $this->assertFileExists((new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEventStore)->streamPath($this->root));
        $this->assertNotNull($history->intelligence);
        $this->assertNotEmpty($history->intelligence->summary);
        $this->assertArrayHasKey('story', $history->latestStory->toArray());
    }

    public function test_decision_memory_answers_why_file_exists(): void
    {
        $memory = new ArchitectureMemory;
        $corr = EventCorrelation::empty()->with(
            issueId: 'issue:1',
            proposalId: 'fix:1',
            executionId: 'exec:1',
            sessionId: 'session_svc',
        );

        $memory->record($this->root, ArchitectureEventType::IssueDetected, 'ProductController', [
            'title' => 'Controller owns business logic',
            'issue_id' => 'issue:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::ProposalCreated, 'ProductController', [
            'title' => 'Introduce service boundary',
            'proposal_id' => 'fix:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::FilesChanged, 'ProductController', [
            'paths' => ['app/Http/Controllers/ProductController.php', 'app/Services/ProductService.php'],
            'execution_id' => 'exec:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::VerificationPassed, 'ProductController', [
            'execution_id' => 'exec:1',
        ], $corr);
        $memory->record($this->root, ArchitectureEventType::SessionCompleted, 'ProductController', [
            'session_id' => 'session_svc',
            'proposal_id' => 'fix:1',
            'execution_id' => 'exec:1',
            'goal' => 'Introduce service boundary',
            'health_before' => 90,
            'health_after' => 93,
            'changes' => ['ProductService.php created'],
        ], $corr);

        $decisions = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionMemory($memory))
            ->forFile($this->root, 'ProductService.php', 'ProductController');

        $this->assertNotEmpty($decisions);
        $this->assertStringContainsString('service', strtolower($decisions[0]->question));
        $this->assertStringContainsString('service', strtolower($decisions[0]->decision));

        $intel = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory))
            ->analyze($this->root);
        $this->assertStringContainsString('productcontroller', strtolower($intel->summary));
        $this->assertStringContainsString('confidence', strtolower($intel->summary));
        $this->assertNotEmpty($intel->mostImprovedAreas);
        $top = $intel->mostImprovedAreas[0];
        $this->assertSame('most_improved', $top->kind());
        $this->assertNotEmpty($top->observed());
        $this->assertNotEmpty($top->whyItMatters());
        $this->assertNotEmpty($top->overTime()->before);
        $this->assertContains($top->confidence(), ['high', 'medium', 'low']);
        $this->assertGreaterThanOrEqual(1, $top->evidence()->events);
        $this->assertArrayHasKey('evidence', $top->toArray());
        $this->assertArrayHasKey('confidence', $top->toArray());
        $this->assertIsArray($top->toArray()['over_time']);
    }

    public function test_intelligence_explainability_for_repeated_problems(): void
    {
        $memory = new ArchitectureMemory;
        foreach (['A', 'B', 'C'] as $i => $ctx) {
            $corr = EventCorrelation::empty()->with(issueId: 'issue:'.$i);
            $memory->record($this->root, ArchitectureEventType::IssueDetected, $ctx, [
                'title' => 'Direct Model Usage',
                'issue_id' => 'issue:'.$i,
            ], $corr);
        }
        $corr = EventCorrelation::empty()->with(issueId: 'issue:0', sessionId: 'session_r0');
        $memory->record($this->root, ArchitectureEventType::SessionCompleted, 'A', [
            'session_id' => 'session_r0',
            'issue_id' => 'issue:0',
            'goal' => 'Extract Service Layer',
            'health_before' => 80,
            'health_after' => 85,
        ], $corr);

        $intel = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory))
            ->analyze($this->root);

        $this->assertNotEmpty($intel->repeatedProblems);
        $repeated = $intel->repeatedProblems[0];
        $this->assertSame('repeated_problem', $repeated->kind());
        $this->assertSame(3, $repeated->occurrences);
        $this->assertSame(1, $repeated->resolved);
        $this->assertSame(2, $repeated->remaining);
        $this->assertStringContainsString('Direct Model', $repeated->insight());
        $this->assertNotEmpty($intel->insights());

        $guidance = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGuidanceService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
        ))->recommend($this->root, currentHealth: 70);

        $this->assertNotNull($guidance);
        $this->assertStringContainsString('worth looking at', strtolower($guidance->headline));
        $this->assertNotEmpty($guidance->why);
        $this->assertSame('service_extraction', $guidance->concept->id);
        $this->assertArrayHasKey('recommendation', $guidance->toArray());
        $this->assertArrayHasKey('evidence', $guidance->toArray());
        $this->assertArrayHasKey('similar_improvements', $guidance->toArray()['evidence']);

        $vocab = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary;
        $this->assertSame(
            'Service Extraction',
            $vocab->canonicalize('Move Logic to Service')->label,
        );

        $journey = (new \KarimAshraf\LaraArchitect\Workspace\GuidedImprovementJourneyService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGuidanceService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            ),
            $memory,
        ))->forContext($this->root, 'A', 70, [
            ['id' => 'issue:open', 'title' => 'Direct Model Usage', 'context' => 'A'],
        ]);
        $this->assertNotNull($journey);
        $this->assertNotEmpty($journey->currentState);
        $this->assertNotEmpty($journey->history);
        $this->assertNotEmpty($journey->expectedDirection);
        $this->assertTrue($journey->canCreateProposal);
        $this->assertSame('issue:open', $journey->proposeIssueId);
        $this->assertArrayHasKey('why_now', $journey->toArray());
        $this->assertArrayHasKey('history', $journey->toArray()['why_now']);
        $this->assertArrayHasKey('expected_direction', $journey->toArray()['why_now']);
        $this->assertArrayHasKey('related_evidence', $journey->toArray());
        $this->assertArrayHasKey('action', $journey->toArray());
        $this->assertNotNull($journey->standard);
        $this->assertStringContainsString('orchestrate', strtolower($journey->standard->principle));
        $this->assertSame('1.0', $journey->standard->version);
        $this->assertNotEmpty($journey->standard->evidence->trustSignals());

        $memory->recordGuidanceDecision(
            $this->root,
            'A',
            \KarimAshraf\LaraArchitect\Workspace\GuidanceDecision::Dismissed,
            $guidance->concept,
            \KarimAshraf\LaraArchitect\Workspace\GuidanceDismissReason::NotNow,
        );
        $events = $memory->eventsForContext($this->root, 'A');
        $dismissed = array_values(array_filter(
            $events,
            static fn ($e) => $e->type === ArchitectureEventType::GuidanceDismissed,
        ));
        $this->assertCount(1, $dismissed);
        $this->assertSame('not_now', $dismissed[0]->payload['reason']);

        $standards = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            $memory,
        ))->all($this->root);
        $this->assertNotEmpty($standards);
        $this->assertSame('Service Extraction', $standards[0]->concept->label);
        $this->assertArrayHasKey('trust_signals', $standards[0]->evidence->toArray());

        $governance = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
        ))->assess($this->root);
        $this->assertStringContainsString('architecture we value', strtolower($governance->question));
        $this->assertNotEmpty($governance->alignments);
        $this->assertNotEmpty($governance->snapshots);
        $this->assertArrayHasKey('overall_alignment', $governance->toArray());
        $this->assertArrayHasKey('alignment', $governance->snapshots[0]->toArray());
        $this->assertArrayHasKey('confidence', $governance->snapshots[0]->toArray());

        $evolution = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService(
            $memory,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            ),
        ))->evolve($this->root);
        $this->assertNotNull($evolution->direction);
        $this->assertContains($evolution->momentum->level, ['positive', 'negative', 'neutral']);
        $this->assertArrayHasKey('momentum', $evolution->toArray());
        $this->assertArrayHasKey('current_direction', $evolution->direction->toArray());
        $this->assertNotEmpty($evolution->trajectories);

        $accepted = $memory->recordGuidanceDecision(
            $this->root,
            'A',
            \KarimAshraf\LaraArchitect\Workspace\GuidanceDecision::Accepted,
            $guidance->concept,
        );
        $this->assertSame('guidance_accepted', $accepted->type->value);
        $intent = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureChangeIntent(
            area: 'A',
            intent: 'simplify_business_logic',
            expectedDirection: 'increase_service_boundary',
            createdFrom: \KarimAshraf\LaraArchitect\Workspace\ChangeIntentSource::Guidance,
            concept: $guidance->concept,
        );
        $memory->recordChangeIntent($this->root, $intent, 'A');

        $learning = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService(
            $memory,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService(
                $memory,
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                ),
            ),
        ))->learn($this->root);
        $this->assertStringContainsString('learned', strtolower($learning->question));
        $this->assertNotEmpty($learning->successfulPatterns);
        $this->assertNotEmpty($learning->preferredPaths);
        $this->assertNotEmpty($learning->recentIntents);
        $this->assertSame('guidance', $learning->recentIntents[0]->createdFrom->value);

        $patternEvidence = $learning->successfulPatterns[0]->evidence;
        $this->assertGreaterThan(0, $patternEvidence->attempts);
        $this->assertArrayHasKey('trust_signals', $patternEvidence->toArray());
        $this->assertArrayHasKey('average_health_delta', $patternEvidence->toArray());
        $this->assertArrayHasKey('evidence', $learning->preferredPaths[0]->toArray());
    }

    public function test_architecture_collaboration_preserves_notes_and_rationales(): void
    {
        $memory = new ArchitectureMemory;
        $collab = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCollaborationService($memory);

        $note = $collab->addNote(
            projectRoot: $this->root,
            subjectType: \KarimAshraf\LaraArchitect\Workspace\CollaborationSubject::Decision,
            subjectKey: 'payments',
            body: 'We keep payment calculations outside controllers because multiple channels share the rules.',
            author: 'karim',
            context: 'payments',
        );
        $this->assertSame('decision', $note->subjectType->value);

        $rationale = $collab->addRationale(
            projectRoot: $this->root,
            question: 'Why Redis instead of database queue?',
            reason: 'High throughput requirement.',
            author: 'karim',
            subjectKey: 'queues',
            subjectType: \KarimAshraf\LaraArchitect\Workspace\CollaborationSubject::Decision,
            context: 'payments',
        );
        $this->assertStringContainsString('Redis', $rationale->question);

        $report = $collab->forContext($this->root, 'payments');
        $this->assertStringContainsString('share architectural knowledge', strtolower($report->question));
        $this->assertCount(1, $report->notes);
        $this->assertCount(1, $report->rationales);
        $this->assertSame($note->body, $report->notes[0]->body);
        $this->assertSame($rationale->reason, $report->rationales[0]->reason);

        $history = (new ArchitectureHistoryService($memory))->forContext($this->root, 'payments');
        $this->assertNotNull($history->collaboration);
        $this->assertNotEmpty($history->collaboration->notes);
        $replayTypes = array_map(
            static fn ($entry) => $entry->type,
            $history->replay,
        );
        $this->assertContains('note_added', $replayTypes);
        $this->assertContains('rationale_recorded', $replayTypes);
        $this->assertSame('contextual', $note->lifecycle->value);
        $this->assertSame('permanent', $rationale->lifecycle->value);
    }

    public function test_knowledge_transfer_onboarding_and_ownership(): void
    {
        $memory = new ArchitectureMemory;
        $ownership = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureOwnershipService($memory);
        $collab = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCollaborationService($memory, $ownership);

        $ownership->record($this->root, 'Billing', 'Billing Architecture', 'Payments Team');
        $collab->addRationale(
            projectRoot: $this->root,
            question: 'Why Redis instead of database queue?',
            reason: 'Peak order processing requires asynchronous throughput.',
            author: 'karim',
            subjectKey: 'Billing',
            context: 'Billing',
            tradeoff: 'Requires Redis infrastructure.',
        );
        $collab->addNote(
            projectRoot: $this->root,
            subjectType: \KarimAshraf\LaraArchitect\Workspace\CollaborationSubject::Decision,
            subjectKey: 'Billing',
            body: 'This module is currently being migrated.',
            context: 'Billing',
        );

        $transfer = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService(
            $memory,
            $collab,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionMemory($memory),
            $ownership,
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeMapService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                $collab,
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
            ),
        ))->transfer($this->root, 'Billing');

        $this->assertStringContainsString('understand', strtolower($transfer->question));
        $this->assertNotNull($transfer->ownership);
        $this->assertSame('Billing Architecture', $transfer->ownership->ownedBy);
        $this->assertNotNull($transfer->onboarding);
        $this->assertStringContainsString('Welcome', $transfer->onboarding->welcome);
        $this->assertNotEmpty($transfer->onboarding->importantDecisions);
        $this->assertNotNull($transfer->brief);
        $this->assertNotNull($transfer->knowledgeMap);
        $this->assertArrayHasKey('context_brief', $transfer->toArray());

        $history = (new ArchitectureHistoryService($memory))->forContext($this->root, 'Billing');
        $this->assertNotNull($history->knowledgeTransfer);
        $this->assertSame('Billing Architecture', $history->knowledgeTransfer->ownership?->ownedBy);
    }

    public function test_architecture_questions_route_deterministically(): void
    {
        $memory = new ArchitectureMemory;
        $ownership = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureOwnershipService($memory);
        $collab = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCollaborationService($memory, $ownership);

        $ownership->record($this->root, 'ProductService', 'Catalog Architecture', 'Orders Team');
        $collab->addRationale(
            projectRoot: $this->root,
            question: 'Why ProductService exists',
            reason: 'Created during Service Extraction improvement.',
            author: 'karim',
            subjectKey: 'ProductService',
            context: 'ProductService',
            tradeoff: 'Extra service class to maintain.',
        );
        $memory->record($this->root, ArchitectureEventType::SessionCompleted, 'ProductService', [
            'goal' => 'Extract ProductService',
            'health_before' => 90,
            'health_after' => 94,
            'session_id' => 'session_ask',
        ]);

        $questions = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureQuestionService(
            $memory,
            $collab,
            $ownership,
        );

        $why = $questions->ask($this->root, 'why ProductService exists');
        $this->assertSame('why_exists', $why->question->type->value);
        $this->assertSame('ProductService', $why->question->subject);
        $this->assertSame('rationale', $why->question->type->routesTo());
        $this->assertStringContainsString('Service Extraction', $why->reason);
        $this->assertNotEmpty($why->evidence);
        $this->assertNotEmpty($why->sources);
        $this->assertSame('rationale', $why->sources[0]->type->value);
        $this->assertFalse($why->question->isChangeRequest);
        $this->assertArrayHasKey('source_counts', $why->toArray());

        $owns = $questions->ask($this->root, 'who owns ProductService');
        $this->assertSame('who_owns', $owns->question->type->value);
        $this->assertStringContainsString('Catalog Architecture', $owns->reason);

        $changed = $questions->ask($this->root, 'what changed in ProductService');
        $this->assertSame('what_changed', $changed->question->type->value);
        $this->assertSame('replay', $changed->sources[0]->type->value);

        $follow = $questions->ask($this->root, 'what should I follow');
        $this->assertSame('what_to_follow', $follow->question->type->value);

        $worked = $questions->ask($this->root, 'what worked before');
        $this->assertSame('what_worked', $worked->question->type->value);
        $this->assertSame('learning', $worked->sources[0]->type->value);

        $blocked = $questions->ask($this->root, 'fix ProductService');
        $this->assertTrue($blocked->question->isChangeRequest);
        $this->assertStringContainsString('read-only', strtolower($blocked->reason));
        $this->assertFalse($blocked->toArray()['mutates']);
    }

    public function test_architecture_conversations_bridge_to_rationale(): void
    {
        $memory = new ArchitectureMemory;
        $collab = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCollaborationService($memory);
        $conversations = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureConversationService($memory, $collab);

        $started = $conversations->start(
            projectRoot: $this->root,
            topic: 'Queue Architecture',
            context: 'OrderProcessing',
            subjectType: \KarimAshraf\LaraArchitect\Workspace\ConversationSubject::Decision,
            subjectKey: 'OrderProcessing',
            openingQuestion: 'Why not database queues?',
        );
        $this->assertSame('discussing', $started->status->value);
        $this->assertCount(1, $started->entries);

        $conversations->addEntry(
            projectRoot: $this->root,
            conversationId: $started->id,
            context: 'OrderProcessing',
            type: \KarimAshraf\LaraArchitect\Workspace\ConversationEntryType::Evidence,
            content: 'Current queue usage: 500 jobs/min',
        );
        $conversations->addEntry(
            projectRoot: $this->root,
            conversationId: $started->id,
            context: 'OrderProcessing',
            type: \KarimAshraf\LaraArchitect\Workspace\ConversationEntryType::Opinion,
            content: 'Database queue could work, but load tests showed latency spikes.',
        );

        $outcome = $conversations->reachDecision(
            projectRoot: $this->root,
            conversationId: $started->id,
            context: 'OrderProcessing',
            decision: 'Keep Redis queue',
            rationaleReason: 'Throughput requirement for payment processing.',
            rationaleQuestion: 'Why Redis instead of database queue?',
            tradeoff: 'Requires Redis infrastructure.',
            alternatives: [
                new \KarimAshraf\LaraArchitect\Workspace\DecisionAlternative(
                    option: 'Database queue',
                    status: \KarimAshraf\LaraArchitect\Workspace\AlternativeStatus::Rejected,
                    reason: 'Load tests showed latency spikes.',
                ),
                new \KarimAshraf\LaraArchitect\Workspace\DecisionAlternative(
                    option: 'Sync processing',
                    status: \KarimAshraf\LaraArchitect\Workspace\AlternativeStatus::Rejected,
                    reason: 'Cannot meet peak order throughput.',
                ),
            ],
        );
        $this->assertSame('rationale_created', $outcome->result);
        $this->assertSame('recorded', $outcome->lifecycle->value);
        $this->assertNotNull($outcome->rationaleId);
        $this->assertCount(2, $outcome->alternatives);
        $this->assertSame('rejected', $outcome->alternatives[0]->status->value);

        $conversations->close($this->root, $started->id, 'OrderProcessing');

        $found = $conversations->find($this->root, $started->id);
        $this->assertNotNull($found);
        $this->assertNotNull($found->outcome);
        $this->assertGreaterThanOrEqual(4, count($found->entries));

        $report = $conversations->forSubject($this->root, 'OrderProcessing');
        $this->assertCount(1, $report->conversations);
        $this->assertStringContainsString('think', strtolower($report->question));

        $rationales = $collab->forContext($this->root, 'OrderProcessing')->rationales;
        $this->assertNotEmpty($rationales);
        $this->assertStringContainsString('Throughput', $rationales[0]->reason);

        $noDecision = $conversations->start(
            projectRoot: $this->root,
            topic: 'Temporary idea',
            context: 'OrderProcessing',
            subjectKey: 'OrderProcessing',
        );
        $closed = $conversations->closeWithoutDecision($this->root, $noDecision->id, 'OrderProcessing');
        $this->assertSame('no_decision', $closed->lifecycle->value);

        $history = (new ArchitectureHistoryService($memory))->forContext($this->root, 'OrderProcessing');
        $this->assertNotNull($history->conversations);
        $this->assertGreaterThanOrEqual(2, $history->conversations->conversations);
        $replayTypes = array_map(static fn ($e) => $e->type, $history->replay);
        $this->assertContains('conversation_started', $replayTypes);
        $this->assertContains('conversation_decision_reached', $replayTypes);

        $decisionHistory = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService(
            $conversations,
            $memory,
            $collab,
        ))->forArea($this->root, 'OrderProcessing');
        $this->assertNotEmpty($decisionHistory->decisions);
        $kept = null;
        foreach ($decisionHistory->decisions as $record) {
            if ($record->decision === 'Keep Redis queue') {
                $kept = $record;
                break;
            }
        }
        $this->assertNotNull($kept);
        $this->assertNotEmpty($kept->alternatives);

        $identity = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
        ))->identify($this->root);
        $this->assertStringContainsString('believe', strtolower($identity->question));
        $this->assertNotSame('', $identity->style);
        $this->assertNotEmpty($identity->principles);
        $this->assertArrayHasKey('strong_areas', $identity->toArray());
        $this->assertArrayHasKey('snapshot', $identity->toArray());
        $this->assertArrayHasKey('style', $identity->snapshot->toArray());
        $this->assertContains($identity->snapshot->styleConfidence, ['low', 'medium', 'high']);

        $communication = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCommunicationService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
        ))->communicate(
            $this->root,
            'OrderProcessing',
            180,
            \KarimAshraf\LaraArchitect\Workspace\CommunicationAudience::Contributor,
        );
        $this->assertStringContainsString('understand', strtolower($communication->question));
        $this->assertNotNull($communication->identity);
        $this->assertNotEmpty($communication->highlights);
        $this->assertNotNull($communication->brief);
        $this->assertSame('architecture_brief', $communication->brief->toArray()['kind']);
        $this->assertSame('contributor', $communication->audience->value);

        $developerBrief = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCommunicationService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
        ))->communicate(
            $this->root,
            'OrderProcessing',
            180,
            \KarimAshraf\LaraArchitect\Workspace\CommunicationAudience::Developer,
        );
        $this->assertStringContainsString('safely', strtolower($developerBrief->question));
        $this->assertSame('developer', $developerBrief->audience->value);

        $architectureContext = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureContextService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGuidanceService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCommunicationService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                            $memory,
                        ),
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            ),
        ))->forSubject($this->root, 'OrderProcessing');
        $this->assertStringContainsString('before i touch', strtolower($architectureContext->question));
        $this->assertSame('architecture_context', $architectureContext->toArray()['kind']);
        $this->assertNotSame('', $architectureContext->purpose);
        $this->assertNotSame('', $architectureContext->identityStyle);

        $envelope = (new \KarimAshraf\LaraArchitect\Workspace\ArchitectureContextService(
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                $memory,
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGuidanceService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
            ),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureCommunicationService(
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService(
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                        $memory,
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService($memory),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService(
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService(
                            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary,
                            new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                            $memory,
                        ),
                        new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    ),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                    new \KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService($memory),
                    $memory,
                ),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService($conversations, $memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService($memory, $collab),
                new \KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService($memory),
            ),
        ))->envelope($this->root, 'OrderProcessing');
        $payload = $envelope->toArray();
        $this->assertSame('1.0', $payload['schema_version']);
        $this->assertSame('architecture_context_envelope', $payload['kind']);
        $this->assertSame('OrderProcessing', $payload['context']['target']);
        $this->assertArrayHasKey('style', $payload['identity']);
        $this->assertArrayHasKey('important', $payload['decisions']);
        $this->assertNotEmpty($payload['allowed_questions']);
        $this->assertContains('why_exists', $payload['allowed_questions']);
        $this->assertContains('what_to_follow', $payload['allowed_questions']);
        $this->assertTrue($payload['boundary']['can_explain']);
        $this->assertFalse($payload['boundary']['can_modify']);
        $this->assertContains('explain', $payload['boundary']['may']);
        $this->assertContains('direct_code_scan', $payload['boundary']['must_not']);
    }

    public function test_baseline_captured_once(): void
    {
        $memory = new ArchitectureMemory;
        $store = new ArchitectureBaselineStore;
        $baseline = new ArchitectureBaseline(gmdate('c'), 82, 124, 560, 'demo');
        $store->save($this->root, $baseline);

        $again = $store->latest($this->root);
        $this->assertNotNull($again);
        $this->assertSame(82, $again->health);
        $this->assertSame(124, $again->violations);
    }

    public function test_session_confidence_derives_from_signals(): void
    {
        $session = new \KarimAshraf\LaraArchitect\Workspace\ArchitectureSession(
            id: \KarimAshraf\LaraArchitect\Workspace\SessionId::of('session_x'),
            proposalId: \KarimAshraf\LaraArchitect\Workspace\FixProposalId::of('fix:x'),
            executionId: \KarimAshraf\LaraArchitect\Workspace\ChangeExecutionId::of('exec:x'),
            context: 'ProductController',
            goal: 'Extract service',
            healthBefore: 91,
            healthAfter: 94,
            changes: ['Layer violation resolved'],
            verificationSummary: ['pint' => 'passed', 'phpstan' => 'passed', 'tests' => 'passed'],
            verification: \KarimAshraf\LaraArchitect\Workspace\VerificationPlan::defaultPlan(),
            timeline: \KarimAshraf\LaraArchitect\Workspace\ArchitectureTimeline::empty(),
            completedAt: gmdate('c'),
        );

        $confidence = SessionConfidence::derive($session, true);
        $this->assertTrue($confidence->success);
        $this->assertSame('high', $confidence->level);
        $this->assertTrue($confidence->signals['verification']);
        $this->assertSame('+3', $confidence->signals['health_change']);
    }

    private function deleteTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $path = $item->getPathname();
            $item->isDir() ? @rmdir($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
