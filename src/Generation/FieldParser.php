<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Parses the --fields option syntax:
 *
 *     name:string, price:decimal:nullable, status:enum:int, parent_id:int
 *
 * Segments after the name are a type (or alias like int→integer), then
 * modifiers (nullable, unique, required). For enum fields, string|int
 * after `enum` sets the backing type (defaults to string).
 */
final class FieldParser
{
    private const MODIFIERS = ['nullable', 'unique', 'required'];

    private const ENUM_BACKINGS = ['string', 'int'];

    private const TYPE_ALIASES = [
        'int' => 'integer',
        'bool' => 'boolean',
        'bigint' => 'biginteger',
    ];

    /**
     * @return list<Field>
     */
    public static function parse(?string $definition): array
    {
        if ($definition === null || trim($definition) === '') {
            return [];
        }

        return array_map(
            static fn (string $segment) => self::parseField(trim($segment)),
            array_filter(explode(',', $definition), static fn (string $s) => trim($s) !== ''),
        );
    }

    private static function parseField(string $segment): Field
    {
        $parts = array_values(array_filter(array_map('trim', explode(':', $segment))));

        if ($parts === []) {
            throw new InvalidArgumentException('Empty field definition.');
        }

        $name = Str::snake($parts[0]);
        $type = 'string';
        $modifiers = [];
        $enumBacking = 'string';

        foreach (array_slice($parts, 1) as $part) {
            $lower = strtolower($part);

            if (in_array($lower, self::MODIFIERS, true)) {
                $modifiers[] = $lower;

                continue;
            }

            // `status:enum:int` — int/string after enum is the backing type,
            // not a field-type rewrite of the already-chosen enum.
            if ($type === 'enum' && in_array($lower, self::ENUM_BACKINGS, true)) {
                $enumBacking = $lower;

                continue;
            }

            $resolved = self::TYPE_ALIASES[$lower] ?? $lower;

            if (! Field::isSupportedType($resolved)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown field type or modifier [%s] for field [%s].',
                    $part,
                    $name,
                ));
            }

            $type = $resolved;
        }

        return new Field(
            name: $name,
            type: $type,
            nullable: in_array('nullable', $modifiers, true),
            unique: in_array('unique', $modifiers, true),
            enumBacking: $type === 'enum' ? $enumBacking : 'string',
        );
    }
}
