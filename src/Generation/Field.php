<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Support\Str;

/**
 * A single module field parsed from the --fields option, e.g. "price:decimal:nullable"
 * or "status:enum:int". Knows how to express itself as a migration column, validation
 * rule, cast, factory definition and PHP type so generators stay declarative.
 */
final class Field
{
    private const TYPES = [
        'string', 'text', 'integer', 'biginteger', 'boolean', 'decimal',
        'float', 'date', 'datetime', 'json', 'uuid', 'foreignid', 'enum',
    ];

    public function __construct(
        public readonly string $name,
        public readonly string $type = 'string',
        public readonly bool $nullable = false,
        public readonly bool $unique = false,
        public readonly string $enumBacking = 'string',
    ) {}

    public static function isSupportedType(string $type): bool
    {
        return in_array(strtolower($type), self::TYPES, true);
    }

    public function migrationColumn(): string
    {
        $method = match ($this->type) {
            'biginteger' => 'bigInteger',
            'datetime' => 'dateTime',
            'foreignid' => 'foreignId',
            'decimal' => 'decimal',
            'enum' => $this->isIntEnum() ? 'unsignedTinyInteger' : 'string',
            default => $this->type,
        };

        $column = $this->type === 'decimal'
            ? sprintf("\$table->decimal('%s', 12, 2)", $this->name)
            : sprintf("\$table->%s('%s')", $method, $this->name);

        if ($this->nullable) {
            $column .= '->nullable()';
        }

        if ($this->unique) {
            $column .= '->unique()';
        }

        if ($this->type === 'foreignid' && ! $this->nullable) {
            $column .= '->constrained()';
        }

        return $column.';';
    }

    /**
     * String validation rules, excluding uniqueness (see uniqueRule()).
     *
     * @return list<string>
     */
    public function validationRules(bool $forUpdate = false): array
    {
        $rules = [$forUpdate ? 'sometimes' : ($this->nullable ? 'nullable' : 'required')];

        if ($forUpdate && $this->nullable) {
            $rules[] = 'nullable';
        }

        return array_merge($rules, match ($this->type) {
            'string', 'uuid' => ['string', 'max:255'],
            'text' => ['string'],
            'integer', 'biginteger', 'foreignid' => ['integer'],
            'boolean' => ['boolean'],
            'decimal', 'float' => ['numeric'],
            'date', 'datetime' => ['date'],
            'json' => ['array'],
            'enum' => [], // Rule::enum(...) is added by the requests generator.
            default => ['string'],
        });
    }

    public function cast(): ?string
    {
        return match ($this->type) {
            'boolean' => 'boolean',
            'integer', 'biginteger', 'foreignid' => 'integer',
            'decimal' => 'decimal:2',
            'float' => 'float',
            'date' => 'date',
            'datetime' => 'datetime',
            'json' => 'array',
            default => null,
        };
    }

    public function factoryDefinition(): string
    {
        return match ($this->type) {
            'enum' => $this->isIntEnum()
                ? 'fake()->randomElement([0, 1])'
                : "fake()->randomElement(['draft', 'active', 'archived'])",
            'string' => 'fake()->words(3, true)',
            'uuid' => '(string) \\Illuminate\\Support\\Str::uuid()',
            'text' => 'fake()->paragraph()',
            'integer', 'biginteger' => 'fake()->numberBetween(1, 1000)',
            'boolean' => 'fake()->boolean()',
            'decimal', 'float' => 'fake()->randomFloat(2, 1, 1000)',
            'date' => 'fake()->date()',
            'datetime' => 'fake()->dateTime()',
            'json' => '[]',
            'foreignid' => 'null',
            default => 'fake()->word()',
        };
    }

    public function phpType(): string
    {
        $type = match ($this->type) {
            'integer', 'biginteger', 'foreignid' => 'int',
            'boolean' => 'bool',
            'decimal', 'float' => 'float',
            'json' => 'array',
            'enum' => $this->isIntEnum() ? 'int' : 'string',
            default => 'string',
        };

        return $this->nullable ? '?'.$type : $type;
    }

    public function camelName(): string
    {
        return Str::camel($this->name);
    }

    public function studlyName(): string
    {
        return Str::studly($this->name);
    }

    public function isEnum(): bool
    {
        return $this->type === 'enum';
    }

    public function isIntEnum(): bool
    {
        return $this->isEnum() && $this->enumBacking === 'int';
    }

    /**
     * Default cases for a generated enum: string → Draft/Active/Archived,
     * int → Inactive=0 / Active=1 (matching common status enums).
     *
     * @return list<array{name: string, value: string|int}>
     */
    public function enumCases(): array
    {
        if ($this->isIntEnum()) {
            return [
                ['name' => 'Inactive', 'value' => 0],
                ['name' => 'Active', 'value' => 1],
            ];
        }

        return [
            ['name' => 'Draft', 'value' => 'draft'],
            ['name' => 'Active', 'value' => 'active'],
            ['name' => 'Archived', 'value' => 'archived'],
        ];
    }
}
