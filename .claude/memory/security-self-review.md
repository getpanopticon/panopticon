# Security self-review

When creating a new feature or improving an existing one in Panopticon, apply this repo's security conventions, then self-evaluate the code I touched for the two vulnerability classes audited in the `security/authz-csrf-audit` work.

**Why:** Panopticon is multi-user with per-site privileges; the common defects here are missing anti-CSRF checks and authentication-vs-authority bugs (IDOR / missing / inverted / wrong-object privilege checks). A prior audit found several (Apitokens IDOR, Coreupdates inverted check, Sites batch, Logs/Phpinfo/Usagestats/Reports missing authz, Selfupdate CSRF).

**How to apply:**
- **CSRF:** every state-changing controller task must call `$this->csrfProtection()`. AWF `DataController` self-protects standard CRUD tasks; raw `Controller` subclasses and any *custom* tasks on a `DataController` must call it themselves. GET links that trigger a protected task must carry the token (`&<token>=1` alt form, or `token=<value>`).
- **Authority:** a controller must `use ACLTrait` + override `execute()` to call `$this->aclCheck($task)` — there is no central ACL. `$aclChecks` task keys MUST be lower-case (`hasAccess()` lower-cases the task). The per-site ACL block only applies to the `sites`/`site` views; other views get a *global* privilege check, so per-site enforcement for them must be done in-controller via `$user->authorise('panopticon.<priv>', $site)`. Verify the object being authorised is the real target (watch `id` vs `site_id`). Valid privileges: `super, admin, run, view, addown, editown` — there is **no `read`**. A logged-in check alone is never sufficient authority.
- After finishing the work, run a **self-evaluation** over the touched files for these issues. Prefer launching a subagent (Opus model, medium/high thinking effort) to review.
- If any security issue is found, **notify me immediately** with its existence and severity, and offer to fix it. Do not bury it in a summary.

Related: `list-view-conventions.md`, `task-params-authority.md`.
