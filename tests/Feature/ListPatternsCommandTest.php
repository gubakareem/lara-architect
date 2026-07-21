<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Feature;

use KarimAshraf\LaraArchitect\Tests\TestCase;

class ListPatternsCommandTest extends TestCase
{
    public function test_it_lists_architectures_and_patterns(): void
    {
        $this->artisan('architect:patterns')
            ->expectsOutputToContain('service-repository')
            ->expectsOutputToContain('actions')
            ->expectsOutputToContain('repository')
            ->assertExitCode(0);
    }

    public function test_custom_presets_from_config_are_listed(): void
    {
        config()->set('lara-architect.generation.architectures.cqrs-lite', ['model', 'migration', 'dto']);

        $this->artisan('architect:patterns')
            ->expectsOutputToContain('cqrs-lite')
            ->assertExitCode(0);
    }
}
