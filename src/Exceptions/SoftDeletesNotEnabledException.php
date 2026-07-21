<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Exceptions;

use RuntimeException;

class SoftDeletesNotEnabledException extends RuntimeException
{
    public static function for(string $model): self
    {
        return new self(sprintf(
            'Model [%s] must use the SoftDeletes trait to perform this operation.',
            $model,
        ));
    }
}
