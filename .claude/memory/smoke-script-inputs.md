# Smoke and test shell scripts

When writing a shell script that takes user-supplied env-var inputs (HOST, TOKEN, ENDPOINT, etc.) and builds URLs / API calls from them, validate the inputs at the top of the script and abort with a clear, specific error on obvious misuse.

**Why:** A smoke script that "works" on garbage input wastes hours. The user spent 45 minutes debugging an API auth issue that was caused by `HOST=https://example.test/api` instead of `HOST=https://example.test` — the script silently produced `${HOST}/api/v1/sites` = `…/api/api/v1/sites`. The downstream symptom (login HTML page) looked like a routing bug; the input validation step would have caught the real cause in one second.

**How to apply:**

- Strip trailing slashes, then check the *shape* of the input against patterns that would obviously be wrong.
- Bash example for an HTTP base URL:

  ```bash
  HOST="${HOST%/}"
  case "$HOST" in
      */api|*/api/v1|*/api/v1/*)
          printf >&2 'ERROR: HOST already includes an /api path: %s\n' "$HOST"
          printf >&2 'Set HOST to the bare site root; the script appends /api/v1/... itself.\n'
          exit 2
          ;;
  esac
  ```

- Same idea applies to: tokens that look like base64 vs literal `"<your-token>"` placeholders, file paths that don't exist, hosts without a scheme, etc.
- The validation should reference what the script will *do* with the input ("the script appends /api/v1/sites itself"), not just say "bad input."

Applies to any script that drives a live system from CLI inputs — Akeeba release scripts, deploy helpers, API smokes, etc.
