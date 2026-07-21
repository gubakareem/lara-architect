<?php

declare(strict_types=1);

namespace KarimAshraf\LaraArchitect\Actions;

use Illuminate\Support\Facades\DB;

/**
 * Single-purpose invokable action. Child classes implement handle() with
 * whatever signature suits them; execute() forwards arguments and wraps the
 * call in a database transaction when enabled.
 *
 * Usage:
 *
 *     class PublishPost extends Action
 *     {
 *         protected function handle(Post $post): Post { ... }
 *     }
 *
 *     PublishPost::run($post);
 *
 * @method mixed handle(mixed ...$arguments)
 */
abstract class Action
{
    /**
     * Whether execute() should run inside a database transaction.
     * Null falls back to the foundation.actions.transactions config value.
     */
    protected ?bool $useTransaction = null;

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Resolve the action from the container and execute it in one go.
     */
    public static function run(mixed ...$arguments): mixed
    {
        return static::make()->execute(...$arguments);
    }

    public function execute(mixed ...$arguments): mixed
    {
        if (! $this->shouldUseTransaction()) {
            return $this->handle(...$arguments);
        }

        return DB::transaction(fn () => $this->handle(...$arguments));
    }

    public function __invoke(mixed ...$arguments): mixed
    {
        return $this->execute(...$arguments);
    }

    protected function shouldUseTransaction(): bool
    {
        return $this->useTransaction ?? (bool) config('lara-architect.actions.transactions', true);
    }
}
