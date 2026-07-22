<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * Request-driven query filter. Each public method on the child class is a
 * filter: when the request contains a query parameter with the same name
 * (snake_case maps to camelCase), the method is called with its value.
 *
 *     class ProductFilter extends ArchitectQueryFilter
 *     {
 *         public function search(string $value): void
 *         {
 *             $this->builder->where(fn ($q) => $q
 *                 ->where('name', 'like', "%{$value}%")
 *                 ->orWhere('description', 'like', "%{$value}%"));
 *         }
 *
 *         public function priceMin(string $value): void
 *         {
 *             $this->builder->where('price', '>=', (float) $value);
 *         }
 *     }
 *
 *     // GET /products?search=desk&price_min=100
 *     Product::filter($filter)->paginate();
 */
abstract class ArchitectQueryFilter
{
    /** @var Builder<covariant Model> */
    protected Builder $builder;

    public function __construct(
        protected readonly Request $request,
    ) {}

    /**
     * Apply every matching filter method to the builder.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->filters() as $name => $value) {
            $method = Str::camel((string) $name);

            if ($value === null || $value === '' || ! $this->isFilterMethod($method)) {
                continue;
            }

            $this->{$method}($value);
        }

        return $builder;
    }

    /**
     * The raw filter input. Override to whitelist or use a different source.
     *
     * @return array<string, mixed>
     */
    protected function filters(): array
    {
        return $this->request->query();
    }

    private function isFilterMethod(string $method): bool
    {
        if (! method_exists($this, $method)) {
            return false;
        }

        $reflection = new ReflectionMethod($this, $method);

        // Only public methods declared on the concrete filter class count;
        // base-class machinery (apply, filters, ...) is never invokable.
        return $reflection->isPublic()
            && ! $reflection->isStatic()
            && $reflection->getDeclaringClass()->getName() !== self::class;
    }
}
