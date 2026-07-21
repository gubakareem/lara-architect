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
        File::delete(app_path('Http/Resources/ProductResource.php'));
        File::delete(app_path('Http/Controllers/ProductController.php'));
        File::delete(database_path('factories/ProductFactory.php'));

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
        $this->assertFileExists(app_path('Http/Controllers/ProductController.php'));
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

        $controllerSource = File::get(app_path('Http/Controllers/ProductController.php'));
        $this->assertStringContainsString('public function index(ProductFilter $filter)', $controllerSource);
        $this->assertStringContainsString('$this->productService->filter($filter', $controllerSource);

        $storeRequest = File::get(app_path('Http/Requests/Products/StoreProductRequest.php'));
        $this->assertStringContainsString("Rule::unique('products', 'sku')", $storeRequest);

        $updateRequest = File::get(app_path('Http/Requests/Products/UpdateProductRequest.php'));
        $this->assertStringContainsString("->ignore(\$this->route('product'))", $updateRequest);

        $controller = File::get(app_path('Http/Controllers/ProductController.php'));
        $this->assertStringContainsString('ProductService $productService', $controller);

        $this->assertGeneratedPhpIsValid([
            app_path('Models/Product.php'),
            app_path('Repositories/ProductRepository.php'),
            app_path('Services/ProductService.php'),
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

        $controller = File::get(app_path('Http/Controllers/ProductController.php'));
        $this->assertStringContainsString('CreateProduct::run(ProductData::fromRequest($request))', $controller);
    }

    public function test_enum_fields_generate_enums_wired_into_the_module(): void
    {
        $this->artisan('make:module', [
            'name' => 'Product',
            '--fields' => 'name:string, status:enum, price:decimal:nullable',
        ])->assertExitCode(0);

        $this->assertFileExists(app_path('Enums/ProductStatus.php'));

        $enum = File::get(app_path('Enums/ProductStatus.php'));
        $this->assertStringContainsString('enum ProductStatus: string', $enum);
        $this->assertStringContainsString('public static function values(): array', $enum);

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
        ]);
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
        $this->assertFileDoesNotExist(app_path('Http/Controllers/ProductController.php'));
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
