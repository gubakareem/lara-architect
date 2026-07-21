# Getting Started

This guide walks you from a fresh install to a working CRUD API, step by step.

## 1. Install and publish the config

```bash
composer require karim-ashraf/lara-architect

php artisan vendor:publish --tag=lara-architect-config
```

This creates `config/lara-architect.php` in your app. You don't have to touch it yet — the defaults work out of the box — but this file is where you later choose your default architecture, namespaces, and custom generators.

## 2. See what the package can generate

```bash
php artisan architect:patterns
```

This lists the available **architecture presets** and the individual **patterns** each one generates:

| Preset | What it generates |
| --- | --- |
| `service-repository` (default) | model, migration, factory, enum, repository, service, filter, requests, resource, controller |
| `actions` | model, migration, factory, enum, DTO, action classes, filter, requests, resource, controller |
| `lean` | model, migration, requests, resource, controller |

## 3. Generate your first CRUD module

Preview first — `--dry-run` shows every file that would be created without writing anything:

```bash
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum, notes:text:nullable" --dry-run
```

Happy with the list? Run it for real:

```bash
php artisan make:module Product --fields="name:string, price:decimal, sku:string:unique, status:enum, notes:text:nullable"
```

With the default `service-repository` preset you get:

```
app/
├── Models/Product.php                          # SoftDeletes, HasUuid, Filterable, casts, fillable
├── Enums/ProductStatus.php                     # string-backed enum for the status field
├── Repositories/ProductRepository.php          # extends BaseRepository<Product>
├── Services/ProductService.php                 # extends BaseService<Product>, injects the repository
└── Http/
    ├── Controllers/ProductController.php       # index/store/show/update/destroy wired to the service
    ├── Filters/ProductFilter.php               # search, exact, range and date-range filters
    ├── Requests/Products/StoreProductRequest.php
    ├── Requests/Products/UpdateProductRequest.php
    └── Resources/ProductResource.php
database/
├── factories/ProductFactory.php
└── migrations/xxxx_xx_xx_xxxxxx_create_products_table.php
```

Everything is derived from `--fields`: the migration columns, validation rules, model casts, factory definitions, filter methods and the resource — all consistent with each other.

## 4. Migrate and register routes

```bash
php artisan migrate
```

Then add the resource routes in `routes/api.php`:

```php
use App\Http\Controllers\ProductController;

Route::apiResource('products', ProductController::class);
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
use KarimAshraf\LaraArchitect\Actions\Action;

class MarkOrderPaid extends Action
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

### 6.4 Hand-picked patterns

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

### 6.5 Project default and custom presets

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

### Enums (`EnumHelpers`)

Generated enums use the package trait. Override any helper by redeclaring it:

```php
namespace App\Enums;

use KarimAshraf\LaraArchitect\Enums\Concerns\EnumHelpers;

enum ProductStatus: string
{
    use EnumHelpers;

    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return __("products.status.{$this->value}");
    }
}

ProductStatus::values();                    // ['draft', 'active', 'archived']
ProductStatus::options();                   // value => label (uses your label())
ProductStatus::Active->is(ProductStatus::Active);
```

### Form requests + JSON responses

```php
class StoreProductRequest extends BaseFormRequest
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

Change target namespaces (e.g. put models in `App\Domain\Models`) under `generation.namespaces` in the config. Add your own pattern by writing a class that implements `KarimAshraf\LaraArchitect\Contracts\Generator`, registering it under `generation.generators`, and adding it to a preset — see [Extending the generator](../README.md#extending-the-generator).
