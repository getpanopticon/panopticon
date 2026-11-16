# Schema migration conditions

Every `<action>` block in `src/schema/mysql.xml` must include a `<condition>` element to make it idempotent (safe to run more than once). Without a condition the ALTER/INSERT will fail on a second run — even though `canfail="1"` swallows the error, it is noisy and masks real failures.

**Column existence check** — use `type="missing"` with the column name in the **`value` ATTRIBUTE**:

```xml
<action table="#__groups" canfail="1">
    <condition type="missing" value="api_token_limit"/>
    <query><![CDATA[
ALTER TABLE `#__groups`
    ADD COLUMN `api_token_limit` INT UNSIGNED NULL DEFAULT NULL;
]]></query>
</action>
```

**CRITICAL GOTCHA (verified 2026-06-19 in `vendor/akeeba/awf/src/Database/Installer.php::conditionMet()`):** the
`missing` condition reads the column name from the `value` **attribute** only (`$attributes->value`). The element-text
form `<condition type="missing">col</condition>` is a **silent no-op** — with no `value` attribute the column name is
empty, so the condition collapses to a *table*-missing check and the ALTER never runs on an existing install. ALWAYS use
the `value="..."` attribute form for column-add migrations, and VERIFY the column actually lands (e.g.
`SHOW COLUMNS FROM …` against the test DB after a suite run, or `getTableColumns('#__table', true)` after
`database:update`) — do not trust that the action ran.

**This bug has bitten for real (2026-07-14, gh-1023 branch):** `#__groups.api_token_limit` used the element-text form
and therefore *never existed in any database*, new or upgraded — the per-group API token limit feature was dead, and
`Model\Apitoken`'s "SELECT api_token_limit FROM #__groups" errored with "Unknown column". Same for the `#__sites.notes`
migration (harmless only because `notes` is also in that table's `CREATE TABLE`). Both were converted to the
`value="..."` form and the fix shipped in 2.2.1. If you are writing a migration, do NOT copy the element-text style from
surrounding code, and do not let a subagent's plan specify it.

**Row existence check** — use a SQL SELECT that returns 1 row with a `condition` column; the action only runs when the result evaluates to truthy (non-zero):

```xml
<action table="#__groups" canfail="1">
<condition type="equals" value="0">
SELECT IF(myCount >= 1, 1, 0) AS `condition` FROM (SELECT COUNT(*) AS `myCount` FROM `#__groups` WHERE `title` = 'Users') x;
</condition>
…
</action>
```

**Always apply this discipline** — even when also setting `canfail="1"`, add a condition so that a clean idempotent run produces zero errors in the output.

**Why:** Re-running `php cli/panopticon.php database:update` during upgrades must be safe and silent. Errors swallowed by `canfail` can hide real problems when the condition is not specific enough.

**How to apply:** Before writing any new `<action>` block (ALTER TABLE, INSERT, CREATE INDEX, etc.), choose the appropriate condition type and add it.
