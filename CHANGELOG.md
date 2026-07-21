# Changelog

All notable changes to `lara-architect` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-07-21

### Added

- **Architecture building blocks**
  - `BaseService` with transactional CRUD, lifecycle hooks (`created`, `updated`, `deleted`, `restored`, `forceDeleted`) and repository delegation.
  - `BaseRepository` with full CRUD, eager loading, pagination and `getBy` criteria queries.
  - `BaseAction` for single-purpose, transaction-wrapped operations.
  - `BaseData` DTO with array/request hydration, backed-enum support and `toArray()` serialization.
  - Contracts (`Repository`, `CrudService`, `Action`) for container binding and swappable implementations.
- **Soft-delete aware operations**
  - `deleteMany()`, `deleteAll()`, `restore()`, `restoreAll()`, `forceDelete()` and `trashed()` on repositories and services.
  - `SoftDeletesNotEnabledException` thrown when soft-delete operations are used on models without the `SoftDeletes` trait.
- **Query filters**
  - `QueryFilter` base class mapping request query parameters to filter methods.
  - `Filterable` model trait providing a `filter()` scope.
  - `filter()` method on repositories and services returning paginated, filtered results.
- **Module generator** (`php artisan make:module`)
  - Architecture presets: `service-repository`, `actions`, and custom presets via config.
  - Field definition syntax (`--fields="title:string, status:enum, price:decimal:nullable"`) driving migrations, validation, casts, factories and DTOs.
  - Generators for model, migration, factory, service, repository, action, DTO, controller, form requests, API resources, enums and query filters.
  - Publishable, customizable stubs.
- **HTTP layer**
  - `BaseFormRequest` with a consistent JSON validation error envelope.
  - `RespondsWithJson` controller trait (`respondSuccess`, `respondCreated`, `respondDeleted`, `respondError`).
- **Model concerns**: `HasUuid` trait with configurable UUID column.
- **Enum support**: string-backed enum generation wired into model casts, `Rule::enum()` validation, factories and DTO property types.
- `architect:patterns` command listing registered generators and presets.
- Support for Laravel 11, 12 and 13 (PHP 8.2 – 8.5, per framework requirements).
- Full test suite (PHPUnit via Orchestra Testbench), PHPStan level 5 (Larastan) and Laravel Pint.

[Unreleased]: https://github.com/gubakareem/lara-architect/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.0.0
