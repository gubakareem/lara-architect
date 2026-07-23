# Lara Architect

**Architecture Memory and Improvement Platform for Laravel**

The Composer package `karim-ashraf/lara-architect` is the entry point. The product vision is a **platform** (core + UI + future integrations) — see [VISION.md](VISION.md) and [docs/architecture/platform.md](docs/architecture/platform.md).

Lara Architect helps you generate solid structure, catch layer violations early, remember what worked, and continuously improve how your app is built — so architecture stays intentional as the codebase grows.

```bash
composer require karim-ashraf/lara-architect

php artisan architect:new          # generate a module the right way
php artisan architect:lint         # enforce layer rules
php artisan architect:analyze      # see health, hotspots, structure
php artisan architect:workspace    # context + issues + explain (Workspace snapshot)
php artisan architect:ask "why ProductService exists"  # Phase 13 — living knowledge query
```

| You want… | Start here |
| --- | --- |
| **To use it** | This README → [Getting started](docs/getting-started.md) |
| **To contribute** | [VISION.md](VISION.md) → [docs/](docs/index.md) → [ADRs](docs/adr/) |
| **To maintain / release** | [MAINTAINERS.md](MAINTAINERS.md) |

---

**Lara Architect Platform** — package is the entry; platform is the vision ([VISION.md](VISION.md)).

| Install | You get |
| --- | --- |
| `karim-ashraf/lara-architect` | Core engine · memory · intelligence · guidance · learning |
| + `karim-ashraf/lara-architect-ui` | Architecture Workspace at `/architect/workspace` |
| + future packages | Debugbar · VS Code · GitHub · AI · Enterprise |

Lifecycle:

```
Design  →  Generate  →  Analyze  →  Enforce  →  Visualize  →  Integrate  →  Platform
```

| Pillar | What it does |
| --- | --- |
| **Design** | Presets, `architect.json`, publishable stubs, `{module}` domain layouts |
| **Generate** | `make:module`, `architect:feature`, `architect:new` wizard |
| **Analyze** | Dependency graph, layer counts, hotspots (`architect:analyze`) |
| **Enforce** | Declarative layer rules + baseline (`architect:lint`) |
| **Visualize** | Architecture Workspace via [lara-architect-ui](../lara-architect-ui/README.md) ([ADR-0008](docs/adr/0008-visualize-architecture-assistant-ux.md)) |
| **Integrate** | Event bus / public extension events ([ADR-0007](docs/adr/0007-event-bus-for-engine-extensibility.md)) |
| **Platform** | Sibling packages around a small, trustworthy core ([platform.md](docs/architecture/platform.md)) |

Under the hood sits a **framework-agnostic ArchitectureEngine** — Artisan commands are thin adapters. You can analyze a codebase with no Laravel bootstrap:

```php
use KarimAshraf\LaraArchitect\Architecture\ArchitectureEngine;

$result = ArchitectureEngine::create()->analyze('/path/to/project', ['app']);
```

```bash
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum"
```

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

**2.** Generate a module (add `--dry-run` first to preview without writing). Default is **API** (JsonResource + `Http\Controllers\Api`). Use `--ui=web` for Blade. Not sure which flags you want? Run the interactive wizard instead:

```bash
php artisan architect:new
```

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

A preset is just a named list of patterns in `config/lara-architect.php`. Built-in presets:

| Preset | What you get |
| --- | --- |
| `service-repository` | Service + repository layered CRUD (default) |
| `actions` | Single-purpose action classes + DTO |
| `adr` | Action–Domain–Responder (same scaffold as `actions`) |
| `ddd` | Domain folders under `App\Domain\{Module}\…` + infrastructure repositories |
| `cqrs` | Commands (writes) + queries (reads) + DTO |
| `pipeline` | Illuminate Pipeline with validation + persist pipes |
| `lean` | Minimal: model, migration, requests, controller |

GoF patterns (add with `--patterns=…`, not Eloquent `factory`):

| Pattern | Generates |
| --- | --- |
| `strategy` | Interface + default/alternative strategies + context |
| `state` | Interface + draft/published/archived states + context |
| `singleton` | `{Model}Registry` singleton (prefer container binding in apps) |
| `abstract-factory` | Family of factories/products (notifier + serializer) + client |

```bash
php artisan make:module Order --patterns=model,strategy,state,singleton,abstract-factory
```

```php
'architectures' => [
    'service-repository' => [/* … */],
    'actions' => [/* … */],
    'adr' => [/* … */],
    'ddd' => [/* … */],
    'cqrs' => [/* … */],
    'pipeline' => [/* … */],
    'lean' => [/* … */],
],
```

Pick one per module:

```bash
# Default preset (service-repository)
php artisan make:module Product --fields="name:string, price:decimal"

# Action + DTO style
php artisan make:module Order --architecture=actions --fields="total:decimal, status:string"

# Domain-Driven layout
php artisan make:module Invoice --architecture=ddd --fields="total:decimal"

# Or hand-pick patterns — no preset needed
php artisan make:module Tag --patterns=model,migration,resource,controller --fields="name:string:unique"

# Complete feature (prompts for name if omitted)
php artisan architect:feature
php artisan architect:feature Product --fields="name:string, price:decimal"

# Interactive wizard — answers a few questions, then generates
php artisan architect:new
```

`architect:feature` accepts the same flags as `make:module` and appends the patterns listed in `generation.feature_extras` (`policy`, `seeder`, `test` by default), so one command ships a model with a passing test, a seeder wired to the factory, and a policy ready to register.

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

Supported types: `string`, `text`, `integer` (alias `int`), `biginteger` (alias `bigint`), `boolean` (alias `bool`), `decimal`, `float`, `date`, `datetime`, `json`, `uuid`, `foreignid`, `enum`. Modifiers: `nullable`, `unique`. For enums, add a backing type: `status:enum` (string) or `status:enum:int` (integer).

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

### Team conventions with `architect.json`

Commit an `architect.json` at your project root to version your team's conventions without publishing the package config. Anything under it deep-merges over `config/lara-architect.php` when the generator runs:

```json
{
    "generation": {
        "default_architecture": "actions",
        "default_ui": "api",
        "namespaces": {
            "service": "App\\Domain\\{module}\\Services",
            "repository": "App\\Domain\\{module}\\Repositories"
        }
    }
}
```

### Domain / modular layouts

Namespace values support a `{module}` placeholder that is replaced with the model name, so you can generate into a domain-oriented structure:

```php
'namespaces' => [
    'service'    => 'App\\Domain\\{module}\\Services',     // App\Domain\Product\Services\ProductService
    'repository' => 'App\\Domain\\{module}\\Repositories', // App\Domain\Product\Repositories\ProductRepository
],
```

## Architecture lint & analysis

Generating a clean structure is half the job — keeping it clean is the other half. The engine builds a **dependency graph**, evaluates **declarative layer rules**, and reports hotspots — all through one `AnalysisResult` rendered as console or JSON.

```bash
# Fails (exit code 1) when layer rules are broken — wire it into CI
php artisan architect:lint
php artisan architect:lint --format=json

# Layer counts + violations + hotspots
php artisan architect:analyze
php artisan architect:analyze --format=json

# Workspace snapshot — current context, issues, explain (UI adapters consume the same JSON)
php artisan architect:workspace --context=ProductController
php artisan architect:workspace --format=json
php artisan architect:workspace --explain="<issue-id>"
```

The Workspace command builds a **WorkspaceSnapshot** read model from the engine ([spec](docs/architecture/workspace.md)). React / Debugbar / VS Code will share that payload later.

Existing apps often have hundreds of violations. Freeze them so only *new* ones fail CI:

```bash
php artisan architect:lint --update-baseline   # writes architect-baseline.json
php artisan architect:lint                    # ignores baselined violations
php artisan architect:lint --ignore-baseline  # see everything
```

### Declarative layer rules

The built-in **Laravel** rule pack encodes service-repository conventions (controllers must not depend on models/repositories/DB/inline validation; models must not depend on the HTTP/service layer). Override for any architecture via config or `architect.json`:

```json
{
  "lint": {
    "layers": {
      "Controller": "App\\Http\\Controllers",
      "Service": "App\\Domain",
      "Model": "App\\Models"
    },
    "dependencies": [
      { "from": "Controller", "allow": ["Service", "Request"] },
      { "from": "Controller", "deny": ["Model", "Repository"] }
    ]
  }
}
```

Rules never inspect PHP source — only the graph (nodes, edges, layers). That keeps them independent of whether extraction is regex today or AST tomorrow.

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
