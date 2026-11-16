# AWF Blade syntax

AWF Blade output syntax is old-style:

- `{{ $variable }}` — **raw, unescaped** output (the opposite of modern Laravel).
- `{{{ $variable }}}` — **escaped** output (use this for any user-supplied data).
- `@lang('STRING_KEY')` — translation in templates. Never `{!! $this->getLanguage()->text('...') !!}`.
- `@sprintf('STRING_KEY', $arg)` — translated + sprintf.
- `@token()` — emit CSRF token.
- `@route('index.php?view=...')` — route URL.

Modern Laravel directives are NOT supported:

- `{!! ... !!}` (raw echo) — does not work; produces literal output.
- `@verbatim`, `@props`, components — assume unsupported unless verified.

**Why:** AWF predates and intentionally does not track modern Laravel Blade. Reaching for Laravel idioms produces literal `{!! ... !!}` strings in the rendered HTML.

**How to apply:** When writing or reviewing any `.blade.php` file under `ViewTemplates/`, use the AWF syntax above. If you need raw output of HTML that's already safe, use `{{ $alreadySafeHtml }}` — not `{!! !!}`. If you need to call a method that returns translated text in a non-text context, prefer `@lang('KEY')` or `$this->getLanguage()->text('KEY')` inside a `{{ ... }}` expression.
