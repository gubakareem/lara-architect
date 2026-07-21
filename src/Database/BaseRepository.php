<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Database;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use InvalidArgumentException;
use KarimAshraf\LaraArchitect\Contracts\Repository;
use KarimAshraf\LaraArchitect\Exceptions\SoftDeletesNotEnabledException;
use KarimAshraf\LaraArchitect\Http\Filters\QueryFilter;

/**
 * Generic Eloquent repository. Extend it, point it at a model and add your
 * own query methods; the boilerplate CRUD is already covered.
 *
 * @template TModel of Model
 *
 * @implements Repository<TModel>
 */
abstract class BaseRepository implements Repository
{
    /** @var TModel */
    protected Model $model;

    public function __construct()
    {
        $this->model = $this->resolveModel();
    }

    /**
     * The fully-qualified class name of the model this repository manages.
     *
     * @return class-string<TModel>
     */
    abstract protected function model(): string;

    public function all(array $columns = ['*'], array $with = []): Collection
    {
        // Larastan's get() extension erases unbound template types,
        // so we assert the (correct) generic type at this boundary.
        /** @var Collection<int, TModel> */
        return $this->query()->with($with)->get($columns);
    }

    public function paginate(int $perPage = 15, array $columns = ['*'], array $with = []): LengthAwarePaginator
    {
        return $this->query()->with($with)->paginate($perPage, $columns);
    }

    public function find(int|string $id, array $columns = ['*'], array $with = []): ?Model
    {
        return $this->query()->with($with)->find($id, $columns);
    }

    public function findOrFail(int|string $id, array $columns = ['*'], array $with = []): Model
    {
        return $this->query()->with($with)->findOrFail($id, $columns);
    }

    public function findBy(string $field, mixed $value, array $columns = ['*'], array $with = []): ?Model
    {
        return $this->query()->with($with)->where($field, $value)->first($columns);
    }

    public function getBy(string $field, mixed $value, array $columns = ['*'], array $with = []): Collection
    {
        // See all() for why this assertion is needed.
        /** @var Collection<int, TModel> */
        return $this->query()->with($with)->where($field, $value)->get($columns);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model|int|string $model, array $attributes): Model
    {
        $model = $this->resolveInstance($model);
        $model->fill($attributes)->save();

        return $model->refresh();
    }

    public function delete(Model|int|string $model): bool
    {
        return (bool) $this->resolveInstance($model)->delete();
    }

    public function deleteMany(array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        return $this->query()->whereKey($ids)->delete();
    }

    public function deleteAll(): int
    {
        return $this->query()->delete();
    }

    public function restore(Model|int|string $model): bool
    {
        $instance = $this->resolveTrashedInstance($model);

        if (! method_exists($instance, 'restore')) {
            throw SoftDeletesNotEnabledException::for($this->model());
        }

        return (bool) $instance->restore();
    }

    public function restoreAll(array $ids = []): int
    {
        return $this->trashedQuery()
            ->when($ids !== [], fn (Builder $query) => $query->whereKey($ids))
            ->update([$this->deletedAtColumn() => null]);
    }

    public function forceDelete(Model|int|string $model): bool
    {
        return (bool) $this->resolveTrashedInstance($model)->forceDelete();
    }

    public function trashed(array $columns = ['*'], array $with = []): Collection
    {
        // See all() for why this assertion is needed.
        /** @var Collection<int, TModel> */
        return $this->trashedQuery()->with($with)->get($columns);
    }

    public function filter(QueryFilter $filter, int $perPage = 15, array $with = []): LengthAwarePaginator
    {
        return $filter->apply($this->query()->with($with))->paginate($perPage);
    }

    public function scoped(Closure $callback): mixed
    {
        return $callback($this->query());
    }

    public function query(): Builder
    {
        // Larastan cannot resolve `static` through the template type here,
        // so we assert the (correct) generic type once at this boundary.
        /** @var Builder<TModel> */
        return $this->model->newQuery();
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function usesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model), true);
    }

    /**
     * @param  TModel|int|string  $model
     * @return TModel
     */
    protected function resolveInstance(Model|int|string $model): Model
    {
        return $model instanceof Model ? $model : $this->findOrFail($model);
    }

    /**
     * Resolve an instance, including soft-deleted ones when applicable.
     *
     * @param  TModel|int|string  $model
     * @return TModel
     */
    protected function resolveTrashedInstance(Model|int|string $model): Model
    {
        if ($model instanceof Model) {
            return $model;
        }

        if (! $this->usesSoftDeletes()) {
            return $this->findOrFail($model);
        }

        return $this->query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->findOrFail($model);
    }

    /**
     * A query limited to soft-deleted records (the equivalent of onlyTrashed()).
     *
     * @return Builder<TModel>
     */
    protected function trashedQuery(): Builder
    {
        return $this->query()
            ->withoutGlobalScope(SoftDeletingScope::class)
            ->whereNotNull($this->deletedAtColumn());
    }

    /**
     * The model's deleted_at column, or a loud failure when the model
     * does not soft delete.
     */
    protected function deletedAtColumn(): string
    {
        $model = $this->model;

        if (! method_exists($model, 'getDeletedAtColumn')) {
            throw SoftDeletesNotEnabledException::for($this->model());
        }

        return $model->getDeletedAtColumn();
    }

    /**
     * @return TModel
     */
    protected function resolveModel(): Model
    {
        $class = $this->model();
        $instance = app($class);

        if (! $instance instanceof Model) {
            throw new InvalidArgumentException(sprintf(
                '%s::model() must return an Eloquent model class, got [%s].',
                static::class,
                $class,
            ));
        }

        return $instance;
    }
}
