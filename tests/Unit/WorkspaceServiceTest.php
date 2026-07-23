<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Architecture\ArchitectureEngine;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceActionState;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceContext;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceService;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceSnapshot;
use PHPUnit\Framework\TestCase;

class WorkspaceServiceTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/lara-architect-workspace-'.uniqid('', true);
        mkdir($this->fixtureRoot.'/app/Http/Controllers', 0777, true);
        mkdir($this->fixtureRoot.'/app/Models', 0777, true);

        file_put_contents($this->fixtureRoot.'/app/Http/Controllers/ProductController.php', <<<'PHP'
        <?php

        namespace App\Http\Controllers;

        use App\Models\Product;

        class ProductController
        {
            public function store()
            {
                return Product::create([]);
            }
        }
        PHP);

        file_put_contents($this->fixtureRoot.'/app/Models/Product.php', <<<'PHP'
        <?php

        namespace App\Models;

        class Product
        {
        }
        PHP);
    }

    protected function tearDown(): void
    {
        foreach ([
            $this->fixtureRoot.'/app/Http/Controllers/ProductController.php',
            $this->fixtureRoot.'/app/Models/Product.php',
        ] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        @rmdir($this->fixtureRoot.'/app/Http/Controllers');
        @rmdir($this->fixtureRoot.'/app/Http');
        @rmdir($this->fixtureRoot.'/app/Models');
        @rmdir($this->fixtureRoot.'/app');
        @rmdir($this->fixtureRoot);

        parent::tearDown();
    }

    public function test_finding_becomes_issue_with_explanation_and_action_states(): void
    {
        $analysis = ArchitectureEngine::create()->analyze($this->fixtureRoot, ['app']);
        $service = new WorkspaceService;
        $snapshot = $service->snapshot(
            project: $this->fixtureRoot,
            context: WorkspaceContext::file('ProductController', 'app/Http/Controllers/ProductController.php'),
            analysis: $analysis,
        );

        $this->assertSame(WorkspaceSnapshot::SCHEMA_VERSION, $snapshot->schemaVersion);
        $this->assertSame('1.1', $snapshot->schemaVersion);
        $this->assertNotSame('', (string) $snapshot->id);
        $this->assertNotSame('', (string) $snapshot->context->id);
        $this->assertSame('file', $snapshot->context->type->value);
        $this->assertNotEmpty($snapshot->issues);

        $issue = $snapshot->issues[0];
        $this->assertSame('Direct Model Usage', $issue->title);
        $this->assertNotEmpty($issue->findings);
        $this->assertStringContainsString('depends on', $issue->findings[0]->summary);
        $this->assertStringContainsString('coordinate requests', $issue->explanation->why);
        $this->assertContains('Better testing', $issue->explanation->benefits);
        $this->assertFalse($issue->safeFix);

        $actionNames = array_map(static fn ($a) => (string) $a->id, $issue->actions);
        $this->assertContains('explain', $actionNames);
        $this->assertContains('generate_service', $actionNames);

        $explain = $issue->actions[0];
        $this->assertSame(WorkspaceActionState::Executable, $explain->state);

        $explained = $service->explain($snapshot, (string) $issue->id);
        $this->assertNotNull($explained);
        $this->assertSame($issue->explanation->why, $explained['explanation']['why']);
        $this->assertArrayHasKey('findings', $explained);
    }

    public function test_snapshot_schema_and_copy_context_for_adapters(): void
    {
        $analysis = ArchitectureEngine::create()->analyze($this->fixtureRoot, ['app']);
        $service = new WorkspaceService;
        $snapshot = $service->snapshot(
            project: $this->fixtureRoot,
            context: WorkspaceContext::project('fixture'),
            analysis: $analysis,
        );

        $payload = $snapshot->toArray();
        $this->assertSame('1.1', $payload['schema_version']);
        $this->assertArrayHasKey('workspace', $payload);
        $this->assertArrayHasKey('id', $payload['workspace']);
        $this->assertArrayHasKey('context', $payload);
        $this->assertArrayHasKey('issues', $payload);
        $this->assertArrayHasKey('actions', $payload);
        $this->assertNotFalse(json_encode($payload));

        $copy = $service->copyArchitectureContext($snapshot);
        $this->assertStringContainsString('Architecture Context', $copy);
        $this->assertStringContainsString('Direct Model Usage', $copy);
        $this->assertStringContainsString('recommended:', $copy);

        $this->assertArrayHasKey('breadcrumb', $payload['context']);
        $this->assertArrayHasKey('related', $payload);
        $this->assertArrayHasKey('neighborhood', $payload);
        $this->assertTrue($snapshot->issues[0]->primary);
        $this->assertIsArray($snapshot->issues[0]->explanation->impact->toArray());
        $this->assertSame('high', $snapshot->issues[0]->explanation->impact->overall);
    }
}
