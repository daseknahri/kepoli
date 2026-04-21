<?php
/**
 * Plugin Name: Kepoli Auto Seed
 * Description: Self-heals a fresh WordPress install when the one-shot WP-CLI seed did not run in the host platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', static function (): void {
    $target_version = '2026-04-21-admin-user';

    if (get_option('kepoli_seed_version') === $target_version && wp_get_theme()->get_stylesheet() === 'kepoli') {
        return;
    }

    if (!file_exists('/seed/bootstrap.php') || !file_exists('/content/posts.json')) {
        return;
    }

    if (get_transient('kepoli_seed_lock')) {
        return;
    }

    set_transient('kepoli_seed_lock', '1', 5 * MINUTE_IN_SECONDS);

    ob_start();
    try {
        require '/seed/bootstrap.php';
    } finally {
        ob_end_clean();
        delete_transient('kepoli_seed_lock');
    }
}, 20);
