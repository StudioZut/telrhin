# Telrhin

Drupal 7 codebase for [telrhin.com](https://telrhin.com), a fictional-world/campaign worldbuilding site (encyclopedia entries, calendar/holidays, campaign session logs).

## Databases

The site uses **two separate MySQL databases**, both accessed via custom helper functions in `modules/telrhin_functions/telrhin_functions.module`:

- **Drupal's default DB** — standard Drupal content, plus at least one custom non-Drupal-managed table: `CampaignSummary` (campaign session log data: `Promo`, `PlayDate`, `CampaignID`, `YearStart/End`, `SeasonStart/End`, `DayStart/End`). Access via Drupal's own `db_query()` with `{}` table prefixing — do NOT use `getdb()`/mysqli for this table, it lives in Drupal's DB, not the external one.
- **External "encyclopedia" DB** (legacy, shared with an old ColdFusion admin app at `studiozut.com`) — holds the `Telrhin` table (encyclopedia entries: `Name`, `Description_Public`, `Title`, `Gender`, `Work_New`, `isReligion`, etc.) and related `Enc_Regions`/`Enc_Races`/`Enc_Period` (+ `_Assoc`) tables. Access via `getzutdb()`, which returns a raw `mysqli` connection — use `mysqli_query()`/`mysqli_fetch_assoc()`/`mysqli_close()`, not Drupal's DB API.

Both connection functions live in `telrhin_functions.module` and pull credentials from `$databases` in `settings.php`. They fail gracefully (return `FALSE` + `watchdog()` log) if credentials are missing or the connection fails — always check the return value before using it.

## Modules

- `telrhin_functions` — shared DB connection helpers (`getdb()`, `getzutdb()`) and `myTruncate()` (multibyte-safe string truncation for DB text).
- `telrhin_encyclopedia` — **UNFINISHED, not ready to deploy or enable** (disabled on the live site). `encyclopedia/entry/%` page callback, pulls from the external encyclopedia DB via `getzutdb()`. This path does not exist on the live site — don't link to it. The working encyclopedia entry page is `/encyc/view?encycid={id}`, a Drupal page (URL alias for node 113), not part of any custom module.
- `telrhin_region_entries` — region entry list blocks (external DB); entries link to `/encyc/view?encycid={id}`.
- `telrhin_map` — **work in progress, not functional** (disabled on the live site). Intended block for the interactive Lowlands map. As committed, `telrhin_map_spitout()` echoes an undefined `$stuff` and returns nothing, and `telrhin_map_getentries()` fetches only one row.
- `telrhin_header_entry` — header block (`highlighted` region) showing a random encyclopedia entry (external DB), with admin-only view/edit links; the edit link points to the legacy ColdFusion tool at `studiozut.com/admin/enc_edit.cfm`. Converts Windows-1252 values to UTF-8 and strips HTML from the description before truncating (see encoding note below).
- `telrhin_calendar` — in-world calendar/holiday blocks. Computes a fictional calendar date and season from the real date, and displays holiday text (editable via `admin/config/content/telrhin_calendar`, stored as Drupal variables via `variable_get`/`system_settings_form`).

## Standalone pages

- `maps/lowlands.php` — interactive Lowlands map at `https://telrhin.com/maps/lowlands.php`, deployed to `web/maps/` on the live site (outside Drupal's page system; `web/maps` is in Composer's `preserve-paths`). Bootstraps Drupal only to use `getzutdb()` and the output filters; never hard-code DB credentials here. Markers for encyclopedia entries of type 6 (settlement) and 8 (geographic) that have `MapGrid`/`MapGrid_Sub`; the map image is `downloads/lowlands_map.jpg`.

## Legacy static assets

Served from the live docroot at the same paths (`web/downloads/`, `web/old_php_files/assets/`); both are in Composer's `preserve-paths`. Content (node bodies, blocks) links to them by URL, so don't rename or move files without updating the links.
- `downloads/` — Adventure Log PDFs and covers (`downloads/alm/`), the Stabmaster rules PDF, and the Lowlands map image.
- `old_php_files/assets/` — 13 images from the 2009 hand-coded site still used by the Campaigns page (node 304) and block 50. The folder name is kept only so those URLs keep working.
- The 2009 site's PHP scripts were removed from the live site on 2026-11-13 (hard-coded DB credentials, SQL injection, mail-header injection); they are intentionally **not** in this repo, which is public. Never commit files containing credentials.
- Don't use spaces in asset filenames: an Apache rewrite rule on the live vhost turns spaces in any URL into `+` and redirects, so such files can't be loaded.
- Known broken links in content (files never on this server): `downloads/5E_CharacterSheet_Fillable.pdf` (block 48), `maps/lowlands_map_2.jpg` (block 4).

## Theme

`themes/telrhin` is the site's default theme, a sub-theme of the contrib **Drupal Bootstrap** base theme (`7.x-3.27`). On the live site Bootstrap is managed by Composer (`"drupal/bootstrap": "3.27.0"`, pinned) and installed at `web/sites/all/themes/contrib/bootstrap`; it is not in this repo. Built from Bootstrap's CDN starterkit: Bootstrap 3.4.1 CSS/JS load from jsDelivr, so there's no build step. The admin theme is Seven. On the live site the theme lives at `web/sites/all/themes/telrhin`.

Customizations:
- `css/style.css` — site styles, including shared classes used in body fields: `.boxout` (bordered call-out box) and `.btn` spacing (`margin-right/bottom: .5em`, reset inside `.input-group-btn` so the search form button stays attached).
- `template.php` — `telrhin_preprocess_field()` renders blog tags (`field_tags`) as `btn btn-primary` buttons; `telrhin_preprocess_node()` replaces the submitted line on blog posts (`siteblog` and older `blog` types) with "Posted November 11, 2026" (no author name/picture). Adding a new preprocess function requires `drush cc all` so the theme registry picks it up.
- `html.tpl.php`, `templates/system/page.tpl.php`, and three Views templates for the history page.

Deploying theme or module changes: back up the live file(s) to `~/maintenance-backups/<date>-<topic>/`, copy the files from the repo, then `drush cc all` (CSS aggregation is on, so a cache clear is needed for CSS too).

## Contrib modules and Composer (live site only)

The site's `composer.json`/`composer.lock` live only on the server (in `/var/www/html/telrhin`), not in any repo. Contrib modules are Composer-managed in `web/sites/all/modules/contrib/`. Drupal 7 is end-of-life: core 7.103 is the final release, and `drush ups` reports every project as "Installed version not supported".
- **Check disk vs lock before any Composer operation.** By Sept 2026 the lock had drifted from the files on disk (lock newer than disk for several modules; Metatag 2.x on disk but 1.x in the lock) — fixed Nov 2026. A likely cause is `preserve-paths` including all of `web/sites`, which can restore old module files during a combined core + module update. Compare each module's `.info` `version` with `composer show drupal/<name>`, and update one package at a time.
- **The CKEditor 4 JS library is hand-installed inside the module** at `web/sites/all/modules/contrib/ckeditor/ckeditor/ckeditor/` (CKEditor profile path `%m/ckeditor/ckeditor`). Any Composer update/reinstall of `drupal/ckeditor` deletes it — back it up and restore it afterwards.
- `composer audit` ignore list (`config.audit.ignore` in `composer.json`) holds advisories that don't apply: Windows-only symfony/process issues, and symfony/yaml issues reachable only through the Drush 8 CLI (Drush 8 caps symfony/yaml at 4.4, and the fixes are in 5.4+). Each entry records its reason; add new ones the same way only after checking they really don't apply.

## PHP-in-content blocks (PHP filter module)

Some site content (e.g. campaign session summaries) is implemented as raw PHP in Drupal block bodies using the **PHP filter module** (`sites/.../modules/php/php.module`, evaluated via `php_eval()`), rather than as custom module code. Keep this in mind when debugging: errors will reference `php.module(80) : eval()'d code` rather than a real file/line in this repo.

**Known constraints for this environment:**
- The site runs on **PHP 7.4.3** (Ubuntu `7.4.3-4ubuntu2.29+esm4`, Apache mod_php) on the EC2 host `ec2-54-202-56-186.us-west-2.compute.amazonaws.com` — confirmed via `php -v` and Drupal's `admin/reports/status` (Oct 2026). `[]` short arrays and `??` are supported; PHP 8-only syntax (`match`, nullsafe `?->`, named arguments, union types, etc.) is not. Older code here uses `array(...)` — match the surrounding style when editing.
- `check_markup($text, 'filtered_html')` can silently return an empty string if `'filtered_html'` doesn't match an actual configured text format machine name on this site — this happened with the `Promo` field on `CampaignSummary` and was fixed by reverting to a raw `echo` (matching original, pre-refactor behavior). If you need filtered markup, verify the real format machine name in the site's `admin/config/content/formats` first.
- These blocks are not tracked in this repo as source files — they live only in the Drupal DB (block config). When editing one, get the current body from the site admin UI first rather than assuming it matches anything here.
- **Encoding trap with the external encyclopedia DB:** the `getzutdb()` connection is latin1 and some values hold Windows-1252 bytes (curly quotes, non-breaking spaces). Drupal's `check_plain()`, `filter_xss()`, `filter_xss_admin()` and `check_markup()` return an empty string for invalid UTF-8, so those entries silently render blank. Convert first: `drupal_validate_utf8($v) ? $v : mb_convert_encoding($v, 'UTF-8', 'Windows-1252')`.
- The encyclopedia pages are PHP-filter content: node 5 (`/encyc`), node 21 (`/encyc/full-record`, GM-only content type), node 113 (`/encyc/view`, player view; GM fields and Alex' Notes shown only to the `AdminGroup` role), and block 18 (stats sidebar). Refactored Nov 2026 to use prepared statements, filtered output, and the UTF-8 conversion above. In eval()'d code, use closures rather than named functions to avoid "cannot redeclare" errors.

## Working with code snippets in this repo

**Do not copy multi-line code out of a terminal-rendered chat/output to paste elsewhere (e.g. into the Drupal admin UI).** This has caused silent, reproducible corruption — chunks of text vanishing mid-line, especially after repeated substrings — even though the destination (Drupal, browser, other apps) was not at fault. The fix: write the snippet to a file and copy it from a text editor (e.g. Sublime Text) instead of from the terminal.
