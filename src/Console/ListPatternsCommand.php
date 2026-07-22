<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

use Illuminate\Console\Command;

class ListPatternsCommand extends Command
{
    protected $signature = 'architect:patterns';

    protected $description = 'List the architecture presets and patterns available to make:module';

    public function handle(): int
    {
        $this->components->info('Architecture presets (use with make:module --architecture=...)');

        foreach (config('lara-architect.generation.architectures', []) as $name => $patterns) {
            /** @var array<string, string> $descriptions */
            $descriptions = config('lara-architect.generation.architecture_descriptions', []);
            $label = isset($descriptions[$name]) ? "{$name} — {$descriptions[$name]}" : $name;

            $this->components->twoColumnDetail($label, implode(', ', $patterns));
        }

        $this->newLine();
        $this->components->info('Patterns (combine freely with make:module --patterns=...)');

        foreach (config('lara-architect.generation.generators', []) as $pattern => $generator) {
            $this->components->twoColumnDetail($pattern, $generator);
        }

        $this->newLine();
        $this->line('Add your own presets and generators in config/lara-architect.php under "generation".');

        return self::SUCCESS;
    }
}
