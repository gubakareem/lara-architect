<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Backwards-compatibility alias kept so applications generated before v1.2
 * keep working after `composer update`.
 *
 * @deprecated Use ArchitectService instead. Will be removed in v2.0.
 *
 * @template TModel of Model
 *
 * @extends ArchitectService<TModel>
 */
abstract class BaseService extends ArchitectService {}
