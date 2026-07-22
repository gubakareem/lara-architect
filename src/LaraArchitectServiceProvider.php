<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect;

use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;
use KarimAshraf\LaraArchitect\Console\AnalyzeCommand;
use KarimAshraf\LaraArchitect\Console\LintCommand;
use KarimAshraf\LaraArchitect\Console\ListPatternsCommand;
use KarimAshraf\LaraArchitect\Console\MakeFeatureCommand;
use KarimAshraf\LaraArchitect\Console\MakeModuleCommand;
use KarimAshraf\LaraArchitect\Console\NewModuleWizardCommand;
use KarimAshraf\LaraArchitect\Generation\StubRenderer;
use KarimAshraf\LaraArchitect\Support\ConfigMerger;

class LaraArchitectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lara-architect.php', 'lara-architect');
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/lara-architect.php' => config_path('lara-architect.php'),
        ], 'lara-architect-config');

        $this->publishes([
            StubRenderer::packageStubPath() => base_path('stubs/lara-architect'),
        ], 'lara-architect-stubs');

        $this->commands([
            MakeModuleCommand::class,
            MakeFeatureCommand::class,
            NewModuleWizardCommand::class,
            ListPatternsCommand::class,
            LintCommand::class,
            AnalyzeCommand::class,
        ]);
    }

    /**
     * Recursively merge package defaults under published/app config so new
     * generators (views, policy, seeder, test, …) remain available when an
     * older published config/lara-architect.php is still in the project.
     *
     * @param  string  $path
     * @param  string  $key
     */
    protected function mergeConfigFrom($path, $key): void
    {
        if ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached()) {
            return;
        }

        /** @var array<array-key, mixed> $package */
        $package = require $path;
        /** @var array<array-key, mixed> $existing */
        $existing = $this->app->make('config')->get($key, []);

        $this->app->make('config')->set($key, ConfigMerger::merge($package, $existing));
    }
}
