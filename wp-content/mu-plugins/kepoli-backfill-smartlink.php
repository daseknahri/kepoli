<?php
/**
 * Plugin Name: Kepoli Backfill Smart-Link (restore OG on legacy posts)
 * Description: The automation-hamri plugin only emits Open Graph / Twitter card tags on posts that carry
 *   the _wpap_smart_link meta (its "managed post" marker). A few legacy imports (the truncated-slug posts)
 *   were published without it, so they ship with NO OG tags at all — shared on Facebook they render with
 *   no card image or title, which kills click-through on an FB-traffic site. This backfills
 *   _wpap_smart_link = canonical permalink on any published post missing it, so the plugin's existing OG
 *   head fires for them too (consistent with every other post). Self-healing and cheap: runs only on
 *   admin/cron, throttled to at most hourly, does nothing once every post has the marker.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kepoli_backfill_smartlink');
add_action('wp_loaded', static function (): void {
    if (wp_doing_cron()) {
        kepoli_backfill_smartlink();
    }
});

function kepoli_backfill_smartlink(): void
{
    if (!is_admin() && !wp_doing_cron()) {
        return; // never on the front-end hot path
    }
    if (get_transient('kepoli_smartlink_checked')) {
        return; // throttle: at most once/hour
    }
    set_transient('kepoli_smartlink_checked', 1, HOUR_IN_SECONDS);

    $ids = get_posts([
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 300,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'meta_query'     => [[
            'key'     => '_wpap_smart_link',
            'compare' => 'NOT EXISTS',
        ]],
    ]);
    if (empty($ids)) {
        return; // all posts already have it — nothing to do
    }

    $n = 0;
    foreach ($ids as $id) {
        try {
            $url = get_permalink((int) $id);
            if ($url) {
                update_post_meta((int) $id, '_wpap_smart_link', $url);
                clean_post_cache((int) $id);
                $n++;
            }
        } catch (\Throwable $e) {
            error_log('[kepoli] smartlink backfill failed for ' . (int) $id . ': ' . $e->getMessage());
        }
    }
    if ($n > 0) {
        // Re-run soon (next admin/cron tick) in case more than one page's worth exist.
        delete_transient('kepoli_smartlink_checked');
        error_log('[kepoli] smartlink backfill: set on ' . $n . ' post(s) — OG tags now emit for them.');
    }
}
