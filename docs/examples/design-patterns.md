# Design patterns & architecture presets

Lara Architect generates **architecture presets** (named stacks) and **individual patterns** you can mix with `--patterns=…`.

List everything registered in your app:

```bash
php artisan architect:patterns
```

> **Note:** Pattern `factory` is the Eloquent model factory (`Database\Factories`). The GoF **Abstract Factory** is `abstract-factory` — a different generator.

---

## Architecture presets

| Preset | Intent | Patterns included |
| --- | --- | --- |
| `service-repository` (default) | Classic layered CRUD | model, migration, factory, enum, repository, service, filter, requests, resource, controller |
| `actions` | One class per use-case + DTO | model, migration, factory, enum, dto, actions, filter, requests, resource, controller |
| `adr` | Action–Domain–Responder | Same scaffold as `actions` |
| `ddd` | Domain folders under `App\Domain\{Module}` | model, migration, factory, enum, dto, repository, service, filter, requests, resource, controller (+ namespace overlays) |
| `cqrs` | Separate writes (commands) and reads (queries) | model, migration, factory, enum, dto, query, command, filter, requests, resource, controller |
| `pipeline` | Illuminate Pipeline pipes | model, migration, factory, enum, pipeline, requests, resource, controller |
| `lean` | Minimal scaffold | model, migration, requests, resource, controller |

```bash
php artisan make:module Product --architecture=service-repository --fields="name:string, price:decimal"
php artisan make:module Order --architecture=actions --fields="total:decimal, status:enum"
php artisan make:module Invoice --architecture=ddd --fields="total:decimal"
php artisan make:module Report --architecture=cqrs --fields="title:string"
php artisan make:module Checkout --architecture=pipeline --fields="total:decimal"
php artisan make:module Tag --architecture=lean --fields="name:string:unique"
```

Set a project default:

```env
LARA_ARCHITECT_ARCHITECTURE=ddd
```

---

## GoF / behavioral & creational patterns

Add with `--patterns=…` (alone or on top of a preset via hand-picked lists):

| Pattern | Generates | Default namespace |
| --- | --- | --- |
| `strategy` | Interface + default/alternative strategies + context | `App\Strategies` |
| `state` | Interface + draft / published / archived states + context | `App\States` |
| `singleton` | `{Model}Registry` (classic singleton) | `App\Singletons` |
| `abstract-factory` | Family of factories + notifier/serializer products + client | `App\Factories` |

```bash
php artisan make:module Order \
  --patterns=model,migration,strategy,state,singleton,abstract-factory \
  --fields="total:decimal, status:string"
```

### Strategy — interchangeable algorithms

```php
use App\Strategies\DefaultOrderStrategy;
use App\Strategies\AlternativeOrderStrategy;
use App\Strategies\OrderStrategyContext;

$context = new OrderStrategyContext(new DefaultOrderStrategy());
$context->execute($order, ['total' => 99.5]);

$context->use(new AlternativeOrderStrategy());
$context->execute($order, ['total' => 120]);

// or by registered name:
$context->use('alternative');
```

**When to use:** shipping calculators, pricing rules, export formats — same interface, swap behaviour.

### State — lifecycle transitions

```php
use App\States\DraftOrderState;
use App\States\OrderStateContext;

$context = new OrderStateContext(new DraftOrderState());
$context->name();      // 'draft'
$context->publish();   // → PublishedOrderState
$context->archive();   // → ArchivedOrderState
```

**When to use:** draft → published → archived workflows; gate actions by current state.

### Singleton — one shared registry

```php
use App\Singletons\OrderRegistry;

$registry = OrderRegistry::getInstance();
$registry->set('last_id', $order->id);
$registry->get('last_id');

OrderRegistry::reset(); // tests / CLI only
```

In Laravel apps prefer the container when you can:

```php
$this->app->singleton(OrderRegistry::class);
```

The generated class is for **explicit Singleton semantics** (teaching, libraries, CLI).

### Abstract Factory — families of related objects

Not the Eloquent `factory` pattern. Generates Standard/Premium factories, notifiers, serializers, and a client that depends only on the abstract factory:

```php
use App\Factories\Orders\OrderComponentClient;
use App\Factories\Orders\StandardOrderComponentFactory;
use App\Factories\Orders\PremiumOrderComponentFactory;

$client = new OrderComponentClient(new StandardOrderComponentFactory());
$client->dispatch(['id' => 1, 'total' => 50]);

// swap the whole product family:
$client = new OrderComponentClient(new PremiumOrderComponentFactory());
$client->dispatch(['id' => 1, 'total' => 50]);
```

**When to use:** themed/partner integrations where notifier + serializer must stay consistent as a set.

---

## DDD preset — domain layout

```bash
php artisan make:module Invoice --architecture=ddd --fields="total:decimal, status:enum"
```

Typical layout (namespaces from `architecture_namespaces.ddd`):

```
app/
├── Domain/Invoice/
│   ├── Models/Invoice.php
│   ├── Services/InvoiceService.php
│   ├── Data/InvoiceData.php
│   ├── Enums/InvoiceStatus.php
│   └── Filters/InvoiceFilter.php
└── Infrastructure/Invoice/InvoiceRepository.php
```

Use the service the same way as service-repository; only the folders change.

---

## CQRS preset — commands & queries

```bash
php artisan make:module Report --architecture=cqrs --fields="title:string, body:text"
```

```
app/
├── Commands/Reports/CreateReportCommand.php
├── Commands/Reports/UpdateReportCommand.php
├── Commands/Reports/DeleteReportCommand.php
├── Queries/Reports/ListReportsQuery.php
├── Queries/Reports/GetReportQuery.php
└── DTOs/ReportData.php
```

```php
// writes
CreateReportCommand::run(ReportData::fromRequest($request));

// reads
$reports = ListReportsQuery::run($filter);
$report  = GetReportQuery::run($id);
```

---

## Pipeline preset

```bash
php artisan make:module Checkout --architecture=pipeline --fields="total:decimal"
```

```php
use App\Pipelines\Checkouts\CheckoutPipeline;

$result = app(CheckoutPipeline::class)->send([
    'total' => 149.99,
    // …
]);
```

Pipes default to validate → persist; add your own pipes in `through([...])`.

---

## Actions / ADR

```bash
php artisan make:module Order --architecture=actions --fields="total:decimal, status:enum"
# same scaffold:
php artisan make:module Order --architecture=adr --fields="total:decimal, status:enum"
```

```php
CreateOrder::run(OrderData::fromRequest($request));
UpdateOrder::run($order, OrderData::fromRequest($request));
DeleteOrder::run($order);
```

---

## Compose your own

```bash
# Model + service, no repository
php artisan make:module Invoice \
  --patterns=model,migration,service,requests,resource,controller \
  --fields="number:string:unique, total:decimal"

# CRUD + Strategy + State
php artisan make:module Ticket \
  --patterns=model,migration,factory,service,repository,filter,requests,resource,controller,strategy,state \
  --fields="title:string, status:enum"
```

Register custom presets under `architectures` in `config/lara-architect.php` — see [Getting started §6.5](../getting-started.md#65-project-default-and-custom-presets).
