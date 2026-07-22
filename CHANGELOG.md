# Changelog

All notable changes to `lara-architect` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-07-22

### Added

- `--ui=api|web` on `make:module`: API modules generate JsonResources and controllers under `Http\Controllers\Api`; web modules generate Blade views and web controllers with redirects.
- `views` pattern / `ViewsGenerator` for index, create, show and edit Blade scaffolds.
- Int-backed enums via `status:enum:int` (Inactive=0, Active=1) with `unsignedTinyInteger` migrations.
- Enum translation files at `lang/{locale}/enums.php` (configurable locales, default `en,ar`) with Class::class => value => label maps.
- `EnumHelpers::label()` resolves translations from `lang/*/enums.php`; magic `is{Case}()` helpers (e.g. `isActive()`).

### Changed

- Default API controllers now live in `App\Http\Controllers\Api` (configurable via `namespaces.controller_api`).
- Renamed public base classes to package-unique names to avoid clashes with app-level `Base*` classes:
  - `BaseRepository` → `ArchitectRepository`
  - `BaseService` → `ArchitectService`
  - `BaseData` → `ArchitectData`
  - `BaseFormRequest` → `ArchitectFormRequest`
  - `Action` → `ArchitectAction`
  - `QueryFilter` → `ArchitectQueryFilter`

## [1.1.0] - 2026-07-21

### Added

- `EnumHelpers` trait providing `values()`, `options()`, `label()`, `is()` and `isNot()` to backed enums; any helper can be overridden by redeclaring it on the enum.
- Getting-started guide and README quick-start walkthrough for first CRUD module.

### Changed

- Generated enums now use the `EnumHelpers` trait instead of duplicating the helper methods in every enum class.

## [1.0.0] - 2026-07-21

### Added

- **Architecture building blocks**
  - `ArchitectService` with transactional CRUD, lifecycle hooks (`created`, `updated`, `deleted`, `restored`, `forceDeleted`) and repository delegation.
  - `ArchitectRepository` with full CRUD, eager loading, pagination and `getBy` criteria queries.
  - `ArchitectAction` for single-purpose, transaction-wrapped operations.
  - `ArchitectData` DTO with array/request hydration, backed-enum support and `toArray()` serialization.
  - Contracts (`Repository`, `CrudService`, `Action`) for container binding and swappable implementations.
- **Soft-delete aware operations**
  - `deleteMany()`, `deleteAll()`, `restore()`, `restoreAll()`, `forceDelete()` and `trashed()` on repositories and services.
  - `SoftDeletesNotEnabledException` thrown when soft-delete operations are used on models without the `SoftDeletes` trait.
- **Query filters**
  - `ArchitectQueryFilter` base class mapping request query parameters to filter methods.
  - `Filterable` model trait providing a `filter()` scope.
  - `filter()` method on repositories and services returning paginated, filtered results.
- **Module generator** (`php artisan make:module`)
  - Architecture presets: `service-repository`, `actions`, and custom presets via config.
  - Field definition syntax (`--fields="title:string, status:enum, price:decimal:nullable"`) driving migrations, validation, casts, factories and DTOs.
  - Generators for model, migration, factory, service, repository, action, DTO, controller, form requests, API resources, enums and query filters.
  - Publishable, customizable stubs.
- **HTTP layer**
  - `ArchitectFormRequest` with a consistent JSON validation error envelope.
  - `RespondsWithJson` controller trait (`respondSuccess`, `respondCreated`, `respondDeleted`, `respondError`).
- **Model concerns**: `HasUuid` trait with configurable UUID column.
- **Enum support**: string-backed enum generation wired into model casts, `Rule::enum()` validation, factories and DTO property types.
- `architect:patterns` command listing registered generators and presets.
- Support for Laravel 11, 12 and 13 (PHP 8.2 – 8.5, per framework requirements).
- Full test suite (PHPUnit via Orchestra Testbench), PHPStan level 5 (Larastan) and Laravel Pint.

[Unreleased]: https://github.com/gubakareem/lara-architect/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.2.0
[1.1.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.1.0
[1.0.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.0.0
