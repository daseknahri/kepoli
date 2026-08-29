<?php
/**
 * Plugin Name: Kepoli Auto-Publish Overdue Scheduled Posts
 * Description: Safety net for WordPress's "missed schedule" problem on a low-traffic / Docker host.
 *   WP-Cron is visitor-triggered; with little traffic and no managed system cron, a scheduled post
 *   whose time passes before cron fires gets stuck as 'future' ("Missed schedule"), and a later
 *   cron hit will NOT recover it. This finds any 'future' post whose time has already passed and
 *   publishes it directly with wp_publish_post() — bypassing the fragile publish_future_post event.
 *   Runs on init (so the wp-cron sidecar ping AND any real visitor both trigger it), throttled to
 *   ~once per 90s via a transient. Only ever touches genuinely OVERDUE posts (post_date_gmt in the
 *   past); it never publishes a post you deliberately scheduled for the future.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', 'kepoli_publish_overdue_scheduled', 30);
function kepoli_publish_overdue_scheduled(): void
{
    // Throttle: run the check at most ~once every 90s, not on every request.
    if (get_transient('kepoli_overdue_pub_lock')) {
        return;
    }
    set_transient('kepoli_overdue_pub_lock', 1, 90);

    global $wpdb;
    $now_gmt = gmdate('Y-m-d H:i:s');
    // Indexed on (post_status, post_date_gmt); cheap, and usually returns nothing.
    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'future' AND post_date_gmt <= %s LIMIT 100",
            $now_gmt
        )
    );
    if (empty($ids)) {
        return;
    }

    $published = 0;
    foreach ($ids as $pid) {
        // wp_publish_post transitions 'future' -> 'publish' and fires the normal publish hooks.
        wp_publish_post((int) $pid);
        clean_post_cache((int) $pid);
        $published++;
    }
    error_log('[kepoli] auto-published ' . $published . ' overdue scheduled post(s).');
}
