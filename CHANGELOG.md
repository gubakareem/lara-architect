# Changelog

All notable changes to `lara-architect` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.4.1] - 2026-07-22

### Fixed

- Field parser treated `int` as an enum backing everywhere, so definitions like `parent_id:int` failed. `int` / `bool` / `bigint` are now type aliases (`integer` / `boolean` / `biginteger`); `int`/`string` are enum backings only after `enum` (e.g. `status:enum:int`).

## [1.4.0] - 2026-07-22

### Added

- **ArchitectureEngine** — framework-agnostic façade (`analyze` / `lint` / `graph`) built from use cases (`BuildDependencyGraph`, `LintArchitecture`, `AnalyzeArchitecture`). No Laravel container required.
- **Dependency graph** with typed edges (`import`, `extends`, `implements`, `uses-trait`, `static-call`, `new`, `method-call`) via `RegexExtractor` behind the `DependencyExtractor` interface.
- **LayerRegistry** + declarative **allow/deny** layer rules (`LayerDependencyRule`).
- Built-in **Laravel rule pack** (default) expressing service-repository conventions as layer rules.
- Immutable **AnalysisResult** with **Console** and **JSON** renderers (`--format=console|json`; `sarif` reserved).
- **Lint baseline** (`--update-baseline`, `architect-baseline.json`) so legacy apps can adopt lint without failing CI on existing debt.
- Value objects / IDs: `NodeId`, `LayerId`, `RuleId`, `Dependency`, `Violation`, `Hotspot`, `ArchitectureFile`.
- Reserved extension interfaces: `MetricCalculator`, `SuggestionProvider`, `RulePack`, `Renderer`.

### Changed

- `architect:lint` / `architect:analyze` now drive the ArchitectureEngine instead of file-scanning convention rules.
- README leads with the Design → Generate → Analyze → Enforce → Evolve lifecycle.

### Deprecated

- File-based `Contracts\LintRule` implementations under `Analysis\Rules\` — prefer declarative `lint.dependencies` / rule packs. Kept for reference until v2.0.

## [1.3.0] - 2026-07-22

### Added

- Backwards-compatibility aliases for the classes renamed in 1.2.0: `BaseRepository`, `BaseService`, `BaseData`, `BaseFormRequest`, `Action` and `QueryFilter` still resolve (deprecated, removal planned for 2.0), so existing apps keep working after `composer update`.
- `policy`, `seeder` and `test` patterns with matching generators and stubs.
- `architect:feature` command — `make:module` plus the `generation.feature_extras` patterns (policy, seeder, feature test by default).
- `architect:new` interactive wizard that asks for name, preset, UI, fields and extras, then generates the module.
- `architect.json` project-level config: a JSON file at the application root that deep-merges over the package config, so teams can version their conventions.
- `{module}` placeholder in namespace config for domain/modular layouts (e.g. `App\Domain\{module}\Services`).
- `generation.user_model` config used by generated policies.
- `architect:lint` command — CI-friendly convention checker (exit code 1 on violations) with four built-in rules: no Eloquent/DB calls in controllers, no repositories injected into controllers, no inline validation in controllers, and models must not depend on the HTTP/service layer. Rules implement `Contracts\LintRule` and are registered in `lint.rules`, so teams can add their own.
- `architect:analyze` command — read-only report of layer counts (controllers, models, services, repositories, actions, form requests) and hotspots: classes exceeding the configurable `lint.thresholds` for public methods, constructor dependencies and file length.

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

[Unreleased]: https://github.com/gubakareem/lara-architect/compare/v1.4.1...HEAD
[1.4.1]: https://github.com/gubakareem/lara-architect/releases/tag/v1.4.1
[1.4.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.4.0
[1.3.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.3.0
[1.2.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.2.0
[1.1.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.1.0
[1.0.0]: https://github.com/gubakareem/lara-architect/releases/tag/v1.0.0
