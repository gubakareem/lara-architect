<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Support;

use BackedEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use KarimAshraf\LaraArchitect\Contracts\DataTransferObject;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Immutable data transfer object hydrated from arrays or requests by
 * matching constructor promoted properties (snake_case keys are mapped to
 * camelCase parameters automatically).
 *
 *     final class ProductData extends BaseData
 *     {
 *         public function __construct(
 *             public readonly string $name,
 *             public readonly float $price,
 *             public readonly ?string $description = null,
 *         ) {}
 *     }
 *
 *     $data = ProductData::fromArray($request->validated());
 */
abstract class BaseData implements DataTransferObject
{
    public static function fromArray(array $data): static
    {
        $reflection = new ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        $arguments = array_map(
            fn (ReflectionParameter $parameter) => static::resolveParameter($parameter, $data),
            $constructor->getParameters(),
        );

        return $reflection->newInstanceArgs($arguments);
    }

    public static function fromRequest(Request $request): static
    {
        $source = method_exists($request, 'validated') && $request instanceof FormRequest
            ? $request->validated()
            : $request->all();

        return static::fromArray($source);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];

        foreach (get_object_vars($this) as $property => $value) {
            $key = str($property)->snake()->value();

            $result[$key] = match (true) {
                $value instanceof DataTransferObject => $value->toArray(),
                $value instanceof BackedEnum => $value->value,
                default => $value,
            };
        }

        return $result;
    }

    /**
     * toArray() without null values — handy for partial updates.
     *
     * @return array<string, mixed>
     */
    public function toFilteredArray(): array
    {
        return array_filter($this->toArray(), static fn (mixed $value) => $value !== null);
    }

    protected static function resolveParameter(ReflectionParameter $parameter, array $data): mixed
    {
        $name = $parameter->getName();
        $key = str($name)->snake()->value();

        $value = $data[$name] ?? $data[$key] ?? null;

        if ($value === null) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            return null;
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $class = $type->getName();

            if (is_array($value) && is_subclass_of($class, DataTransferObject::class)) {
                return $class::fromArray($value);
            }

            if ((is_string($value) || is_int($value)) && is_subclass_of($class, BackedEnum::class)) {
                return $class::from($value);
            }
        }

        return $value;
    }
}
