# List and edit view conventions

Panopticon list views (the "default.blade.php" of an Apitokens/Groups/Mailtemplates/Tasks/etc.-style view) follow a strict pattern. Diverging from it produces a page that "doesn't look like Panopticon" — a reliable user complaint.

**Why:** Consistency across dozens of CRUD pages. Built on AWF `DataController` + `CrudTasksTrait` (see `src/View/Trait/CrudTasksTrait.php`). The trait already wires the standard toolbar buttons (`add`, `edit`, `copy`, `delete`, `publish`, `unpublish`, `save`, `apply`, `cancel`, `back`, `batch`, `inlineHelp`).

**How to apply:** Build new list/edit views by copying the structure of `ViewTemplates/Mailtemplates/default.blade.php` (list) + `ViewTemplates/Groups/form.blade.php` (edit), plus the matching `src/View/.../Html.php` that `use`s `CrudTasksTrait`.

## List page (`ViewTemplates/Foos/default.blade.php`)

1. **No per-row action column.** Row actions are NEVER a column. Use the toolbar instead:
   - Toolbar buttons via `addButtons(['add', 'edit', 'copy', 'delete', 'publish', 'unpublish'])` in `onBeforeBrowse()`. The CrudTasksTrait already calls `addButtons(['add', 'edit', 'copy', 'delete'])`; opt into more as needed.
   - "Publish", "Unpublish", "Delete" act on the rows whose checkboxes are ticked (mass action).
2. **First column is always the checkbox.** Rendered via `$this->getContainer()->html->grid->id(++$i, $row->id)`. Increment `$i` starting from 1. Header has `<span class="visually-hidden">@lang('PANOPTICON_LBL_TABLE_HEAD_GRID_SELECT')</span>` and `class="pnp-w-1"`.
3. **Inline edit link.** Clicking the row's *primary* text (typically `description` or `title`) opens the edit page via `@route(sprintf('index.php?view=foo&task=edit&id=%d', $row->id))`. Wrap the value in a single `<a>` with no extra styling.
4. **Enabled column has clickable icon for single-row publish/unpublish.** Pattern:
   ```blade
   @if ($row->enabled)
       <a class="text-decoration-none text-success"
          href="@route(sprintf('index.php?view=foos&task=unpublish&id=%d&%s=1', $row->id, $token))"
          data-bs-toggle="tooltip" data-bs-title="@lang('PANOPTICON_LBL_PUBLISHED')">
           <span class="fa fa-circle-check" aria-hidden="true"></span>
           <span class="visually-hidden">@sprintf('PANOPTICON_LBL_PUBLISHED_SR', $row->id)</span>
       </a>
   @else
       ...mirror with task=publish and fa-circle-xmark...
   @endif
   ```
   The CSRF token comes from `$this->container->session->getCsrfToken()->getValue()` (assigned to `$token` at the top).
5. **Sortable headers** via `$this->getContainer()->html->grid->sort(...)`:
   ```blade
   {{ $this->getContainer()->html->grid->sort(
       'PANOPTICON_FOOS_TABLE_HEAD_NAME', 'name',
       $this->lists->order_Dir, $this->lists->order, 'browse'
   ) }}
   ```
6. **ID column on the right, narrow, monospace.** Class `pnp-w-5`, `font-monospace text-end`. Always sortable.
7. **Hidden form inputs at the bottom** (inside the `<form>`):
   - `boxchecked` (0), `task` (browse), `filter_order`, `filter_order_Dir`, `token`.
8. **Filter bar** (above the table, in a `border rounded-1 p-2 bg-body-tertiary` block):
   - Search input named after the searched field (e.g., `name`, `title`, `description`), wrapped in `pnp-mw-50` input-group with a magnifier-icon submit button.
   - Filter selects use `class="form-select akeebaGridViewAutoSubmitOnChange"` so they auto-submit on change.
   - Use `$this->container->html->select->genericList(...)` for option lists, with a leading empty option whose label is `PANOPTICON_FOOS_LBL_SELECT_*`.
   - Date filters: a single text input or a datetime-local; the model interprets them as "before"/"after" depending on field name (`created_after`, `expires_before`, `last_used_before`).
9. **No-results row** spans all columns with `class="text-center text-body-tertiary"` and shows `@lang('AWF_PAGINATION_LBL_NO_RESULTS')`.
10. **Pagination footer** is a single `<tfoot>` row spanning all columns rendering `{{ $this->pagination->getListFooter(['class' => 'form-select akeebaGridViewAutoSubmitOnChange']) }}`.
11. **Form wrapper**: `<form action="@route('index.php?view=foos')" method="post" name="adminForm" id="adminForm">`. The form's name MUST be `adminForm` for `akeeba.System.submitForm` to find it.

## Edit page (`ViewTemplates/Foo/form.blade.php` — singular)

- Use Bootstrap row/label/input layout (`<div class="row mb-3"><label class="col-sm-3 col-form-label">…</label><div class="col-sm-9">…</div></div>`).
- Hidden `id`, `token`, `task` (initial value `browse`).
- The toolbar provides Save / Apply / Cancel via `addButtons(['save', 'apply', 'cancel'])` in the View's `onBeforeAdd`/`onBeforeEdit` — already handled by CrudTasksTrait when the View `use`s the trait.
- Read-only metadata (created on/by, modified on/by) is shown on the edit page, not the list.

## Model conventions for a browse-able list

- Extend `Awf\Mvc\DataModel`; in the constructor add `$this->addBehaviour('filters')`.
- Override `buildQuery($overrideLimits = false)` to translate state filters into SQL:
  - `search` → `WHERE description LIKE '%…%'` (or whichever field).
  - `enabled` → `WHERE enabled = …` (cast to int; empty string = no filter).
  - `created_after`, `expires_before`, `last_used_before` → date range filters.
- Don't add custom AJAX endpoints for browse/publish/delete — `DataController`'s `browse`/`publish`/`unpublish`/`remove` tasks already handle these.

## Controller conventions

- Extend `Awf\Mvc\DataController`. Use the `ACLTrait` and call `$this->aclCheck($task);` in `execute()`.
- For pre-save data massaging override `onBeforeApplySave(array|object|null &$data)`.

## Where things live

- `src/Model/Foos.php` — plural model (the browse-able list) and also the per-row record (singular access by id is via `find()`).
- `src/Controller/Foos.php` — plural controller (list, mass actions).
- `src/Controller/Foo.php` — singular controller (single-row edit/save), often a thin subclass.
- `src/View/Foos/Html.php` — list view.
- `src/View/Foo/Html.php` — edit view.
- `ViewTemplates/Foos/default.blade.php` — list template.
- `ViewTemplates/Foo/form.blade.php` — add/edit template (one template handles both).

## Collapsible info cards (Bootstrap pattern)

Information sections that aren't required for the primary task should be collapsible cards. Long pages with lots of secondary text get ignored entirely; collapse what isn't immediately needed. Reference: `ViewTemplates/Sites/item_server.blade.php` (Site Information view — Server Information, Extensions, Backup, Security, Core File Integrity, Notes are all this pattern).

```blade
<div class="card mb-3">
    <h5 class="card-header h5 d-flex flex-row gap-1 align-items-center">
        <span class="fa fa-link" aria-hidden="true"></span>
        <span class="flex-grow-1">@lang('PANOPTICON_FOO_TITLE')</span>
        <button class="btn btn-success btn-sm ms-2" type="button"
                data-bs-toggle="collapse" data-bs-target="#cardFooBody"
                aria-expanded="false" aria-controls="cardFooBody"
                data-bs-tooltip="tooltip" data-bs-placement="bottom"
                data-bs-title="@lang('PANOPTICON_LBL_EXPAND_COLLAPSE')">
            <span class="fa fa-arrow-down-up-across-line" aria-hidden="true"></span>
            <span class="visually-hidden">@lang('PANOPTICON_LBL_EXPAND_COLLAPSE')</span>
        </button>
    </h5>
    <div class="card-body collapse" id="cardFooBody">
        …body content…
    </div>
</div>
```

- `collapse` alone = collapsed by default; `collapse show` = expanded by default; pick based on whether the user will need the content immediately. For reference/help text, default to collapsed.
- `aria-expanded` value must mirror the initial state (`false` when collapsed, `true` when `show`).
- The id on `card-body` must match `data-bs-target` (with `#`) and `aria-controls` (without `#`).

## Things to avoid

- Custom JS-driven inline create/edit/delete on the list page. Panopticon uses full-page add/edit instead, except for a few intentionally-AJAX flows that have their own UX rationale.
- A separate "Actions" column with per-row buttons. Single-row actions live on either the title link (edit) or the Enabled icon (publish/unpublish).
- Showing read-only metadata (Created On, Created By) as columns in the list. Reserve list columns for fields the user filters/sorts on or visually scans.
- Skipping pagination, even when the row count is small today.
