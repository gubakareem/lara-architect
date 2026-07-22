<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Support\Facades\File;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class MakeModuleCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::deleteDirectory(app_path('Models'));
        File::deleteDirectory(app_path('Repositories'));
        File::deleteDirectory(app_path('Services'));
        File::deleteDirectory(app_path('Actions'));
        File::deleteDirectory(app_path('DTOs'));
        File::deleteDirectory(app_path('Enums'));
        File::deleteDirectory(app_path('Http/Filters'));
        File::deleteDirectory(app_path('Http/Requests/Products'));
        File::deleteDirectory(app_path('Http/Controllers/Api'));
        File::delete(app_path('Http/Controllers/ProductController.php'));
        File::delete(app_path('Http/Resources/ProductResource.php'));
        File::deleteDirectory(resource_path('views/products'));
        File::deleteDirectory(app_path('Policies'));
        File::deleteDirectory(app_path('Domain'));
        File::deleteDirectory(app_path('Infrastructure'));
        File::deleteDirectory(app_path('Commands'));
        File::deleteDirectory(app_path('Queries'));
        File::deleteDirectory(app_path('Pipelines'));
        File::delete(lang_path('en/enums.php'));
        File::delete(lang_path('ar/enums.php'));
        File::delete(database_path('factories/ProductFactory.php'));
        File::delete(database_path('seeders/ProductSeeder.php'));
        File::delete(base_path('tests/Feature/ProductModuleTest.php'));
        File::delete(base_path('architect.json'));

        foreach (File::glob(database_path('migrations/*_create_products_table.php')) as $migration) {
            File::delete($migration);
        }

        parent::tearDown();
    }

    public function test_it_generates_a_service_repository_module(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--fields' => 'name:string, price:decimal, sku:string:unique, notes:text:nullable',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileExists(app_path('Repositories/ProductRepository.php'));
        $this->assertFileExists(app_path('Services/ProductService.php'));
        $this->assertFileExists(app_path('Http/Requests/Products/StoreProductRequest.php'));
        $this->assertFileExists(app_path('Http/Requests/Products/UpdateProductRequest.php'));
        $this->assertFileExists(app_path('Http/Resources/ProductResource.php'));
        $this->assertFileExists(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertFileExists(database_path('factories/ProductFactory.php'));
        $this->assertNotEmpty(File::glob(database_path('migrations/*_create_products_table.php')));

        $model = File::get(app_path('Models/Product.php'));
        $this->assertStringContainsString('class Product extends Model', $model);
        $this->assertStringContainsString('use Filterable, HasFactory, HasUuid, SoftDeletes;', $model);
        $this->assertStringContainsString("'price' => 'decimal:2',", $model);

        $this->assertFileExists(app_path('Http/Filters/ProductFilter.php'));
        $filter = File::get(app_path('Http/Filters/ProductFilter.php'));
        $this->assertStringContainsString('public function search(string $value): void', $filter);
        $this->assertStringContainsString('public function priceMin(string $value): void', $filter);

        $controllerSource = File::get(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertStringContainsString('namespace App\Http\Controllers\Api;', $controllerSource);
        $this->assertStringContainsString('public function index(ProductFilter $filter)', $controllerSource);
        $this->assertStringContainsString('$this->productService->filter($filter', $controllerSource);
        $this->assertStringContainsString('ProductService $productService', $controllerSource);
        $this->assertStringContainsString('RespondsWithJson', $controllerSource);

        $storeRequest = File::get(app_path('Http/Requests/Products/StoreProductRequest.php'));
        $this->assertStringContainsString("Rule::unique('products', 'sku')", $storeRequest);

        $updateRequest = File::get(app_path('Http/Requests/Products/UpdateProductRequest.php'));
        $this->assertStringContainsString("->ignore(\$this->route('product'))", $updateRequest);

        $this->assertGeneratedPhpIsValid([
            app_path('Models/Product.php'),
            app_path('Repositories/ProductRepository.php'),
            app_path('Services/ProductService.php'),
            app_path('Http/Controllers/Api/ProductController.php'),
        ]);
    }

    public function test_it_generates_a_web_module_with_blade_views(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--ui' => 'web',
            '--fields' => 'name:string, price:decimal',
        ])->assertExitCode(0);

        $this->assertFileDoesNotExist(app_path('Http/Resources/ProductResource.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertFileExists(app_path('Http/Controllers/ProductController.php'));
        $this->assertFileExists(resource_path('views/products/index.blade.php'));
        $this->assertFileExists(resource_path('views/products/create.blade.php'));
        $this->assertFileExists(resource_path('views/products/show.blade.php'));
        $this->assertFileExists(resource_path('views/products/edit.blade.php'));

        $controller = File::get(app_path('Http/Controllers/ProductController.php'));
        $this->assertStringContainsString('namespace App\Http\Controllers;', $controller);
        $this->assertStringContainsString("view('products.index'", $controller);
        $this->assertStringContainsString('RedirectResponse', $controller);
        $this->assertStringNotContainsString('RespondsWithJson', $controller);
        $this->assertStringNotContainsString('ProductResource', $controller);

        $this->assertGeneratedPhpIsValid([
            app_path('Http/Controllers/ProductController.php'),
        ]);
    }

    public function test_it_generates_an_actions_module_with_dto(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--architecture' => 'actions',
            '--fields' => 'name:string, price:decimal',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Actions/Products/CreateProduct.php'));
        $this->assertFileExists(app_path('Actions/Products/UpdateProduct.php'));
        $this->assertFileExists(app_path('Actions/Products/DeleteProduct.php'));
        $this->assertFileExists(app_path('DTOs/ProductData.php'));

        $createAction = File::get(app_path('Actions/Products/CreateProduct.php'));
        $this->assertStringContainsString('handle(ProductData $data): Product', $createAction);

        $controller = File::get(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertStringContainsString('CreateProduct::run(ProductData::fromRequest($request))', $controller);
    }

    public function test_enum_fields_generate_enums_wired_into_the_module(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--fields' => 'name:string, status:enum, price:decimal:nullable',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Enums/ProductStatus.php'));
        $this->assertFileExists(lang_path('en/enums.php'));
        $this->assertFileExists(lang_path('ar/enums.php'));

        $enum = File::get(app_path('Enums/ProductStatus.php'));
        $this->assertStringContainsString('enum ProductStatus: string', $enum);
        $this->assertStringContainsString('use KarimAshraf\LaraArchitect\Enums\Concerns\EnumHelpers;', $enum);
        $this->assertStringContainsString('use EnumHelpers;', $enum);
        $this->assertStringContainsString('@method bool isActive()', $enum);
        $this->assertStringContainsString("case Draft = 'draft';", $enum);

        $en = File::get(lang_path('en/enums.php'));
        $this->assertStringContainsString('use App\Enums\ProductStatus;', $en);
        $this->assertStringContainsString('ProductStatus::class =>', $en);
        $this->assertStringContainsString("ProductStatus::Active->value => 'Active'", $en);

        $ar = File::get(lang_path('ar/enums.php'));
        $this->assertStringContainsString("ProductStatus::Active->value => 'نشط'", $ar);

        $model = File::get(app_path('Models/Product.php'));
        $this->assertStringContainsString('use App\Enums\ProductStatus;', $model);
        $this->assertStringContainsString("'status' => ProductStatus::class,", $model);

        $storeRequest = File::get(app_path('Http/Requests/Products/StoreProductRequest.php'));
        $this->assertStringContainsString('Rule::enum(ProductStatus::class)', $storeRequest);

        $factory = File::get(database_path('factories/ProductFactory.php'));
        $this->assertStringContainsString('fake()->randomElement(ProductStatus::cases())', $factory);

        $this->assertGeneratedPhpIsValid([
            app_path('Enums/ProductStatus.php'),
            app_path('Models/Product.php'),
            app_path('Http/Filters/ProductFilter.php'),
            app_path('Http/Requests/Products/StoreProductRequest.php'),
            database_path('factories/ProductFactory.php'),
            lang_path('en/enums.php'),
            lang_path('ar/enums.php'),
        ]);
    }

    public function test_int_backed_enums_are_supported(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--fields' => 'name:string, status:enum:int',
        ])->assertExitCode(0);

        $enum = File::get(app_path('Enums/ProductStatus.php'));
        $this->assertStringContainsString('enum ProductStatus: int', $enum);
        $this->assertStringContainsString('case Inactive = 0;', $enum);
        $this->assertStringContainsString('case Active = 1;', $enum);

        $migration = File::get(File::glob(database_path('migrations/*_create_products_table.php'))[0]);
        $this->assertStringContainsString("\$table->unsignedTinyInteger('status')", $migration);

        $ar = File::get(lang_path('ar/enums.php'));
        $this->assertStringContainsString("ProductStatus::Inactive->value => 'غير نشط'", $ar);
        $this->assertStringContainsString("ProductStatus::Active->value => 'نشط'", $ar);
    }

    public function test_custom_pattern_lists_are_supported(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--patterns' => 'model,migration',
            '--fields' => 'name:string',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileDoesNotExist(app_path('Services/ProductService.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Api/ProductController.php'));
    }

    public function test_existing_files_are_skipped_without_force(): void
    {
        $this->artisan('make:module', ['name' => 'Product', '--patterns' => 'model'])->assertExitCode(0);

        File::put(app_path('Models/Product.php'), '<?php // customized');

        $this->artisan('make:module', ['name' => 'Product', '--patterns' => 'model'])->assertExitCode(0);
        $this->assertSame('<?php // customized', File::get(app_path('Models/Product.php')));

        $this->artisan('make:module', ['name' => 'Product', '--patterns' => 'model', '--force' => true])->assertExitCode(0);
        $this->assertStringContainsString('class Product extends Model', File::get(app_path('Models/Product.php')));
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertFileDoesNotExist(app_path('Models/Product.php'));
    }

    public function test_unknown_architecture_fails_with_a_clear_message(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--architecture' => 'hexagonal',
        ])->assertExitCode(1);
    }

    public function test_architect_feature_adds_policy_seeder_and_test(): void
    {
        $this->artisan('architect:feature', [
            'name' => 'Product',
            '--fields' => 'name:string, price:decimal',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileExists(app_path('Policies/ProductPolicy.php'));
        $this->assertFileExists(database_path('seeders/ProductSeeder.php'));
        $this->assertFileExists(base_path('tests/Feature/ProductModuleTest.php'));

        $policy = File::get(app_path('Policies/ProductPolicy.php'));
        $this->assertStringContainsString('namespace App\Policies;', $policy);
        $this->assertStringContainsString('public function update(User $user, Product $product): bool', $policy);

        $seeder = File::get(database_path('seeders/ProductSeeder.php'));
        $this->assertStringContainsString('namespace Database\Seeders;', $seeder);
        $this->assertStringContainsString('Product::factory()->count(10)->create();', $seeder);

        $test = File::get(base_path('tests/Feature/ProductModuleTest.php'));
        $this->assertStringContainsString('namespace Tests\Feature;', $test);
        $this->assertStringContainsString('class ProductModuleTest extends TestCase', $test);
        $this->assertStringContainsString('$this->assertSoftDeleted($product);', $test);

        $this->assertGeneratedPhpIsValid([
            app_path('Policies/ProductPolicy.php'),
            database_path('seeders/ProductSeeder.php'),
            base_path('tests/Feature/ProductModuleTest.php'),
        ]);
    }

    public function test_architect_json_overrides_package_config(): void
    {
        File::put(base_path('architect.json'), json_encode([
            'generation' => [
                'default_architecture' => 'lean',
                'default_ui' => 'web',
            ],
        ], JSON_PRETTY_PRINT));

        $this->artisan('make:module', [
            'name' => 'Product',
            '--fields' => 'name:string',
        ])->assertExitCode(0);

        // lean preset: no service/repository; web ui: no Api controller.
        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileDoesNotExist(app_path('Services/ProductService.php'));
        $this->assertFileDoesNotExist(app_path('Repositories/ProductRepository.php'));
        $this->assertFileDoesNotExist(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertFileExists(app_path('Http/Controllers/ProductController.php'));
    }

    public function test_module_placeholder_enables_domain_layouts(): void
    {
        config()->set('lara-architect.generation.namespaces.service', 'App\\Domain\\{module}\\Services');
        config()->set('lara-architect.generation.namespaces.repository', 'App\\Domain\\{module}\\Repositories');

        $this->artisan('make:module', [
            'name' => 'Product',
            '--patterns' => 'model,repository,service',
            '--fields' => 'name:string',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Domain/Product/Services/ProductService.php'));
        $this->assertFileExists(app_path('Domain/Product/Repositories/ProductRepository.php'));

        $service = File::get(app_path('Domain/Product/Services/ProductService.php'));
        $this->assertStringContainsString('namespace App\Domain\Product\Services;', $service);
        $this->assertStringContainsString('use App\Domain\Product\Repositories\ProductRepository;', $service);
    }

    public function test_architect_new_wizard_scaffolds_interactively(): void
    {
        $this->artisan('architect:new')
            ->expectsQuestion('What should the module be called?', 'Product')
            ->expectsChoice(
                'Which architecture preset?',
                'service-repository',
                [
                    'service-repository' => 'service-repository — Service + repository layer (classic layered CRUD)',
                    'actions' => 'actions — Single-purpose action classes + DTO (no service/repository)',
                    'adr' => 'adr — Action–Domain–Responder style (same scaffold as actions)',
                    'ddd' => 'ddd — Domain-oriented folders under App\\Domain\\{Module}\\…',
                    'cqrs' => 'cqrs — Separate commands (writes) and queries (reads) + DTO',
                    'pipeline' => 'pipeline — Illuminate Pipeline with validation + persist pipes',
                    'lean' => 'lean — Minimal: model, migration, requests, controller only',
                ],
            )
            ->expectsChoice(
                'API or web (Blade) presentation?',
                'api',
                [
                    'api' => 'api — JsonResource + controllers under Http\\Controllers\\Api',
                    'web' => 'web — Blade views + controllers under Http\\Controllers',
                ],
            )
            ->expectsQuestion('Field definitions (e.g. "name:string, status:enum:int, price:decimal:nullable") — leave empty to skip', 'name:string')
            ->expectsConfirmation('Include policy, seeder and test (full feature)?', 'yes')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileExists(app_path('Services/ProductService.php'));
        $this->assertFileExists(app_path('Policies/ProductPolicy.php'));
        $this->assertFileExists(database_path('seeders/ProductSeeder.php'));
        $this->assertFileExists(base_path('tests/Feature/ProductModuleTest.php'));
    }

    public function test_architect_feature_prompts_for_name_when_omitted(): void
    {
        $this->artisan('architect:feature')
            ->expectsQuestion('What should the module be called?', 'Product')
            ->assertExitCode(0);

        $this->assertFileExists(app_path('Models/Product.php'));
        $this->assertFileExists(app_path('Policies/ProductPolicy.php'));
    }

    public function test_ddd_preset_uses_domain_namespaces(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--architecture' => 'ddd',
            '--fields' => 'name:string',
            '--ui' => 'api',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Domain/Product/Models/Product.php'));
        $this->assertFileExists(app_path('Domain/Product/Services/ProductService.php'));
        $this->assertFileExists(app_path('Infrastructure/Product/ProductRepository.php'));
        $this->assertFileExists(app_path('Domain/Product/Data/ProductData.php'));

        $service = File::get(app_path('Domain/Product/Services/ProductService.php'));
        $this->assertStringContainsString('namespace App\Domain\Product\Services;', $service);
        $this->assertStringContainsString('use App\Infrastructure\Product\ProductRepository;', $service);
    }

    public function test_cqrs_preset_generates_commands_and_queries(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--architecture' => 'cqrs',
            '--fields' => 'name:string',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Commands/Products/CreateProductCommand.php'));
        $this->assertFileExists(app_path('Queries/Products/ListProductsQuery.php'));
        $this->assertFileExists(app_path('Queries/Products/GetProductQuery.php'));

        $controller = File::get(app_path('Http/Controllers/Api/ProductController.php'));
        $this->assertStringContainsString('CreateProductCommand::run', $controller);
    }

    /**
     * @param  list<string>  $paths
     */
    private function assertGeneratedPhpIsValid(array $paths): void
    {
        foreach ($paths as $path) {
            $output = [];
            $exitCode = 0;

            exec(sprintf('php -l %s 2>&1', escapeshellarg($path)), $output, $exitCode);

            $this->assertSame(0, $exitCode, "Generated file failed php -l: {$path}\n".implode("\n", $output));
        }
    }
}
