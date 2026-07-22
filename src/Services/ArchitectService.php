<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Services;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use KarimAshraf\LaraArchitect\Contracts\CrudService;
use KarimAshraf\LaraArchitect\Contracts\Repository;
use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;

/**
 * CRUD service layered on top of a repository. Write operations run inside a
 * database transaction (configurable) and expose before/after hooks so child
 * services only implement what is specific to them.
 *
 * @template TModel of Model
 *
 * @implements CrudService<TModel>
 */
abstract class ArchitectService implements CrudService
{
    /**
     * @param  Repository<TModel>  $repository
     */
    public function __construct(
        protected Repository $repository,
    ) {}

    public function all(array $with = []): Collection
    {
        return $this->repository->all(['*'], $with);
    }

    public function paginate(int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, ['*'], $with);
    }

    public function find(int|string $id, array $with = []): ?Model
    {
        return $this->repository->find($id, ['*'], $with);
    }

    public function findOrFail(int|string $id, array $with = []): Model
    {
        return $this->repository->findOrFail($id, ['*'], $with);
    }

    public function create(array $data): Model
    {
        return $this->transactional(function () use ($data) {
            $model = $this->repository->create($this->prepareForCreate($data));

            $this->created($model, $data);

            return $model;
        });
    }

    public function update(Model|int|string $model, array $data): Model
    {
        return $this->transactional(function () use ($model, $data) {
            $model = $this->repository->update($model, $this->prepareForUpdate($data));

            $this->updated($model, $data);

            return $model;
        });
    }

    public function delete(Model|int|string $model): bool
    {
        return $this->transactional(function () use ($model) {
            $result = $this->repository->delete($model);

            $this->deleted($model);

            return $result;
        });
    }

    public function deleteMany(array $ids): int
    {
        return $this->transactional(function () use ($ids) {
            $count = $this->repository->deleteMany($ids);

            foreach ($ids as $id) {
                $this->deleted($id);
            }

            return $count;
        });
    }

    public function deleteAll(): int
    {
        return $this->transactional(fn () => $this->repository->deleteAll());
    }

    public function restore(Model|int|string $model): bool
    {
        return $this->transactional(function () use ($model) {
            $result = $this->repository->restore($model);

            $this->restored($model);

            return $result;
        });
    }

    public function restoreAll(array $ids = []): int
    {
        return $this->transactional(fn () => $this->repository->restoreAll($ids));
    }

    public function forceDelete(Model|int|string $model): bool
    {
        return $this->transactional(function () use ($model) {
            $result = $this->repository->forceDelete($model);

            $this->forceDeleted($model);

            return $result;
        });
    }

    public function trashed(array $with = []): Collection
    {
        return $this->repository->trashed(['*'], $with);
    }

    public function filter(ArchitectQueryFilter $filter, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $this->repository->filter($filter, $perPage, $with);
    }

    public function repository(): Repository
    {
        return $this->repository;
    }

    /**
     * Massage input before creating. Override in child services.
     */
    protected function prepareForCreate(array $data): array
    {
        return $data;
    }

    /**
     * Massage input before updating. Override in child services.
     */
    protected function prepareForUpdate(array $data): array
    {
        return $data;
    }

    protected function created(Model $model, array $data): void {}

    protected function updated(Model $model, array $data): void {}

    protected function deleted(Model|int|string $model): void {}

    protected function restored(Model|int|string $model): void {}

    protected function forceDeleted(Model|int|string $model): void {}

    protected function transactional(Closure $callback): mixed
    {
        if (! config('lara-architect.services.transactions', true)) {
            return $callback();
        }

        return DB::transaction($callback);
    }
}
