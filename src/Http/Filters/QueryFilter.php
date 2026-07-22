<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Http\Filters;

/**
 * Backwards-compatibility alias kept so applications generated before v1.2
 * keep working after `composer update`.
 *
 * @deprecated Use ArchitectQueryFilter instead. Will be removed in v2.0.
 */
abstract class QueryFilter extends ArchitectQueryFilter {}
