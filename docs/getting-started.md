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

You have three ways to control what gets generated, from broadest to finest:

**Per command** — pass `--architecture` (or `-a`):

```bash
php artisan make:module Order --architecture=actions --fields="total:decimal, status:enum"
```

The `actions` preset skips the service/repository and instead generates `CreateOrder` / `UpdateOrder` / `DeleteOrder` action classes plus an `OrderData` DTO, and the controller dispatches those actions.

**As a project default** — set it once in `config/lara-architect.php` (or `.env`):

```php
'generation' => [
    'default_architecture' => env('LARA_ARCHITECT_ARCHITECTURE', 'actions'),
],
```

Now every `make:module` without `--architecture` uses it.

**Hand-picked patterns** — skip presets entirely with `--patterns`:

```bash
php artisan make:module Tag --patterns=model,migration,resource,controller --fields="name:string:unique"
```

Other useful flags: `--force` (overwrite existing files), `--no-uuid`, `--no-soft-deletes`.

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

## 8. Beyond the generator

The generated classes extend the package's base classes, which you can also use directly in hand-written code:

- `BaseRepository` / `BaseService` — CRUD plus soft-delete operations: `restore()`, `restoreAll()`, `deleteAll()`, `deleteMany()`, `forceDelete()`, `trashed()`
- `Action` — single-purpose, transaction-wrapped classes invoked with `MyAction::run(...)`
- `QueryFilter` + the `Filterable` model trait — request-driven filtering
- `BaseData` — DTOs hydrated from requests/arrays, with enum and nested-DTO support
- `BaseFormRequest` + `RespondsWithJson` — consistent JSON envelopes

See the [README](../README.md#runtime-building-blocks) for examples of each.

## 9. Customizing the generated code

Publish the stubs and edit them — your published copies always win:

```bash
php artisan vendor:publish --tag=lara-architect-stubs
# edit stubs/lara-architect/*.stub
```

Change target namespaces (e.g. put models in `App\Domain\Models`) under `generation.namespaces` in the config. Add your own pattern by writing a class that implements `KarimAshraf\LaraArchitect\Contracts\Generator`, registering it under `generation.generators`, and adding it to a preset — see [Extending the generator](../README.md#extending-the-generator).
