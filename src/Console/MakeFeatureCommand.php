<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Console;

/**
 * make:module plus the "feature extras" (policy, seeder, test by default),
 * so one command ships a complete, tested feature.
 */
class MakeFeatureCommand extends MakeModuleCommand
{
    protected $signature = 'architect:feature
        {name? : The feature name, e.g. Product (prompted if omitted)}
        {--a|architecture= : Architecture preset (run architect:patterns to list them)}
        {--p|patterns= : Comma-separated pattern list, overrides the preset}
        {--ui= : Presentation layer: api (JsonResource + Api controller) or web (Blade views)}
        {--fields= : Field definitions, e.g. "name:string, status:enum:int, price:decimal:nullable"}
        {--no-uuid : Skip the uuid column and HasUuid trait}
        {--no-soft-deletes : Skip soft deletes}
        {--force : Overwrite existing files}
        {--dry-run : Preview the files without writing anything}';

    protected $description = 'Generate a complete feature: module patterns plus policy, seeder and test';

    protected function extraPatterns(): array
    {
        /** @var list<string> $extras */
        $extras = config('lara-architect.generation.feature_extras', ['policy', 'seeder', 'test']);

        return $extras;
    }
}
