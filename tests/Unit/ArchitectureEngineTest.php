<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Architecture\ArchitectureEngine;
use KarimAshraf\LaraArchitect\Architecture\Baseline;
use KarimAshraf\LaraArchitect\Architecture\EngineFactory;
use KarimAshraf\LaraArchitect\Architecture\LayerRegistry;
use KarimAshraf\LaraArchitect\Architecture\Packs\LaravelRulePack;
use KarimAshraf\LaraArchitect\Architecture\Rules\LayerDependencyRule;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Proves the engine runs with no Laravel container, no Artisan, no config().
 */
class ArchitectureEngineTest extends TestCase
{
    private string $fixtureRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixtureRoot = sys_get_temp_dir().'/lara-architect-engine-'.uniqid('', true);
        mkdir($this->fixtureRoot.'/app/Http/Controllers', 0777, true);
        mkdir($this->fixtureRoot.'/app/Models', 0777, true);
        mkdir($this->fixtureRoot.'/app/Services', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->deleteTree($this->fixtureRoot);

        parent::tearDown();
    }

    public function test_engine_detects_layer_violations_without_laravel(): void
    {
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

        $engine = ArchitectureEngine::create();
        $result = $engine->analyze($this->fixtureRoot, ['app']);

        $this->assertGreaterThan(0, count($result->violations));
        $this->assertStringContainsString('Model', $result->violations[0]->message);
    }

    public function test_allow_list_forbids_unlisted_layers(): void
    {
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

        $engine = ArchitectureEngine::create(
            layers: new LayerRegistry([
                'Controller' => 'App\\Http\\Controllers',
                'Service' => 'App\\Services',
                'Model' => 'App\\Models',
            ]),
            rules: [
                new LayerDependencyRule(from: 'Controller', allow: ['Service']),
            ],
        );

        $result = $engine->lint($this->fixtureRoot, ['app']);

        $this->assertNotEmpty($result->violations);
        $this->assertStringContainsString('may only depend on: Service', $result->violations[0]->message);
    }

    public function test_json_renderer_and_baseline_are_framework_free(): void
    {
        file_put_contents($this->fixtureRoot.'/app/Services/CatalogService.php', <<<'PHP'
        <?php

        namespace App\Services;

        class CatalogService
        {
        }
        PHP);

        $engine = ArchitectureEngine::create(pack: new LaravelRulePack);
        $result = $engine->analyze($this->fixtureRoot, ['app']);

        $json = EngineFactory::renderer('json')->render($result, $this->fixtureRoot);
        $this->assertStringContainsString('"files_scanned"', $json);

        $baselinePath = $this->fixtureRoot.'/architect-baseline.json';
        $baseline = new Baseline($baselinePath, $this->fixtureRoot);
        $baseline->write($result->violations);

        $this->assertFileExists($baselinePath);
        [$new] = $baseline->partition($result->violations);
        $this->assertSame([], $new);
    }

    private function deleteTree(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($path);
    }
}
