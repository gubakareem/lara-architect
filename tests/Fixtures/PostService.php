<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use KarimAshraf\LaraArchitect\Services\ArchitectService;

/**
 * @extends ArchitectService<Post>
 */
class PostService extends ArchitectService
{
    /** @var list<string> */
    public array $events = [];

    public function __construct(PostRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function prepareForCreate(array $data): array
    {
        $data['title'] = Str::title($data['title']);

        return $data;
    }

    protected function created(Model $model, array $data): void
    {
        $this->events[] = 'created:'.$model->getKey();
    }

    protected function updated(Model $model, array $data): void
    {
        $this->events[] = 'updated:'.$model->getKey();
    }

    protected function deleted(Model|int|string $model): void
    {
        $this->events[] = 'deleted';
    }

    protected function restored(Model|int|string $model): void
    {
        $this->events[] = 'restored:'.($model instanceof Model ? $model->getKey() : $model);
    }

    protected function forceDeleted(Model|int|string $model): void
    {
        $this->events[] = 'force-deleted:'.($model instanceof Model ? $model->getKey() : $model);
    }
}
