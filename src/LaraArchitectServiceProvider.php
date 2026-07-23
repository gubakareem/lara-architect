<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect;

use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;
use KarimAshraf\LaraArchitect\Console\AnalyzeCommand;
use KarimAshraf\LaraArchitect\Console\AskCommand;
use KarimAshraf\LaraArchitect\Console\LintCommand;
use KarimAshraf\LaraArchitect\Console\ListPatternsCommand;
use KarimAshraf\LaraArchitect\Console\MakeFeatureCommand;
use KarimAshraf\LaraArchitect\Console\MakeModuleCommand;
use KarimAshraf\LaraArchitect\Console\NewModuleWizardCommand;
use KarimAshraf\LaraArchitect\Console\WorkspaceCommand;
use KarimAshraf\LaraArchitect\Generation\StubRenderer;
use KarimAshraf\LaraArchitect\Support\ConfigMerger;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureBaselineStore;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureCollaborationService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureCommunicationService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureContextService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureConversationService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionHistoryService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureDecisionMemory;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureEvolutionService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureGovernanceService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureGuidanceService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureHistoryService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureIdentityService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureIntelligenceService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeMapService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureKnowledgeTransferService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureLearningService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureMemory;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureOwnershipService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureQuestionService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureStandardsService;
use KarimAshraf\LaraArchitect\Workspace\ArchitectureVocabulary;
use KarimAshraf\LaraArchitect\Workspace\ControlledChangeService;
use KarimAshraf\LaraArchitect\Workspace\FixProposalService;
use KarimAshraf\LaraArchitect\Workspace\GuidedImprovementJourneyService;
use KarimAshraf\LaraArchitect\Workspace\WorkspaceService;

class LaraArchitectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/lara-architect.php', 'lara-architect');
        $this->app->singleton(WorkspaceService::class);
        $this->app->singleton(FixProposalService::class);
        $this->app->singleton(ControlledChangeService::class);
        $this->app->singleton(ArchitectureMemory::class);
        $this->app->singleton(ArchitectureHistoryService::class);
        $this->app->singleton(ArchitectureBaselineStore::class);
        $this->app->singleton(ArchitectureDecisionMemory::class);
        $this->app->singleton(ArchitectureIntelligenceService::class);
        $this->app->singleton(ArchitectureVocabulary::class);
        $this->app->singleton(ArchitectureGuidanceService::class);
        $this->app->singleton(GuidedImprovementJourneyService::class);
        $this->app->singleton(ArchitectureStandardsService::class);
        $this->app->singleton(ArchitectureGovernanceService::class);
        $this->app->singleton(ArchitectureEvolutionService::class);
        $this->app->singleton(ArchitectureLearningService::class);
        $this->app->singleton(ArchitectureCollaborationService::class);
        $this->app->singleton(ArchitectureOwnershipService::class);
        $this->app->singleton(ArchitectureKnowledgeMapService::class);
        $this->app->singleton(ArchitectureKnowledgeTransferService::class);
        $this->app->singleton(ArchitectureQuestionService::class);
        $this->app->singleton(ArchitectureConversationService::class);
        $this->app->singleton(ArchitectureDecisionHistoryService::class);
        $this->app->singleton(ArchitectureIdentityService::class);
        $this->app->singleton(ArchitectureCommunicationService::class);
        $this->app->singleton(ArchitectureContextService::class);
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
            AskCommand::class,
            WorkspaceCommand::class,
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
