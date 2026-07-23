# Getting Started

This guide walks you from a fresh install to a working CRUD API, step by step.

## Requirements

| Package | PHP | Laravel | Other |
| --- | --- | --- | --- |
| Core (`karim-ashraf/lara-architect`) | ^8.2 | 11 / 12 / 13 | Composer |
| Workspace UI (`karim-ashraf/lara-architect-ui`) | ^8.2 | 11 / 12 / 13 | Core package installed; **Node.js 18+** + npm to build assets |

## 1. Install and publish the config

```bash
composer require karim-ashraf/lara-architect

php artisan vendor:publish --tag=lara-architect-config
```

This creates `config/lara-architect.php` in your app. You don't have to touch it yet — the defaults work out of the box — but this file is where you later choose your default architecture, namespaces, and custom generators.

### Update the core package

```bash
composer update karim-ashraf/lara-architect --prefer-dist
```

Prefer `--prefer-dist` (zipball) over a git checkout. If you see `would clobber existing tag`:

```bash
composer clear-cache
# PowerShell
Remove-Item -Recurse -Force vendor\karim-ashraf\lara-architect -ErrorAction SilentlyContinue
# bash: rm -rf vendor/karim-ashraf/lara-architect

composer update karim-ashraf/lara-architect --prefer-dist
```

Or answer **yes** when Composer offers to reinstall the package.

After upgrading:

1. Re-check [CHANGELOG.md](../CHANGELOG.md) for breaking or config changes.
2. If you published config earlier, merge new keys from the package (or delete and re-publish carefully). From **1.4.2** onward, package defaults are deep-merged under a published config, so newer generators keep working without a full re-publish.
3. Optionally refresh stubs if you customized them:

```bash
php artisan vendor:publish --tag=lara-architect-stubs --force
```

### Install the Architecture Workspace (UI / “dashboard”)

The Workspace is a **sibling package** — context-first shell at `/architect/workspace`. It does **not** replace the core CLI.

**Requirements:** core package installed · PHP ^8.2 · Laravel 11–13 · Node.js 18+ (to build React assets once).

**From Packagist / GitHub** (sibling repo [`gubakareem/lara-architect-ui`](https://github.com/gubakareem/lara-architect-ui)):

```bash
composer require karim-ashraf/lara-architect-ui
```

Until the package is on Packagist, add a VCS repository in your app `composer.json`:

```json
"repositories": [
  { "type": "vcs", "url": "https://github.com/gubakareem/lara-architect-ui" }
]
```

```bash
composer require karim-ashraf/lara-architect-ui:^0.1
```

**From a local / path install** (monorepo or sibling folders):

```json
// composer.json in your Laravel app
"repositories": [
  { "type": "path", "url": "../lara-architect" },
  { "type": "path", "url": "../lara-architect-ui" }
]
```

```bash
composer require karim-ashraf/lara-architect-ui:@dev
cd vendor/karim-ashraf/lara-architect-ui   # or your path package root
npm install && npm run build
php artisan vendor:publish --tag=lara-architect-ui-assets
```

Open:

```text
/architect/workspace?context=ProductController&context_type=file
```

JSON (same snapshot adapters use):

```text
/architect/workspace?context=ProductController&format=json
```

**Update the Workspace UI:**

```bash
composer update karim-ashraf/lara-architect-ui --prefer-dist
cd vendor/karim-ashraf/lara-architect-ui && npm install && npm run build
php artisan vendor:publish --tag=lara-architect-ui-assets --force
```

If you hit `would clobber existing tag`, use the same `composer clear-cache` + remove vendor package steps as for [core updates](#update-the-core-package).

Full UI notes: [lara-architect-ui README](../../lara-architect-ui/README.md).

## 2. See what the package can generate

```bash
php artisan architect:patterns
```

This lists **architecture presets** (named stacks) and every **individual pattern** you can pass to `--patterns=…`.

### Architecture presets

| Preset | What it generates |
| --- | --- |
| `service-repository` (default) | model, migration, factory, enum, repository, service, filter, requests, resource, controller |
| `actions` | model, migration, factory, enum, DTO, action classes, filter, requests, resource, controller |
| `adr` | Same scaffold as `actions` (Action–Domain–Responder) |
| `ddd` | Domain folders under `App\Domain\{Module}\…` + infrastructure repositories |
| `cqrs` | Commands (writes) + queries (reads) + DTO + filter, requests, resource, controller |
| `pipeline` | Illuminate Pipeline (validate + persist pipes) + model/migration/requests/controller |
| `lean` | model, migration, requests, resource, controller |

```bash
php artisan make:module Invoice --architecture=ddd --fields="total:decimal"
php artisan make:module Report --architecture=cqrs --fields="title:string"
php artisan make:module Checkout --architecture=pipeline --fields="total:decimal"
```

### GoF / design patterns (add with `--patterns`)

These are **not** Eloquent `factory`. Use the names below:

| Pattern | What it generates |
| --- | --- |
| `strategy` | Interface + default/alternative strategies + context |
| `state` | Interface + draft / published / archived states + context |
| `singleton` | `{Model}Registry` singleton (prefer container binding in apps) |
| `abstract-factory` | Family of factories/products (notifier + serializer) + client |

```bash
php artisan make:module Order --patterns=model,strategy,state,singleton,abstract-factory
```

Usage examples (Strategy, State, Singleton, Abstract Factory, DDD, CQRS, Pipeline, Actions): **[Design patterns & examples](examples/design-patterns.md)**.

## 3. Generate your first CRUD module

Choose the presentation layer with `--ui`:

| `--ui` | Controllers | Responses | Also generates |
| --- | --- | --- | --- |
| `api` (default) | `App\Http\Controllers\Api` | JsonResource + JSON envelope | `ProductResource` |
| `web` | `App\Http\Controllers` | Blade views + redirects | `resources/views/products/*` |

Preview first — `--dry-run` shows every file that would be created without writing anything:

```bash
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum, notes:text:nullable" --dry-run
```

Happy with the list? Run it for real:

```bash
# API module (default)
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum, notes:text:nullable"

# Blade / web module
php artisan make:module Product --ui=web --fields="name:string, price:decimal, status:enum:int"
```

With the default `service-repository` + `api` preset you get:

```
app/
├── Models/Product.php
├── Enums/ProductStatus.php                     # EnumHelpers + isActive()-style helpers
├── Repositories/ProductRepository.php
├── Services/ProductService.php
└── Http/
    ├── Controllers/Api/ProductController.php   # API namespace
    ├── Filters/ProductFilter.php
    ├── Requests/Products/...
    └── Resources/ProductResource.php
lang/
├── en/enums.php                                # ProductStatus::class => value => label
└── ar/enums.php                                # Arabic defaults for common statuses
database/
├── factories/ProductFactory.php
└── migrations/xxxx_create_products_table.php
```

With `--ui=web`, `Resources` is skipped and you get `Http/Controllers/ProductController.php` plus `resources/views/products/{index,create,show,edit}.blade.php` instead.

**Enum field syntax**

```
status:enum          # string-backed: Draft/Active/Archived
status:enum:int      # int-backed: Inactive=0, Active=1 (unsignedTinyInteger column)
```

Labels come from `lang/{locale}/enums.php` via `EnumHelpers::label()`. Set locales with `LARA_ARCHITECT_ENUM_LOCALES=en,ar` or `config/lara-architect.php` → `enums.locales`.

Everything else is derived from `--fields`: the migration columns, validation rules, model casts, factory definitions, filter methods and the resource — all consistent with each other.

## 4. Migrate and register routes

```bash
php artisan migrate
```

Then add the resource routes — API vs web:

```php
// routes/api.php  (--ui=api, default)
use App\Http\Controllers\Api\ProductController;
Route::apiResource('products', ProductController::class);

// routes/web.php  (--ui=web)
use App\Http\Controllers\ProductController;
Route::resource('products', ProductController::class);
```

(The command prints this exact line as a "next step" after generating.)

## 5. Try the API

```bash
# Create — validated by StoreProductRequest, status must be a valid ProductStatus value
curl -X POST http://your-app.test/api/products \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name": "Desk", "price": 149.99, "sku": "DSK-001", "status": "active"}'

# List with filters — handled by ProductFilter automatically
curl "http://your-app.test/api/products?search=desk&price_min=100&status=active"

# Show / update / delete
curl http://your-app.test/api/products/1
curl -X PUT http://your-app.test/api/products/1 -H "Content-Type: application/json" -d '{"price": 129.99}'
curl -X DELETE http://your-app.test/api/products/1   # soft delete
```

All responses use `ProductResource` and a consistent JSON envelope; validation errors come back as `status` / `message` / `errors`.

## 6. Choosing a design pattern (architecture)

You have three ways to control what gets generated:

| Approach | When to use | Example |
| --- | --- | --- |
| `--architecture=` | One module needs a different style | `--architecture=actions` |
| Config / `.env` default | Whole project uses one style | `LARA_ARCHITECT_ARCHITECTURE=actions` |
| `--patterns=` | Mix-and-match for this module only | `--patterns=model,migration,service,controller` |

Other useful flags: `--force`, `--no-uuid`, `--no-soft-deletes`, `--dry-run`.

### 6.1 Service–Repository (default)

Best when you want a clear layer between HTTP, business rules, and data access.

```bash
php artisan make:module Product \
  --architecture=service-repository \
  --fields="name:string, price:decimal, sku:string:unique, status:enum"
```

**How the pieces fit together**

```
Controller  →  ProductService  →  ProductRepository  →  Product (Eloquent)
     ↑                ↑
StoreProductRequest   prepareForCreate / created hooks
ProductFilter (index)
```

**Controller** injects the service:

```php
public function __construct(
    private readonly ProductService $productService,
) {}

public function store(StoreProductRequest $request): JsonResponse
{
    $product = $this->productService->create($request->validated());

    return $this->respondCreated(new ProductResource($product));
}
```

**Extend the repository** with module-specific queries:

```php
// app/Repositories/ProductRepository.php
public function findBySku(string $sku): ?Product
{
    return $this->findBy('sku', $sku);
}

public function active(): Collection
{
    return $this->getBy(['status' => ProductStatus::Active]);
}
```

**Extend the service** with business logic (still transactional):

```php
// app/Services/ProductService.php
protected function prepareForCreate(array $data): array
{
    $data['sku'] = strtoupper($data['sku']);

    return $data;
}

protected function created(Model $model, array $data): void
{
    // e.g. dispatch ProductCreated event, clear cache, ...
}

public function archive(Product $product): Product
{
    return $this->update($product, ['status' => ProductStatus::Archived]);
}
```

**Soft-delete helpers** (available on both repository and service):

```php
$service->delete($product);           // soft delete
$service->restore($product);          // restore one
$service->restoreAll([1, 2, 3]);      // restore by ids (or all trashed if empty)
$service->forceDelete($product);      // permanent
$service->trashed();                  // list soft-deleted
$service->deleteMany([1, 2, 3]);
$service->deleteAll();
```

### 6.2 Actions + DTO

Best when each use-case should be a single, reusable class (ADR-style / single-responsibility).

```bash
php artisan make:module Order \
  --architecture=actions \
  --fields="total:decimal, status:enum, notes:text:nullable"
```

**What you get**

```
app/
├── Actions/Orders/CreateOrder.php
├── Actions/Orders/UpdateOrder.php
├── Actions/Orders/DeleteOrder.php
├── DTOs/OrderData.php
├── Enums/OrderStatus.php
├── Models/Order.php
└── Http/Controllers/OrderController.php   # calls CreateOrder::run(...), etc.
```

**Controller** dispatches actions (no service injection):

```php
public function store(StoreOrderRequest $request): JsonResponse
{
    $order = CreateOrder::run(OrderData::fromRequest($request));

    return $this->respondCreated(new OrderResource($order));
}

public function update(UpdateOrderRequest $request, Order $order): JsonResponse
{
    $order = UpdateOrder::run($order, OrderData::fromRequest($request));

    return $this->respondSuccess(new OrderResource($order), 'Order updated successfully.');
}

public function destroy(Order $order): JsonResponse
{
    DeleteOrder::run($order);

    return $this->respondDeleted();
}
```

**Add a custom action** for a non-CRUD use-case:

```php
// app/Actions/Orders/MarkOrderPaid.php
namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use KarimAshraf\LaraArchitect\Actions\ArchitectAction;

class MarkOrderPaid extends ArchitectAction
{
    protected function handle(Order $order): Order
    {
        $order->update(['status' => OrderStatus::Active]);

        return $order->refresh();
    }
}

// anywhere in the app:
MarkOrderPaid::run($order);
```

**DTO usage** outside the controller:

```php
$data = OrderData::fromArray([
    'total' => 99.5,
    'status' => 'active',
    'notes' => null,
]);

$data->toArray();           // snake_case keys, enums as values
$data->toFilteredArray();   // nulls removed — good for partial updates
```

### 6.3 Lean (Eloquent in the controller)

Best for tiny resources or admin prototypes where a full stack is overkill.

```bash
php artisan make:module Tag \
  --architecture=lean \
  --fields="name:string:unique"
```

Generates only: model, migration, form requests, resource, controller. The controller talks to Eloquent directly — no service, repository, or actions.

### 6.4 DDD (domain folders)

```bash
php artisan make:module Invoice --architecture=ddd --fields="total:decimal, status:enum"
```

Namespaces remap via `architecture_namespaces.ddd` — models/services/DTOs under `App\Domain\Invoice\…`, repositories under `App\Infrastructure\Invoice`. Behaviour matches service-repository; only layout changes. Full tree: [design-patterns.md](examples/design-patterns.md#ddd-preset--domain-layout).

### 6.5 CQRS (commands + queries)

```bash
php artisan make:module Report --architecture=cqrs --fields="title:string, body:text"
```

Writes go through command classes; reads through query classes + DTO. Example usage: [design-patterns.md](examples/design-patterns.md#cqrs-preset--commands--queries).

### 6.6 Pipeline

```bash
php artisan make:module Checkout --architecture=pipeline --fields="total:decimal"

$result = app(\App\Pipelines\Checkouts\CheckoutPipeline::class)->send(['total' => 149.99]);
```

### 6.7 GoF patterns (Strategy, State, Singleton, Abstract Factory)

```bash
php artisan make:module Order \
  --patterns=model,strategy,state,singleton,abstract-factory \
  --fields="total:decimal"
```

```php
// Strategy
$context = new \App\Strategies\OrderStrategyContext(new \App\Strategies\DefaultOrderStrategy());
$context->execute($order, ['total' => 99.5]);

// State
$state = new \App\States\OrderStateContext(new \App\States\DraftOrderState());
$state->publish();

// Singleton registry
\App\Singletons\OrderRegistry::getInstance()->set('last', $order->id);

// Abstract Factory (not Eloquent factory)
$client = new \App\Factories\Orders\OrderComponentClient(
    new \App\Factories\Orders\StandardOrderComponentFactory()
);
$client->dispatch(['id' => 1]);
```

Longer examples: [Design patterns & examples](examples/design-patterns.md).

### 6.8 Hand-picked patterns

Skip presets and compose your own stack for one module:

```bash
# Model + service, no repository
php artisan make:module Invoice \
  --patterns=model,migration,service,requests,resource,controller \
  --fields="number:string:unique, total:decimal"

# Actions without DTO
php artisan make:module Note \
  --patterns=model,migration,actions,requests,resource,controller \
  --fields="body:text"

# Read-only resource (no write layer)
php artisan make:module Report \
  --patterns=model,migration,resource,controller \
  --fields="title:string"
```

List every registered pattern anytime:

```bash
php artisan architect:patterns
```

### 6.9 Project default and custom presets

**Set a project-wide default** in `config/lara-architect.php` or `.env`:

```env
LARA_ARCHITECT_ARCHITECTURE=actions
```

```php
'generation' => [
    'default_architecture' => env('LARA_ARCHITECT_ARCHITECTURE', 'service-repository'),
],
```

**Add your own preset** — a named list of patterns — then use it like any built-in:

```php
// config/lara-architect.php
'architectures' => [
    'service-repository' => [/* ... */],
    'actions' => [/* ... */],
    'lean' => [/* ... */],

    // your team style:
    'api-full' => [
        'model', 'migration', 'factory', 'enum',
        'repository', 'service', 'filter',
        'requests', 'resource', 'controller',
    ],
],
```

```bash
php artisan make:module Customer --architecture=api-full --fields="name:string, email:string:unique"
```

## 7. Field definition reference

```
--fields="name:type[:modifier], ..."
```

- **Types:** `string`, `text`, `integer`, `biginteger`, `boolean`, `decimal`, `float`, `date`, `datetime`, `json`, `uuid`, `foreignid`, `enum`
- **Modifiers:** `nullable`, `unique`

Examples:

```
title:string, body:text:nullable            # strings and nullable text
price:decimal, stock:integer                # numbers -> range filters (price_min/price_max)
sku:string:unique                           # Rule::unique in store + ignore-current in update
status:enum                                 # generates App\Enums\ProductStatus + cast + validation
published_at:datetime:nullable              # date-range filters (published_at_from/_to)
category_id:foreignid                       # foreign key column
```

## 8. Patterns you use after generation

These work with generated modules and with hand-written code.

### Query filters

```php
// GET /api/products?search=desk&price_min=100&status=active
public function index(ProductFilter $filter): AnonymousResourceCollection
{
    return ProductResource::collection(
        $this->productService->filter($filter),
    );
}

// or on the model / repository:
Product::filter($filter)->paginate();
$repository->filter($filter, perPage: 20);
```

Add a custom filter method — any public method on the filter maps from the query string (`?featured=1` → `featured()`):

```php
// app/Http/Filters/ProductFilter.php
public function featured(string $value): void
{
    $this->builder->where('is_featured', filter_var($value, FILTER_VALIDATE_BOOLEAN));
}
```

### Enums (`EnumHelpers` + translations)

```bash
php artisan make:module Product --fields="status:enum"       # string
php artisan make:module Branch --fields="status:enum:int"    # int 0/1
```

Generated `lang/en/enums.php` (and `ar`, etc.):

```php
use App\Enums\ProductStatus;

return [
    ProductStatus::class => [
        ProductStatus::Draft->value => 'Draft',
        ProductStatus::Active->value => 'Active',
        ProductStatus::Archived->value => 'Archived',
    ],
];
```

```php
ProductStatus::Active->label();     // from lang file, or headline fallback
ProductStatus::Active->isActive();  // true
ProductStatus::values();
ProductStatus::options();           // value => label (for selects)
```

### Form requests + JSON responses

```php
class StoreProductRequest extends ArchitectFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ];
    }
}

// In any controller using RespondsWithJson:
return $this->respondCreated($resource);
return $this->respondSuccess($resource, 'Updated.');
return $this->respondDeleted();
return $this->respondError('Something went wrong.', 422);
```

More detail: [README — Runtime building blocks](../README.md#runtime-building-blocks).

## 9. Customizing the generated code

Publish the stubs and edit them — your published copies always win:

```bash
php artisan vendor:publish --tag=lara-architect-stubs
# edit stubs/lara-architect/*.stub
```

Change target namespaces (e.g. put models in `App\Domain\Models`) under `generation.namespaces` in the config. Namespace values support a `{module}` placeholder for domain layouts — `App\Domain\{module}\Services` generates `App\Domain\Product\Services\ProductService`. Add your own pattern by writing a class that implements `KarimAshraf\LaraArchitect\Contracts\Generator`, registering it under `generation.generators`, and adding it to a preset — see [Extending the generator](../README.md#extending-the-generator).

## 10. More commands

```bash
# Interactive wizard — asks for name, preset, UI and fields, then generates
php artisan architect:new

# make:module + policy + seeder + feature test in one go
php artisan architect:feature Product --fields="name:string, price:decimal"

# Check the app against the architecture conventions (CI-friendly, exit 1 on violations)
php artisan architect:lint

# Layer counts and hotspot report (fat controllers, God services, oversized files)
php artisan architect:analyze
```

Team conventions can be committed as an `architect.json` file at the project root; it deep-merges over the package config whenever these commands run — see [Team conventions with architect.json](../README.md#team-conventions-with-architectjson).
