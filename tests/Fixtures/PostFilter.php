<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;

class PostFilter extends ArchitectQueryFilter
{
    public function search(string $value): void
    {
        $this->builder->where(function ($query) use ($value) {
            $query->where('title', 'like', "%{$value}%")
                ->orWhere('body', 'like', "%{$value}%");
        });
    }

    public function published(string $value): void
    {
        $this->builder->where('published', filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }
}
