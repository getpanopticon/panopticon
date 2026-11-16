# Build and distribution gotchas

## assets/ is not distributed

The `assets/` directory is excluded from release archives by two mechanisms:

1. `.gitattributes`: `/assets export-ignore` (affects `git archive`)
2. `build.xml`: `<exclude name="assets/**" />` (affects Phing release builds)

**Why:** `assets/` is intended for developer tooling, documentation, local-dev helpers, Docker entry points, and design files — none of which belong in a production release package.

**How to apply:** Never place a file that the running application needs at runtime inside `assets/` or any subdirectory of it. Runtime-required files that ship with the app belong under `src/`, `includes/`, `languages/`, `media/`, `templates/`, `cli/`, or the project root. This was discovered when `assets/private/routes.php` (the API router) was mistakenly placed there and excluded from all releases, breaking the JSON API for every install-from-archive user (issue #994). The file was moved to `src/routes.php` and `Application.php` updated accordingly.

## SCSS partial recompile

`composer run-script scss` (the `InstallationScript::sass` task) decides whether to recompile by comparing each **top-level** `media/scss/*.scss` file's mtime against its `media/css/*.min.css` output. It does NOT look at the mtime of imported partials (`media/scss/section/**/_*.scss`).

**Why:** so editing only a partial (e.g. `section/pnp/_stacked-tables.scss`) leaves the top-level `theme.scss`/`minty.scss`/etc. older than the output, and the build silently skips it — the old CSS stays in place and you debug a "change that didn't apply".

**How to apply:** after editing any `_*.scss` partial, run `touch media/scss/*.scss && composer run-script scss` to force recompilation of all five themes. Verify with `grep <selector> media/css/theme.min.css`.
