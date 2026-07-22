<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Actions\Action;
use KarimAshraf\LaraArchitect\Actions\ArchitectAction;
use KarimAshraf\LaraArchitect\Database\ArchitectRepository;
use KarimAshraf\LaraArchitect\Database\BaseRepository;
use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;
use KarimAshraf\LaraArchitect\Http\Filters\QueryFilter;
use KarimAshraf\LaraArchitect\Http\Requests\ArchitectFormRequest;
use KarimAshraf\LaraArchitect\Http\Requests\BaseFormRequest;
use KarimAshraf\LaraArchitect\Services\ArchitectService;
use KarimAshraf\LaraArchitect\Services\BaseService;
use KarimAshraf\LaraArchitect\Support\ArchitectData;
use KarimAshraf\LaraArchitect\Support\BaseData;
use PHPUnit\Framework\TestCase;

/**
 * Apps generated before v1.2 extend the old Base* names; these aliases must
 * keep resolving to the renamed Architect* classes.
 */
class BackwardsCompatibilityAliasesTest extends TestCase
{
    public function test_legacy_base_classes_extend_the_architect_classes(): void
    {
        $aliases = [
            BaseRepository::class => ArchitectRepository::class,
            BaseService::class => ArchitectService::class,
            BaseData::class => ArchitectData::class,
            BaseFormRequest::class => ArchitectFormRequest::class,
            Action::class => ArchitectAction::class,
            QueryFilter::class => ArchitectQueryFilter::class,
        ];

        foreach ($aliases as $legacy => $current) {
            $this->assertContains(
                $current,
                array_values((array) class_parents($legacy)),
                sprintf('%s must extend %s for backwards compatibility.', $legacy, $current),
            );
        }
    }
}
