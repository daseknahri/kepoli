<?php
/**
 * Plugin Name: Kepoli Slug Cleanup (honest URLs, one-time)
 * Description: A batch of indexed posts still carried CLICKBAIT / FABRICATED-AUTHORITY slugs from before the
 *   content rewrite (e.g. /what-japans-oldest-doctor-reportedly-ate-before-bed/ for an honest saffron post,
 *   /...the-trick-women-are-sharing/, /...seniors-swear.../). Titles + bodies were already cleaned, but the
 *   URL is reviewer-visible (it's in the sitemap) and a deceptive URL is a needless AdSense "misrepresentation"
 *   tell. This renames each to the sanitized honest TITLE slug ONCE, and records the previous slug in
 *   _kepoli_slug_aliases so kepoli-link-recovery still 301s any already-shared old link to the post (no
 *   traffic lost). Idempotent (guarded by an option), collision-safe, admin/cron only.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kepoli_slug_cleanup_run');
add_action('wp_loaded', static function (): void {
    if (wp_doing_cron()) {
        kepoli_slug_cleanup_run();
    }
});

function kepoli_slug_cleanup_run(): void
{
    if (!is_admin() && !wp_doing_cron()) {
        return;
    }
    if (get_option('kepoli_slug_cleanup_v1')) {
        return; // already done
    }

    // Indexed posts whose slug carries fabricated social-proof / clickbait phrasing.
    $ids = [
        3848, 3878, 3869, 3845, 3866, 3803, 3851, 3827, 3839, 3818, 3812,
        3863, 3809, 3791, 3821, 3833, 3806, 3860, 3800, 3797, 3836, 3857,
    ];

    $renamed = 0;
    foreach ($ids as $id) {
        try {
            $post = get_post($id);
            if (!$post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
                continue;
            }
            $old = (string) $post->post_name;
            $new = sanitize_title(get_the_title($id));
            if ($new === '' || $new === $old) {
                continue;
            }
            // Collision guard: never steal a slug another published post already owns.
            $taken = get_posts([
                'name'           => $new,
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'no_found_rows'  => true,
            ]);
            if (!empty($taken) && (int) $taken[0] !== $id) {
                continue;
            }
            // Keep the old slug as a recovery alias so shared/campaign links to it never 404.
            $aliases = array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($id, '_kepoli_slug_aliases', true))), 'strlen'));
            if (!in_array($old, $aliases, true)) {
                $aliases[] = $old;
                update_post_meta($id, '_kepoli_slug_aliases', implode(',', $aliases));
            }
            // Rename.
            $res = wp_update_post(['ID' => $id, 'post_name' => $new], true);
            if (is_wp_error($res)) {
                error_log('[kepoli] slug cleanup: post ' . $id . ' rename failed: ' . $res->get_error_message());
                continue;
            }
            // Keep the stored share link in sync with the new permalink.
            update_post_meta($id, '_wpap_smart_link', get_permalink($id));
            clean_post_cache($id);
            $renamed++;
        } catch (\Throwable $e) {
            error_log('[kepoli] slug cleanup: post ' . $id . ' threw: ' . $e->getMessage());
        }
    }

    delete_transient('kepoli_link_recovery_maps'); // rebuild the recovery map with the new aliases
    update_option('kepoli_slug_cleanup_v1', 1, false);
    error_log('[kepoli] slug cleanup: renamed ' . $renamed . ' post(s) to honest slugs (old slugs kept as recovery aliases).');
}
