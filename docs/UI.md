# UI guide

How the panel's interface is put together, and the rules for changing it.
Everything is server-rendered PHP + Tailwind + one small JavaScript file;
there is no build step beyond the Tailwind CLI and no front-end framework.

- [Look and feel](#look-and-feel)
- [Working on views locally (preview)](#working-on-views-locally-preview)
- [Design tokens](#design-tokens)
- [Component classes](#component-classes)
- [PHP helpers (`Ui::`)](#php-helpers-ui)
- [Page structure](#page-structure)
- [Behaviour (`app.js`)](#behaviour-appjs)
- [Rules](#rules)

## Look and feel

Calm and dense: neutral surfaces, one accent colour (indigo), status colours
only for status. Light and dark themes from the same tokens; the viewer
picks light, dark or "system" with the button in the top bar. Motion only
on state changes (toasts, dialogs, progress), never ambient, and none for
people who ask their OS for reduced motion. Monospace for technical values
(paths, hostnames, records, keys).

## Working on views locally (preview)

The preview renders every view with sample data, with plain PHP 8.2+ and
no server stack:

```bash
dev/preview/serve.sh                       # builds the CSS (and rebuilds on change), serves :8787
open http://127.0.0.1:8787/__preview       # index of every page and state
```

Pages open at their real URLs (`/cpanel/domains`, `/whm/accounts`, ...).
`?as=admin|reseller|user` switches role, `?variant=<name>` shows another
state (empty lists, errors). Forms don't change anything: a POST shows what
would have been sent.

The data comes from `dev/preview/fixtures/<view>.php` - one per view (CI
fails on a view without one). A fixture returns:

```php
return [
    'layout' => 'cpanel',            // whm | cpanel | auth | blank | null
    'role' => 'user',                // admin | reseller | user | guest
    'path' => '/cpanel/domains',     // the page's real URL
    'data' => [...],                 // exactly what the controller passes to View::render
    'variants' => ['empty' => ['domains' => []]],
    'flash' => [['success', 'Saved.']],
];
```

Before pushing:

```bash
php dev/preview/render-all.php     # every view and variant: no PHP errors, labelled controls, named buttons
dev/preview/shot.sh /cpanel/domains /tmp/d.png dark   # screenshot (headless Chrome)
```

Server-only classes (`Config`, `Auth`, `Database`, `Quota`) are stubs in
`dev/preview/bootstrap.php`. A view that queries the database fails the
preview on purpose: views get their data from the controller.

## Design tokens

Defined once in `public/assets/css/input.css` (light and dark), exposed as
Tailwind colours in `tailwind.config.js`. Use these, never raw palette
colours like `bg-slate-100` or `text-red-700`:

| Token | Use |
|---|---|
| `bg-canvas` | page background |
| `bg-surface`, `bg-surface-2`, `bg-surface-3` | cards; table headers and footers; subtle fills, hovers |
| `border-line`, `border-line-strong` | dividers and card borders; input borders |
| `text-fg`, `text-muted`, `text-subtle` | main text; secondary text; hints and placeholders (all pass WCAG AA) |
| `bg-accent`, `text-accent-text`, `bg-accent-soft` | primary actions; links and active items; selected backgrounds |
| `success`, `warning`, `danger`, `info` (+ `-soft`, `-text`) | status only: `bg-danger-soft text-danger-text` |

## Component classes

Plain CSS classes (in `@layer components`), used directly in views:

| Class | |
|---|---|
| `card`, `card-header`, `card-title`, `card-desc`, `card-body`, `card-footer` | the basic container |
| `btn` + `btn-primary` / `btn-secondary` / `btn-ghost` / `btn-danger` / `btn-danger-ghost`, `btn-sm`, `btn-icon`, `btn-block` | buttons and button-links; an `icon()` inside is sized for you |
| `input`, `select`, `textarea`, `input-sm`, `label`, `hint`, `field-error`, `input-group` + `input-addon` (`input-group-suffix` for one after), `check` | form controls (prefer `Ui::field()`) |
| `badge` + `badge-neutral` / `-accent` / `-success` / `-warning` / `-danger` / `-info`, `dot` | status pills (prefer `Ui::badge()` / `Ui::status()`) |
| `alert` + `alert-info` / `-success` / `-warning` / `-danger` | inline messages (prefer `Ui::alert()`) |
| `table-wrap`, `table`, `table-tight` | tables: wrap in `table-wrap` inside a `card` |
| `dl` | label/value rows |
| `progress`, `progress-bar` (+ `-success` / `-warning` / `-danger`) | bars (prefer `Ui::progress()` / `Ui::meter()`) |
| `tabs`, `tab` | tab bar (prefer `Ui::tabs()`) |
| `link`, `link-muted`, `mono`, `kbd`, `chip`, `log` | text bits; `log` is a scrolling monospace box |
| `empty`, `stat`, `grid-cards`, `stack`, `section-title` | empty states, dashboard numbers, card grids, vertical rhythm |
| `disclosure` | a styled `<details>` |
| `menu`, `menu-panel`, `menu-item` | a dropdown (`<details class="menu" data-menu>`) |
| `dialog`, `dialog-body`, `dialog-title`, `dialog-text`, `dialog-footer` | a `<dialog>` opened with `data-dialog-open` |
| `js-hide` | hidden only when JavaScript runs (a page script shows it) - for no-JS fallbacks |

## PHP helpers (`Ui::`)

`app/src/Support/Ui.php`. Each returns escaped HTML.

| Helper | |
|---|---|
| `Ui::field($name, $label, [...])` | labelled input with `hint`, `error`, `required`, `optional`, `mono`, `addon` / `suffix`, `generate` (password generator), `attrs`. Refills from the last failed submit. |
| `Ui::select($name, $label, $options, $selected, [...])`, `Ui::textarea(...)`, `Ui::checkbox(...)` | the same for other controls |
| `Ui::pageHeader()`, `Ui::cardHeader($title, $desc, $actionsHtml, id: '...')` | headings; `id` for a section's `aria-labelledby` |
| `Ui::badge($text, $tone, $dot)`, `Ui::status($word)` | pills; `status` picks the tone from words like active, failed, pending |
| `Ui::progress($pct, $label, $tone)`, `Ui::meter($label, $value, $pct, $tone, $note)`, `Ui::usageTone($pct)` | bars |
| `Ui::empty($title, $text, $icon, $actionHtml)`, `Ui::emptyRow($cols, ...)` | empty states (in a card / in a table) |
| `Ui::stat($label, $value, $icon, $hint, $href)` | dashboard number |
| `Ui::iconButton($icon, $label)`, `Ui::iconLink($href, $icon, $label)` | icon-only controls with an accessible name |
| `Ui::actionForm($action, $icon, $label, $confirm, $hidden, $danger)`, `Ui::deleteForm($action, $confirm)` | one-button POST forms with CSRF and a confirm dialog |
| `Ui::copy($text)`, `Ui::copyable($text)` | copy-to-clipboard |
| `Ui::alert($text, $tone)` | inline message |
| `Ui::busy($message)` | "a job is running" banner; the page reloads itself when the job is done |
| `Ui::tabs($tabs, $currentHref)`, `Ui::plural($n, 'domain')` | |

`icon($name, $class)` (in `View.php`) draws the inline SVG icons; they are
hidden from screen readers, so the control around them needs a name. New
icons go in `View.php`, `assets/icons/` and `docs/ICONS.md`.

`t($text, $vars)` wraps interface text. It returns the English text today;
it is where translations will be looked up, so new text should use it.

## Page structure

The layout (`views/partials/shell.php`) draws the sidebar, top bar
(breadcrumbs, Ctrl-K search, theme, account menu), the page title from
`$title`, toasts for flash messages and the confirm dialog. A view only
renders the page body, and can hand the layout:

```php
View::slot('desc', e('One sentence under the title.'));      // raw HTML: escape it
View::slot('actions', '<a href="/x/create" class="btn btn-primary">' . icon('plus') . 'Create</a>');
View::breadcrumbs([['Domains', '/cpanel/domains'], [$domain]]);  // default: section > title
View::script('files');      // loads /assets/js/pages/files.js
View::slot('header', 'none');  // the view draws its own heading
```

Typical page: optional summary (`stat`s or a usage card), then cards in a
`stack` or a grid. Lists are tables in a card with an empty state; the
"create" form sits in a card beside the list (`xl:grid-cols-3`, list
`xl:col-span-2`) with `id` so header actions can link to it.

## Behaviour (`app.js`)

All behaviour hangs off data attributes, so views have no inline scripts:

| Attribute | |
|---|---|
| `form[data-confirm="..."]` (+ `data-confirm-action="Delete"`, `data-confirm-danger`, `data-confirm-title`) | ask in a dialog before submitting |
| `select[data-autosubmit]` | submit on change - only for filters/navigation, never for settings that change a site |
| `[data-copy="text"]` | copy to the clipboard |
| `button[data-generate-password="<input id>"]` | fill a strong password (`Ui::field(..., ['generate' => true])`) |
| `[data-busy="ms"]` | a background job is running: re-check the page, reload when done (`Ui::busy()`) |
| `input[data-filter="#table"]` | filter table rows while typing; a row with `data-filter-none` shows when nothing matches |
| `[data-countup]` | number counts up once |
| `button[data-dialog-open="<dialog id>"]`, `[data-dialog-close]`, `[data-dialog-focus]` | open/close a page's own `<dialog class="dialog">` and focus a field in it |

Page-specific code goes in `public/assets/js/pages/<name>.js`, loaded with
`View::script('<name>')`. Pass values to it through `data-` attributes or a
`<script type="application/json">` block, never inline JavaScript.
`window.JP` offers `confirm({title, text, action, danger})` and
`prompt({title, label, value})` (both return Promises) and `toast(msg, 'error'?)`.

## Rules

- Tokens and component classes, not raw colours. Class names written out in
  full (Tailwind can't see `"bg-{$x}-600"`).
- Every control has a label (`Ui::field` does it; otherwise `<label for>` or
  `aria-label`). Every icon-only button or link has `aria-label` (`Ui::iconButton`).
- No inline `<script>`, `onclick=`, `onchange=` or `javascript:` links: the
  Content-Security-Policy (`script-src 'self'`) blocks them, on the server
  and in the preview.
- Settings that change something live are saved with an explicit button,
  not on change. Destructive actions confirm (`data-confirm`, `Ui::deleteForm`).
- Every POST form keeps `Csrf::field()`; every value printed goes through `e()`.
- Lists get an empty state that says what to do next. Errors say what went
  wrong and how to fix it.
- Check at phone width (the preview's `/__frame?w=390&url=/your/page`).
