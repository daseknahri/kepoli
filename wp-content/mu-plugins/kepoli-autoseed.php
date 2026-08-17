<?php
/**
 * Plugin Name: Food Blog Auto Seed
 * Description: Self-heals a fresh WordPress install when the one-shot WP-CLI seed did not run in the host platform.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists('/seed/version.php')) {
    require_once '/seed/version.php';
}

function kepoli_autoseed_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || $value === '') {
        return $default;
    }

    return (string) $value;
}

function kepoli_autoseed_env_bool(string $key, bool $default = false): bool
{
    $value = strtolower(trim(kepoli_autoseed_env($key, $default ? '1' : '0')));
    if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }

    return $default;
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

function kepoli_autoseed_has_real_content(): bool
{
    $content = get_posts([
        'post_type' => ['post', 'page'],
        'post_status' => ['publish', 'draft', 'pending', 'future', 'private'],
        'posts_per_page' => 20,
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);

    $starter_slugs = ['hello-world', 'sample-page', 'privacy-policy'];
    foreach ($content as $post) {
        if (!$post instanceof WP_Post) {
            continue;
        }
        if (!in_array((string) $post->post_name, $starter_slugs, true)) {
            return true;
        }
    }

    return false;
}

function kepoli_autoseed_should_cutover(): bool
{
    if (kepoli_autoseed_env_bool('KEPOLI_FRESH_CUTOVER', false)) {
        return true;
    }
    // Deterministic fallback trigger that does NOT depend on the host passing an
    // env var through to the container: GET /?kepoli_do_cutover=<token>. Still
    // guarded by the one-time marker + lock in the init handler below.
    if (isset($_GET['kepoli_do_cutover'])
        && hash_equals('kpc-8f3a2e7b', (string) $_GET['kepoli_do_cutover'])) {
        return true;
    }
    return false;
}

// Observable diagnostic: GET /?kepoli_debug=1 prints a small HTML comment with
// the cutover state + a build tag, so the deployed state is checkable over HTTP.
add_action('wp_footer', static function (): void {
    if (!isset($_GET['kepoli_debug'])) {
        return;
    }
    $counts = wp_count_posts();
    printf(
        "\n<!-- kepoli-diag build=cutover-url-v1 flag=%s cutover_done=%s seed_ver=%s published=%d -->\n",
        kepoli_autoseed_env_bool('KEPOLI_FRESH_CUTOVER', false) ? '1' : '0',
        esc_html((string) get_option('kepoli_cutover_done')),
        esc_html((string) get_option('kepoli_seed_version')),
        (int) ($counts->publish ?? 0)
    );
}, 99);

add_action('init', static function (): void {
    if (defined('WP_INSTALLING') && WP_INSTALLING) {
        return;
    }

    if (function_exists('is_blog_installed') && !is_blog_installed()) {
        return;
    }

    kepoli_autoseed_activate_plugin('automation-hamri/wp-automator-pro.php');

    // One-time destructive CUTOVER. Runs in-process here because a Coolify
    // redeploy updates code but does NOT run the wp-cli seed. When
    // KEPOLI_FRESH_CUTOVER=1, wipe every existing post/page/attachment and
    // reseed the clean site, exactly once (guarded by a version marker + lock).
    // IMPORTANT: take a VPS/DB backup first, then set the flag back to 0 after.
    if (kepoli_autoseed_should_cutover()) {
        $marker = 'kepoli_cutover_done';
        $target = function_exists('kepoli_seed_target_version') ? kepoli_seed_target_version() : 'cutover';
        if ((string) get_option($marker) !== (string) $target
            && !get_transient('kepoli_seed_lock')
            && file_exists('/seed/bootstrap.php')
            && file_exists('/content/posts.json')
        ) {
            set_transient('kepoli_seed_lock', '1', 15 * MINUTE_IN_SECONDS);
            @set_time_limit(0);
            @ignore_user_abort(true);

            $ids = get_posts([
                'post_type'      => ['post', 'page', 'attachment'],
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
            ]);
            foreach ($ids as $pid) {
                wp_delete_post((int) $pid, true);
            }

            ob_start();
            try {
                require '/seed/bootstrap.php';
            } finally {
                ob_end_clean();
            }

            update_option($marker, (string) $target, true);
            delete_transient('kepoli_seed_lock');
            return;
        }
    }

    if (!kepoli_autoseed_env_bool('KEPOLI_AUTOSEED_ENABLE', true)) {
        return;
    }

    $target_version = function_exists('kepoli_seed_target_version')
        ? kepoli_seed_target_version()
        : 'seed-fallback';

    $current_version = (string) get_option('kepoli_seed_version', '');
    $force_reseed = kepoli_autoseed_env_bool('KEPOLI_FORCE_RESEED', false);

    if (!$force_reseed && $current_version !== '') {
        return;
    }

    if (!$force_reseed && kepoli_autoseed_has_real_content()) {
        return;
    }

    if ($current_version === $target_version && wp_get_theme()->get_stylesheet() === 'viral-reader') {
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
