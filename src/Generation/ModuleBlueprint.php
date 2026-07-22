<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Support\Str;

/**
 * Immutable description of the module being generated: its name, fields,
 * architecture, presentation layer (api|web) and the namespaces every
 * generated class should live in. Generators read from the blueprint
 * instead of re-deriving names.
 */
final class ModuleBlueprint
{
    public const PRESENTATION_API = 'api';

    public const PRESENTATION_WEB = 'web';

    /**
     * @param  list<Field>  $fields
     * @param  list<string>  $patterns
     * @param  array<string, string>  $namespaces
     */
    public function __construct(
        public readonly string $name,
        public readonly array $fields,
        public readonly string $architecture,
        public readonly array $patterns,
        public readonly array $namespaces,
        public readonly bool $usesUuid = true,
        public readonly bool $usesSoftDeletes = true,
        public readonly string $presentation = self::PRESENTATION_API,
    ) {}

    public function isApi(): bool
    {
        return $this->presentation === self::PRESENTATION_API;
    }

    public function isWeb(): bool
    {
        return $this->presentation === self::PRESENTATION_WEB;
    }

    public function model(): string
    {
        return Str::studly(Str::singular($this->name));
    }

    public function modelVariable(): string
    {
        return Str::camel($this->model());
    }

    public function pluralModel(): string
    {
        return Str::pluralStudly($this->model());
    }

    public function table(): string
    {
        return Str::snake($this->pluralModel());
    }

    public function routeName(): string
    {
        return Str::kebab($this->pluralModel());
    }

    public function viewPath(): string
    {
        return Str::snake($this->pluralModel());
    }

    /**
     * Target namespace for a generated class type. Supports a `{module}`
     * placeholder for domain/modular layouts, e.g. configuring
     * "App\Domain\{module}\Services" generates App\Domain\Product\Services.
     */
    public function namespaceFor(string $type): string
    {
        $namespace = $this->namespaces[$type] ?? 'App';

        return str_replace('{module}', $this->model(), $namespace);
    }

    public function modelClass(): string
    {
        return $this->namespaceFor('model').'\\'.$this->model();
    }

    public function hasPattern(string $pattern): bool
    {
        return in_array($pattern, $this->patterns, true);
    }

    /**
     * @return list<Field>
     */
    public function fillableFields(): array
    {
        return $this->fields;
    }

    /**
     * @return list<Field>
     */
    public function enumFields(): array
    {
        return array_values(array_filter($this->fields, static fn (Field $field) => $field->isEnum()));
    }

    /**
     * Whether enum classes are generated and should be referenced in casts,
     * validation rules, factories and DTOs.
     */
    public function usesEnums(): bool
    {
        return $this->hasPattern('enum') && $this->enumFields() !== [];
    }

    /**
     * Short class name of the enum generated for a field, e.g. ProductStatus.
     */
    public function enumClassName(Field $field): string
    {
        return $this->model().$field->studlyName();
    }

    /**
     * Fully-qualified class name of the enum generated for a field.
     */
    public function enumClass(Field $field): string
    {
        return $this->namespaceFor('enum').'\\'.$this->enumClassName($field);
    }
}
