<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use KarimAshraf\LaraArchitect\Tests\TestCase;

class LintCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(app_path('Http/Controllers/BadWidgetController.php'));
        File::delete(app_path('Http/Controllers/GoodWidgetController.php'));
        File::delete(app_path('Models/BadWidget.php'));
        File::delete(app_path('Services/HugeService.php'));
        File::delete(base_path('architect-baseline.json'));

        parent::tearDown();
    }

    public function test_lint_passes_on_a_clean_codebase(): void
    {
        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::put(app_path('Http/Controllers/GoodWidgetController.php'), <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Services\WidgetService;

class GoodWidgetController
{
    public function __construct(private WidgetService $service) {}

    public function index()
    {
        return $this->service->paginate();
    }
}
PHP);

        $this->artisan('architect:lint')->assertExitCode(0);
    }

    public function test_lint_flags_controllers_that_break_layer_rules(): void
    {
        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::put(app_path('Http/Controllers/BadWidgetController.php'), <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\Widget;
use App\Repositories\WidgetRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BadWidgetController
{
    public function __construct(private WidgetRepository $repository) {}

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);

        DB::table('widgets')->count();

        return Widget::create($request->all());
    }
}
PHP);

        $exit = Artisan::call('architect:lint');
        $output = Artisan::output();

        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('layer-dependency', $output);
        $this->assertStringContainsString('Model', $output);
        $this->assertStringContainsString('Repository', $output);
        $this->assertStringContainsString('Validation', $output);
        $this->assertStringContainsString('Infrastructure', $output);
    }

    public function test_lint_flags_models_depending_on_services(): void
    {
        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/BadWidget.php'), <<<'PHP'
<?php

namespace App\Models;

use App\Services\WidgetService;
use Illuminate\Database\Eloquent\Model;

class BadWidget extends Model
{
    public function service(): WidgetService
    {
        return app(WidgetService::class);
    }
}
PHP);

        $exit = Artisan::call('architect:lint');
        $output = Artisan::output();

        $this->assertSame(1, $exit, $output);
        $this->assertStringContainsString('layer-dependency', $output);
        $this->assertStringContainsString('Service', $output);
    }

    public function test_baseline_hides_existing_violations(): void
    {
        File::ensureDirectoryExists(app_path('Models'));
        File::put(app_path('Models/BadWidget.php'), <<<'PHP'
<?php

namespace App\Models;

use App\Services\WidgetService;
use Illuminate\Database\Eloquent\Model;

class BadWidget extends Model
{
    public function service(): WidgetService
    {
        return app(WidgetService::class);
    }
}
PHP);

        $this->artisan('architect:lint', ['--update-baseline' => true])->assertExitCode(0);
        $this->assertFileExists(base_path('architect-baseline.json'));

        $this->artisan('architect:lint')->assertExitCode(0);

        $this->artisan('architect:lint', ['--ignore-baseline' => true])->assertExitCode(1);
    }

    public function test_lint_json_format(): void
    {
        File::ensureDirectoryExists(app_path('Http/Controllers'));
        File::put(app_path('Http/Controllers/GoodWidgetController.php'), <<<'PHP'
<?php

namespace App\Http\Controllers;

class GoodWidgetController
{
}
PHP);

        $exit = Artisan::call('architect:lint', ['--format' => 'json']);
        $output = Artisan::output();

        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('files_scanned', $output);
        $this->assertStringContainsString('violations', $output);
    }

    public function test_analyze_reports_layer_counts_and_hotspots(): void
    {
        File::ensureDirectoryExists(app_path('Services'));

        $methods = implode("\n", array_map(
            static fn (int $i): string => "    public function method{$i}(): void {}",
            range(1, 10),
        ));

        File::put(app_path('Services/HugeService.php'), <<<PHP
<?php

namespace App\Services;

class HugeService
{
{$methods}
}
PHP);

        $this->artisan('architect:analyze')
            ->expectsOutputToContain('hotspot')
            ->expectsOutputToContain('HugeService.php')
            ->assertExitCode(0);
    }
}
