<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;

/**
 * @template TModel of Model
 */
interface CrudService
{
    /**
     * @return Collection<int, TModel>
     */
    public function all(array $with = []): Collection;

    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * @return TModel|null
     */
    public function find(int|string $id, array $with = []): ?Model;

    /**
     * @return TModel
     */
    public function findOrFail(int|string $id, array $with = []): Model;

    /**
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * @param  TModel|int|string  $model
     * @return TModel
     */
    public function update(Model|int|string $model, array $data): Model;

    /**
     * @param  TModel|int|string  $model
     */
    public function delete(Model|int|string $model): bool;

    /**
     * @param  list<int|string>  $ids
     */
    public function deleteMany(array $ids): int;

    public function deleteAll(): int;

    /**
     * @param  TModel|int|string  $model
     */
    public function restore(Model|int|string $model): bool;

    /**
     * @param  list<int|string>  $ids
     */
    public function restoreAll(array $ids = []): int;

    /**
     * @param  TModel|int|string  $model
     */
    public function forceDelete(Model|int|string $model): bool;

    /**
     * @return Collection<int, TModel>
     */
    public function trashed(array $with = []): Collection;

    public function filter(ArchitectQueryFilter $filter, int $perPage = 15, array $with = []): LengthAwarePaginator;

    /**
     * @return Repository<TModel>
     */
    public function repository(): Repository;
}
