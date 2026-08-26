# Changelog — Automation Hamri (build-v9, the full/active product)

Newest-first. build-v9 is the modular (`includes/*.php`), full-featured product that keeps its
front-end (SEO/ads/recipe). See `readme.txt` for the WordPress-directory changelog.

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
