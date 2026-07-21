<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use KarimAshraf\LaraArchitect\Database\Concerns\Filterable;
use KarimAshraf\LaraArchitect\Database\Concerns\HasUuid;

/**
 * @property int $id
 * @property string $uuid
 * @property string $title
 * @property string|null $body
 * @property bool $published
 * @property Carbon|null $deleted_at
 */
class Post extends Model
{
    use Filterable;
    use HasUuid;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'body',
        'published',
    ];

    protected function casts(): array
    {
        return [
            'published' => 'boolean',
        ];
    }
}
