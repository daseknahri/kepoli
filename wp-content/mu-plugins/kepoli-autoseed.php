<?php
/**
 * Plugin Name: Kepoli Auto Seed
 * Description: Self-heals a fresh WordPress install when the one-shot WP-CLI seed did not run in the host platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_autoseed_activate_plugin(string $plugin): void
{
    $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
    if (!file_exists($plugin_path)) {
        return;
    }

    if (!function_exists('is_plugin_active') || !function_exists('activate_plugin')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!is_plugin_active($plugin)) {
        activate_plugin($plugin, '', false, true);
    }
}

add_action('init', static function (): void {
    kepoli_autoseed_activate_plugin('kepoli-author-tools/kepoli-author-tools.php');

    $target_version = '2026-04-22-seo-content-enhancement';

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
