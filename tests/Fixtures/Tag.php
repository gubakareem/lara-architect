<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/**
 * Intentionally does NOT use SoftDeletes, to test the guard rails.
 */
class Tag extends Model
{
    protected $fillable = ['name'];
}
