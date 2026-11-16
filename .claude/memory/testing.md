# Testing

PHPUnit suite in `tests/` — `composer test`, `composer test:unit`, `composer test:integration`.

New-installation release gate (setup wizard in a browser, all languages, PHP/DB edges): `composer test:install`, see `tests/Installation/README.md`.

Writing or debugging tests: see `tests/AGENTS.md` for the test database setup and the transaction constraints.

## PHPUnit missing from vendor/

When PHPUnit is not found at `vendor/bin/phpunit` or `vendor/phpunit/phpunit/phpunit`, it means `composer install --no-dev` was run (the default build action), which strips dev dependencies including PHPUnit.

**Fix:** Run `composer install` (without `--no-dev`) to restore dev dependencies before running tests.

**Why:** The production build uses `composer install --no-dev`, so PHPUnit will often be absent in the working copy. Never assume PHPUnit is present — check first, and install if missing.

**How to apply:** Before running tests, check for PHPUnit with `ls vendor/bin/phpunit 2>/dev/null`. If absent, run `composer install` first.
