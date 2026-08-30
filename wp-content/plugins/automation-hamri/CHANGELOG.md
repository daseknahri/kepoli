# Changelog — Automation Hamri (build-v9, the full/active product)

Newest-first. build-v9 is the modular (`includes/*.php`), full-featured product that keeps its
front-end (SEO/ads/recipe). See `readme.txt` for the WordPress-directory changelog.

## 9.26.0

Hardening + SEO/recipe compatibility pass (multi-agent review). No DB schema change.

### Added
- **SEOPress + The SEO Framework meta.** `wpap_set_seo_meta()` now fill-if-empty seeds SEOPress
  (`_seopress_titles_desc`/`_seopress_titles_title`/`_seopress_analysis_target_kw`) and TSF
  (`_genesis_description`/`_genesis_title`) in addition to Yoast + Rank Math. Both are already counted
  by `wpap_seo_plugin_active()` (so the plugin's own `<head>` stays silent on those sites), so without
  these branches the writer's curated meta was silently dropped and the SEO plugin auto-generated a
  generic one. (AIOSEO 4.x stores SEO in its own table, not post meta — a known limitation shared with
  build-final.)
- **Recipe-plugin deference.** New `wpap_recipe_plugin_active()` (WP Recipe Maker / Tasty Recipes /
  WPZOOM / WP Ultimate Recipe); `wpap_recipe_should_render()` returns false when one is active, so
  build-v9 no longer paints a second recipe card or emits a second Recipe JSON-LD (duplicate
  structured data Google Search Console flags). Ported from build-final.
- **Opt-in uninstall purge.** `define( 'WPAP_UNINSTALL_PURGE', true )` in wp-config.php makes uninstall
  drop the Hub table and delete every option (API keys + license included); default-off preserves the
  multi-version-folder-safe behavior.

### Fixed
- **Recipe JSON-LD image.** `wpap_recipe_head()` resolved the required `image` only from the featured
  attachment and still emitted the Recipe block when there was none — invalid structured data, and it
  ignored the external `_wpap_image_url` this pipeline usually uses. It now resolves featured →
  `_wpap_image_url` → first in-content `<img>` (via `set_url_scheme`), and skips the Recipe JSON-LD
  entirely when none resolves (the visible card still renders).
- **Atomic run locks.** New `wpap_atomic_lock_acquire()` uses `INSERT IGNORE` (a true UNIQUE-key CAS).
  The prior `add_option()` acquire is not atomic — WP core runs `INSERT … ON DUPLICATE KEY UPDATE`, so
  two runs straddling a 1-second boundary (both writing `time()`) could both return true and
  double-acquire. Applied to the automation cron lock and both bulk-publish locks (JSON + ZIP).

### Performance
- **Hub export N+1.** `wpap_ajax_export_distribution_json` bulk-primes post meta once
  (`update_meta_cache`) instead of a per-row meta query — thousands fewer queries on the `?all=1`
  (up to 5000-row) export.

## 9.25.1

Fix the 9.25.0 Facebook image on the primary publish path. No DB schema change.

### Fixed
- **FB image is now exported on the `wpap_publish_article` path.** 9.25.0 stored `_wpap_fb_image_url`
  but the Distribution Hub row insert kept `image_url => $image_url` (the blog image), and the export
  reads the row's `image_url` directly — so Direct Publish / bulk / Bulk-ZIP / Sheet-automation posts
  still sent the **blog** image to Facebook and the feature silently no-op'd there. The row now stores
  the Facebook-preferred image (`'' !== $fb_image_url ? $fb_image_url : $image_url`), matching
  `wpap_autoadd_post_to_hub` / `wpap_restore_distribution_row_for_post` (which use `wpap_fb_image_url()`).
  Only affects rows created after the fix; the fallback is unchanged when no `fbImage` is supplied.

## 9.25.0

Separate Facebook image (blog image vs FB image). No DB schema change.

### Added
- **`fbImage` import field** (aliases `fbImageUrl`, `facebook_image`, `fb_image`). A post can carry a
  DIFFERENT image for Facebook than for the blog featured image. A local path inside a Bulk-ZIP
  bundle (e.g. `fb-images/x.jpg`) is sideloaded to a hosted attachment; a remote URL is stored
  as-is. Kept in `_wpap_fb_image_url`. New `wpap_fb_image_url()` helper resolves the Distribution
  Hub export image as **FB image → blog image → featured thumbnail**, so exports/extraction use the
  Facebook image while a single image still serves both when only one is provided. `wpap_publish_article`
  stores it (Direct Publish + Bulk-ZIP, which resolves the zip path to `local_fb_image_path`); the Hub
  "Bulk Import JSON" box prefers `fbImage` for its poster rows. Back-compatible — omit it and nothing
  changes.

## 9.24.0

Recipe `course` → schema.org `recipeCategory`. No DB schema change. (Pairs with viral-reader ≥ 1.9.8.)

### Added
- **`course` recipe field.** A recipe item may now carry a `course` (alias `recipeCategory`) —
  the meal/course type ("Main course", "Dessert", "Home remedy"), which is what schema.org's
  `recipeCategory` means (NOT the blog category). `wpap_publish_article()` stores it as
  `_wpap_recipe_course`, `wpap_recipe_render_data()` exposes it, and both the plugin's Recipe
  renderer and the theme emit `recipeCategory` when present (entity-decoded via `wpap_ld_text`).
  Optional and back-compatible — recipes without a course emit exactly as before. Documented in
  `BULK-IMPORT-CONTRACT.md`; the content-pipeline can fill it from a profile `defaultCourse` /
  `courseByCategory` (the remedies profile defaults ingestible recipes to "Home remedy").

## 9.23.1

Structured-data correctness fix. No DB schema change. (Pairs with viral-reader ≥ 1.9.7.)

### Fixed
- **Double-encoded HTML entities in JSON-LD.** WordPress returns term names, titles, author
  display names, and bios entity-encoded for HTML display (`get_the_title()` on "Honey &
  Pepper" → `"Honey &amp; Pepper"`; a bio's "doesn't" → `"doesn&#039;t"`). The schema builders
  passed that straight to `wp_json_encode`, double-encoding it in the output
  (`"Colds &amp; Respiratory"`, which Google reads as the literal "Colds &amp;
  Respiratory"). Added `wpap_ld_text()` and decode at the JSON-LD boundary for the
  breadcrumb, `articleSection`, WebPage/Article title + description, author `Person`
  (name + bio), and the plugin's own Recipe renderer (name, description, ingredients,
  steps). HTML `<meta>` paths are unaffected — they still run through `esc_attr()`, which
  re-encodes for the HTML context.

## 9.23.0

Optional SEO enhancements. Richer structured data + curated internal linking. No DB schema
change. (Pairs with viral-reader ≥ 1.9.5 for the curated-links front-end.)

### Added
- **Connected schema `@graph` + E-E-A-T author**: the per-post JSON-LD is now one connected
  graph — `WebPage → Article → Person(author) → Organization(publisher)` plus a primary
  `ImageObject` and `BreadcrumbList`, all cross-referenced by `@id`. The author is a real
  **Person** with `url` (author archive), `image` (avatar), `description` (bio) and `sameAs`
  (website) instead of a bare name. The `#organization`/`#website` @ids reuse the site
  convention so they merge with a site-level Organization/WebSite graph when present. The
  Article node is still omitted when a Recipe renders as the primary entity.
- **Curated internal links**: a per-item `related` list of slugs (import field, stored as
  `_wpap_related_manual`) is rendered first by the related-posts block — the companion
  theme's and the plugin's own — with auto-by-category filling any remaining slots. Lets an
  author hand-pick cross-links; absent, behaviour is unchanged.

### Notes
- Deliberately NOT added: **FAQ schema** — Google restricted FAQ rich results to
  authoritative government/health sites in 2023, so it renders no rich result for these
  sites (same reason HowTo was skipped).

## 9.22.1

Hardening pass on the 9.22.0 bulk-import path (now the main publishing tool), from a
security + correctness review (2 lens agents + verification). No schema change, no
front-end change.

### Fixed
- **ZIP publish concurrency + temp cleanup**: the Bulk-ZIP handler now holds the same
  atomic `add_option` CAS lock the JSON path uses, so a double-click / XHR retry can't
  republish the whole bundle; plus a `register_shutdown_function` cleanup of the extract
  dir so an *uncatchable* OOM fatal (thumbnailing a large image) can't leak an extracted
  bundle under uploads.
- **Duration parser**: compact forms like `"1h30m"` / `"2hrs30mins"` no longer drop the
  hours (the `\b` boundary failed before a digit) — now 90 / 150. Spaced/ISO/bare forms
  unchanged.
- **Recipe gating**: an explicit non-recipe `type` (`article`/`guide`/`story`) is now
  authoritative and never gets Recipe markup; `ingredients`/`steps` are accepted as a
  newline STRING as well as an array; `_wpap_recipe_on` is set only when BOTH lists
  survive cleaning, so a partial/dataless recipe publishes as a valid Article instead of
  a broken Recipe; an explicit `total`/`totalTime` is honored.
- **Category hierarchy**: the root-segment lookup is scoped to top-level (a bare
  `"Football"` can't bind to a nested `"Sports > Football"`), and a creation collision
  recovers via the parent-accurate term id WordPress returns in the error.

## 9.22.0

Per-type SEO for bulk import. Bulk-published items now earn the RIGHT schema automatically —
recipes become real recipes, guides/stories stay Articles — and categories can nest. No DB
schema change, no front-end change. Contract: `BULK-IMPORT-CONTRACT.md`.

### Added
- **Recipe schema on bulk import** (`wpap_publish_article`): an item with `type:"recipe"` (or that
  simply carries both `ingredients` and `steps`) now sets the `_wpap_recipe_*` meta, so the theme
  renders the recipe card and emits **schema.org/Recipe** (ingredients, instructions, prep/cook/
  total times, yield) with zero manual editor steps. Previously bulk recipes published as plain
  Articles. Guides/stories stay **Article** (Google retired HowTo rich results in 2023).
- **Duration parser** (`wpap_parse_duration_to_minutes`): accepts `"40 min"`, `"2 hr"`,
  `"1 hr 30 min"`, `"PT1H30M"`, or a bare integer → minutes, which the SEO emitter renders as
  ISO-8601 `PT..H..M`.
- **Hierarchical categories** (`wpap_resolve_category_path`): `category` now accepts a
  `"Parent > Child"` path (each level created lazily) as well as a bare name or numeric id, so the
  flat Recipes/Tips/Stories taxonomy can grow sub-categories with no plugin change.
- **Descriptive featured-image alt**: a per-item `image_alt` overrides the title-derived default
  on the featured attachment (Google Images / accessibility).

### Notes
- All fields are optional and back-compatible: existing `{title, content, imageUrl, category}`
  feeds import exactly as before. The per-item fatal isolation, batch caps, atomic publish lock,
  and content-hash dedup already in the bulk handler still apply.

## 9.21.0

Second parity pass — build-final's safe back-end feature groups (8.50.0). No DB schema change,
no front-end change. (Skipped by design: the FB 1200×630 OG-card head-swap, external-image
thumbnail rendering, and content page-nav — build-v9 keeps its own `wpap_seo_head` front-end and
pairs with the viral-reader theme, which already covers those.)

### Added
- **Import Blog Posts → Hub** (`wpap_ajax_import_all_posts`, 🗂 button): backfills a Hub row for
  every published post not already tracked (idempotent, batched). Plus **auto-add** on future
  publishes (`wpap_autoadd_post_to_hub` on `transition_post_status`), suppressed for the plugin's
  own publishes (they write their own row) via `$GLOBALS['wpap_suppress_hub_autoadd']`.
- **Per-row Posted toggle** (`wpap_ajax_toggle_fb_posted`, ☐/✅ Posted button) for 1:1 Facebook
  tracking, alongside the existing bulk "Mark posted".
- **Automation de-dup hardening**: a stable **content-hash key** `_wpap_source_alt_key`
  (`wpap_automation_content_key` / `wpap_automation_alt_key_is_done`) checked as an additional
  anchor so a Google-Sheet `id`-column change can't re-publish the archive; **durable give-up
  markers** (`wpap_automation_mark_giveup`, `wpap_automation_giveup_keys`) so a 3-strike row isn't
  re-attempted after the seen cache ages out; a throttled admin **email alert**
  (`wpap_automation_alert`) on Sheet-read failure or an all-errored run; and a **WP-Cron-disabled
  warning** on the plugin's pages (`wpap_cron_health_notice`).
- Optional **"Clean media"** (Settings → Content options, off by default): SEO filenames from the
  post title + EXIF/metadata scrub on imported images (`wpap_clean_media_enabled`,
  `wpap_strip_image_metadata`) — applied by both the remote and local sideloaders.

### Changed
- A re-slugged **published** post now refreshes its stored Hub/share link
  (`wpap_sync_distribution_permalink` on `post_updated`, `wpap_refresh_stored_link`), and a
  scheduled post going live re-derives its canonical link — no more 404 share links after a
  permalink edit.

## 9.20.0

Parity port of build-final's 8.43.0–8.49.0 Distribution-Hub work into the modular build.
No DB schema change (reuses `wpap_generated_posts` + post meta). No front-end change.

### Added
- **First-comment templates** for the Distribution Hub. A global default (**Settings →
  First-comment text**, `wpap_content_opts['fb_comment_template']`, with a `{{link}}` token)
  and a per-post override (**✏️ Comment** on each Hub row → `_wpap_fb_comment`, AJAX
  `wpap_save_fb_comment`; blank clears to the global). The export and Hub compose the template
  with each post's real link; with no template the comment stays the bare link (unchanged).
  Helpers `wpap_resolve_fb_template()` / `wpap_compose_fb_comment()` (PHP) and
  `wpapComposeComment()` (JS).
- **Smart export.** **📤 Export page** / **📦 Export all** (every matching row, ~5000 cap →
  `capped` warns), a **Not-posted-yet** filter, a **✓ Mark these as posted** action
  (AJAX `wpap_mark_posted_bulk` → `_wpap_fb_posted`, drops rows from the filter), plus the
  existing Per-page (10/25/50/100) and First/Prev/…/Next/Last + Go-to-page pager.
- **Bulk ZIP Publish** — a new admin page (**WP Automator Pro → Bulk ZIP Publish**,
  `wpap_render_bundle`) that publishes a whole batch from one uploaded `.zip` (a `posts.json`
  array of `{title,content,hook,image}` + the referenced image files), each post's image pulled
  **straight from the zip** — no image hosting/public URLs. New AJAX `wpap_bulk_publish_zip`
  (`manage_options`, `wpap_nonce`, fatal-shielded; requires `ZipArchive`) extracts to a
  **private** temp dir under `wp_upload_dir()` with path-traversal, absolute-path and zip-bomb
  guards (entry-count, ~64 MB upload, uncompressed-size caps), publishes each item via
  `wpap_publish_article`, and always deletes the temp dir. Helpers `wpap_bundle_rrmdir`,
  `wpap_bundle_find_posts_json`, `wpap_bundle_resolve_image`, plus the shutdown fatal shield
  `wpap_ajax_fatal_shield`.
- New publish option `wpap_publish_article( $item, [ 'local_image_path' => … ] )`: a readable
  local file is sideloaded as the featured image via new `wpap_import_local_image_as_attachment()`
  (real-content mime check against jpg/png/gif/webp/avif).

### Changed
- The JSON export and the Hub list now share one WHERE clause (`wpap_distribution_where_clause`)
  so a filtered export always matches the table (Status + the new Facebook filter), and share
  `wpap_distribution_per_page()`. Publish-first ordering stays filesort-free (the two-query helper).
- Bulk-import field mapping is now symmetric with the export (`caption` = the hook,
  `comment` = the first-comment template, with a bare-URL guard), so an exported file re-imports
  faithfully. Publishing stores a per-article `comment` template (`_wpap_fb_comment`) with
  bare-URL and `!== hook` guards.
- Extracted the featured-image wiring shared by the remote and local sideloaders into
  `wpap_apply_featured_attachment()` and the allowed-mime filters into
  `wpap_ensure_image_mime_filters()` — no behavior change to existing publish paths.
  (build-v9 carries no Facebook-card / clean-media / EXIF features, so those build-final
  branches are intentionally omitted.)
