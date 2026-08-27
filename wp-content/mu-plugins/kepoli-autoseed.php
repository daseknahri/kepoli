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

/**
 * Admin recovery (opt-in). WP_ADMIN_PASSWORD is applied ONLY at first install by the WP-CLI seed
 * (`bootstrap.sh`, which is profile:seed and does NOT run on a normal redeploy), so a later
 * Coolify password change never reaches the existing admin — "login from env" then fails. Set
 * KEPOLI_RESET_ADMIN=1 for ONE deploy to re-apply the env password + guarantee the administrator
 * role on the WP_ADMIN_USER account, then set it back to 0.
 *
 * Runs at most ONCE per (user + password) value: a marker keyed on a hash of the env credentials
 * is stored, so it never re-fires on later requests. That matters because wp_set_password() kills
 * the user's sessions — a per-request reset would log the admin out in a loop. Changing
 * WP_ADMIN_PASSWORD (or WP_ADMIN_USER) re-arms it for one more reset.
 */
add_action('init', static function (): void {
    if (!kepoli_autoseed_env_bool('KEPOLI_RESET_ADMIN', false)) {
        return;
    }
    $login = kepoli_autoseed_env('WP_ADMIN_USER', 'admin');
    $pass  = kepoli_autoseed_env('WP_ADMIN_PASSWORD', '');
    if ($login === '' || $pass === '') {
        return;
    }
    $want = hash('sha256', $login . '|' . $pass);
    if (get_option('kepoli_admin_reset_marker') === $want) {
        return; // already applied for this exact credential — don't re-run (avoids a session-kill loop)
    }
    $user = get_user_by('login', $login);
    if (!$user instanceof WP_User) {
        return; // no such account — nothing to recover here
    }
    if (function_exists('wp_set_password')) {
        wp_set_password($pass, $user->ID); // resets the password (invalidates old sessions once)
    }
    $user->set_role('administrator');      // guarantee the admin can reach wp-admin
    update_option('kepoli_admin_reset_marker', $want, false);
    error_log('[kepoli] KEPOLI_RESET_ADMIN: re-applied password + administrator role for "' . $login . '". Set KEPOLI_RESET_ADMIN back to 0.');
}, 1);

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

function kepoli_autoseed_cutover_token(): string
{
    // Per-deploy secret, NEVER a committed default: a hardcoded fallback in source is public,
    // so it defeats the guard entirely. An EMPTY secret DISABLES the URL trigger — the
    // env-flag KEPOLI_FRESH_CUTOVER deploy trigger (server-config, not web-reachable) remains
    // the safe way to fire a destructive reseed. Set KEPOLI_CUTOVER_SECRET in Coolify to a
    // long random value to (re-)enable the URL trigger.
    return kepoli_autoseed_env('KEPOLI_CUTOVER_SECRET', '');
}

/**
 * Is the URL-based destructive-cutover trigger authorized for THIS request? All of:
 *   1. a real per-deploy secret is configured (fail closed when unset),
 *   2. it matches (constant-time),
 *   3. the caller is a logged-in administrator,
 *   4. a valid WP nonce is present.
 * (4) is the anti-CSRF factor: a state-changing, destructive GET must carry a nonce a stray
 * link / <img> / cross-site request can't forge, even if the secret ever leaked via
 * Referer/history/logs. Admins get the ready-to-click nonce'd URL via ?kepoli_debug=1.
 */
function kepoli_autoseed_url_trigger_ok(): bool
{
    $token = kepoli_autoseed_cutover_token();
    if ('' === $token) {
        return false;                                             // no secret set → trigger disabled
    }
    if (!current_user_can('manage_options') || !isset($_GET['kepoli_do_cutover'])) {
        return false;
    }
    if (!hash_equals($token, (string) $_GET['kepoli_do_cutover'])) {
        return false;
    }
    return (bool) wp_verify_nonce((string) ($_GET['_wpnonce'] ?? ''), 'kepoli_cutover');
}

function kepoli_autoseed_should_cutover(): bool
{
    // Server-config trigger (trusted): env flag set at deploy time.
    if (kepoli_autoseed_env_bool('KEPOLI_FRESH_CUTOVER', false)) {
        return true;
    }
    // Manual URL trigger — nonce + secret + admin (see kepoli_autoseed_url_trigger_ok).
    return kepoli_autoseed_url_trigger_ok();
}

// Observable diagnostic: GET /?kepoli_debug=1 prints a small HTML comment with
// the cutover state + a build tag, so the deployed state is checkable over HTTP.
add_action('wp_footer', static function (): void {
    // Admin-only diagnostic — never expose build/seed internals to anonymous visitors.
    if (!isset($_GET['kepoli_debug']) || !current_user_can('manage_options')) {
        return;
    }
    $counts = wp_count_posts();
    printf(
        "\n<!-- kepoli-diag build=media-v2 flag=%s cutover_done=%s seed_ver=%s published=%d -->\n",
        kepoli_autoseed_env_bool('KEPOLI_FRESH_CUTOVER', false) ? '1' : '0',
        esc_html((string) get_option('kepoli_cutover_done')),
        esc_html((string) get_option('kepoli_seed_version')),
        (int) ($counts->publish ?? 0)
    );
    // Surface the ready-to-click, nonce-protected cutover URL to the admin (only when a real
    // KEPOLI_CUTOVER_SECRET is configured). The nonce is per-user/per-action/time-bound, so
    // this URL can't be reused cross-site as a CSRF payload.
    if ('' !== kepoli_autoseed_cutover_token()) {
        printf(
            "\n<!-- kepoli-cutover-url (admin only, single-use nonce): %s -->\n",
            esc_html(add_query_arg([
                'kepoli_do_cutover' => kepoli_autoseed_cutover_token(),
                '_wpnonce'          => wp_create_nonce('kepoli_cutover'),
            ], home_url('/')))
        );
    }
}, 99);

add_action('init', static function (): void {
    if (defined('WP_INSTALLING') && WP_INSTALLING) {
        return;
    }

    if (function_exists('is_blog_installed') && !is_blog_installed()) {
        return;
    }

    kepoli_autoseed_activate_plugin('automation-hamri/wp-automator-pro.php');

    // One-time destructive CUTOVER / reseed. Runs in-process here because a
    // Coolify redeploy updates code but does NOT run the wp-cli seed. When
    // KEPOLI_FRESH_CUTOVER=1 (or the token URL is hit), wipe every existing
    // post/page/attachment and reseed the clean site — including re-importing the
    // current featured images. Guarded by a seed-version marker + lock so it runs
    // once per seed version. Take a backup first; set the flag back to 0 after.
    if (kepoli_autoseed_should_cutover()) {
        $marker = 'kepoli_cutover_done';
        $target = function_exists('kepoli_seed_target_version') ? kepoli_seed_target_version() : 'cutover';
        // An admin using the (nonce-protected) URL trigger may force a reseed past the marker
        // (to pick up new content/images); the env flag stays marker-guarded so it runs once.
        $via_token = kepoli_autoseed_url_trigger_ok();

        // Steal a stale lock left by a prior run that hard-crashed before releasing.
        $lock = get_option('kepoli_cutover_lock');
        if (false !== $lock && (int) $lock < time() - 20 * MINUTE_IN_SECONDS) {
            delete_option('kepoli_cutover_lock');
        }

        if (((string) get_option($marker) !== (string) $target || $via_token)
            && file_exists('/seed/bootstrap.php')
            && file_exists('/content/posts.json')
            // Atomic claim: add_option returns false if the row already exists, so
            // only ONE concurrent request can enter this destructive path.
            && add_option('kepoli_cutover_lock', (string) time(), '', 'no')
        ) {
            @set_time_limit(0);
            @ignore_user_abort(true);
            // Record the attempt up-front: a hard seed failure then can't loop the
            // env-flag trigger into repeatedly wiping content (an admin can still
            // force a fresh attempt via the token).
            update_option($marker, (string) $target, true);
            try {
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
                delete_option('kepoli_cutover_fails');   // clean run — reset the retry counter
            } catch (\Throwable $e) {
                error_log('kepoli cutover failed: ' . $e->getMessage());
                // The content was already wiped above but the seed did not finish. Leaving the
                // marker set would strand the site PERMANENTLY EMPTY (neither the cutover nor the
                // normal autoseed path would re-run). Instead, roll back the recovery state so the
                // next request re-attempts — re-wiping an already-empty site is a no-op, so this
                // only re-runs the seed. Bounded so a permanently-failing seed can't loop forever.
                $fails = (int) get_option('kepoli_cutover_fails', 0) + 1;
                update_option('kepoli_cutover_fails', (string) $fails, false);
                if ($fails < 5) {
                    delete_option($marker);                 // let should_cutover re-enter, OR
                    delete_option('kepoli_seed_version');   // let the normal autoseed path recover
                }
            } finally {
                // Always release the atomic lock, even if the seed threw.
                delete_option('kepoli_cutover_lock');
            }
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
    } catch (\Throwable $e) {
        // Match the cutover path: a seed failure (e.g. one unreadable content image) must
        // degrade to an unseeded-but-serving site, NOT a PHP fatal / HTTP 500 on the public
        // front-end request that happened to trigger the self-heal.
        error_log('kepoli autoseed failed: ' . $e->getMessage());
    } finally {
        ob_end_clean();
        delete_transient('kepoli_seed_lock');
    }
}, 20);
