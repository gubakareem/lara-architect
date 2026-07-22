<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Parses the --fields option syntax:
 *
 *     name:string, price:decimal:nullable, status:enum:int, sku:string:unique
 *
 * Segments after the type are modifiers (nullable, unique, required) or, for
 * enum fields, a backing type (string|int — defaults to string).
 */
final class FieldParser
{
    private const MODIFIERS = ['nullable', 'unique', 'required'];

    private const ENUM_BACKINGS = ['string', 'int'];

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

            if (in_array($lower, self::ENUM_BACKINGS, true)) {
                $enumBacking = $lower;

                continue;
            }

            if (! Field::isSupportedType($lower)) {
                throw new InvalidArgumentException(sprintf(
                    'Unknown field type or modifier [%s] for field [%s].',
                    $part,
                    $name,
                ));
            }

            $type = $lower;
        }

        if ($enumBacking !== 'string' && $type !== 'enum') {
            throw new InvalidArgumentException(sprintf(
                'Backing type [%s] is only valid for enum fields (field [%s]).',
                $enumBacking,
                $name,
            ));
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
