<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Architecture\ArchitectureEngine;
use KarimAshraf\LaraArchitect\Workspace\FileChangeType;
use KarimAshraf\LaraArchitect\Workspace\FixProposalService;
use KarimAshraf\LaraArchitect\Workspace\FixRisk;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceContext;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceService;
use PHPUnit\Framework\TestCase;

class FixProposalServiceTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/lara-architect-fix-'.uniqid('', true);
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

    public function test_propose_builds_change_understanding_contract_without_apply(): void
    {
        $analysis = ArchitectureEngine::create()->analyze($this->fixtureRoot, ['app']);
        $snapshot = (new WorkspaceService)->snapshot(
            project: $this->fixtureRoot,
            context: WorkspaceContext::file('ProductController', 'app/Http/Controllers/ProductController.php'),
            analysis: $analysis,
        );

        $issue = $snapshot->issues[0];
        $proposal = (new FixProposalService)->propose($snapshot, (string) $issue->id, $this->fixtureRoot);

        $this->assertNotNull($proposal);
        $this->assertSame('Extract ProductService', $proposal->title);
        $this->assertSame(FixRisk::Assisted, $proposal->risk);
        $this->assertFalse($proposal->applyEnabled);
        $this->assertFalse($proposal->toArray()['policy']['apply_enabled']);
        $this->assertCount(2, $proposal->changeSet->files);
        $this->assertSame(2, $proposal->changeSet->summary->filesChanged);
        $this->assertGreaterThan(0, $proposal->changeSet->summary->linesAdded);
        $this->assertSame(FileChangeType::Modified, $proposal->changeSet->files[0]->type);
        $this->assertSame(FileChangeType::Created, $proposal->changeSet->files[1]->type);
        $this->assertCount(3, $proposal->verification->checks);
        $this->assertSame('medium', $proposal->confidence->level);
        $this->assertSame('created', $proposal->status->value);
        $this->assertSame('controller_should_not_access_models', $proposal->reasoning->rule);
        $this->assertContains('Improves testability', $proposal->reasoning->benefits);
        $this->assertSame('Extract business logic into service', $proposal->summary->intent);
        $this->assertSame(2, $proposal->summary->affectedFilesCount);
        $this->assertSame(3, $proposal->summary->verificationCount);
        $this->assertContains('Layer violation resolved', $proposal->architectureImpact->results);
        $this->assertNotEmpty($proposal->architectureImpact->removed);
        $this->assertNotEmpty($proposal->architectureImpact->added);
        $this->assertArrayHasKey('change_set', $proposal->toArray());
        $this->assertSame('viewed', $proposal->markViewed()->status->value);
        $this->assertSame('reviewed', $proposal->markViewed()->markReviewed()->status->value);
        $this->assertStringContainsString('productService->create', (string) $proposal->changeSet->files[0]->after);
    }
}
