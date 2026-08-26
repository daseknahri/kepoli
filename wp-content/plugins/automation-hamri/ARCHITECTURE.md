# Automation Hamri — architecture

`wp-automator-pro.php` was historically a single ~6,900-line file. As of **9.18.0** it is
split into a slim bootstrap plus per-concern modules under `includes/`. The split is a
**verbatim, order-preserving extraction** — every function kept its exact body; the only
new code is the `require_once` block and each module's `<?php`/ABSPATH guard. Behaviour is
identical (verified by byte-exact reconstruction, `php -l` on all files, and a live
activation + front-end smoke test).

## Load model

`wp-automator-pro.php` defines the plugin header, all `WPAP_*` constants, licensing, the
HTTP/SSRF transport layer, activation/DB-upgrade, and the admin-menu registration — then
`require_once`s each module (in the order below). Every module **self-registers its own
hooks** (`add_action`/`add_filter`/`add_shortcode`) next to the handler, so load order only
matters for constants (defined before the requires) — never for hook callbacks, which fire
after the full plugin is loaded.

## Modules

| File | Concern |
|------|---------|
| `wp-automator-pro.php` | Bootstrap: header, constants, **licensing**, HTTP/SSRF transport (`wpap_safe_remote_get`, TLS/redirect pinning), rate limits, `wpap_create_table` / `wpap_ensure_meta_dedup_index` / `wpap_maybe_upgrade_db` / `wpap_activate`, admin-menu + asset registration, and the module `require` block. |
| `includes/admin.php` | Admin UI — dashboard shell + the full settings page (with its inline preview/estimator JS). |
| `includes/scheduling.php` | Queue-anchored ordered scheduling, public permalink resolution, content→pages splitting. |
| `includes/media.php` | Image upload fallback, WebP conversion (memory pre-flight), **SSRF-guarded** remote image import + dedup, per-hop-safe download. |
| `includes/publishing.php` | Distribution import, `wpap_publish_article`, bulk publish, JSON export, and `wpap_ajax_process_title`. ⚠️ **`process_title` nests the AI hook/title generators (`wpap_generate_hook_via_ai`, `wpap_build_clean_hook`) — do NOT edit the AI portions.** |
| `includes/distribution.php` | Distribution Hub list/stats queries, delete/bulk-delete/cleanup, row lifecycle on trash/untrash/delete, cache purge, image proxy. |
| `includes/automation.php` | Google-Sheet CSV automation cron (fetch/parse/dedup/run), SSRF IP helpers, lock ownership release. |
| `includes/ai-content.php` | ⚠️ **AI content + image generation pipeline — do NOT edit.** `wpap_generate_content(_gemini)`, markdown/cleanup, `wpap_generate_image_*`, `wpap_save_image_to_library`. |
| `includes/seo-schema.php` | Front-end `wp_head`: OG/Twitter, meta description, Article/Recipe/Breadcrumb JSON-LD, related-posts block (defers to a SEO plugin / the companion theme when present). |
| `includes/ads.php` | `ads.txt`, IndexNow (opt-in), ad zones (shortcode + block), header/footer ad output, in-content ad injection with density guard + storage-time length cap. |
| `includes/settings-io.php` | Settings export/import (secrets excluded) + the admin dashboard health widget. |
| `includes/editor-tools.php` | Gutenberg "Author Tools" — meta box + derived-field save (`wpap_ed_*`). |

## Do-not-touch (AI generation pipeline)

By project constraint the AI generation code is off-limits. It lives in
`includes/ai-content.php` (entirely) and inside `wpap_ajax_process_title` in
`includes/publishing.php` (the nested `wpap_generate_hook_via_ai` / `wpap_build_clean_hook`).
Edit everything else freely.

## Scale notes (9.18.0)

- Dedup lookups (`_wpap_source_key`, image imports keyed on `_wpap_source_image_hash`) are
  index-backed by a composite `wp_postmeta (meta_key(32), meta_value(191))` index added on
  upgrade (`wpap_ensure_meta_dedup_index`, idempotent + best-effort).
- Orphan-Hub cleanup is a single batched set-based `DELETE` (no fetch-all + N+1).
- Dashboard stats (5-min) and the related-posts block (12-h) are transient-cached.
  Because a related block embeds *other* posts, invalidation is site-wide: any plugin-post
  publish/trash/delete calls `wpap_invalidate_content_caches()`, which clears the dashboard
  transient and bumps `wpap_rel_ver` (folded into every related cache key), so every block
  recomputes at once — no stale links to removed posts.
- The `wpap_automation_seen` map is written once per cron run; ad code is length-capped at
  storage; the Hub count drops its `wp_posts` join on the default (unfiltered) view.

### Extreme-scale items (implemented in 9.19.0)

- **Hub list ordering** no longer filesorts a computed joined-column expression. `wpap_hub_ordered_rows()`
  (distribution.php) fetches "published first, then newest" as two segments, each ordered by the
  primary key `t.id` (index-backed, no filesort), with straddle math preserving the exact
  pagination. Used by both the Hub list and the JSON export. (Chosen over denormalizing
  `post_status` into the Hub table, which couldn't be kept correct without editing the off-limits
  AI handler where one of the row-inserts lives.)
- **Tombstones** moved from the O(n) `wpap_automation_deleted_keys` option to the indexed
  `wpap_tombstones` table (`WPAP_TOMB_TABLE`): recording a deletion is one `INSERT IGNORE`
  (`wpap_automation_tombstone_key`), the dedup check is an indexed `SELECT`
  (`wpap_automation_key_is_done`), and existing tombstones backfill once on upgrade
  (`wpap_migrate_tombstones_to_table`, DB v4). The legacy option is left in place for rollback.
