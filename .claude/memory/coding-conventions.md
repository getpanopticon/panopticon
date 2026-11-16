# Coding conventions

- **PHP**: Tabs for indentation, Allman brace style (opening braces on new line), `else`/`catch`/`finally` on new line
- **PHP version**: 8.3 minimum (`composer.json` requires `>=8.3`)
- **SCSS**: 2-space indentation
- **JS**: 4-space indentation, double quotes, semicolons, Allman brace style
- **JSON**: Tab indentation
- **Line length**: 120 characters max
- **Namespace**: `Akeeba\Panopticon\{Component}` with PSR-4 autoloading from `src/` and `user_code/`
- **Security guard**: All PHP files start with `defined('AKEEBA') || die;`
- **License header**: Every PHP file has a `@package panopticon` / `@copyright` / `@license` docblock
