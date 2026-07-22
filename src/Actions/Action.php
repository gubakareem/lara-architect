<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Actions;

/**
 * Backwards-compatibility alias kept so applications generated before v1.2
 * keep working after `composer update`.
 *
 * @deprecated Use ArchitectAction instead. Will be removed in v2.0.
 */
abstract class Action extends ArchitectAction {}
