<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Support\Facades\File;
use KarimAshraf\LaraArchitect\Support\ConfigMerger;
use KarimAshraf\LaraArchitect\Tests\TestCase;

/**
 * An outdated published config (missing views/policy/…) must not break
 * web modules or architect:feature after a package upgrade.
 */
class PublishedConfigCompatibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Models'));
        File::deleteDirectory(app_path('Enums'));
        File::deleteDirectory(app_path('Http/Controllers'));
        File::deleteDirectory(app_path('Http/Requests/Categories'));
        File::deleteDirectory(resource_path('views/categories'));
        File::deleteDirectory(app_path('Policies'));
        File::delete(lang_path('en/enums.php'));
        File::delete(lang_path('ar/enums.php'));
        File::delete(database_path('seeders/CategorySeeder.php'));
        File::delete(base_path('tests/Feature/CategoryModuleTest.php'));

        foreach (File::glob(database_path('migrations/*_create_categories_table.php')) as $migration) {
            File::delete($migration);
        }

        parent::tearDown();
    }

    public function test_outdated_published_config_still_generates_web_views_and_feature_extras(): void
    {
        /** @var array<array-key, mixed> $package */
        $package = require dirname(__DIR__, 2).'/config/lara-architect.php';

        $outdatedPublished = [
            'generation' => [
                'default_architecture' => 'lean',
                'default_ui' => 'api',
                'generators' => [
                    'model' => $package['generation']['generators']['model'],
                    'migration' => $package['generation']['generators']['migration'],
                    'requests' => $package['generation']['generators']['requests'],
                    'resource' => $package['generation']['generators']['resource'],
                    'controller' => $package['generation']['generators']['controller'],
                ],
                'architectures' => [
                    'lean' => ['model', 'migration', 'requests', 'resource', 'controller'],
                ],
            ],
        ];

        config(['lara-architect' => ConfigMerger::merge($package, $outdatedPublished)]);

        $this->assertArrayHasKey('views', config('lara-architect.generation.generators'));
        $this->assertArrayHasKey('policy', config('lara-architect.generation.generators'));
        $this->assertArrayHasKey('seeder', config('lara-architect.generation.generators'));
        $this->assertArrayHasKey('test', config('lara-architect.generation.generators'));

        $this->artisan('architect:feature', [
            'name' => 'Category',
            '--architecture' => 'lean',
            '--ui' => 'web',
            '--fields' => 'name:json, parent_id:int, order:integer, status:enum:int',
        ])->assertExitCode(0);

        $this->assertFileExists(resource_path('views/categories/index.blade.php'));
        $this->assertFileExists(app_path('Policies/CategoryPolicy.php'));
        $this->assertFileExists(app_path('Http/Controllers/CategoryController.php'));
        $this->assertFileDoesNotExist(app_path('Http/Resources/CategoryResource.php'));
    }
}
