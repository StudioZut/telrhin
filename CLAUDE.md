# Telrhin

Drupal 7 codebase for [telrhin.com](https://telrhin.com), a fictional-world/campaign worldbuilding site (encyclopedia entries, calendar/holidays, campaign session logs).

## Databases

The site uses **two separate MySQL databases**, both accessed via custom helper functions in `modules/telrhin_functions/telrhin_functions.module`:

- **Drupal's default DB** — standard Drupal content, plus at least one custom non-Drupal-managed table: `CampaignSummary` (campaign session log data: `Promo`, `PlayDate`, `CampaignID`, `YearStart/End`, `SeasonStart/End`, `DayStart/End`). Access via Drupal's own `db_query()` with `{}` table prefixing — do NOT use `getdb()`/mysqli for this table, it lives in Drupal's DB, not the external one.
- **External "encyclopedia" DB** (legacy, shared with an old ColdFusion admin app at `studiozut.com`) — holds the `Telrhin` table (encyclopedia entries: `Name`, `Description_Public`, `Title`, `Gender`, `Work_New`, `isReligion`, etc.) and related `Enc_Regions`/`Enc_Races`/`Enc_Period` (+ `_Assoc`) tables. Access via `getzutdb()`, which returns a raw `mysqli` connection — use `mysqli_query()`/`mysqli_fetch_assoc()`/`mysqli_close()`, not Drupal's DB API.

Both connection functions live in `telrhin_functions.module` and pull credentials from `$databases` in `settings.php`. They fail gracefully (return `FALSE` + `watchdog()` log) if credentials are missing or the connection fails — always check the return value before using it.

## Modules

- `telrhin_functions` — shared DB connection helpers (`getdb()`, `getzutdb()`) and `myTruncate()` (multibyte-safe string truncation for DB text).
- `telrhin_encyclopedia` — `encyclopedia/entry/%` page callback, pulls from the external encyclopedia DB via `getzutdb()`.
- `telrhin_header_entry` — sidebar/header block showing a random encyclopedia entry (external DB), with admin-only edit links pointing to the legacy ColdFusion tool at `studiozut.com/admin/enc_edit.cfm`.
- `telrhin_calendar` — in-world calendar/holiday blocks. Computes a fictional calendar date and season from the real date, and displays holiday text (editable via `admin/config/content/telrhin_calendar`, stored as Drupal variables via `variable_get`/`system_settings_form`).

## PHP-in-content blocks (PHP filter module)

Some site content (e.g. campaign session summaries) is implemented as raw PHP in Drupal block bodies using the **PHP filter module** (`sites/.../modules/php/php.module`, evaluated via `php_eval()`), rather than as custom module code. Keep this in mind when debugging: errors will reference `php.module(80) : eval()'d code` rather than a real file/line in this repo.

**Known constraints for this environment:**
- The server's PHP version does **not** support PHP 7+ syntax: no `[]` short array literals (use `array(...)`), no `??` null-coalescing operator (use `isset($x) ? $x : $default`).
- `check_markup($text, 'filtered_html')` can silently return an empty string if `'filtered_html'` doesn't match an actual configured text format machine name on this site — this happened with the `Promo` field on `CampaignSummary` and was fixed by reverting to a raw `echo` (matching original, pre-refactor behavior). If you need filtered markup, verify the real format machine name in the site's `admin/config/content/formats` first.
- These blocks are not tracked in this repo as source files — they live only in the Drupal DB (block config). When editing one, get the current body from the site admin UI first rather than assuming it matches anything here.

## Working with code snippets in this repo

**Do not copy multi-line code out of a terminal-rendered chat/output to paste elsewhere (e.g. into the Drupal admin UI).** This has caused silent, reproducible corruption — chunks of text vanishing mid-line, especially after repeated substrings — even though the destination (Drupal, browser, other apps) was not at fault. The fix: write the snippet to a file and copy it from a text editor (e.g. Sublime Text) instead of from the terminal.
