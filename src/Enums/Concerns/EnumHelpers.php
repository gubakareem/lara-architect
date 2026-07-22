<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Enums\Concerns;

use BadMethodCallException;
use Illuminate\Support\Str;

/**
 * Shared helpers for backed enums (string or int). Labels resolve from
 * `lang/{locale}/enums.php` when present (class => [value => label]), then
 * fall back to a headline of the value. Any method can be overridden by
 * redeclaring it on the enum.
 *
 * Magic `is{Case}()` helpers are provided, e.g. `$status->isActive()`.
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
     * Translated label from lang/{locale}/enums.php, or a headline fallback.
     */
    public function label(): string
    {
        $translations = trans('enums');

        if (is_array($translations) && isset($translations[static::class][$this->value])) {
            return (string) $translations[static::class][$this->value];
        }

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

    /**
     * Support `$enum->isActive()` / `$enum->isInactive()` for each case name.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (str_starts_with($name, 'is') && strlen($name) > 2 && $name !== 'isNot') {
            $caseName = substr($name, 2);

            foreach (self::cases() as $case) {
                if (strcasecmp($case->name, $caseName) === 0) {
                    return $this === $case;
                }
            }
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s() does not exist.',
            static::class,
            $name,
        ));
    }
}
