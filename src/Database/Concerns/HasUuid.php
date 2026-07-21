<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Database\Concerns;

use Illuminate\Support\Str;

/**
 * Fills a `uuid` column on creation. Add `$table->uuid('uuid')->unique()`
 * to the migration. Route binding stays on the primary key unless you
 * override getRouteKeyName().
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(static function (self $model): void {
            $column = $model->uuidColumn();

            if (empty($model->getAttribute($column))) {
                $model->setAttribute($column, (string) Str::uuid());
            }
        });
    }

    public function uuidColumn(): string
    {
        return 'uuid';
    }

    public function scopeWhereUuid($query, string $uuid)
    {
        return $query->where($this->uuidColumn(), $uuid);
    }
}
