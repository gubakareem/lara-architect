# Contributing to LaraArchitect

Thank you for considering contributing to LaraArchitect! Contributions of all kinds are welcome: bug reports, feature ideas, documentation improvements and pull requests.

## Reporting issues

Before opening an issue, please:

1. Search existing issues to avoid duplicates.
2. Include the package version, Laravel version and PHP version.
3. Provide a minimal reproduction — the `make:module` command you ran, the relevant config, and the actual vs. expected output.

For security vulnerabilities, please **do not** open a public issue. Email the maintainer instead.

## Development setup

```bash
git clone https://github.com/karim-ashraf/lara-architect.git
cd lara-architect
composer install
```

The test suite boots a full Laravel application through [Orchestra Testbench](https://github.com/orchestral/testbench), so no separate Laravel app is required.

## Before submitting a pull request

Run the full verification suite locally — CI runs the same three checks against every supported PHP/Laravel combination:

```bash
composer test      # PHPUnit (unit + feature tests)
composer analyse   # PHPStan level 5 (Larastan)
composer format    # Laravel Pint (code style)
```

All three must pass. A few guidelines:

- **Add tests.** New features need feature or unit tests; bug fixes need a regression test that fails without the fix.
- **Keep PRs focused.** One feature or fix per pull request makes review faster.
- **Follow the existing style.** Pint enforces formatting; PHPStan enforces type safety. New public methods should carry proper generics/docblocks where Eloquent types are involved.
- **Update documentation.** If you change behavior or add a feature, update `README.md` and add an entry under `[Unreleased]` in `CHANGELOG.md`.
- **Stubs and generators.** When adding a new pattern generator, register it in `config/lara-architect.php`, add its stub under `stubs/`, and cover it in `tests/Feature/MakeModuleCommandTest.php`.

## Commit messages

Use clear, imperative commit messages (e.g. `Add restoreAll support to BaseRepository`). Reference related issues where applicable (`Fixes #12`).

## Branching

- Create feature branches from `main`.
- Target pull requests at `main`.

## Code of conduct

Be respectful and constructive. We want this to be a welcoming project for everyone.
