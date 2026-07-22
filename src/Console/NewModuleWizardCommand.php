<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;
use KarimAshraf\LaraArchitect\Support\TeamConfig;

/**
 * Interactive wizard: answer a few questions, get a full module — no need
 * to remember flags or preset names.
 */
class NewModuleWizardCommand extends Command
{
    protected $signature = 'architect:new {name? : The module name, e.g. Product}';

    protected $description = 'Interactively scaffold a module (wizard around make:module)';

    public function handle(): int
    {
        TeamConfig::apply();

        $name = (string) ($this->argument('name') ?: $this->ask('What should the module be called?', 'Product'));

        /** @var array<string, mixed> $architectures */
        $architectures = config('lara-architect.generation.architectures', []);

        $architecture = (string) $this->choice(
            'Which architecture preset?',
            array_keys($architectures),
            (string) config('lara-architect.generation.default_architecture', 'service-repository'),
        );

        $ui = (string) $this->choice(
            'API or web (Blade) presentation?',
            ['api', 'web'],
            (string) config('lara-architect.generation.default_ui', 'api'),
        );

        $fields = (string) $this->ask(
            'Field definitions (e.g. "name:string, status:enum:int, price:decimal:nullable") — leave empty to skip',
            '',
        );

        $extras = $this->confirm('Include policy, seeder and test (full feature)?', true);

        $command = $extras ? 'architect:feature' : 'make:module';

        return $this->call($command, array_filter([
            'name' => $name,
            '--architecture' => $architecture,
            '--ui' => $ui,
            '--fields' => $fields !== '' ? $fields : null,
        ], static fn ($value) => $value !== null));
    }
}
