# AGENTS.md

This file provides guidance to AI coding agents when working with code in this repository.

## Project Overview

Akeeba Panopticon is a self-hosted PHP application for remotely monitoring and managing Joomla and WordPress sites. It tracks updates, extensions, backups, SSL certificates, domain expiration, uptime, and security issues across multiple CMS installations.

Built on **Akeeba Web Framework (AWF)** — a custom MVC framework, not Joomla, Laravel or Symfony. Layout: `src/` (Controller, Model, View, Task, Library, Plugin), `ViewTemplates/` (Blade), `media/`, `languages/`, `cli/`, `tests/`, `user_code/`, `assets/` (dev tooling, not distributed).

## Build & Development Commands

```bash
# Full setup. Beyond PHP deps this also runs npm install, compiles SCSS, transpiles JS,
# generates version.php, and UPDATES THE DATABASE SCHEMA.
composer install

# Compile SCSS only
composer run-script scss

# CLI entry point (Symfony Console) — lists all commands
php cli/panopticon.php list
```

Tests: `composer test`, `composer test:unit`, `composer test:integration`; new-installation release gate: `composer test:install`. Writing or debugging tests: see `tests/AGENTS.md`.

## Always-on rules

- **Outbound HTTP:** always get a Guzzle client from `$container->httpFactory->makeClient()`; never `new Client()` (SSRF guard). Details in `outbound-http.md`.
- **Translations:** `languages/en-GB.ini` is the only hand-edited INI; every other language is a `.po` compiled by `phing language`. Never hand-edit a non-English `.ini`.
- **Blade:** AWF Blade is old-style (`{{ raw }}`, `{{{ escaped }}}`); never emit `{!! !!}`.
- **Schema migrations:** column checks use `<condition type="missing" value="col"/>` (attribute form), never element text.
- **`assets/`** is excluded from release archives; never put runtime-required files there.
- **Security:** state-changing tasks call `csrfProtection()`; controllers do `aclCheck()`. Self-review per `security-self-review.md`.
- **Release:** Panopticon deviates from the generic Akeeba release-workflow recipe. See the `panopticon-release` skill before running any Phing release target.
- PHP 8.3 minimum; tabs and Allman braces; every PHP file starts with `defined('AKEEBA') || die;`.

## Project memory

Project memory lives in `.claude/memory/`. Read the matching file before starting that kind of work.

| File | Read it when… |
|------|---------------|
| [`architecture.md`](.claude/memory/architecture.md) | Before changing MVC structure, DI, the task system, plugins, frontend build, database or configuration |
| [`outbound-http.md`](.claude/memory/outbound-http.md) | Before writing any code that makes an HTTP request or opens an outbound connection |
| [`coding-conventions.md`](.claude/memory/coding-conventions.md) | Before writing or reformatting PHP, SCSS, JS or JSON |
| [`testing.md`](.claude/memory/testing.md) | Before writing or running tests, or when PHPUnit is missing from `vendor/` |
| [`release-and-docker.md`](.claude/memory/release-and-docker.md) | Before a release, or before building, testing or publishing the Docker image |
| [`related-projects.md`](.claude/memory/related-projects.md) | Before touching the connector extensions or wiki working copies |
| [`build-and-distribution.md`](.claude/memory/build-and-distribution.md) | Before adding files under `assets/` or editing SCSS partials |
| [`list-view-conventions.md`](.claude/memory/list-view-conventions.md) | Before adding or changing a browse/list/edit view, its model or controller |
| [`awf-blade-syntax.md`](.claude/memory/awf-blade-syntax.md) | Before writing or reviewing any `.blade.php` template |
| [`task-callbacks.md`](.claude/memory/task-callbacks.md) | Before writing or changing a callback in `src/Task/` |
| [`task-params-authority.md`](.claude/memory/task-params-authority.md) | Before adding a task type that targets sites or users from its params, or changing the task API's `params` handling |
| [`schema-migrations.md`](.claude/memory/schema-migrations.md) | Before editing `src/schema/mysql.xml` or writing any migration |
| [`language-files.md`](.claude/memory/language-files.md) | Before adding, changing or translating any language string, or touching `LanguageTools` |
| [`smoke-script-inputs.md`](.claude/memory/smoke-script-inputs.md) | Before writing a shell smoke/test/deploy script that takes env-var inputs |
| [`security-self-review.md`](.claude/memory/security-self-review.md) | After building or changing a feature, controller or task, before you finish |
