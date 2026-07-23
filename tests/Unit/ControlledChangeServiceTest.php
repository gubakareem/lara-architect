<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Workspace\ArchitectureEdge;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureImpact;
use KarimAshraf\LaraArchitect\Workspace\ChangeExecutionStatus;
use KarimAshraf\LaraArchitect\Workspace\ChangeSet;
use KarimAshraf\LaraArchitect\Workspace\ControlledChangeService;
use KarimAshraf\LaraArchitect\Workspace\FileChange;
use KarimAshraf\LaraArchitect\Workspace\FileChangeType;
use KarimAshraf\LaraArchitect\Workspace\FixConfidence;
use KarimAshraf\LaraArchitect\Workspace\FixProposal;
use KarimAshraf\LaraArchitect\Workspace\FixProposalId;
use KarimAshraf\LaraArchitect\Workspace\FixProposalReasoning;
use KarimAshraf\LaraArchitect\Workspace\FixProposalSummary;
use KarimAshraf\LaraArchitect\Workspace\FixRisk;
use KarimAshraf\LaraArchitect\Workspace\ImprovementMetricsStore;
use KarimAshraf\LaraArchitect\Workspace\IssueId;
use KarimAshraf\LaraArchitect\Workspace\VerificationPlan;
use PHPUnit\Framework\TestCase;

class ControlledChangeServiceTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/lara-architect-cc-'.uniqid('', true);
        mkdir($this->fixtureRoot.'/app/Http/Controllers', 0777, true);
        file_put_contents(
            $this->fixtureRoot.'/app/Http/Controllers/ProductController.php',
            "<?php\nclass ProductController {\n    public function store() {\n        Validator::make([])->validate();\n    }\n}\n",
        );
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->fixtureRoot);
        parent::tearDown();
    }

    public function test_controlled_change_writes_files_verifies_and_records_session(): void
    {
        $proposal = $this->safeFormRequestProposal();
        $result = (new ControlledChangeService)->improve(
            proposal: $proposal,
            projectRoot: $this->fixtureRoot,
            contextName: 'ProductController',
            healthBefore: 91,
            approvedBy: 'developer',
            reviewDurationSeconds: 12.5,
        );

        $this->assertTrue($result->succeeded());
        $this->assertSame('high', $result->reviewed->confidenceAtReview);
        $this->assertSame(12.5, $result->reviewed->durationSeconds);
        $this->assertSame(ChangeExecutionStatus::Completed, $result->execution->status());
        $this->assertNotNull($result->session);
        $this->assertSame(91, $result->session->healthBefore);
        $this->assertSame(94, $result->session->healthAfter);
        $this->assertSame('completed', $result->proposal->status->value);
        $this->assertSame('Move validation out of the controller', $result->session->goal);
        $this->assertArrayHasKey('before', $result->session->toArray());
        $this->assertNotEmpty($result->execution->events);
        $this->assertNotEmpty($result->timeline->events);
        $this->assertFileExists($this->fixtureRoot.'/app/Http/Requests/ProductStoreRequest.php');
        $this->assertStringContainsString(
            'ProductStoreRequest',
            (string) file_get_contents($this->fixtureRoot.'/app/Http/Controllers/ProductController.php'),
        );

        $sessionFiles = glob($this->fixtureRoot.'/storage/architect/sessions/*.json') ?: [];
        $this->assertNotEmpty($sessionFiles);

        $metrics = (new ImprovementMetricsStore)->load($this->fixtureRoot);
        $this->assertSame(1, $metrics->started);
        $this->assertSame(1, $metrics->completedSessions);
        $this->assertSame(1.0, $metrics->rate());
    }

    public function test_assisted_proposal_is_gated_without_mutation(): void
    {
        $proposal = $this->safeFormRequestProposal();
        $assisted = new FixProposal(
            id: $proposal->id,
            issueId: $proposal->issueId,
            title: $proposal->title,
            description: $proposal->description,
            risk: FixRisk::Assisted,
            confidence: $proposal->confidence,
            changeSet: $proposal->changeSet,
            verification: $proposal->verification,
            reasoning: $proposal->reasoning,
            summary: $proposal->summary,
            architectureImpact: $proposal->architectureImpact,
            applyEnabled: false,
        );

        $result = (new ControlledChangeService)->improve(
            $assisted,
            $this->fixtureRoot,
            'ProductController',
            90,
        );

        $this->assertFalse($result->succeeded());
        $this->assertNull($result->session);
        $this->assertSame(ChangeExecutionStatus::Failed, $result->execution->status());
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Http/Requests/ProductStoreRequest.php');
    }

    public function test_review_captures_proposal_reviewed_without_mutation(): void
    {
        $proposal = $this->safeFormRequestProposal();
        $reviewed = (new ControlledChangeService)->review($proposal, 8.0);

        $this->assertSame((string) $proposal->id, (string) $reviewed->proposalId);
        $this->assertSame(8.0, $reviewed->durationSeconds);
        $this->assertFileDoesNotExist($this->fixtureRoot.'/app/Http/Requests/ProductStoreRequest.php');
    }

    public function test_improvement_confidence_updates_session_and_metrics(): void
    {
        $proposal = $this->safeFormRequestProposal();
        $service = new ControlledChangeService;
        $result = $service->improve($proposal, $this->fixtureRoot, 'ProductController', 91, 'developer', 5.0);
        $this->assertTrue($result->succeeded());
        $this->assertNotNull($result->session);

        $service->recordConfidence($this->fixtureRoot, (string) $result->session->id, true, null);

        $path = (new ImprovementMetricsStore)->path($this->fixtureRoot);
        $this->assertFileExists($path);
        $metrics = (new ImprovementMetricsStore)->load($this->fixtureRoot);
        $this->assertSame(1, $metrics->confidenceHelped);
        $this->assertSame(1, $metrics->confidenceResponses);

        $sessionFiles = glob($this->fixtureRoot.'/storage/architect/sessions/*.json') ?: [];
        $raw = json_decode((string) file_get_contents($sessionFiles[0]), true);
        $this->assertTrue($raw['confidence']['helped']);
    }

    private function safeFormRequestProposal(): FixProposal
    {
        $changeSet = ChangeSet::of([
            FileChange::make(
                'app/Http/Controllers/ProductController.php',
                FileChangeType::Modified,
                'Validator::make([])->validate();',
                '// type-hint ProductStoreRequest $request',
            ),
            FileChange::make(
                'app/Http/Requests/ProductStoreRequest.php',
                FileChangeType::Created,
                null,
                "<?php\n\nnamespace App\\Http\\Requests;\n\nclass ProductStoreRequest {}\n",
            ),
        ]);
        $verification = VerificationPlan::defaultPlan();

        return new FixProposal(
            id: FixProposalId::of('fix:test-form-request'),
            issueId: IssueId::of('issue:test'),
            title: 'Generate Product Form Request',
            description: 'Move validation into a Form Request.',
            risk: FixRisk::Safe,
            confidence: FixConfidence::high(['Deterministic']),
            changeSet: $changeSet,
            verification: $verification,
            reasoning: new FixProposalReasoning(
                'controller_should_not_validate_inline',
                'controllers_orchestrate_only',
                ['Reusable validation'],
            ),
            summary: FixProposalSummary::make(
                'Generate Product Form Request',
                'Move validation out of the controller',
                'Thin orchestrator',
                $changeSet,
                $verification,
            ),
            architectureImpact: ArchitectureImpact::graph(
                ['Controller', 'Inline Validator'],
                [new ArchitectureEdge('Controller', 'Inline Validator')],
                ['Controller', 'ProductStoreRequest'],
                [new ArchitectureEdge('Controller', 'ProductStoreRequest')],
                ['Validation boundary clarified', 'Controller stays thin', 'Reusable request object'],
            ),
            applyEnabled: true,
        );
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
