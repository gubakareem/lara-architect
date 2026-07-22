<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Unit;

use KarimAshraf\LaraArchitect\Generation\Generators\ViewsGenerator;
use KarimAshraf\LaraArchitect\Support\ConfigMerger;
use PHPUnit\Framework\TestCase;

class ConfigMergerTest extends TestCase
{
    public function test_nested_generators_from_package_survive_outdated_published_config(): void
    {
        $package = [
            'generation' => [
                'generators' => [
                    'model' => 'Package\\ModelGenerator',
                    'views' => ViewsGenerator::class,
                    'policy' => 'Package\\PolicyGenerator',
                ],
                'default_ui' => 'api',
            ],
        ];

        $published = [
            'generation' => [
                'generators' => [
                    'model' => 'App\\CustomModelGenerator',
                    'controller' => 'Package\\ControllerGenerator',
                ],
                'default_ui' => 'web',
            ],
        ];

        $merged = ConfigMerger::merge($package, $published);

        $this->assertSame('App\\CustomModelGenerator', $merged['generation']['generators']['model']);
        $this->assertSame(ViewsGenerator::class, $merged['generation']['generators']['views']);
        $this->assertSame('Package\\PolicyGenerator', $merged['generation']['generators']['policy']);
        $this->assertSame('Package\\ControllerGenerator', $merged['generation']['generators']['controller']);
        $this->assertSame('web', $merged['generation']['default_ui']);
    }

    public function test_list_values_are_replaced_not_merged(): void
    {
        $merged = ConfigMerger::merge(
            ['feature_extras' => ['policy', 'seeder', 'test']],
            ['feature_extras' => ['policy']],
        );

        $this->assertSame(['policy'], $merged['feature_extras']);
    }
}
