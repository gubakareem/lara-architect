# LaraArchitect Documentation

Welcome to the LaraArchitect documentation. The [README](../README.md) is the primary reference — this page is a map of where to find things.

## Quick links

- [Getting started: from install to a working CRUD API](getting-started.md)
- [Requirements & supported versions](../README.md#requirements)
- [Installation](../README.md#installation)
- [The module generator](../README.md#the-module-generator) — architecture presets, field definitions, enum fields, query filters
- [Extending the generator](../README.md#extending-the-generator) — register your own pattern generators
- [Runtime building blocks](../README.md#runtime-building-blocks) — repositories, services, actions, filters, DTOs
- [Configuration reference](../README.md#configuration-reference)
- [Changelog](../CHANGELOG.md)
- [Contributing](../CONTRIBUTING.md)

## What's new

### Version 1.0.0

- Configurable architecture presets (`service-repository`, `actions`, `lean`, or your own)
- One-command module generation: model, migration, factory, enums, service/actions, DTO, query filter, requests, resource, controller
- Soft-delete aware repositories and services (`restore`, `restoreAll`, `deleteAll`, `forceDelete`, `trashed`)
- Request-driven query filters with a `Filterable` model trait
- String-backed enum generation wired into casts, validation, factories and DTOs
- Support for Laravel 11, 12 and 13
- Full test suite, PHPStan (Larastan) and Pint

See the [CHANGELOG](../CHANGELOG.md) for full details.

## License

LaraArchitect is open-sourced software licensed under the [MIT license](../LICENSE.md).
