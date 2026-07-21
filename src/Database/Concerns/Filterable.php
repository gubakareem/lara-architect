<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Database\Concerns;

use Illuminate\Database\Eloquent\Builder;
use KarimAshraf\LaraArchitect\Http\Filters\QueryFilter;

/**
 * Adds a `filter()` scope that applies a QueryFilter to the model's query:
 *
 *     Product::filter($filter)->latest()->paginate();
 */
trait Filterable
{
    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFilter(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
