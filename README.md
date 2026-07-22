# LaraArchitect

An architecture toolkit for Laravel. It gives you two things:

1. **Runtime building blocks** — lean base classes for repositories (with full soft-delete support), CRUD services, single-purpose actions, request-driven query filters, data transfer objects, form requests and JSON responses, so business logic stays out of your controllers.
2. **A dynamic module generator** — one `make:module` command that scaffolds an entire feature (model, migration, factory, enums, service/actions, filter, requests, resource, controller) using **configurable architecture presets**. Prefer actions over services? Repository pattern? Your own preset? It is all config.

```bash
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum, notes:text:nullable"
```

generates a ready-to-use module — typed, validated, transactional, and consistent with the rest of your codebase.

## Requirements

LaraArchitect supports **Laravel 11, 12 and 13** and follows the framework's own PHP requirements:

| Laravel | PHP | Status |
| --- | --- | --- |
| 13.x | 8.3 – 8.5 | ✅ Supported (latest) |
| 12.x | 8.2 – 8.5 | ✅ Supported |
| 11.x | 8.2 – 8.4 | ✅ Supported |

The package itself requires PHP `^8.2`; when you install it into a Laravel 13 application, Composer will already enforce PHP 8.3+ through the framework. The test suite runs against all three Laravel versions in CI.

## Installation

```bash
composer require karim-ashraf/lara-architect
```

The service provider is auto-discovered. Publish the config to customize presets, namespaces and behavior:

```bash
php artisan vendor:publish --tag=lara-architect-config
```

Optionally publish the stubs to customize every generated file:

```bash
php artisan vendor:publish --tag=lara-architect-stubs
```

Published stubs live in `stubs/lara-architect/` and always win over the package defaults.

## Quick start: your first CRUD in five steps

> The full walkthrough with explanations lives in [docs/getting-started.md](docs/getting-started.md).

**1.** See the available presets and patterns:

```bash
php artisan architect:patterns
```

**2.** Generate a module (add `--dry-run` first to preview without writing). Default is **API** (JsonResource + `Http\Controllers\Api`). Use `--ui=web` for Blade:

```bash
# API (default) — controller under App\Http\Controllers\Api + ProductResource
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum"

# Web / Blade — views + controller under App\Http\Controllers (no API resource)
php artisan make:module Product --ui=web --fields="name:string, price:decimal, status:enum:int"
```

This creates the model (with soft deletes, UUID and filtering), migration, factory, enum (+ `lang/*/enums.php` translations), repository, service, query filter, store/update form requests, and either an API resource + Api controller **or** Blade views + web controller — all wired together and consistent with your `--fields`.

Int-backed enums: `status:enum:int` → `enum ProductStatus: int` with `Inactive=0` / `Active=1`. String enums: `status:enum` (default).

**3.** Run the migration:

```bash
php artisan migrate
```

**4.** Register the routes in `routes/api.php` (API) or `routes/web.php` (web) — the command prints the exact line:

```php
// --ui=api (default)
Route::apiResource('products', \App\Http\Controllers\Api\ProductController::class);

// --ui=web
Route::resource('products', \App\Http\Controllers\ProductController::class);
```

**5.** Use the API:

```bash
curl -X POST http://your-app.test/api/products \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name": "Desk", "price": 149.99, "sku": "DSK-001", "status": "active"}'

curl "http://your-app.test/api/products?search=desk&price_min=100&status=active"
```

Validation, filtering, resources and the JSON envelope all work out of the box. To use a different design pattern, pass `--architecture=actions` (action classes + DTO instead of service + repository), set a project-wide default in the config, or hand-pick patterns with `--patterns=...` — details below.

## The module generator

### Architecture presets

A preset is just a named list of patterns in `config/lara-architect.php`:

```php
'architectures' => [
    'service-repository' => ['model', 'migration', 'factory', 'enum', 'repository', 'service', 'filter', 'requests', 'resource', 'controller'],
    'actions'            => ['model', 'migration', 'factory', 'enum', 'dto', 'actions', 'filter', 'requests', 'resource', 'controller'],
    'lean'               => ['model', 'migration', 'requests', 'resource', 'controller'],
],
```

Pick one per module:

```bash
# Default preset (service-repository)
php artisan make:module Product --fields="name:string, price:decimal"

# Action + DTO style
php artisan make:module Order --architecture=actions --fields="total:decimal, status:string"

# Or hand-pick patterns — no preset needed
php artisan make:module Tag --patterns=model,migration,resource,controller --fields="name:string:unique"
```

Other useful flags:

| Flag | Effect |
| --- | --- |
| `--ui=api` | JsonResource + controller in `Http\Controllers\Api` (default) |
| `--ui=web` | Blade views + web controller (no API resource) |
| `--dry-run` | Preview every file that would be generated, write nothing |
| `--force` | Overwrite existing files (they are skipped by default) |
| `--no-uuid` | Skip the `uuid` column + `HasUuid` trait |
| `--no-soft-deletes` | Skip soft deletes |

Discover what is available at any time:

```bash
php artisan architect:patterns
```

### Field definitions

`--fields` drives the migration, validation rules, casts, factory definitions, DTO properties and API resource in one go:

```
name:string, price:decimal, sku:string:unique, published_at:datetime:nullable, meta:json:nullable
```

Supported types: `string`, `text`, `integer`, `biginteger`, `boolean`, `decimal`, `float`, `date`, `datetime`, `json`, `uuid`, `foreignid`, `enum`. Modifiers: `nullable`, `unique`. For enums, add a backing type: `status:enum` (string) or `status:enum:int` (integer).

A unique field automatically gets `Rule::unique(...)` in the store request and `Rule::unique(...)->ignore($this->route(...))` in the update request.

### Enum fields

Declare a field as `enum` (e.g. `status:enum`) or int-backed `status:enum:int`. With the `enum` pattern enabled, the generator produces a backed enum (`App\Enums\ProductStatus`) that uses `EnumHelpers` — `values()`, `options()`, translated `label()`, `is()` / `isNot()`, and magic `isActive()`-style helpers. It also writes `lang/{locale}/enums.php` maps (default locales `en` and `ar`, configurable via `LARA_ARCHITECT_ENUM_LOCALES`). Override any helper by redeclaring it on the enum. The enum is wired through the whole module:

- the model casts the attribute to the enum (`'status' => ProductStatus::class`)
- the form requests validate it with `Rule::enum(ProductStatus::class)`
- the factory uses `fake()->randomElement(ProductStatus::cases())`
- the DTO property is typed with the enum, and `ArchitectData` hydrates it from strings

### Query filters

Every preset includes the `filter` pattern: a `ProductFilter` class (extending `ArchitectQueryFilter`) is generated with a `search()` method across text fields, exact matches for booleans/integers/enums, and `_min`/`_max` (numeric) or `_from`/`_to` (date) range methods. The generated model gets the `Filterable` trait and the controller's `index()` injects the filter:

```
GET /products?search=desk&price_min=100&status=active&created_at_from=2026-01-01
```

### The generated controller adapts to the module

- With the `service` pattern, the controller injects the generated service.
- With the `actions` pattern, it dispatches `CreateProduct::run(...)` / `UpdateProduct::run(...)` / `DeleteProduct::run(...)` (with a typed DTO if the `dto` pattern is included).
- With neither, it uses plain Eloquent.

### Extending the generator

Every pattern is a class implementing `KarimAshraf\LaraArchitect\Contracts\Generator`. Register your own and use it immediately:

```php
// config/lara-architect.php
'generators' => [
    // ...
    'observer' => App\Foundation\ObserverGenerator::class,
],
'architectures' => [
    'my-team-style' => ['model', 'migration', 'service', 'observer', 'requests', 'resource', 'controller'],
],
```

```bash
php artisan make:module Invoice --architecture=my-team-style
```

## Runtime building blocks

### Repository

```php
use KarimAshraf\LaraArchitect\Database\ArchitectRepository;

/**
 * @extends ArchitectRepository<Product>
 */
class ProductRepository extends ArchitectRepository
{
    protected function model(): string
    {
        return Product::class;
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->findBy('slug', $slug);
    }
}
```

You get `all()`, `paginate()`, `find()`, `findOrFail()`, `findBy()`, `getBy()`, `create()`, `update()`, `delete()`, `filter()` and `scoped(fn ($query) => ...)` for ad-hoc queries — fully generic-annotated for PHPStan/Larastan.

Soft deletes are first-class citizens:

```php
$repository->delete($product);          // soft delete (or hard delete if the model doesn't soft delete)
$repository->deleteMany([1, 2, 3]);     // bulk delete, returns the affected count
$repository->deleteAll();               // delete everything
$repository->restore($product);         // bring one back
$repository->restoreAll([1, 2]);        // restore given ids — or every trashed record with no arguments
$repository->forceDelete($product);     // permanently remove, even when already trashed
$repository->trashed();                 // list soft-deleted records
```

Restore operations throw a descriptive `SoftDeletesNotEnabledException` when the model doesn't use the `SoftDeletes` trait, instead of failing silently.

### Service

```php
use KarimAshraf\LaraArchitect\Services\ArchitectService;

/**
 * @extends ArchitectService<Product>
 */
class ProductService extends ArchitectService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function prepareForCreate(array $data): array
    {
        $data['slug'] ??= Str::slug($data['name']);

        return $data;
    }

    protected function created(Model $model, array $data): void
    {
        // dispatch events, clear caches, ...
    }
}
```

Write operations run inside a database transaction (disable via `lara-architect.services.transactions`). The service mirrors the full repository surface — including `deleteMany()`, `deleteAll()`, `restore()`, `restoreAll()`, `forceDelete()`, `trashed()` and `filter()` — with `restored()` and `forceDeleted()` hooks alongside `created()`, `updated()` and `deleted()`.

### Actions

```php
use KarimAshraf\LaraArchitect\Actions\ArchitectAction;

class PublishPost extends ArchitectAction
{
    protected function handle(Post $post): Post
    {
        $post->update(['published' => true]);

        return $post->refresh();
    }
}

// Resolved from the container, executed in a transaction:
PublishPost::run($post);
```

### Query filters

```php
use KarimAshraf\LaraArchitect\Http\Filters\ArchitectQueryFilter;

class ProductFilter extends ArchitectQueryFilter
{
    public function search(string $value): void
    {
        $this->builder->where(fn ($q) => $q
            ->where('name', 'like', "%{$value}%")
            ->orWhere('description', 'like', "%{$value}%"));
    }

    public function priceMin(string $value): void
    {
        $this->builder->where('price', '>=', (float) $value);
    }
}
```

Each public method is a filter; query string parameters map onto them automatically (`?price_min=100` calls `priceMin('100')`). Empty values and unknown parameters are ignored, and base-class methods can never be invoked from the outside. Apply a filter anywhere:

```php
Product::filter($filter)->paginate();          // via the Filterable model trait
$repository->filter($filter, perPage: 20);     // via the repository
$service->filter($filter);                     // via the service
```

### Data transfer objects

```php
use KarimAshraf\LaraArchitect\Support\ArchitectData;

final class ProductData extends ArchitectData
{
    public function __construct(
        public readonly string $name,
        public readonly float $price,
        public readonly ?string $description = null,
    ) {}
}

$data = ProductData::fromRequest($request);   // uses validated() on form requests
$data = ProductData::fromArray(['name' => 'Desk', 'price' => 99.9]);
$data->toArray();          // snake_case keys
$data->toFilteredArray();  // nulls removed — great for partial updates
```

Snake_case input keys map to camelCase constructor parameters automatically, nested `ArchitectData` types are hydrated recursively, and backed enums are hydrated from their raw values (`'status' => 'active'` becomes `ProductStatus::Active`).

### Form requests and JSON responses

```php
class StoreProductRequest extends ArchitectFormRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255']];
    }
}
```

JSON validation failures return a consistent envelope (`status` / `message` / `errors` — key names configurable). In controllers, the `RespondsWithJson` trait gives you `respondSuccess()`, `respondCreated()`, `respondDeleted()` and `respondError()` with the same envelope.

## Configuration reference

| Key | Purpose |
| --- | --- |
| `generation.default_architecture` | Preset used when `--architecture` is omitted |
| `generation.architectures` | Preset → pattern list map |
| `generation.generators` | Pattern → generator class map |
| `generation.namespaces` | Target namespace per generated class type |
| `models.uuids` / `models.soft_deletes` | Defaults for generated models and migrations |
| `services.transactions` / `actions.transactions` | Wrap writes in DB transactions |
| `responses.keys` | JSON envelope key names |

## Development

```bash
composer test      # PHPUnit (unit + feature, via orchestra/testbench)
composer analyse   # PHPStan level 5 with Larastan
composer format    # Laravel Pint
```

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of notable changes in each release.

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for the development setup, coding standards and pull request checklist.

## License

MIT. See [LICENSE.md](LICENSE.md).
