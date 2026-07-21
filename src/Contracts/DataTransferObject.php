<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * @extends Arrayable<string, mixed>
 */
interface DataTransferObject extends Arrayable
{
    public static function fromArray(array $data): static;
}
