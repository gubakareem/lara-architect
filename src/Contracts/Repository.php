<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Contracts;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;

/**
 * @template TModel of Model
 */
interface Repository
{
    /**
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*'], array $with = []): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function find(int|string $id, array $columns = ['*'], array $with = []): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int|string $id, array $columns = ['*'], array $with = []): Model;

    /**
     * @return TModel|null
     */
    public function findBy(string $field, mixed $value, array $columns = ['*'], array $with = []): ?Model;

    /**
     * @return Collection<int, TModel>
     */
    public function getBy(string $field, mixed $value, array $columns = ['*'], array $with = []): Collection;

    /**
     * @return TModel
     */
    public function create(array $attributes): Model;

    /**
     * @param  TModel|int|string  $model
     * @return TModel
     */
    public function update(Model|int|string $model, array $attributes): Model;

    /**
     * @param  TModel|int|string  $model
     */
    public function delete(Model|int|string $model): bool;

    /**
     * Delete (or soft delete) the given ids. Returns the number of deleted rows.
     *
     * @param  list<int|string>  $ids
     */
    public function deleteMany(array $ids): int;

    /**
     * Delete (or soft delete) every record. Returns the number of deleted rows.
     */
    public function deleteAll(): int;

    /**
     * Restore a soft-deleted record.
     *
     * @param  TModel|int|string  $model
     */
    public function restore(Model|int|string $model): bool;

    /**
     * Restore the given ids, or every trashed record when no ids are given.
     * Returns the number of restored rows.
     *
     * @param  list<int|string>  $ids
     */
    public function restoreAll(array $ids = []): int;

    /**
     * Permanently delete a record, even a soft-deleted one.
     *
     * @param  TModel|int|string  $model
     */
    public function forceDelete(Model|int|string $model): bool;

    /**
     * All soft-deleted records.
     *
     * @return Collection<int, TModel>
     */
    public function trashed(array $columns = ['*'], array $with = []): Collection;

    /**
     * Paginate records with a request-driven query filter applied.
     */
    public function filter(ArchitectQueryFilter $filter, int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * Whether the underlying model uses the SoftDeletes trait.
     */
    public function usesSoftDeletes(): bool;

    /**
     * Run an ad-hoc query against the underlying model.
     *
     * @param  Closure(Builder<TModel>): mixed  $callback
     */
    public function scoped(Closure $callback): mixed;

    /**
     * @return Builder<TModel>
     */
    public function query(): Builder;

    /**
     * @return TModel
     */
    public function getModel(): Model;
}
