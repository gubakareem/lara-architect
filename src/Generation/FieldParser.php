<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Generation;

use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Parses the --fields option syntax:
 *
 *     name:string:required, price:decimal:nullable, sku:string:unique
 *
 * Segments after the type are modifiers (nullable, unique, required).
 */
final class FieldParser
{
    private const MODIFIERS = ['nullable', 'unique', 'required'];

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

        foreach (array_slice($parts, 1) as $part) {
            $lower = strtolower($part);

            if (in_array($lower, self::MODIFIERS, true)) {
                $modifiers[] = $lower;

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

        return new Field(
            name: $name,
            type: $type,
            nullable: in_array('nullable', $modifiers, true),
            unique: in_array('unique', $modifiers, true),
        );
    }
}
