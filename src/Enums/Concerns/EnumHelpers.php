<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Shared helpers for backed enums. Any method can be overridden by simply
 * redeclaring it on the enum — a common case is a custom label():
 *
 *     enum ProductStatus: string
 *     {
 *         use EnumHelpers;
 *
 *         case Active = 'active';
 *
 *         public function label(): string
 *         {
 *             return __("statuses.{$this->value}");
 *         }
 *     }
 */
trait EnumHelpers
{
    /**
     * All case values, e.g. for validation or database columns.
     *
     * @return list<string|int>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Value => label map, e.g. for select inputs.
     *
     * @return array<string|int, string>
     */
    public static function options(): array
    {
        return array_combine(
            self::values(),
            array_map(fn (self $case): string => $case->label(), self::cases()),
        );
    }

    /**
     * Human-readable label ("in_review" becomes "In Review").
     */
    public function label(): string
    {
        return Str::headline((string) $this->value);
    }

    public function is(self $other): bool
    {
        return $this === $other;
    }

    public function isNot(self $other): bool
    {
        return $this !== $other;
    }
}
