<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect;

use Illuminate\Support\ServiceProvider;
use KarimAshraf\LaraArchitect\Console\AnalyzeCommand;
use KarimAshraf\LaraArchitect\Console\LintCommand;
use KarimAshraf\LaraArchitect\Console\ListPatternsCommand;
use KarimAshraf\LaraArchitect\Console\MakeFeatureCommand;
use KarimAshraf\LaraArchitect\Console\MakeModuleCommand;
use KarimAshraf\LaraArchitect\Console\NewModuleWizardCommand;
use KarimAshraf\LaraArchitect\Generation\StubRenderer;

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
}
