<?php
/**
 * Plugin Name: Automation Hamri
 * Plugin URI:  https://github.com/hamrioussama19-jpg
 * Description: An advanced AI-powered bulk content generator for WordPress that automates SEO articles, internal linking, and multi-engine image sourcing. Optimized for high-traffic niches.
 * Version:     9.11.0
 * Author:      Oussama Hamri
 * License:     GPL-2.0+
 * Text Domain: automation-hamri
 */

defined( 'ABSPATH' ) || exit;

/* ════════════════════════════════════════════
   LICENSE PROTECTION SYSTEM
   Remote verification against GitHub Gist JSON.
   All plugin menus are locked until a valid
   Username + License Key pair is confirmed.
════════════════════════════════════════════ */

define( 'WPAP_LICENSE_URL',
    'https://gist.githubusercontent.com/hamrioussama19-jpg/9d6bbf3b426c750cd0f481814fbeed37/raw/Automation%2520Hamri%2520Licenses'
);
define( 'WPAP_LICENSE_OPTION',  'wpap_license_data' );   /* stores {user, key, status} */
define( 'WPAP_LICENSE_CACHE',   'wpap_license_cache' );  /* transient: remote JSON     */

/* ── Helper: is the plugin currently activated? ── */
function wpap_is_licensed(): bool {
    /* Licensing removed — the GitHub-Gist access-key system is disabled and the
       plugin is fully unlocked. Always licensed. */
    return true;
}

/* ── Helper: fetch & cache remote license list (60-min transient) ── */
function wpap_fetch_license_list(): array {
    $cached = get_transient( WPAP_LICENSE_CACHE );
    if ( is_array( $cached ) ) {
        return $cached;
    }
    $bust_url = WPAP_LICENSE_URL . '?t=' . time();
    $response = wp_remote_get( $bust_url, array(
        'timeout'   => 15,
        'sslverify' => true,
    ) );
    if ( is_wp_error( $response ) ) {
        return array();
    }
    $code = wp_remote_retrieve_response_code( $response );
    if ( $code !== 200 ) {
        return array();
    }
    $body = wp_remote_retrieve_body( $response );
    $json = json_decode( $body, true );
    if ( ! is_array( $json ) ) {
        return array();
    }
    set_transient( WPAP_LICENSE_CACHE, $json, HOUR_IN_SECONDS );
    return $json;
}

/* ── Helper: verify a username+key pair against remote JSON ── */
/**
 * Verify a username + key pair against the remote JSON.
 *
 * Returns an array with:
 *   'valid'  => bool   -- credentials matched
 *   'error'  => string -- human-readable reason when not valid (empty on success)
 *   'domain' => string -- the domain value stored in the remote JSON entry
 */
function wpap_verify_license( string $username, string $key ): array {
    $list = wpap_fetch_license_list();
    if ( empty( $list ) ) {
        return array( 'valid' => false, 'error' => 'Could not reach the license server. Please try again.', 'domain' => '' );
    }

    foreach ( $list as $entry ) {
        if ( ! is_array( $entry ) ) {
            continue;
        }
        $remote_user   = trim( (string) ( $entry['user']    ?? $entry['username'] ?? '' ) );
        $remote_key    = trim( (string) ( $entry['key']     ?? $entry['license']  ?? '' ) );
        $remote_domain = trim( (string) ( $entry['domain']  ?? '' ) );

        if (
            strtolower( $remote_user ) !== strtolower( trim( $username ) ) ||
            $remote_key                !== trim( $key )
        ) {
            continue;
        }

        /* Credentials matched -- now check domain lock */
        $site_domain = (string) parse_url( get_site_url(), PHP_URL_HOST );

        if ( $remote_domain === '' ) {
            return array( 'valid' => true, 'error' => '', 'domain' => $remote_domain );
        }

        if ( $remote_domain === $site_domain ) {
            return array( 'valid' => true, 'error' => '', 'domain' => $remote_domain );
        }

        return array(
            'valid'  => false,
            'error'  => 'This license is already locked to another website. Please contact Oussama Hamri.',
            'domain' => $remote_domain,
        );
    }

    return array( 'valid' => false, 'error' => 'Invalid Username or License Key. Please check your credentials and try again.', 'domain' => '' );
}

/* ── Admin menu: show only Activate License when unlicensed ── */
add_action( 'admin_menu', 'wpap_license_menu', 5 );   /* priority 5 — runs before main menu */
function wpap_license_menu(): void {
    if ( wpap_is_licensed() ) {
        return;   /* licensed: let the real menu register normally */
    }
    /* Unlicensed: register a locked top-level menu with only the activation page */
    add_menu_page(
        'Automation Hamri — Activate',
        'Automation Hamri',
        'manage_options',
        'wpap-activate',
        'wpap_render_activation_page',
        'dashicons-lock',
        3
    );
}

/* ── Activation page renderer ── */
function wpap_render_activation_page(): void {
    $error   = '';
    $success = '';

    /* Show revocation notice if the background check fired */
    $revoke_msg = get_transient( 'wpap_revoke_notice' );
    if ( $revoke_msg ) {
        $error = $revoke_msg;
        delete_transient( 'wpap_revoke_notice' );
    }

    if ( isset( $_POST['wpap_activate_license'] ) && check_admin_referer( 'wpap_activate_nonce' ) ) {
        $username = sanitize_text_field( wp_unslash( $_POST['wpap_license_user'] ?? '' ) );
        $key      = sanitize_text_field( wp_unslash( $_POST['wpap_license_key']  ?? '' ) );

        if ( empty( $username ) || empty( $key ) ) {
            $error = 'Please enter both your Username and License Key.';
        } else {
            /* Clear cache to force a fresh remote fetch on activation */
            delete_transient( WPAP_LICENSE_CACHE );
            $license_result = wpap_verify_license( $username, $key );
            if ( $license_result['valid'] ) {
                update_option( WPAP_LICENSE_OPTION, array(
                    'user'   => $username,
                    'key'    => $key,
                    'status' => 'active',
                ) );
                $success = 'License activated successfully! Redirecting…';
                echo '<script>setTimeout(function(){ window.location.href="' . esc_url( admin_url( 'admin.php?page=wp-automator-pro' ) ) . '"; }, 1500);</script>';
            } else {
                $error = $license_result['error'];
            }
        }
    }
    ?>
    <style>
        .wpap-activate-wrap {
            max-width: 480px;
            margin: 80px auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 40px 48px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .wpap-activate-wrap h1 {
            font-size: 22px;
            margin: 0 0 8px;
            color: #1a1a2e;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .wpap-activate-wrap p.sub {
            color: #666;
            margin: 0 0 28px;
            font-size: 14px;
        }
        .wpap-activate-wrap label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .wpap-activate-wrap input[type="text"],
        .wpap-activate-wrap input[type="password"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 18px;
            box-sizing: border-box;
        }
        .wpap-activate-wrap input:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        }
        .wpap-activate-wrap .btn-activate {
            width: 100%;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }
        .wpap-activate-wrap .btn-activate:hover { background: #4f46e5; }
        .wpap-activate-notice {
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .wpap-notice-error   { background: #fef2f2; border-left: 4px solid #ef4444; color: #b91c1c; }
        .wpap-notice-success { background: #f0fdf4; border-left: 4px solid #22c55e; color: #15803d; }
        .wpap-lock-icon { font-size: 26px; }
        /* Password toggle */
        .wpap-key-wrap { position: relative; margin-bottom: 18px; }
        .wpap-key-wrap input { margin-bottom: 0 !important; padding-right: 44px !important; }
        .wpap-eye-btn {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 4px;
            color: #888; line-height: 1;
        }
        .wpap-eye-btn:hover { color: #6366f1; }
        /* Support link */
        .wpap-support-line {
            margin-top: 18px; text-align: center;
            font-size: 13px; color: #666;
        }
        .wpap-support-line a { color: #6366f1; text-decoration: none; font-weight: 600; }
        .wpap-support-line a:hover { text-decoration: underline; }
    </style>
    <div class="wpap-activate-wrap">
        <h1><span class="wpap-lock-icon">🔐</span> Automation Hamri</h1>
        <p class="sub">Enter your license credentials to activate the plugin.</p>
        <?php if ( $error )   : ?><div class="wpap-activate-notice wpap-notice-error"><?php echo esc_html( $error ); ?></div><?php endif; ?>
        <?php if ( $success ) : ?><div class="wpap-activate-notice wpap-notice-success"><?php echo esc_html( $success ); ?></div><?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'wpap_activate_nonce' ); ?>
            <label for="wpap_license_user">Username</label>
            <input type="text" id="wpap_license_user" name="wpap_license_user"
                   value="<?php echo esc_attr( wp_unslash( $_POST['wpap_license_user'] ?? '' ) ); ?>"
                   placeholder="Your license username" autocomplete="username" />
            <label for="wpap_license_key">License Key</label>
            <div class="wpap-key-wrap">
                <input type="password" id="wpap_license_key" name="wpap_license_key"
                       placeholder="Your license key" autocomplete="off" />
                <button type="button" class="wpap-eye-btn" id="wpap_toggle_key" title="Show / Hide">
                    <svg id="wpap-eye-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <button type="submit" name="wpap_activate_license" class="btn-activate">
                Activate License
            </button>
        </form>
        <p class="wpap-support-line">Need help? Contact Oussama Hamri &mdash; <a href="https://wa.me/+212637122491" target="_blank" rel="noopener">Click Here</a></p>
    </div>
    <script>
    (function(){
        var btn   = document.getElementById('wpap_toggle_key');
        var field = document.getElementById('wpap_license_key');
        var icon  = document.getElementById('wpap-eye-icon');
        var eyeOpen  = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
        var eyeOff   = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>';
        if ( btn && field ) {
            btn.addEventListener('click', function(){
                if ( field.type === 'password' ) {
                    field.type = 'text';
                    icon.innerHTML = eyeOff;
                } else {
                    field.type = 'password';
                    icon.innerHTML = eyeOpen;
                }
            });
        }
    })();
    </script>
    <?php
}

/* ── Block all real plugin AJAX actions when unlicensed ── */
add_action( 'wp_ajax_wpap_process_title', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_get_posts',     'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_upload_fallback','wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_bulk_import_distribution', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_bulk_import_remote_images', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_export_distribution_json', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_proxy_image',   'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_bulk_publish_posts', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_delete_distribution',  'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_cleanup_distribution', 'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_automation_run_now',   'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_dashboard_stats',       'wpap_ajax_license_gate', 1 );
add_action( 'wp_ajax_wpap_bulk_delete_distribution', 'wpap_ajax_license_gate', 1 );
function wpap_ajax_license_gate(): void {
    if ( ! wpap_is_licensed() ) {
        wp_send_json_error( 'Plugin not activated. Please enter your license key.' );
    }
    /* Licensed — let the real handler run (do nothing, return) */
}

/* ── Deactivation handler: keep the license so deactivate/reactivate does
   NOT force re-activation. Full license teardown lives in uninstall.php
   (runs only on delete). Only the remote-list cache is cleared here. ── */
register_deactivation_hook( __FILE__, 'wpap_deactivate' );
function wpap_deactivate(): void {
    delete_transient( WPAP_LICENSE_CACHE );
    /* Google-Sheet auto-publish: stop the recurring cron. */
    wp_clear_scheduled_hook( 'wpap_automation_cron' );
}

/* ════════════════════════════════════════════
   PERIODIC BACKGROUND LICENSE CHECK
   Fires only when an admin loads the plugin's
   own Dashboard or Settings pages.
   Always fetches live from the Gist (no cache).
   Auto-revokes and redirects if the entry has
   been deleted or the domain changed.
════════════════════════════════════════════ */
add_action( 'admin_init', 'wpap_schedule_revalidation' );
function wpap_schedule_revalidation(): void {
    /* Licensing removed — no remote re-check. */
    return;
    /* Only relevant in the admin area — never on the front-end */
    if ( ! is_admin() ) {
        return;
    }
    /* Only check when the current user can manage options */
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    /* Only proceed if the plugin thinks it is currently licensed */
    if ( ! wpap_is_licensed() ) {
        return;
    }
    /* Determine which admin page is loading */
    $screen_id = $_GET['page'] ?? '';
    $is_plugin_page = in_array( $screen_id, array(
        'wp-automator-pro',
        'wp-automator-pro-settings',
    ), true );
    if ( ! $is_plugin_page ) {
        return;
    }

    /* ── Throttle: run the live Gist check at most once per 24h ──────────
       Without this, every plugin admin page load makes a blocking request
       to GitHub and can hang the page. Between checks the stored license
       option remains the gate (wpap_is_licensed above). A revoke is still
       honored the next time the check actually runs (within 24h), and the
       initial activation verification is unaffected. */
    $last_check = (int) get_option( 'wpap_license_last_check', 0 );
    if ( $last_check > 0 && ( time() - $last_check ) < DAY_IN_SECONDS ) {
        return;
    }
    /* Stamp BEFORE the network call so a slow/unreachable Gist does not
       re-hang the next page load — mirrors the existing "benefit of the
       doubt" behavior when the remote is down. The revoke logic below still
       runs fully in THIS request; the stamp only gates future loads. */
    update_option( 'wpap_license_last_check', time(), false );

    /* ── Always fetch live — bypass transient cache ── */
    delete_transient( WPAP_LICENSE_CACHE );
    $bust_url = WPAP_LICENSE_URL . '?nocache=' . time();
    $response = wp_remote_get( $bust_url, array(
        'timeout'   => 12,
        'sslverify' => true,
    ) );

    /* If the remote is unreachable, do nothing — benefit of the doubt */
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return;
    }

    $body = wp_remote_retrieve_body( $response );
    $list = json_decode( $body, true );
    if ( ! is_array( $list ) ) {
        return;   /* Malformed JSON — do nothing */
    }

    /* ── Load the stored license data ── */
    $stored      = get_option( WPAP_LICENSE_OPTION, array() );
    $stored_user = strtolower( trim( (string) ( $stored['user'] ?? '' ) ) );
    $stored_key  = trim( (string) ( $stored['key'] ?? '' ) );
    $site_domain = (string) parse_url( get_site_url(), PHP_URL_HOST );

    /* ── Search for a matching entry in the live Gist ── */
    $entry_found  = false;
    $domain_valid = false;

    foreach ( $list as $entry ) {
        if ( ! is_array( $entry ) ) {
            continue;
        }
        $remote_user   = strtolower( trim( (string) ( $entry['user']    ?? $entry['username'] ?? '' ) ) );
        $remote_key    = trim( (string) ( $entry['key']     ?? $entry['license']  ?? '' ) );
        $remote_domain = trim( (string) ( $entry['domain']  ?? '' ) );

        if ( $remote_user !== $stored_user || $remote_key !== $stored_key ) {
            continue;
        }

        /* Credentials still exist in the Gist */
        $entry_found = true;

        /* Domain check: empty = unlocked (valid); non-empty must match site */
        $domain_valid = ( $remote_domain === '' || $remote_domain === $site_domain );
        break;
    }

    /* ── If entry gone or domain mismatched → revoke immediately ── */
    if ( ! $entry_found || ! $domain_valid ) {
        delete_option( WPAP_LICENSE_OPTION );
        delete_transient( WPAP_LICENSE_CACHE );

        /* Store a one-time revocation message for the activation page */
        set_transient( 'wpap_revoke_notice',
            'Your license has been revoked or modified. Please contact Oussama Hamri.',
            120
        );

        wp_safe_redirect( admin_url( 'admin.php?page=wpap-activate' ) );
        exit;
    }
}

/* ════════════════════════════════════════════
   END LICENSE SYSTEM — plugin continues below
════════════════════════════════════════════ */

/* ── Unblock outgoing HTTP ── */
if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) ) {
    define( 'WP_HTTP_BLOCK_EXTERNAL', false );
}
if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL && ! defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
    define( 'WP_ACCESSIBLE_HOSTS', 'api.anthropic.com,generativelanguage.googleapis.com,image.pollinations.ai,api.pexels.com,images.pexels.com' );
}

/* ── Increase wp_remote_* timeout for all AJAX requests ── */
add_filter( 'http_request_timeout', function ( $t ) {
    return ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ? 180 : $t;
} );

/* ── Unblock external image/AI API hosts unconditionally ── */
add_filter( 'http_request_args', function ( $args, $url ) {
    static $allowed = array(
        'api.anthropic.com',
        'generativelanguage.googleapis.com',
        'image.pollinations.ai',
        'api.pexels.com',
        'images.pexels.com',
    );
    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( in_array( $host, $allowed, true ) ) {
        $args['reject_unsafe_urls'] = false;
        if ( wpap_tls_should_bypass() ) {
            /* One-shot fallback: this host's CA store is broken — retry without verification. */
            $args['sslverify'] = false;
        } else {
            $args['sslverify'] = true;
            $bundle = ABSPATH . WPINC . '/certificates/ca-bundle.crt';
            if ( is_readable( $bundle ) ) {
                $args['sslcertificates'] = $bundle;
            }
        }
    }
    return $args;
}, 10, 2 );

/* ════════════════════════════════════════════
   SECURITY HARDENING HELPERS
   TLS resilience + cost circuit-breaker + payload caps.
   NOTE: this block does NOT touch AI content/image generation.
════════════════════════════════════════════ */

/* TLS bypass state: true only during a one-shot retry after a CA/cert failure. */
function wpap_tls_should_bypass( $set = null ) {
    static $bypass = false;
    if ( null !== $set ) {
        $bypass = (bool) $set;
    }
    return $bypass;
}

/* Verify TLS by default for the trusted AI/image hosts; retry once WITHOUT
   verification only when the failure is a genuine certificate/CA problem, so a
   broken CA store never hard-breaks generation. Implemented via pre_http_request
   because it is the only WP HTTP hook that lets us observe the WP_Error and
   re-issue the request without editing the protected generator functions. */
add_filter( 'pre_http_request', 'wpap_tls_resilient_request', 10, 3 );
function wpap_tls_resilient_request( $pre, $parsed_args, $url ) {
    static $in_flight = false;

    /* Respect an earlier short-circuit, and never recurse into our own retry. */
    if ( false !== $pre || $in_flight ) {
        return $pre;
    }

    static $allowed = array(
        'api.anthropic.com',
        'generativelanguage.googleapis.com',
        'image.pollinations.ai',
        'api.pexels.com',
        'images.pexels.com',
    );
    $host = wp_parse_url( $url, PHP_URL_HOST );
    if ( ! in_array( $host, $allowed, true ) ) {
        return $pre; /* not one of ours — let WP handle it normally */
    }

    $in_flight = true;
    $response  = wp_remote_request( $url, $parsed_args );

    if ( is_wp_error( $response ) ) {
        $msg = $response->get_error_message();
        $is_cert_error = ( false !== stripos( $msg, 'certificate' )
            || false !== stripos( $msg, 'SSL' )
            || false !== stripos( $msg, 'cURL error 60' )
            || false !== stripos( $msg, 'cURL error 77' ) );

        /* Only the two image-only hosts (which carry NO API key in the request)
           may fall back to an unverified retry. The key-bearing hosts —
           Anthropic (x-api-key), Gemini (?key= in URL), Pexels API
           (Authorization) — are NEVER retried without verification, so a secret
           can never be sent over an unverified / MITM'd connection. A genuine
           cert failure on a key host surfaces as an error the operator can fix
           (usually a stale server CA bundle) instead of silently leaking. */
        $bypass_ok_hosts = array( 'image.pollinations.ai', 'images.pexels.com' );
        if ( $is_cert_error && in_array( $host, $bypass_ok_hosts, true ) ) {
            error_log( '[Automation Hamri] TLS verification failed for image host ' . $host . ' — ' . $msg . '. Retrying once without verification (no secret in this request).' );
            wpap_tls_should_bypass( true );
            $response = wp_remote_request( $url, $parsed_args );
            wpap_tls_should_bypass( false );
        } elseif ( $is_cert_error ) {
            error_log( '[Automation Hamri] TLS verification failed for key-bearing host ' . $host . ' — ' . $msg . '. NOT retrying unverified (protecting the API key). Fix the server CA bundle if this persists.' );
        }
    }

    $in_flight = false;
    return $response;
}

/* Cost circuit-breaker: per-user hourly + global daily ceilings via transients.
   $units = how many generation units this request will consume. */
function wpap_rate_limit_ok( $units = 1 ) {
    $units = max( 1, (int) $units );

    $hourly_cap = (int) apply_filters( 'wpap_rate_limit_per_user_hourly', 120 );
    $daily_cap  = (int) apply_filters( 'wpap_rate_limit_global_daily', 1000 );

    /* Per-user, per-clock-hour bucket (key rotates each hour). */
    $user_key = 'wpap_rl_u_' . get_current_user_id() . '_' . gmdate( 'YmdH' );
    $user_now = (int) get_transient( $user_key );
    if ( $user_now + $units > $hourly_cap ) {
        return false;
    }

    /* Global, per-day bucket. */
    $day_key = 'wpap_rl_g_' . gmdate( 'Ymd' );
    $day_now = (int) get_transient( $day_key );
    if ( $day_now + $units > $daily_cap ) {
        return false;
    }

    set_transient( $user_key, $user_now + $units, HOUR_IN_SECONDS );
    set_transient( $day_key,  $day_now  + $units, DAY_IN_SECONDS );
    return true;
}

/* Payload guards for the JSON bulk endpoints (filterable). */
function wpap_bulk_max_bytes() {
    return (int) apply_filters( 'wpap_bulk_max_payload_bytes', 2 * 1024 * 1024 );
}
function wpap_bulk_max_items() {
    /* Per-request cap. The whole batch is processed in ONE request (downloading
       each image in turn), so this is bounded by PHP's execution time on this
       host. 200 was proven safe here; 300 gives headroom. If a very large batch
       ever errors (a timeout), split it — or raise this via the filter. */
    return (int) apply_filters( 'wpap_bulk_max_items', 300 );
}

define( 'WPAP_VERSION', '9.11.0' );
define( 'WPAP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPAP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAP_TABLE',      'wpap_generated_posts' );

/* ── API key resolver: a wp-config.php constant wins over the DB option ──
   Lets site owners define keys in wp-config.php (kept out of the database
   and out of the admin UI). Falls back to the value saved in wpap_settings.
   $which is one of 'claude', 'gemini', 'pexels'. Returns '' when unset. */
function wpap_get_api_key( string $which ): string {
    static $map = array(
        'claude' => array( 'WPAP_CLAUDE_API_KEY', 'claude_api_key' ),
        'gemini' => array( 'WPAP_GEMINI_API_KEY', 'gemini_api_key' ),
        'pexels' => array( 'WPAP_PEXELS_API_KEY', 'pexels_api_key' ),
    );
    if ( ! isset( $map[ $which ] ) ) {
        return '';
    }
    list( $const_name, $option_key ) = $map[ $which ];

    /* 1) wp-config.php constant takes precedence when defined and non-empty. */
    if ( defined( $const_name ) ) {
        $const_val = trim( (string) constant( $const_name ) );
        if ( '' !== $const_val ) {
            return $const_val;
        }
    }

    /* 2) Fall back to the saved setting. */
    $settings = get_option( 'wpap_settings', array() );
    if ( ! is_array( $settings ) ) {
        $settings = array();
    }
    return trim( (string) ( $settings[ $option_key ] ?? '' ) );
}

/* ════════════════════════════════════════════
   1. ACTIVATION — create table + default settings
════════════════════════════════════════════ */
register_activation_hook( __FILE__, 'wpap_activate' );
function wpap_activate() {
    global $wpdb;
    $t = $wpdb->prefix . WPAP_TABLE;
    $c = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    /* No "IF NOT EXISTS": dbDelta mis-parses the table name when it's present
       (it captures "IF"), which would block future column migrations. dbDelta
       already handles existence — it only creates or ALTERs what's missing. */
    dbDelta( "CREATE TABLE {$t} (
        id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
        title       VARCHAR(500)    NOT NULL DEFAULT '',
        post_url    VARCHAR(800)    NOT NULL DEFAULT '',
        image_url   VARCHAR(800)    NOT NULL DEFAULT '',
        fb_text     TEXT,
        fb_post_id  VARCHAR(200)    NOT NULL DEFAULT '',
        smart_link  VARCHAR(900)    NOT NULL DEFAULT '',
        created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY post_id  (post_id)
    ) {$c};" );
    $existing = get_option( 'wpap_settings', array() );
    $defaults = array(
        'claude_api_key'  => '',
        'gemini_api_key'  => '',
        'pexels_api_key'  => '',
    );
    update_option( 'wpap_settings', array_merge( $defaults, $existing ), false );   /* autoload = no: API keys stay out of the all-options cache */

    /* Google-Sheet auto-publish: schedule the hourly poll cron (guarded so a
       re-activate doesn't stack duplicate events; first run +1h out). */
    if ( ! wp_next_scheduled( 'wpap_automation_cron' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'wpap_automation_cron' );
    }
}

/* ════════════════════════════════════════════
   2. ADMIN MENU
════════════════════════════════════════════ */

add_action( 'admin_menu', 'wpap_admin_menu' );
function wpap_admin_menu() {
    if ( ! wpap_is_licensed() ) {
        return;   /* locked — only the activation menu is shown */
    }
    add_menu_page( 'WP Automator Pro', 'WP Automator Pro', 'manage_options', 'wp-automator-pro', 'wpap_render_dashboard', 'dashicons-superhero', 3 );
    add_submenu_page( 'wp-automator-pro', 'Dashboard', 'Dashboard', 'manage_options', 'wp-automator-pro', 'wpap_render_dashboard' );
    add_submenu_page( 'wp-automator-pro', 'Settings', 'Settings', 'manage_options', 'wp-automator-pro-settings', 'wpap_render_settings' );
}

/* ════════════════════════════════════════════
   3. ENQUEUE ASSETS
════════════════════════════════════════════ */
add_action( 'admin_enqueue_scripts', 'wpap_enqueue_assets' );
function wpap_enqueue_assets( $hook ) {
    if ( strpos( $hook, 'wp-automator-pro' ) === false ) return;
    /* Version assets by file modification time so browsers always pick up the
       latest file after an update (no stale-cache "nothing changed" surprises). */
    $css_path = WPAP_PLUGIN_DIR . 'assets/admin-style.css';
    $js_path  = WPAP_PLUGIN_DIR . 'assets/admin-script.js';
    $css_ver  = file_exists( $css_path ) ? (string) filemtime( $css_path ) : WPAP_VERSION;
    $js_ver   = file_exists( $js_path )  ? (string) filemtime( $js_path )  : WPAP_VERSION;
    wp_enqueue_style(  'wpap-style',  WPAP_PLUGIN_URL . 'assets/admin-style.css', array(), $css_ver );
    wp_enqueue_script( 'wpap-script', WPAP_PLUGIN_URL . 'assets/admin-script.js', array( 'jquery' ), $js_ver, true );
    wp_localize_script( 'wpap-script', 'WPAP', array(
        'ajax_url'   => admin_url( 'admin-ajax.php' ),
        'nonce'      => wp_create_nonce( 'wpap_nonce' ),
        'plugin_url' => WPAP_PLUGIN_URL,
    ) );
}

/* ════════════════════════════════════════════
   4. DASHBOARD PAGE
════════════════════════════════════════════ */
function wpap_render_dashboard() {
    echo '<div id="wpap-app" class="wpap-root"><div style="padding:40px;text-align:center;font-family:sans-serif;color:#888;font-size:32px;">&#9889; Loading&hellip;</div></div>';
    echo '<p style="text-align:center;margin-top:16px;font-family:sans-serif;font-size:13px;color:#666;">Need help? Contact Oussama Hamri &mdash; <a href="https://wa.me/+212637122491" target="_blank" rel="noopener" style="color:#6366f1;font-weight:600;text-decoration:none;">Click Here</a></p>';
}

/* ════════════════════════════════════════════
   5. SETTINGS PAGE
════════════════════════════════════════════ */
function wpap_render_settings() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html( 'You do not have permission to access this page.' ) );
    }

    /* Map each API-key field to the wp-config.php constant that can lock it. */
    $const_map = array(
        'claude_api_key' => 'WPAP_CLAUDE_API_KEY',
        'gemini_api_key' => 'WPAP_GEMINI_API_KEY',
        'pexels_api_key' => 'WPAP_PEXELS_API_KEY',
    );

    if ( isset( $_POST['wpap_save'] ) && check_admin_referer( 'wpap_settings_nonce' ) ) {
        $existing = get_option( 'wpap_settings', array() );
        if ( ! is_array( $existing ) ) { $existing = array(); }
        $saved = $existing;
        foreach ( array( 'claude_api_key', 'gemini_api_key', 'pexels_api_key' ) as $field ) {
            /* Skip fields locked by a wp-config.php constant — never store a shadow value. */
            $const = $const_map[ $field ] ?? '';
            if ( $const && defined( $const ) && '' !== trim( (string) constant( $const ) ) ) {
                continue;
            }
            $submitted = sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) );
            /* Blank field = keep the stored key (the form never re-echoes secrets). */
            if ( '' !== $submitted ) {
                $saved[ $field ] = $submitted;
            }
        }
        update_option( 'wpap_settings', $saved, false );   /* autoload = no: keep secrets out of the all-options cache */

        /* ── Automation (Google Sheet) settings — same nonce/cap guard ── */
        update_option( 'wpap_automation', array(
            'enabled'          => isset( $_POST['wpap_auto_enabled'] ) ? 1 : 0,
            'sheet_url'        => esc_url_raw( wp_unslash( $_POST['wpap_auto_sheet_url'] ?? '' ) ),
            'per_day'          => max( 0, min( 500, (int) ( $_POST['wpap_auto_per_day'] ?? 12 ) ) ),
            'per_run'          => max( 1, min( 50,  (int) ( $_POST['wpap_auto_per_run'] ?? 3 ) ) ),
            'default_category' => sanitize_text_field( wp_unslash( $_POST['wpap_auto_default_category'] ?? '' ) ),
            'schedule_window'  => max( 0, min( 168, (float) ( $_POST['wpap_auto_schedule_window'] ?? 0 ) ) ),
        ), false );

        /* ads.txt content (plain text; tags stripped). */
        update_option( 'wpap_ads_txt', sanitize_textarea_field( wp_unslash( $_POST['wpap_ads_txt'] ?? '' ) ), false );

        /* ── AdSense ad-injection settings (wpap_ads_inject) ── */
        /* Custom placements (Option 2): parallel arrays, one entry per row.
           Empty-code rows are dropped, so a blank row simply disappears and the
           three arrays stay index-aligned. Capped at 10. */
        $wpap_custom   = array();
        $wpap_cust_pos = ( isset( $_POST['wpap_ads_cust_pos'] ) && is_array( $_POST['wpap_ads_cust_pos'] ) ) ? wp_unslash( $_POST['wpap_ads_cust_pos'] ) : array();
        $wpap_cust_aft = ( isset( $_POST['wpap_ads_cust_after'] ) && is_array( $_POST['wpap_ads_cust_after'] ) ) ? wp_unslash( $_POST['wpap_ads_cust_after'] ) : array();
        $wpap_cust_cod = ( isset( $_POST['wpap_ads_cust_code'] ) && is_array( $_POST['wpap_ads_cust_code'] ) ) ? wp_unslash( $_POST['wpap_ads_cust_code'] ) : array();
        $wpap_cust_n   = count( $wpap_cust_cod );
        for ( $wpap_i = 0; $wpap_i < $wpap_cust_n && count( $wpap_custom ) < 10; $wpap_i++ ) {
            $wpap_code = trim( (string) ( $wpap_cust_cod[ $wpap_i ] ?? '' ) );
            if ( '' === $wpap_code ) { continue; }
            $wpap_pos = isset( $wpap_cust_pos[ $wpap_i ] ) ? sanitize_key( (string) $wpap_cust_pos[ $wpap_i ] ) : 'after';
            if ( ! in_array( $wpap_pos, array( 'after', 'top', 'before_related' ), true ) ) { $wpap_pos = 'after'; }
            $wpap_custom[] = array(
                'pos'   => $wpap_pos,
                'after' => max( 1, min( 50, (int) ( $wpap_cust_aft[ $wpap_i ] ?? 2 ) ) ),
                'code'  => $wpap_code,
            );
        }

        update_option( 'wpap_ads_inject', array(
            'enabled'   => isset( $_POST['wpap_ads_enabled'] ) ? 1 : 0,
            'scope_all' => isset( $_POST['wpap_ads_scope_all'] ) ? 1 : 0,
            'auto_code' => trim( (string) wp_unslash( $_POST['wpap_ads_auto_code'] ?? '' ) ),
            'min_gap'   => max( 0, min( 20, (int) ( $_POST['wpap_ads_min_gap'] ?? 1 ) ) ),
            'max_ads'   => max( 0, min( 20, (int) ( $_POST['wpap_ads_max_ads'] ?? 0 ) ) ),
            'label'     => isset( $_POST['wpap_ads_label'] ) ? 1 : 0,
            'zones'     => array(
                'header'  => array( 'on' => isset( $_POST['wpap_ads_zone_header_on'] ) ? 1 : 0,  'code' => trim( (string) wp_unslash( $_POST['wpap_ads_zone_header_code'] ?? '' ) ) ),
                'sidebar' => array( 'on' => isset( $_POST['wpap_ads_zone_sidebar_on'] ) ? 1 : 0, 'code' => trim( (string) wp_unslash( $_POST['wpap_ads_zone_sidebar_code'] ?? '' ) ) ),
                'footer'  => array( 'on' => isset( $_POST['wpap_ads_zone_footer_on'] ) ? 1 : 0,  'code' => trim( (string) wp_unslash( $_POST['wpap_ads_zone_footer_code'] ?? '' ) ) ),
            ),
            'custom'    => $wpap_custom,
            'slots'     => array(
                'top'       => array(
                    'on'   => isset( $_POST['wpap_ads_top_on'] ) ? 1 : 0,
                    'code' => trim( (string) wp_unslash( $_POST['wpap_ads_top_code'] ?? '' ) ),
                ),
                'incontent' => array(
                    'on'    => isset( $_POST['wpap_ads_inc_on'] ) ? 1 : 0,
                    'code'  => trim( (string) wp_unslash( $_POST['wpap_ads_inc_code'] ?? '' ) ),
                    'after' => max( 1, min( 50, (int) ( $_POST['wpap_ads_inc_after'] ?? 2 ) ) ),
                ),
                'repeat'    => array(
                    'on'    => isset( $_POST['wpap_ads_rep_on'] ) ? 1 : 0,
                    'code'  => trim( (string) wp_unslash( $_POST['wpap_ads_rep_code'] ?? '' ) ),
                    'every' => max( 1, min( 50, (int) ( $_POST['wpap_ads_rep_every'] ?? 4 ) ) ),
                    'max'   => max( 1, min( 10, (int) ( $_POST['wpap_ads_rep_max'] ?? 3 ) ) ),
                ),
                'bottom'    => array(
                    'on'   => isset( $_POST['wpap_ads_bot_on'] ) ? 1 : 0,
                    'code' => trim( (string) wp_unslash( $_POST['wpap_ads_bot_code'] ?? '' ) ),
                ),
            ),
        ), false );

        /* IndexNow instant-indexing toggle. */
        update_option( 'wpap_indexnow', array(
            'enabled' => isset( $_POST['wpap_indexnow_enabled'] ) ? 1 : 0,
        ), false );

        /* ── Content options (publishing guards; all off by default) ── */
        update_option( 'wpap_content_opts', array(
            'min_words'        => max( 0, min( 5000, (int) ( $_POST['wpap_min_words'] ?? 0 ) ) ),
            'skip_dupe_titles' => isset( $_POST['wpap_skip_dupe_titles'] ) ? 1 : 0,
            'disable_comments' => isset( $_POST['wpap_disable_comments'] ) ? 1 : 0,
        ), false );

        /* Image optimization: convert imported JPEG/PNG to WebP (default ON). */
        update_option( 'wpap_webp_enabled', isset( $_POST['wpap_webp_enabled'] ) ? '1' : '0', true );

        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $s = get_option( 'wpap_settings', array() );
    if ( ! is_array( $s ) ) { $s = array(); }

    /* Automation (Google Sheet) state for the UI below. */
    $auto        = wpap_get_automation();
    $auto_status = get_option( 'wpap_automation_status', array() );
    if ( ! is_array( $auto_status ) ) { $auto_status = array(); }

    /* Show a masked hint instead of the real key, so secrets never leave the server. */
    $hint = function ( $key ) {
        $key = (string) $key;
        if ( '' === $key ) { return 'Not set'; }
        $tail = strlen( $key ) > 4 ? substr( $key, -4 ) : $key;
        return 'Saved (' . str_repeat( "\xE2\x80\xA2", 6 ) . $tail . ') — leave blank to keep';
    };

    /* True when a field is locked by a non-empty wp-config.php constant. */
    $is_const = function ( $field ) use ( $const_map ) {
        $c = $const_map[ $field ] ?? '';
        return ( $c && defined( $c ) && '' !== trim( (string) constant( $c ) ) );
    };

    /* Render one settings row: a locked note when constant-defined, else the input. */
    $render_key_row = function ( $label, $field, $desc_html = '' ) use ( $s, $hint, $is_const, $const_map ) {
        echo '<tr><th>' . esc_html( $label ) . '</th><td>';
        if ( $is_const( $field ) ) {
            echo '<input type="text" value="' . esc_attr( 'Set via wp-config.php' ) . '" class="large-text" disabled />';
            echo '<p class="description">&#128274; Defined in <code>wp-config.php</code> via <code>' . esc_html( $const_map[ $field ] ) . '</code>. Remove that constant to manage this key here.</p>';
        } else {
            echo '<input type="password" name="' . esc_attr( $field ) . '" value="" autocomplete="new-password" placeholder="' . esc_attr( $hint( $s[ $field ] ?? '' ) ) . '" class="large-text" />';
            if ( '' !== $desc_html ) {
                echo $desc_html;   /* trusted static markup passed from the caller below */
            }
        }
        echo '</td></tr>';
    };
    ?>
    <div class="wrap">
        <h1>WP Automator Pro &mdash; Settings</h1>
        <form method="post" autocomplete="off">
            <?php wp_nonce_field( 'wpap_settings_nonce' ); ?>
            <table class="form-table">
                <?php
                $render_key_row( 'Claude API Key', 'claude_api_key' );
                $render_key_row( 'Gemini API Key', 'gemini_api_key' );
                $render_key_row( 'Pexels API Key', 'pexels_api_key', '<p class="description">Free images — <a href="https://www.pexels.com/api/" target="_blank">pexels.com/api</a></p>' );
                ?>
            </table>

            <?php /* ── Automation — Google Sheet (config; saved by the same button) ── */ ?>
            <h2 style="margin-top:32px;">Automation &mdash; Google Sheet</h2>
            <p class="description" style="max-width:760px;">
                Auto-publish ready-made articles straight from a Google Sheet &mdash; no API key or login.
                In Google Sheets: <strong>File &rarr; Share &rarr; Publish to web</strong>, choose the specific
                tab, pick <strong>Comma-separated values (.csv)</strong>, click <strong>Publish</strong>, and paste
                the link below. First row must be the header. Recognized columns:
                <code>title</code>, <code>content</code>, <code>imageUrl</code>, <code>hook</code>,
                <code>category</code>, <code>id</code>. Each row needs at least a <code>title</code> or
                <code>content</code>. An <code>id</code> is optional but recommended (stable dedup).
            </p>
            <p class="description" style="max-width:760px;">
                <strong>Timing:</strong> the poll runs on WordPress cron, which fires on site traffic &mdash; on a
                quiet site ticks can drift. For reliable timing, add a real server cron hitting
                <code>wp-cron.php</code>. For an even drip set <em>Max per run</em> to 1 and raise
                <em>Max per day</em>. Keep the Sheet private / owner-only &mdash; its <code>content</code> is published as-is.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable auto-publish</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpap_auto_enabled" value="1" <?php checked( ! empty( $auto['enabled'] ) ); ?> />
                            Publish new Sheet rows automatically (hourly).
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Published CSV URL</th>
                    <td>
                        <input type="url" name="wpap_auto_sheet_url" class="large-text" value="<?php echo esc_attr( $auto['sheet_url'] ); ?>"
                               placeholder="https://docs.google.com/spreadsheets/d/e/&hellip;/pub?gid=0&amp;single=true&amp;output=csv" />
                        <p class="description">The &ldquo;Publish to web&rdquo; CSV link (ends in <code>output=csv</code>).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Max per day</th>
                    <td><input type="number" name="wpap_auto_per_day" min="0" max="500" step="1" class="small-text" value="<?php echo esc_attr( (string) $auto['per_day'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Max per run</th>
                    <td>
                        <input type="number" name="wpap_auto_per_run" min="1" max="50" step="1" class="small-text" value="<?php echo esc_attr( (string) $auto['per_run'] ); ?>" />
                        <p class="description">Published each hourly run, capped by the daily limit.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default category</th>
                    <td><input type="text" name="wpap_auto_default_category" class="regular-text" placeholder="Uncategorized" value="<?php echo esc_attr( $auto['default_category'] ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Schedule window (hours)</th>
                    <td>
                        <input type="number" name="wpap_auto_schedule_window" min="0" max="168" step="0.5" class="small-text" value="<?php echo esc_attr( (string) $auto['schedule_window'] ); ?>" />
                        <p class="description">0 = publish immediately. Otherwise each post is scheduled at a random time within this many hours.</p>
                    </td>
                </tr>
            </table>

            <?php /* ── ads.txt (AdSense) ── */ ?>
            <?php /* ── AdSense ad placement (ported engine; saved by the same button) ── */ ?>
            <?php $ads = wpap_get_ads(); ?>
            <h2 style="margin-top:32px;">AdSense ad placement</h2>
            <p class="description" style="max-width:820px;">
                Paste your own AdSense code into any slot below and turn it on. <strong>Auto Ads</strong> lets Google
                place ads automatically; the <strong>manual slots</strong> give you exact control (use an
                <em>In-article</em> unit for the in-content slots). The <strong>zones</strong> feed a compatible theme's
                header/sidebar/footer. Leave a slot blank/off to skip it. Nothing is sent anywhere — your code is only
                printed on your own pages.
            </p>

            <h3 style="margin:18px 0 4px;">Live preview <span style="font-weight:400;color:#666;">— where your ads land, at their real sizes</span></h3>
            <p class="description" style="max-width:820px;">A sample story rendered like your theme. It updates as you change the settings below, so you can see the placement and typical AdSense unit sizes before you paste any code. Auto Ads add more, placed by Google.</p>
            <style>
                .wpap-adprev{max-width:430px;margin:8px 0 24px}
                .wpap-adp-off{background:#fff8ef;border:1px solid #f0dccb;color:#b45309;border-radius:10px;padding:16px;font-size:13px;text-align:center}
                .wpap-adp-art{background:#fff;border:1px solid #e8e2d8;border-radius:14px;padding:18px 18px 22px;box-shadow:0 8px 26px -14px rgba(60,50,40,.3);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
                .wpap-adp-art .chip{display:inline-block;background:#d1604a;color:#fff;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:.3em .6em;border-radius:5px}
                .wpap-adp-art h3.t{font-family:Georgia,"Times New Roman",serif;font-size:20px;line-height:1.15;margin:.5em 0 .3em;color:#1b1815;font-weight:800}
                .wpap-adp-art .meta{color:#8a8178;font-size:12px;margin-bottom:12px}
                .wpap-adp-art .hero{aspect-ratio:16/9;border-radius:9px;background:linear-gradient(135deg,#f9a03f,#d1604a 55%,#8e2d8e);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.92);font-size:12px;font-weight:600;margin-bottom:14px}
                .wpap-adp-art p{font-size:13.5px;line-height:1.7;color:#3a352f;margin:0 0 .85em}
                .wpap-adp-art p.lede{font-size:15px;color:#1b1815}
                .wpap-adp-art .rel{border-top:1px solid #eee7dd;margin-top:14px;padding-top:10px}
                .wpap-adp-art .rel h4{font-family:Georgia,serif;font-size:15px;margin:0 0 8px;color:#1b1815}
                .wpap-adp-art .relg{display:grid;grid-template-columns:1fr 1fr;gap:8px}
                .wpap-adp-art .relg span{height:46px;border-radius:6px;background:#f1ede6;display:block}
                .wpap-adp-ad{border:1px dashed #e6b98f;background:repeating-linear-gradient(45deg,#fffaf4,#fffaf4 8px,#fdf2e7 8px,#fdf2e7 16px);border-radius:8px;margin:14px 0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;color:#b06a3a}
                .wpap-adp-ad .lab{font-size:8.5px;letter-spacing:.14em;text-transform:uppercase;color:#c99b6f;font-weight:700}
                .wpap-adp-ad .nm{font-size:13px;font-weight:800;margin-top:2px}
                .wpap-adp-ad .sz{font-size:10.5px;color:#9c7a55;margin-top:1px;font-weight:600}
                .wpap-adp-ad.hwide{min-height:58px}
                .wpap-adp-ad.hmed{min-height:92px}
                .wpap-adp-ad.htall{min-height:150px}
                .wpap-adp-ad.hbox{min-height:180px;max-width:250px;margin-left:auto;margin-right:auto}
                .wpap-adp-zone{margin:0 0 12px}
                .wpap-adp-auto{margin-top:12px;font-size:12px;color:#4f46e5;background:#eef0ff;border:1px solid #dfe3ff;border-radius:8px;padding:8px 12px}
                .wpap-adp-side{margin-top:12px;border:1px dashed #cbd5e1;border-radius:8px;padding:10px}
                .wpap-adp-side .note{display:block;font-size:11px;color:#94a3b8;margin-top:6px;text-align:center}
                .wpap-adp-count{font-size:12px;color:#6b7280;margin:0 0 10px}
                .wpap-adp-count b{color:#111827}
            </style>
            <div id="wpap-adprev" class="wpap-adprev"></div>
            <script>
            (function(){
                var host = document.getElementById('wpap-adprev');
                if ( ! host ) { return; }
                function el(n){ return document.querySelector('[name="'+n+'"]'); }
                function on(n){ var e=el(n); return !!(e && e.checked); }
                function numv(n,d){ var e=el(n); var v=parseInt(e&&e.value,10); return isNaN(v)?d:v; }
                function customs(){
                    var pos=document.getElementsByName('wpap_ads_cust_pos[]');
                    var aft=document.getElementsByName('wpap_ads_cust_after[]');
                    var out=[];
                    for(var i=0;i<pos.length;i++){ out.push({pos:pos[i].value, after:(parseInt(aft[i]&&aft[i].value,10)||2)}); }
                    return out;
                }
                var SZ={
                    top:['Top banner','Leaderboard 728×90 / responsive','hwide'],
                    incontent:['In-content','In-article · responsive','hmed'],
                    repeat:['Repeating','In-article · responsive','hmed'],
                    custom:['Custom','In-article · responsive','hmed'],
                    bottom:['Before related','Multiplex / display · responsive','htall'],
                    header:['Header zone','Leaderboard 728×90 / responsive','hwide'],
                    footer:['Footer zone','Banner · responsive','hwide'],
                    sidebar:['Sidebar zone','Medium rectangle 300×250','hbox']
                };
                function ad(kind){
                    var s=SZ[kind];
                    return '<div class="wpap-adp-ad '+s[2]+'"><span class="lab">Advertisement</span><span class="nm">'+s[0]+'</span><span class="sz">'+s[1]+'</span></div>';
                }
                var PARAS=[
                    'It started, like most things do now, with a single photo shared into a group of half a million people.',
                    'By morning the little town square had a line around it, and nobody could quite say why.',
                    'The story is simple, which is exactly why it travels: a shopkeeper, a folded note, and a promise.',
                    'She did not recognize him at first, but she knew the handwriting the moment he slid it across the counter.',
                    'Nineteen years earlier he had left that note on her door and then left the town for good.',
                    'Neighbours gathered. Phones came out. The clip was already climbing before lunch.',
                    'What he wrote, and what he came back to do, is the part people keep screenshotting.',
                    'They closed the shop for the afternoon. Someone brought chairs out to the pavement.',
                    'A town nobody could find on a map the day before had the whole internet’s attention.',
                    'And the shopkeeper, who had kept the note in a drawer all this time, finally read it aloud.',
                    'By evening the video had four million views and a comment section full of strangers in tears.',
                    'Some stories are shared because they shock you. This one was shared because it was kind.'
                ];
                function render(){
                    if ( ! on('wpap_ads_enabled') ){
                        host.innerHTML='<div class="wpap-adp-off">Ad injection is OFF — turn on the master switch below to preview placements.</div>';
                        return;
                    }
                    var minGap=numv('wpap_ads_min_gap',1), maxAds=numv('wpap_ads_max_ads',0);
                    var incOn=on('wpap_ads_inc_on'), incAfter=numv('wpap_ads_inc_after',2);
                    var repOn=on('wpap_ads_rep_on'), repEvery=numv('wpap_ads_rep_every',4), repMax=numv('wpap_ads_rep_max',3);
                    var topOn=on('wpap_ads_top_on'), botOn=on('wpap_ads_bot_on');
                    var cust=customs();

                    var topHtml='', botHtml='', afterMap={};
                    if(topOn){ topHtml+=ad('top'); }
                    if(botOn){ botHtml+=ad('bottom'); }   /* seed named bottom first, like the injector ($bot) */
                    if(incOn){ (afterMap[incAfter]=afterMap[incAfter]||[]).push('incontent'); }
                    cust.forEach(function(c){
                        if(c.pos==='top'){ topHtml+=ad('custom'); }
                        else if(c.pos==='before_related'){ botHtml+=ad('custom'); }
                        else { (afterMap[c.after]=afterMap[c.after]||[]).push('custom'); }
                    });

                    var body='', para=0, reps=0, placed=0, last=null;
                    for(var i=0;i<PARAS.length;i++){
                        body+='<p'+(i===0?' class="lede"':'')+'>'+PARAS[i]+'</p>';
                        para=i+1;
                        var here=[];
                        if(afterMap[para]){ afterMap[para].forEach(function(k){ here.push(k); }); }
                        else if(repOn && repEvery>0 && (para%repEvery)===0 && reps<repMax){ here.push('repeat'); }
                        for(var j=0;j<here.length;j++){
                            if(maxAds>0 && placed>=maxAds){ break; }
                            if(minGap>0 && last!==null && (para-last)<minGap){ break; }
                            body+=ad(here[j]);
                            last=para; placed++;
                            if(here[j]==='repeat'){ reps++; }
                        }
                    }

                    var hZone=on('wpap_ads_zone_header_on')?'<div class="wpap-adp-zone">'+ad('header')+'</div>':'';
                    var fZone=on('wpap_ads_zone_footer_on')?'<div class="wpap-adp-zone">'+ad('footer')+'</div>':'';
                    var sZone=on('wpap_ads_zone_sidebar_on')?'<div class="wpap-adp-side">'+ad('sidebar')+'<span class="note">Sidebar shows on desktop, beside the article</span></div>':'';
                    var aCode=el('wpap_ads_auto_code');
                    var aNote=(aCode && aCode.value.trim()!=='')?'<div class="wpap-adp-auto">＋ Auto Ads is on: Google places additional ads automatically, site-wide.</div>':'';

                    var inContentCount=placed;
                    var totalManual=inContentCount+(topOn?1:0)+(botOn?1:0);
                    cust.forEach(function(c){ if(c.pos==='top'||c.pos==='before_related'){ totalManual++; } });

                    host.innerHTML=
                        '<p class="wpap-adp-count"><b>'+totalManual+'</b> ad'+(totalManual===1?'':'s')+' in this article ('+inContentCount+' in-content)'+(maxAds>0?' · cap '+maxAds:'')+' · min '+minGap+' ¶ apart</p>'+
                        hZone+
                        '<article class="wpap-adp-art">'+
                            '<span class="chip">Human Stories</span>'+
                            '<h3 class="t">The Note on the Door That a Town Waited 19 Years to Read</h3>'+
                            '<div class="meta">By Sara M. · 4 min read</div>'+
                            '<div class="hero">Featured image</div>'+
                            topHtml+body+botHtml+
                            '<div class="rel"><h4>You May Also Like</h4><div class="relg"><span></span><span></span><span></span><span></span></div></div>'+
                        '</article>'+
                        fZone+aNote+sZone;
                }
                var t=null;
                document.addEventListener('input', function(e){ if(e.target&&e.target.name&&e.target.name.indexOf('wpap_ads_')===0){ clearTimeout(t); t=setTimeout(render,120); } });
                document.addEventListener('change', function(e){ if(e.target&&e.target.name&&e.target.name.indexOf('wpap_ads_')===0){ render(); } });
                document.addEventListener('click', function(e){ if(e.target&&(e.target.id==='wpap-add-placement'||(e.target.className&&(''+e.target.className).indexOf('wpap-cust-remove')>-1))){ setTimeout(render,60); } });
                render();
            })();
            </script>

            <?php /* ── Earnings estimator + one-click ad modes (additive; fills the slots below) ── */ ?>
            <h3 style="margin:26px 0 4px;">Earnings estimator &amp; ad modes <span style="font-weight:400;color:#666;">— rough monthly projection</span></h3>
            <p class="description" style="max-width:820px;">Set your pageviews and a realistic viewable eCPM, then compare three ready-made layouts. Click <strong>Apply</strong> to fill the slots below with that mode (the live preview updates) — then <strong>Save changes</strong> to keep it. Estimates only; nothing here reads or touches your AdSense account.</p>
            <style>
                .wpap-earn{max-width:820px;margin:8px 0 26px}
                .wpap-earn .ctl{display:flex;flex-wrap:wrap;gap:16px 22px;align-items:flex-end;margin-bottom:14px}
                .wpap-earn .ctl label{font-size:12px;font-weight:600;color:#444;display:flex;flex-direction:column;gap:5px}
                .wpap-earn .ctl input{font-size:14px;padding:6px 8px;border:1px solid #c7cdd6;border-radius:6px;width:150px}
                .wpap-earn table{border-collapse:collapse;width:100%;background:#fff;border:1px solid #e2e4e9;border-radius:10px;overflow:hidden}
                .wpap-earn th,.wpap-earn td{padding:10px 12px;text-align:right;border-bottom:1px solid #eef0f3;font-variant-numeric:tabular-nums}
                .wpap-earn th:first-child,.wpap-earn td:first-child{text-align:left}
                .wpap-earn thead th{background:#f6f7f9;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:#6b7280}
                .wpap-earn tbody tr:last-child td{border-bottom:0}
                .wpap-earn .m{font-weight:700}
                .wpap-earn .sub{font-size:11px;color:#8a8f98;font-weight:400}
                .wpap-earn .money{color:#158a55;font-weight:700}
                .wpap-earn .note{font-size:11.5px;color:#8a8f98;margin-top:9px;line-height:1.5}
            </style>
            <div class="wpap-earn">
                <div class="ctl">
                    <label>Monthly pageviews<input type="text" id="wpap-earn-pv" value="120,000" inputmode="numeric"></label>
                    <label>Viewable eCPM ($ per 1,000)<input type="number" id="wpap-earn-ecpm" value="5" min="0" step="0.5"></label>
                </div>
                <table>
                    <thead><tr><th>Ad mode</th><th>Ads / page</th><th>Reader load</th><th>Est. RPM</th><th>Est. monthly</th><th></th></tr></thead>
                    <tbody id="wpap-earn-body"></tbody>
                </table>
                <p class="note">RPM = revenue per 1,000 pageviews. &ldquo;Reader load&rdquo; is how heavy the page feels. Real earnings depend on your niche, country, season and Google&rsquo;s auction &mdash; set the eCPM to your own number. More ads earn more per page but ask more of the reader. <strong>Maximized&rsquo;s total includes Auto Ads</strong> &mdash; Apply sets the manual slots, but paste your Auto Ads code in the field below to actually turn those extra ads on.</p>
            </div>
            <script>
            (function(){
                var body=document.getElementById('wpap-earn-body');
                if(!body){return;}
                function el(n){return document.querySelector('[name="'+n+'"]');}
                function pnum(s){var n=parseInt((''+s).replace(/[^0-9]/g,''),10);return isNaN(n)?0:n;}
                var CAT={
                    header:{v:.68,p:.95}, top:{v:.72,p:1.0}, incontent:{v:.66,p:1.15},
                    repeat:{v:.50,p:1.0}, bottom:{v:.46,p:.9}, sidebar:{v:.60,p:.85}, footer:{v:.30,p:.7}
                };
                var DECAY=[.54,.47,.41,.36,.32];
                var MODES={
                    minimal:{label:'Minimal',load:'Light',auto:false,slots:['incontent','bottom'],
                        apply:{enabled:1,top:0,inc:1,incAfter:3,rep:0,repEvery:4,repMax:3,bot:1,zh:0,zs:0,zf:0,gap:2,max:0}},
                    balanced:{label:'Balanced',load:'Medium',auto:false,slots:['top','incontent','repeat','repeat','bottom','sidebar'],
                        apply:{enabled:1,top:1,inc:1,incAfter:2,rep:1,repEvery:4,repMax:2,bot:1,zh:0,zs:1,zf:0,gap:1,max:0}},
                    max:{label:'Maximized',load:'Heavy',auto:true,slots:['header','top','incontent','repeat','repeat','repeat','repeat','bottom','sidebar','footer'],
                        apply:{enabled:1,top:1,inc:1,incAfter:2,rep:1,repEvery:3,repMax:4,bot:1,zh:1,zs:1,zf:1,gap:1,max:0}}
                };
                function money(n){return '$'+Math.round(n).toLocaleString('en-US');}
                function calc(mode,pv,ecpm){
                    var total=0,ri=0;
                    mode.slots.forEach(function(k){
                        var c=CAT[k]; var view=(k==='repeat')?DECAY[Math.min(ri++,DECAY.length-1)]:c.v;
                        total+= pv*view*(ecpm*c.p)/1000;
                    });
                    if(mode.auto){ total*=1.14; }
                    var count=mode.slots.length+(mode.auto?1:0);
                    var rpm=pv>0?total/pv*1000:0;
                    return {count:count,rpm:rpm,total:total};
                }
                function render(){
                    var pv=pnum(document.getElementById('wpap-earn-pv').value);
                    var ecpm=Math.max(0,parseFloat(document.getElementById('wpap-earn-ecpm').value)||0);
                    var html='';
                    Object.keys(MODES).forEach(function(key){
                        var m=MODES[key]; var r=calc(m,pv,ecpm);
                        html+='<tr><td class="m">'+m.label+(m.auto?' <span class="sub">+ Auto Ads</span>':'')+'</td>'+
                              '<td>'+r.count+'</td><td>'+m.load+'</td><td>$'+r.rpm.toFixed(2)+'</td>'+
                              '<td class="money">'+money(r.total)+'</td>'+
                              '<td><button type="button" class="button button-small" data-wpapmode="'+key+'">Apply</button></td></tr>';
                    });
                    body.innerHTML=html;
                }
                function applyMode(key){
                    var a=MODES[key].apply;
                    function setc(name,val){var e=el(name);if(e){e.checked=!!val;}}
                    function setn(name,val){var e=el(name);if(e){e.value=val;}}
                    setc('wpap_ads_enabled',a.enabled);
                    setc('wpap_ads_top_on',a.top);
                    setc('wpap_ads_inc_on',a.inc); setn('wpap_ads_inc_after',a.incAfter);
                    setc('wpap_ads_rep_on',a.rep); setn('wpap_ads_rep_every',a.repEvery); setn('wpap_ads_rep_max',a.repMax);
                    setc('wpap_ads_bot_on',a.bot);
                    setc('wpap_ads_zone_header_on',a.zh); setc('wpap_ads_zone_sidebar_on',a.zs); setc('wpap_ads_zone_footer_on',a.zf);
                    setn('wpap_ads_min_gap',a.gap); setn('wpap_ads_max_ads',a.max);
                    var m=el('wpap_ads_enabled'); if(m){ m.dispatchEvent(new Event('change',{bubbles:true})); }
                    var host=document.getElementById('wpap-adprev'); if(host&&host.scrollIntoView){ host.scrollIntoView({behavior:'smooth',block:'center'}); }
                }
                document.getElementById('wpap-earn-pv').addEventListener('input',render);
                document.getElementById('wpap-earn-ecpm').addEventListener('input',render);
                body.addEventListener('click',function(e){
                    var b=e.target.closest?e.target.closest('[data-wpapmode]'):null;
                    if(b){ applyMode(b.getAttribute('data-wpapmode')); }
                });
                render();
            })();
            </script>

            <table class="form-table">
                <tr>
                    <th scope="row">Enable ads</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_enabled" value="1" <?php checked( ! empty( $ads['enabled'] ) ); ?>> Master switch — print ads on the front end</label><br>
                        <label style="display:inline-block;margin-top:6px;"><input type="checkbox" name="wpap_ads_scope_all" value="1" <?php checked( ! empty( $ads['scope_all'] ) ); ?>> Apply to <strong>all</strong> posts (off = only posts this plugin created)</label><br>
                        <label style="display:inline-block;margin-top:6px;"><input type="checkbox" name="wpap_ads_label" value="1" <?php checked( ! empty( $ads['label'] ) ); ?>> Show a small &ldquo;Advertisement&rdquo; label above each ad</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Auto Ads code</th>
                    <td>
                        <textarea name="wpap_ads_auto_code" rows="4" class="large-text code" placeholder="&lt;script async src=&quot;https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXX&quot; crossorigin=&quot;anonymous&quot;&gt;&lt;/script&gt;"><?php echo esc_textarea( (string) $ads['auto_code'] ); ?></textarea>
                        <p class="description">Your Auto Ads snippet from AdSense. This snippet is also the loader for the manual units below, so pasting it once is enough.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">In-content density</th>
                    <td>
                        Min paragraphs between ads: <input type="number" name="wpap_ads_min_gap" min="0" max="20" step="1" value="<?php echo esc_attr( (string) $ads['min_gap'] ); ?>" class="small-text" />
                        &nbsp;&nbsp; Max in-content ads per post (0 = no limit): <input type="number" name="wpap_ads_max_ads" min="0" max="20" step="1" value="<?php echo esc_attr( (string) $ads['max_ads'] ); ?>" class="small-text" />
                    </td>
                </tr>
            </table>

            <h3 style="margin-top:20px;">Manual slots</h3>
            <table class="form-table">
                <tr>
                    <th scope="row">Top of article</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_top_on" value="1" <?php checked( ! empty( $ads['slots']['top']['on'] ) ); ?>> On</label>
                        <textarea name="wpap_ads_top_code" rows="3" class="large-text code" placeholder="Paste an AdSense unit…"><?php echo esc_textarea( (string) $ads['slots']['top']['code'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">In-content (after a paragraph)</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_inc_on" value="1" <?php checked( ! empty( $ads['slots']['incontent']['on'] ) ); ?>> On</label>
                        &nbsp; after paragraph <input type="number" name="wpap_ads_inc_after" min="1" max="50" step="1" value="<?php echo esc_attr( (string) $ads['slots']['incontent']['after'] ); ?>" class="small-text" />
                        <textarea name="wpap_ads_inc_code" rows="3" class="large-text code" placeholder="Use an In-article unit…"><?php echo esc_textarea( (string) $ads['slots']['incontent']['code'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Repeating in-content</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_rep_on" value="1" <?php checked( ! empty( $ads['slots']['repeat']['on'] ) ); ?>> On</label>
                        &nbsp; every <input type="number" name="wpap_ads_rep_every" min="1" max="50" step="1" value="<?php echo esc_attr( (string) $ads['slots']['repeat']['every'] ); ?>" class="small-text" /> paragraphs,
                        max <input type="number" name="wpap_ads_rep_max" min="1" max="10" step="1" value="<?php echo esc_attr( (string) $ads['slots']['repeat']['max'] ); ?>" class="small-text" /> times
                        <textarea name="wpap_ads_rep_code" rows="3" class="large-text code" placeholder="Use an In-article unit…"><?php echo esc_textarea( (string) $ads['slots']['repeat']['code'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Bottom of article</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_bot_on" value="1" <?php checked( ! empty( $ads['slots']['bottom']['on'] ) ); ?>> On</label>
                        <textarea name="wpap_ads_bot_code" rows="3" class="large-text code" placeholder="Paste an AdSense unit…"><?php echo esc_textarea( (string) $ads['slots']['bottom']['code'] ); ?></textarea>
                    </td>
                </tr>
            </table>

            <h3 style="margin-top:20px;">Page zones (header / sidebar / footer)</h3>
            <p class="description" style="max-width:820px;">Filled into a compatible theme's ad zones (via <code>wpap_zone_html()</code>). If your theme doesn't call it, these are simply unused.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Header</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_zone_header_on" value="1" <?php checked( ! empty( $ads['zones']['header']['on'] ) ); ?>> On</label>
                        <textarea name="wpap_ads_zone_header_code" rows="2" class="large-text code"><?php echo esc_textarea( (string) $ads['zones']['header']['code'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Sidebar</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_zone_sidebar_on" value="1" <?php checked( ! empty( $ads['zones']['sidebar']['on'] ) ); ?>> On</label>
                        <textarea name="wpap_ads_zone_sidebar_code" rows="2" class="large-text code"><?php echo esc_textarea( (string) $ads['zones']['sidebar']['code'] ); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Footer</th>
                    <td>
                        <label><input type="checkbox" name="wpap_ads_zone_footer_on" value="1" <?php checked( ! empty( $ads['zones']['footer']['on'] ) ); ?>> On</label>
                        <textarea name="wpap_ads_zone_footer_code" rows="2" class="large-text code"><?php echo esc_textarea( (string) $ads['zones']['footer']['code'] ); ?></textarea>
                    </td>
                </tr>
            </table>

            <?php /* ── Content options (publishing guards) ── */ ?>
            <?php
            $copts_ui = get_option( 'wpap_content_opts', array() );
            if ( ! is_array( $copts_ui ) ) { $copts_ui = array(); }
            ?>
            <h2 style="margin-top:32px;">Content options</h2>
            <p class="description" style="max-width:760px;">Optional safety nets for Direct Publish &amp; the Google-Sheet automation. All off by default.</p>
            <table class="form-table">
                <tr>
                    <th scope="row">Skip duplicate titles</th>
                    <td><label><input type="checkbox" name="wpap_skip_dupe_titles" value="1" <?php checked( ! empty( $copts_ui['skip_dupe_titles'] ) ); ?> /> Don't publish a post if one with the same title already exists</label>
                        <p class="description">Protects against creating duplicates when you re-upload the same file/batch.</p></td>
                </tr>
                <tr>
                    <th scope="row">Minimum word count</th>
                    <td><input type="number" name="wpap_min_words" min="0" max="5000" step="10" class="small-text" value="<?php echo esc_attr( (string) ( $copts_ui['min_words'] ?? 0 ) ); ?>" /> words
                        <p class="description">Skip publishing a post whose body is below this. 0 = no minimum.</p></td>
                </tr>
                <tr>
                    <th scope="row">Disable comments</th>
                    <td><label><input type="checkbox" name="wpap_disable_comments" value="1" <?php checked( ! empty( $copts_ui['disable_comments'] ) ); ?> /> Close comments on posts this plugin publishes</label></td>
                </tr>
                <tr>
                    <th scope="row">Convert images to WebP</th>
                    <td><label><input type="checkbox" name="wpap_webp_enabled" value="1" <?php checked( '1' === (string) get_option( 'wpap_webp_enabled', '1' ) ); ?> /> Re-encode downloaded JPEG/PNG images to WebP &mdash; ~25&ndash;50% smaller, faster on mobile</label>
                        <p class="description">On by default. Applies to newly imported images (featured image + all sizes). Needs GD or Imagick with WebP support (standard on most hosts); if unsupported it keeps the original automatically, so publishing is never affected.</p></td>
                </tr>
            </table>

            <h2 style="margin-top:32px;">ads.txt (AdSense)</h2>
            <p class="description" style="max-width:760px;">
                Paste your ad networks' <code>ads.txt</code> lines. Served automatically at
                <code><?php echo esc_html( home_url( '/ads.txt' ) ); ?></code> unless a real
                <code>ads.txt</code> file already exists at your site root. Leave blank to disable.
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">ads.txt content</th>
                    <td><textarea name="wpap_ads_txt" rows="4" class="large-text code" placeholder="google.com, pub-0000000000000000, DIRECT, f08c47fec0942fa0"><?php echo esc_textarea( (string) get_option( 'wpap_ads_txt', '' ) ); ?></textarea></td>
                </tr>
            </table>

            <?php /* ── IndexNow (instant indexing) ── */ ?>
            <?php $in_on = wpap_indexnow_enabled(); $in_key = wpap_indexnow_key(); $in_last = get_option( 'wpap_indexnow_last', array() ); ?>
            <h2 style="margin-top:32px;">Instant indexing (IndexNow)</h2>
            <p class="description" style="max-width:760px;">
                Pings Bing, Yandex &amp; other IndexNow engines the moment a post goes live, so it's
                crawled in minutes instead of days. <strong>Google doesn't use IndexNow</strong> — for Google,
                submit your XML sitemap once in Search Console (Yoast/Rank Math generate it automatically).
            </p>
            <table class="form-table">
                <tr>
                    <th scope="row">Enable</th>
                    <td><label><input type="checkbox" name="wpap_indexnow_enabled" value="1" <?php checked( $in_on ); ?>> Ping on every new publish</label></td>
                </tr>
                <tr>
                    <th scope="row">Key file</th>
                    <td>
                        <a href="<?php echo esc_url( home_url( '/' . $in_key . '.txt' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( home_url( '/' . $in_key . '.txt' ) ); ?></a>
                        <p class="description">Served automatically — click to confirm it loads (it should show the key).</p>
                    </td>
                </tr>
                <?php if ( ! empty( $in_last['time'] ) ) : ?>
                <tr>
                    <th scope="row">Last ping</th>
                    <td><?php echo esc_html( human_time_diff( (int) $in_last['time'] ) . ' ago — ' . (int) ( $in_last['count'] ?? 0 ) . ' URL(s)' ); ?></td>
                </tr>
                <?php endif; ?>
            </table>

            <?php submit_button( 'Save Settings', 'primary', 'wpap_save' ); ?>
        </form>

        <?php /* ── Automation status + manual Run-now (own AJAX action, outside the settings form) ── */ ?>
        <h2 style="margin-top:32px;">Automation status</h2>
        <table class="form-table">
            <tr><th scope="row">Last run</th><td><?php echo esc_html( (string) ( $auto_status['last_run']   ?? '—' ) ); ?></td></tr>
            <tr><th scope="row">Rows found</th><td><?php echo esc_html( (string) ( $auto_status['rows_found'] ?? '—' ) ); ?></td></tr>
            <tr><th scope="row">Published</th><td><?php echo esc_html( (string) ( $auto_status['published']  ?? '—' ) ); ?></td></tr>
            <tr><th scope="row">Skipped</th><td><?php echo esc_html( (string) ( $auto_status['skipped']    ?? '—' ) ); ?></td></tr>
            <tr><th scope="row">Errors</th><td><?php echo esc_html( (string) ( $auto_status['errors']     ?? '—' ) ); ?></td></tr>
            <tr><th scope="row">Message</th><td><?php echo esc_html( (string) ( $auto_status['message']    ?? '' ) ); ?></td></tr>
        </table>
        <p>
            <button type="button" class="button button-secondary" id="wpap-auto-run-now">Run now</button>
            <span id="wpap-auto-run-msg" style="margin-left:10px;color:#555;"></span>
        </p>
        <script>
        (function(){
            var btn = document.getElementById('wpap-auto-run-now'),
                msg = document.getElementById('wpap-auto-run-msg');
            if(!btn) return;
            var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
                nonce   = <?php echo wp_json_encode( wp_create_nonce( 'wpap_nonce' ) ); ?>;
            btn.addEventListener('click', function(){
                btn.disabled = true; msg.textContent = 'Running…';
                var body = 'action=wpap_automation_run_now&nonce=' + encodeURIComponent(nonce);
                fetch(ajaxUrl, {
                    method:'POST', credentials:'same-origin',
                    headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8'},
                    body: body
                }).then(function(r){ return r.json(); }).then(function(res){
                    btn.disabled = false;
                    if(res && res.success){ msg.textContent = (res.data && res.data.message) ? res.data.message : 'Done.'; }
                    else { msg.textContent = 'Error: ' + ((res && res.data) ? res.data : 'unknown'); }
                }).catch(function(e){ btn.disabled = false; msg.textContent = 'Request failed: ' + e; });
            });
        })();
        </script>

        <h2 style="margin-top:32px;">Backup &amp; restore</h2>
        <?php
        if ( isset( $_GET['wpap_imported'] ) ) {
            echo '<div class="notice notice-success"><p>Settings imported.</p></div>';
        } elseif ( isset( $_GET['wpap_import_error'] ) ) {
            $err_map = array(
                'nofile' => 'No file was uploaded.',
                'size'   => 'The file is empty or larger than 512 KB.',
                'parse'  => 'That file isn\'t a valid WP Automator settings export.',
            );
            $ec = sanitize_key( wp_unslash( $_GET['wpap_import_error'] ) );
            echo '<div class="notice notice-error"><p>Import failed: ' . esc_html( $err_map[ $ec ] ?? 'unknown error.' ) . '</p></div>';
        }
        ?>
        <p class="description" style="max-width:760px;">
            Download every plugin setting — ad codes, all placements, publishing guards, automation (including your Google Sheet link), IndexNow, ads.txt, UTM link tracking — as one file, or restore it here (or on another site). <strong>Your API keys and license are never included</strong> in the export, so re-enter those after restoring.
        </p>
        <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:flex-start;margin-top:8px;">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="wpap_export_settings">
                <?php wp_nonce_field( 'wpap_export_settings' ); ?>
                <?php submit_button( 'Export settings', 'secondary', 'submit', false ); ?>
            </form>
            <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="hidden" name="action" value="wpap_import_settings">
                <?php wp_nonce_field( 'wpap_import_settings' ); ?>
                <input type="file" name="wpap_import_file" accept="application/json,.json" required>
                <?php submit_button( 'Import settings', 'secondary', 'submit', false ); ?>
            </form>
        </div>

        <p style="margin-top:24px;font-size:13px;color:#666;">Need help? Contact Oussama Hamri &mdash; <a href="https://wa.me/+212637122491" target="_blank" rel="noopener" style="color:#6366f1;font-weight:600;text-decoration:none;">Click Here</a></p>
    </div>
    <?php
}

/* ════════════════════════════════════════════
   5b. SCHEDULING + CONTENT-SPLIT HELPERS
   Shared by the Bulk Generator (wpap_ajax_process_title)
   and Direct Publish (wpap_ajax_bulk_publish_posts).
════════════════════════════════════════════ */

/**
 * Turn a "schedule window" (in hours) into a concrete post status + dates.
 *
 * When $window_hours is 0 (or less) the post publishes immediately. Otherwise
 * the post is set to WordPress's native "future" status with a RANDOM publish
 * time between +5 minutes and +$window_hours from now, so a batch of posts
 * spreads out naturally over the next few hours instead of all going live at
 * the exact same moment.
 *
 * @param float $window_hours Max hours ahead to schedule (0 = publish now).
 * @return array{status:string,date:string,date_gmt:string,ts_gmt:?int,label:string}
 */
function wpap_compute_schedule( $window_hours, $index = null, $total = null ) {
    $window_hours = (float) $window_hours;

    if ( $window_hours <= 0 ) {
        /* Publish-now resets the ordered-schedule anchor so the NEXT scheduled
           batch starts fresh from "now" instead of chaining behind old posts. */
        delete_transient( 'wpap_last_sched_ts' );
        return array(
            'status'   => 'publish',
            'date'     => current_time( 'mysql' ),
            'date_gmt' => current_time( 'mysql', 1 ),
            'ts_gmt'   => null,
            'label'    => '',
        );
    }

    $min_offset = 5 * MINUTE_IN_SECONDS;                        /* never exactly "now" */
    $max_offset = (int) round( $window_hours * HOUR_IN_SECONDS );
    if ( $max_offset < $min_offset ) {
        $max_offset = $min_offset;
    }

    /* ORDERED mode: when a batch index + total are supplied, spread the posts
       EVENLY across the window in submission order (post 1 earliest, post 2 next,
       …) so they go live in the exact order they were added — not random times.
       Without index/total we keep the old random spread (single posts, Sheet). */
    if ( null !== $index && null !== $total && (int) $total > 0 ) {
        $index = max( 0, (int) $index );
        $total = max( 1, (int) $total );
        $step  = ( $total <= 1 ) ? 0 : ( ( $max_offset - $min_offset ) / ( $total - 1 ) );
        $ts_gmt = time() + $min_offset + (int) round( $index * $step );

        /* Running high-water-mark: never schedule at or before the last post
           already scheduled. This keeps EVERY post at a distinct, increasing
           time — in submission order — both WITHIN a batch (even if the window
           is tiny) and ACROSS multiple "Publish All" batches (batch 2 continues
           after batch 1 instead of overlapping it). Cleared on Publish-now. */
        $gap  = ( $step >= 60 ) ? (int) round( $step ) : 60;   /* ≥ 1 min apart */
        $last = (int) get_transient( 'wpap_last_sched_ts' );
        if ( $last > 0 && $ts_gmt <= $last ) {
            $ts_gmt = $last + $gap;
        }
        set_transient( 'wpap_last_sched_ts', $ts_gmt, 2 * DAY_IN_SECONDS );
    } else {
        $ts_gmt = time() + wp_rand( $min_offset, $max_offset );   /* legacy random spread */
    }

    $date_gmt = gmdate( 'Y-m-d H:i:s', $ts_gmt );

    return array(
        'status'   => 'future',
        'date'     => get_date_from_gmt( $date_gmt ),           /* site-local time */
        'date_gmt' => $date_gmt,
        'ts_gmt'   => $ts_gmt,
        'label'    => get_date_from_gmt( $date_gmt, 'M j, Y g:i A' ),
    );
}

/**
 * Wire the auto-publish cron for a post inserted straight into $wpdb->posts.
 *
 * wp_insert_post() schedules the "publish_future_post" event automatically, but
 * the Bulk Generator writes directly to $wpdb->posts (to keep <!--nextpage-->
 * out of kses), which skips that wiring. Call this after such an insert or the
 * "future" post would sit there unpublished forever.
 *
 * @param int $post_id
 * @param int $ts_gmt  UTC timestamp the post should go live.
 */
function wpap_schedule_future_publish( $post_id, $ts_gmt ) {
    $post_id = (int) $post_id;
    $ts_gmt  = (int) $ts_gmt;
    if ( $post_id <= 0 || $ts_gmt <= 0 ) {
        return;
    }
    wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );
    wp_schedule_single_event( $ts_gmt, 'publish_future_post', array( $post_id ) );
}

/**
 * Split an HTML article body into N paginated parts joined with <!--nextpage-->.
 *
 * WordPress paginates a post wherever it finds a <!--nextpage--> comment, so
 * "click Next → jump to the next page" works out of the box once the markers
 * are in place. Splitting happens on block-level boundaries only (closing </p>
 * and heading tags), so tags are never cut in half, and parts are balanced by
 * length so the pages feel evenly sized.
 *
 * Safe fallbacks: a body that already contains <!--nextpage--> is returned
 * untouched; a body with too few blocks to reach $parts is split into as many
 * pages as it safely can (down to returning it unchanged).
 *
 * @param string $html
 * @param int    $parts Desired number of pages (1 = don't split).
 * @return string
 */
function wpap_split_content_into_parts( $html, $parts ) {
    $parts = (int) $parts;
    $html  = (string) $html;

    if ( $parts < 2 || '' === trim( $html ) ) {
        return $html;
    }
    /* Respect page breaks the author already placed. */
    if ( false !== strpos( $html, '<!--nextpage-->' ) ) {
        return $html;
    }

    /* Break the body into block-level chunks, keeping each closing tag intact.
       Only paragraphs and headings are split points — lists, figures, tables and
       other containers stay whole so nested tags never straddle a page break. */
    $tokens = preg_split(
        '#(</(?:p|h[1-6])>)#i',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    $blocks = array();
    for ( $i = 0, $n = count( $tokens ); $i < $n; $i += 2 ) {
        $chunk = $tokens[ $i ] . ( isset( $tokens[ $i + 1 ] ) ? $tokens[ $i + 1 ] : '' );
        if ( '' !== trim( $chunk ) ) {
            $blocks[] = $chunk;
        }
    }

    /* Fallback: no block tags found — split on blank lines, keeping the blank
       line attached to each chunk so paragraph spacing is never lost when two
       chunks end up on the same page. */
    if ( count( $blocks ) < 2 ) {
        $blocks = array();
        $bits   = preg_split( '/(\n\s*\n)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
        for ( $j = 0, $m = count( $bits ); $j < $m; $j += 2 ) {
            $chunk = $bits[ $j ] . ( isset( $bits[ $j + 1 ] ) ? $bits[ $j + 1 ] : '' );
            if ( '' !== trim( $chunk ) ) {
                $blocks[] = $chunk;
            }
        }
    }

    $total_blocks = count( $blocks );
    if ( $total_blocks < 2 ) {
        return $html;                       /* nothing safe to split on */
    }
    if ( $parts > $total_blocks ) {
        $parts = $total_blocks;             /* can't have more pages than blocks */
    }

    /* Balance blocks across the pages by character length (greedy fill). */
    $total_len = 0;
    foreach ( $blocks as $b ) {
        $total_len += strlen( $b );
    }
    $target = $total_len / $parts;

    $pages       = array();
    $current     = '';
    $current_len = 0;
    $blocks_left = $total_blocks;

    foreach ( $blocks as $b ) {
        $current     .= $b;
        $current_len += strlen( $b );
        $blocks_left--;

        $pages_done      = count( $pages );
        $parts_remaining = $parts - $pages_done - 1;          /* pages still to open after this one */
        $reached_target  = ( $current_len >= $target );
        $must_close_now  = ( $blocks_left <= $parts_remaining ); /* leave >=1 block per remaining page */

        if ( $pages_done < $parts - 1 && ( $must_close_now || ( $reached_target && $blocks_left > $parts_remaining ) ) ) {
            $pages[]     = $current;
            $current     = '';
            $current_len = 0;
        }
    }
    if ( '' !== $current ) {
        $pages[] = $current;
    }

    if ( count( $pages ) < 2 ) {
        return $html;
    }

    return implode( "\n\n<!--nextpage-->\n\n", $pages );
}

/* ════════════════════════════════════════════
   6. AJAX: UPLOAD FALLBACK IMAGE
   Accepts any image type (jpg/png/gif/webp/avif)
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_upload_fallback', 'wpap_ajax_upload_fallback' );
function wpap_ajax_upload_fallback() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    if ( empty( $_FILES['image'] ) ) wp_send_json_error( 'No file received.' );

    /* Allow all image mime types */
    add_filter( 'upload_mimes', function ( $mimes ) {
        $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
        $mimes['png']          = 'image/png';
        $mimes['gif']          = 'image/gif';
        $mimes['webp']         = 'image/webp';
        $mimes['avif']         = 'image/avif';
        return $mimes;
    } );
    add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
        if ( empty( $data['type'] ) ) {
            $info = wp_check_filetype( $filename, $mimes );
            if ( $info['type'] ) {
                $data['ext']  = $info['ext'];
                $data['type'] = $info['type'];
            }
        }
        return $data;
    }, 10, 4 );

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attach_id = media_handle_upload( 'image', 0 );
    if ( is_wp_error( $attach_id ) ) {
        wp_send_json_error( 'Upload error: ' . $attach_id->get_error_message() );
    }
    wp_send_json_success( array(
        'attach_id' => $attach_id,
        'image_url' => wp_get_attachment_url( $attach_id ),
    ) );
}

/**
 * Convert a freshly-downloaded JPEG/PNG temp file to WebP.
 *
 * WebP is typically 25–50% smaller than the same JPEG/PNG at equal quality, so
 * every imported image (featured + all generated sub-sizes WordPress derives
 * from it) becomes lighter — a direct mobile LCP + bandwidth win, done ONCE at
 * import with zero per-request cost.
 *
 * Design contract — this must NEVER break a publish:
 *   • Fail-safe: on ANY problem (no GD/Imagick, unreadable file, encode error,
 *     WebP not smaller) it returns the ORIGINAL file unchanged.
 *   • Format-only: it re-encodes the downloaded pixels; it does not touch the AI
 *     generation pipeline or any post content.
 *   • Skips GIF (would lose animation) and already-WebP/AVIF sources.
 *   • Disable with option `wpap_webp_enabled` = '0' or filter `wpap_convert_to_webp`.
 *
 * @param string $tmp       Path to the downloaded temp file.
 * @param string $file_name Intended upload file name (with extension).
 * @return array{0:string,1:string,2:string} [ tmp_path, file_name, mime ] — mime
 *         is 'image/webp' when converted, '' when the original was kept.
 */
function wpap_maybe_convert_image_to_webp( $tmp, $file_name ) {
    $original = array( $tmp, $file_name, '' );

    /* Master switch: option (default ON) then a filter, so it can be disabled
       without a code change and overridden per-import by developers. */
    if ( '1' !== (string) get_option( 'wpap_webp_enabled', '1' ) ) { return $original; }
    if ( ! apply_filters( 'wpap_convert_to_webp', true, $tmp, $file_name ) ) { return $original; }

    if ( ! is_string( $tmp ) || '' === $tmp || ! is_readable( $tmp ) ) { return $original; }

    /* Identify the real type from the file's bytes, not its extension. */
    $info = @getimagesize( $tmp );
    if ( ! is_array( $info ) || empty( $info[2] ) ) { return $original; }
    $type = (int) $info[2];
    if ( ! in_array( $type, array( IMAGETYPE_JPEG, IMAGETYPE_PNG ), true ) ) {
        return $original; /* leave GIF / WebP / AVIF alone */
    }

    $quality = (int) apply_filters( 'wpap_webp_quality', 82 );
    if ( $quality < 1 || $quality > 100 ) { $quality = 82; }

    $webp_tmp = preg_replace( '/\.[A-Za-z0-9]+$/', '', (string) $tmp );
    $webp_tmp = ( '' !== (string) $webp_tmp ? $webp_tmp : $tmp ) . '.webp';
    if ( $webp_tmp === $tmp ) { $webp_tmp = $tmp . '.webp'; }

    $ok = false;

    /* Prefer Imagick, fall back to GD — both are common on shared/VPS PHP. */
    if ( class_exists( 'Imagick' ) ) {
        try {
            $im = new Imagick();
            $im->readImage( $tmp );
            $im->setImageFormat( 'webp' );
            $im->setImageCompressionQuality( $quality );
            $im->stripImage();
            $ok = (bool) $im->writeImage( $webp_tmp );
            $im->clear();
            $im->destroy();
        } catch ( Exception $e ) {
            $ok = false;
        }
    }

    if ( ! $ok && function_exists( 'imagewebp' ) ) {
        $src = null;
        if ( IMAGETYPE_JPEG === $type && function_exists( 'imagecreatefromjpeg' ) ) {
            $src = @imagecreatefromjpeg( $tmp );
        } elseif ( IMAGETYPE_PNG === $type && function_exists( 'imagecreatefrompng' ) ) {
            $src = @imagecreatefrompng( $tmp );
            if ( $src ) {
                if ( function_exists( 'imagepalettetotruecolor' ) ) { @imagepalettetotruecolor( $src ); }
                imagealphablending( $src, false );
                imagesavealpha( $src, true );   /* keep PNG transparency */
            }
        }
        if ( $src ) {
            $ok = @imagewebp( $src, $webp_tmp, $quality );
            imagedestroy( $src );
        }
    }

    if ( ! $ok || ! is_readable( $webp_tmp ) ) {
        if ( is_file( $webp_tmp ) ) { @unlink( $webp_tmp ); }
        return $original;
    }

    /* Only adopt WebP when it is actually smaller (it usually is; small/flat PNGs
       occasionally aren't). Otherwise keep the source. */
    $src_size  = @filesize( $tmp );
    $webp_size = @filesize( $webp_tmp );
    if ( $webp_size && $src_size && $webp_size >= $src_size ) {
        @unlink( $webp_tmp );
        return $original;
    }

    @unlink( $tmp ); /* discard the original download; sideload the WebP instead */

    $new_name = preg_replace( '/\.[A-Za-z0-9]+$/', '', (string) $file_name );
    if ( '' === (string) $new_name ) { $new_name = 'image'; }
    $new_name .= '.webp';

    return array( $webp_tmp, $new_name, 'image/webp' );
}

/* ════════════════════════════════════════════
   7. AJAX: PROCESS SINGLE TITLE
   ┌─────────────────────────────────────────┐
   │ IMAGE PRIORITY LOGIC:                   │
   │ 1. Manual fallback selected → USE IT   │
   │    DO NOT call Gemini at all.           │
   │ 2. No manual image → call Gemini/AI    │
   └─────────────────────────────────────────┘
════════════════════════════════════════════ */
function wpap_import_remote_image_as_attachment( string $image_url, int $post_id, string $title ) {
    $image_url = esc_url_raw( trim( $image_url ) );
    if ( ! $image_url || ! wp_http_validate_url( $image_url ) ) {
        return new WP_Error( 'wpap_invalid_image_url', 'Invalid image URL.' );
    }

    /* Deduplicate: if this exact source URL was already imported, REUSE that
       attachment instead of downloading a second copy. Saves disk + bandwidth,
       speeds up re-uploads, and avoids hammering the source host. Each imported
       image is tagged with _wpap_source_image_url below. Filterable off. */
    if ( apply_filters( 'wpap_dedupe_images', true ) ) {
        $dupe = get_posts( array(
            'post_type'        => 'attachment',
            'post_status'      => 'inherit',
            'meta_key'         => '_wpap_source_image_url',
            'meta_value'       => $image_url,
            'fields'           => 'ids',
            'posts_per_page'   => 1,
            'no_found_rows'    => true,
            'suppress_filters' => false,
        ) );
        if ( ! empty( $dupe ) && (int) $dupe[0] > 0 && 'attachment' === get_post_type( (int) $dupe[0] ) ) {
            return (int) $dupe[0];   /* reuse the existing local copy */
        }
    }

    /* SSRF guard: block hosts that resolve to loopback / link-local / private ranges
       (notably 169.254.169.254 cloud-metadata) which wp_http_validate_url misses. */
    $host = wp_parse_url( $image_url, PHP_URL_HOST );
    $ips  = array();
    if ( $host ) {
        if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
            $ips[] = $host;
        } else {
            $records = @dns_get_record( $host, DNS_A + DNS_AAAA );
            if ( is_array( $records ) ) {
                foreach ( $records as $r ) {
                    if ( ! empty( $r['ip'] ) )   { $ips[] = $r['ip']; }
                    if ( ! empty( $r['ipv6'] ) ) { $ips[] = $r['ipv6']; }
                }
            }
            $v4 = gethostbynamel( $host );
            if ( is_array( $v4 ) ) { $ips = array_merge( $ips, $v4 ); }
        }
    }
    foreach ( $ips as $ip ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
            return new WP_Error( 'wpap_blocked_host', 'Image host resolves to a private or reserved address and was blocked.' );
        }
    }

    static $mime_filters_added = false;
    if ( ! $mime_filters_added ) {
        add_filter( 'upload_mimes', function ( $mimes ) {
            $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
            $mimes['png']          = 'image/png';
            $mimes['gif']          = 'image/gif';
            $mimes['webp']         = 'image/webp';
            $mimes['avif']         = 'image/avif';
            return $mimes;
        } );
        add_filter( 'wp_check_filetype_and_ext', function ( $data, $file, $filename, $mimes ) {
            if ( empty( $data['type'] ) ) {
                $info = wp_check_filetype( $filename, $mimes );
                if ( $info['type'] ) {
                    $data['ext']  = $info['ext'];
                    $data['type'] = $info['type'];
                }
            }
            return $data;
        }, 10, 4 );
        $mime_filters_added = true;
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    /* Download the image into the local Media Library. Retry once on a transient
       failure (a slow/blocked fetch from the image host) so the copy lands here
       and the post gets a proper LOCAL featured image instead of no image. */
    $tmp = download_url( $image_url, 60 );
    if ( is_wp_error( $tmp ) ) {
        sleep( 2 );
        $tmp = download_url( $image_url, 90 );   /* second attempt, longer timeout */
    }
    if ( is_wp_error( $tmp ) ) {
        return $tmp;
    }

    $file_name = wp_basename( (string) wp_parse_url( $image_url, PHP_URL_PATH ) );
    if ( ! $file_name ) {
        $file_name = sanitize_file_name( sanitize_title( $title ?: 'bulk-import-image' ) . '.jpg' );
    }

    /* Re-encode JPEG/PNG downloads to WebP (~25–50% smaller → faster mobile LCP).
       Fail-safe: keeps the original file on any problem, so this can never block
       a publish. Records the source URL below for de-duplication either way. */
    $webp      = wpap_maybe_convert_image_to_webp( $tmp, $file_name );
    $tmp       = $webp[0];
    $file_name = $webp[1];

    $file_array = array(
        'name'     => $file_name,
        'tmp_name' => $tmp,
    );
    if ( '' !== $webp[2] ) {
        $file_array['type'] = $webp[2];   /* image/webp */
    }

    $attach_id = media_handle_sideload( $file_array, $post_id, $title );
    if ( is_wp_error( $attach_id ) ) {
        @unlink( $tmp );
        return $attach_id;
    }

    update_post_meta( $attach_id, '_wpap_source_image_url', $image_url );

    return $attach_id;
}

add_action( 'wp_ajax_wpap_bulk_import_remote_images', 'wpap_ajax_bulk_import_remote_images' );
function wpap_ajax_bulk_import_remote_images() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    @set_time_limit( 300 );
    @ignore_user_abort( true );
    @ini_set( 'max_execution_time', '300' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $raw_items = trim( (string) wp_unslash( $_POST['items'] ?? '' ) );
    if ( '' === $raw_items ) {
        wp_send_json_error( 'Paste rows or URLs first.' );
    }

    if ( strlen( $raw_items ) > wpap_bulk_max_bytes() ) {
        wp_send_json_error( sprintf(
            'Payload too large (%d KB). Maximum is %d KB — split it into smaller batches.',
            (int) round( strlen( $raw_items ) / 1024 ),
            (int) round( wpap_bulk_max_bytes() / 1024 )
        ) );
    }

    $payload = json_decode( $raw_items, true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
        wp_send_json_error( 'Invalid input. Expected a JSON array of rows.' );
    }

    if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
        $payload = $payload['items'];
    } elseif ( isset( $payload['title'] ) || isset( $payload['caption'] ) || isset( $payload['imageUrl'] ) || isset( $payload['image_url'] ) || isset( $payload['image'] ) ) {
        $payload = array( $payload );
    }

    $items = array_values( array_filter( $payload, function ( $item ) {
        return is_array( $item ) || is_object( $item ) || is_scalar( $item );
    } ) );
    if ( empty( $items ) ) {
        wp_send_json_error( 'No valid rows found.' );
    }

    $created  = array();
    $messages = array();

    /* Cap batch size to bound worker time on a single request. */
    $wpap_max_items = wpap_bulk_max_items();
    if ( count( $items ) > $wpap_max_items ) {
        $messages[] = sprintf(
            '%d row(s) ignored: this batch is capped at %d items per request.',
            count( $items ) - $wpap_max_items,
            $wpap_max_items
        );
        $items = array_slice( $items, 0, $wpap_max_items );
    }

    foreach ( $items as $index => $raw_item ) {
        $row_number = $index + 1;

        $item = is_array( $raw_item ) ? $raw_item : ( is_object( $raw_item ) ? get_object_vars( $raw_item ) : array() );
        if ( is_scalar( $raw_item ) ) {
            $scalar_value = trim( (string) $raw_item );
            if ( wp_http_validate_url( $scalar_value ) ) {
                $item['imageUrl'] = $scalar_value;
            } else {
                $item['title'] = $scalar_value;
            }
        }

        $title_raw = $item['title'] ?? $item['caption'] ?? $item['name'] ?? '';
        $title     = sanitize_text_field( wp_unslash( is_scalar( $title_raw ) ? (string) $title_raw : '' ) );

        $image_raw = $item['imageUrl'] ?? $item['image_url'] ?? $item['image'] ?? '';
        $image_raw = is_scalar( $image_raw ) ? trim( (string) $image_raw ) : '';
        $image_url = $image_raw ? esc_url_raw( $image_raw ) : '';

        if ( $image_raw && ! wp_http_validate_url( $image_raw ) ) {
            $messages[] = sprintf( 'Row %d skipped: invalid image URL.', $row_number );
            continue;
        }

        if ( ! $title && $image_url ) {
            $title = sprintf( 'Imported Image %d', $row_number );
        } elseif ( ! $title ) {
            $title = sprintf( 'Imported Item %d', $row_number );
        }

        $attach_id = 0;
        if ( $image_url ) {
            /* Isolate each import — a fatal on one image (OOM in thumbnailing, a
               hostile file) must not abort the whole batch. */
            try {
                $attach_id = wpap_import_remote_image_as_attachment( $image_url, 0, $title );
            } catch ( \Throwable $e ) {
                error_log( '[Automation Hamri] Image import crashed on row ' . $row_number . ': ' . $e->getMessage() );
                $messages[] = sprintf( 'Row %d skipped: image import failed (%s).', $row_number, $e->getMessage() );
                continue;
            }
            if ( is_wp_error( $attach_id ) || ! $attach_id ) {
                $messages[] = sprintf(
                    'Row %d skipped: %s',
                    $row_number,
                    is_wp_error( $attach_id ) ? $attach_id->get_error_message() : 'image download failed.'
                );
                continue;
            }
        }

        $created[] = array(
            'attach_id' => (int) $attach_id,
            'image_url' => $attach_id ? (string) wp_get_attachment_url( $attach_id ) : '',
            'source_url' => $image_url,
            'title'     => $title,
            'label'     => $attach_id ? ( wp_basename( (string) wp_parse_url( $image_url, PHP_URL_PATH ) ) ?: $title ) : 'No image',
        );
    }

    if ( empty( $created ) ) {
        wp_send_json_error( array(
            'message'  => 'No images were imported.',
            'messages' => $messages,
        ) );
    }

    wp_send_json_success( array(
        'created'  => count( $created ),
        'skipped'  => count( $items ) - count( $created ),
        'total'    => count( $items ),
        'messages' => $messages,
        'items'    => $created,
    ) );
}

add_action( 'wp_ajax_wpap_bulk_import_distribution', 'wpap_ajax_bulk_import_distribution' );
function wpap_ajax_bulk_import_distribution() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    @set_time_limit( 300 );
    @ignore_user_abort( true );
    @ini_set( 'max_execution_time', '300' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $raw_items = trim( (string) wp_unslash( $_POST['items'] ?? '' ) );
    if ( '' === $raw_items ) {
        wp_send_json_error( 'Paste a JSON array first.' );
    }

    if ( strlen( $raw_items ) > wpap_bulk_max_bytes() ) {
        wp_send_json_error( sprintf(
            'Payload too large (%d KB). Maximum is %d KB — split it into smaller batches.',
            (int) round( strlen( $raw_items ) / 1024 ),
            (int) round( wpap_bulk_max_bytes() / 1024 )
        ) );
    }

    $payload = json_decode( $raw_items, true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
        wp_send_json_error( 'Invalid JSON. Expected an array like [{"caption":"Hello","comment":"link","imageUrl":"https://..."}].' );
    }

    if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
        $payload = $payload['items'];
    } elseif ( isset( $payload['title'] ) || isset( $payload['caption'] ) || isset( $payload['comment'] ) || isset( $payload['imageUrl'] ) || isset( $payload['image_url'] ) || isset( $payload['image'] ) ) {
        $payload = array( $payload );
    }

    $items = array_values( array_filter( $payload, 'is_array' ) );
    if ( empty( $items ) ) {
        wp_send_json_error( 'No valid items found in the JSON payload.' );
    }

    global $wpdb;
    $table    = $wpdb->prefix . WPAP_TABLE;
    $created  = array();
    $messages = array();

    /* Cap batch size to bound worker time on a single request. */
    $wpap_max_items = wpap_bulk_max_items();
    if ( count( $items ) > $wpap_max_items ) {
        $messages[] = sprintf(
            '%d item(s) ignored: this batch is capped at %d items per request.',
            count( $items ) - $wpap_max_items,
            $wpap_max_items
        );
        $items = array_slice( $items, 0, $wpap_max_items );
    }

    foreach ( $items as $index => $item ) {
        $row_number = $index + 1;

        $image_raw = $item['imageUrl'] ?? $item['image_url'] ?? $item['image'] ?? '';
        $image_url = esc_url_raw( trim( is_scalar( $image_raw ) ? (string) $image_raw : '' ) );

        $caption_raw = $item['caption'] ?? $item['title'] ?? '';
        $title       = sanitize_text_field( wp_unslash( is_scalar( $caption_raw ) ? (string) $caption_raw : '' ) );

        $hook_raw = $item['comment'] ?? $item['fb_text'] ?? $item['hook'] ?? '';
        $hook_text = sanitize_textarea_field( wp_unslash( is_scalar( $hook_raw ) ? (string) $hook_raw : '' ) );

        if ( ! $title && $hook_text ) {
            $title = wp_trim_words( wp_strip_all_tags( $hook_text ), 8, '' );
        }
        if ( ! $title ) {
            $title = sprintf( 'Imported Item %d', $row_number );
        }

        if ( ! $hook_text ) {
            $hook_text = $title;
        }

        $stored_image_url = $image_url;

        $post_id   = wp_insert_post( array(
            'post_author'    => get_current_user_id(),
            'post_title'     => $title,
            'post_content'   => $hook_text,
            'post_status'    => 'publish',
            'post_type'      => 'post',
            'comment_status' => 'open',
            'ping_status'    => 'open',
        ), true );

        if ( is_wp_error( $post_id ) || ! $post_id ) {
            $messages[] = sprintf(
                'Row %d skipped: %s',
                $row_number,
                is_wp_error( $post_id ) ? $post_id->get_error_message() : 'post creation failed.'
            );
            continue;
        }

        $post_url   = get_permalink( $post_id );
        $smart_link = $post_url;   /* clean permalink (no ?v=) so shared links behave like a manual post */

        update_post_meta( $post_id, '_wpap_image_url',  $stored_image_url );
        update_post_meta( $post_id, '_wpap_fb_hook',    $hook_text );
        update_post_meta( $post_id, 'ah_social_hook',   $hook_text );
        update_post_meta( $post_id, '_wpap_smart_link', $smart_link );

        $saved = $wpdb->insert( $table, array(
            'post_id'    => $post_id,
            'title'      => $title,
            'post_url'   => $post_url,
            'image_url'  => $stored_image_url,
            'fb_text'    => $hook_text,
            'fb_post_id' => '',
            'smart_link' => $smart_link,
        ) );

        if ( false === $saved ) {
            wp_delete_post( $post_id, true );
            $messages[] = sprintf( 'Row %d skipped: unable to save the distribution record.', $row_number );
            continue;
        }

        clean_post_cache( $post_id );

        $created[] = array(
            'id'         => (int) $wpdb->insert_id,
            'post_id'    => (int) $post_id,
            'title'      => $title,
            'post_url'   => $post_url,
            'image_url'  => $stored_image_url,
            'smart_link' => $smart_link,
        );
    }

    if ( empty( $created ) ) {
        wp_send_json_error( array(
            'message'  => 'No items were imported.',
            'messages' => $messages,
        ) );
    }

    wp_send_json_success( array(
        'created'  => count( $created ),
        'skipped'  => count( $items ) - count( $created ),
        'total'    => count( $items ),
        'messages' => $messages,
        'rows'     => $created,
    ) );
}

/* ════════════════════════════════════════════
   AJAX: BULK PUBLISH POSTS (NO EXTERNAL API)
   Publishes ready-made posts straight from JSON:
   [{ "title", "imageUrl", "content", "hook" }]
   - No Claude / Gemini calls.
   - Downloads imageUrl → sets it as the featured image.
   - Writes the same meta + distribution row the AI path
     does, so rows appear in the Distribution Hub and
     export as { caption:hook, comment:smart_link, imageUrl }.
════════════════════════════════════════════ */
/**
 * Publish ONE ready-made article (no AI). Shared by Direct Publish (AJAX)
 * and the Google-Sheet automation. Returns the new post ID (int) on success
 * or a WP_Error on failure.
 *
 * $item keys: title, content, imageUrl|image_url|image, hook|fb_text|comment,
 *             category (name or id, optional), parts (int, optional)
 * $opts keys: default_parts (int, default 1), schedule_window (float hrs, default 0),
 *             default_category (string|int, optional),
 *             source_key (string, optional — stored as _wpap_source_key meta for dedup)
 * @return int|WP_Error
 */

/* True if a non-trashed post with this exact title already exists. Uses WP_Query
   (get_page_by_title is deprecated in WP 6.2) — exact post_title match, ids only. */
function wpap_post_title_exists( $title ) {
    $title = trim( (string) $title );
    if ( '' === $title ) { return false; }
    $q = new WP_Query( array(
        'post_type'      => 'post',
        'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
        'title'          => $title,
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'no_found_rows'  => true,
        'cache_results'  => false,
    ) );
    $found = ! empty( $q->posts );
    wp_reset_postdata();
    return $found;
}

function wpap_publish_article( array $item, array $opts = array() ) {
    global $wpdb;
    $table = $wpdb->prefix . WPAP_TABLE;

    /* ── options ── */
    $default_parts    = isset( $opts['default_parts'] ) ? intval( $opts['default_parts'] ) : 1;
    $schedule_window  = isset( $opts['schedule_window'] ) ? (float) $opts['schedule_window'] : 0;
    /* Ordered scheduling: the batch position + size, so scheduled posts go live
       in submission order (spread evenly across the window) instead of random. */
    $schedule_index   = isset( $opts['schedule_index'] ) ? (int) $opts['schedule_index'] : null;
    $schedule_total   = isset( $opts['schedule_total'] ) ? (int) $opts['schedule_total'] : null;
    $default_category = ( isset( $opts['default_category'] ) && is_scalar( $opts['default_category'] ) )
        ? trim( (string) $opts['default_category'] ) : '';
    $source_key       = ( isset( $opts['source_key'] ) && is_scalar( $opts['source_key'] ) )
        ? (string) $opts['source_key'] : '';
    $author           = isset( $opts['author'] ) ? (int) $opts['author'] : get_current_user_id();
    $force_kses        = ! empty( $opts['force_kses'] );   /* automation forces kses on external content */

    /* ── title (legacy 'caption' fallback preserved for parity) ── */
    $title_raw = $item['title'] ?? $item['caption'] ?? '';
    $title     = sanitize_text_field( wp_unslash( is_scalar( $title_raw ) ? (string) $title_raw : '' ) );

    /* ── content (article body, HTML allowed — WP filters by capability) ── */
    $content_raw = $item['content'] ?? $item['post_content'] ?? $item['body'] ?? '';
    $content     = is_scalar( $content_raw ) ? (string) wp_unslash( $content_raw ) : '';

    /* ── hook (Facebook caption) ── */
    $hook_raw = $item['hook'] ?? $item['fb_text'] ?? $item['comment'] ?? '';
    $hook     = sanitize_textarea_field( wp_unslash( is_scalar( $hook_raw ) ? (string) $hook_raw : '' ) );

    /* ── image url ── */
    $image_raw = $item['imageUrl'] ?? $item['image_url'] ?? $item['image'] ?? '';
    $image_raw = is_scalar( $image_raw ) ? trim( (string) $image_raw ) : '';

    /* ── optional SEO metadata (per-item; the description auto-derives when absent) ── */
    $meta_desc_raw  = $item['metaDescription'] ?? $item['description'] ?? $item['excerpt'] ?? '';
    $meta_desc_in   = is_scalar( $meta_desc_raw ) ? sanitize_text_field( wp_unslash( (string) $meta_desc_raw ) ) : '';
    $meta_title_raw = $item['metaTitle'] ?? $item['seo_title'] ?? '';
    $meta_title     = is_scalar( $meta_title_raw ) ? sanitize_text_field( wp_unslash( (string) $meta_title_raw ) ) : '';
    $focus_kw_raw   = $item['focusKeyword'] ?? $item['keyword'] ?? '';
    $focus_kw       = is_scalar( $focus_kw_raw ) ? sanitize_text_field( wp_unslash( (string) $focus_kw_raw ) ) : '';
    $tags_raw       = $item['tags'] ?? '';

    if ( '' === $title && '' === trim( wp_strip_all_tags( $content ) ) ) {
        return new WP_Error( 'wpap_empty', 'needs at least a title or content.' );
    }
    if ( '' === $title ) {
        $title = wp_trim_words( wp_strip_all_tags( $content ), 8, '' );
        if ( '' === $title ) { $title = 'Untitled'; }
    }

    /* ── optional content guards (Settings → Content options; all OFF by default) ── */
    $copts = get_option( 'wpap_content_opts', array() );
    if ( ! is_array( $copts ) ) { $copts = array(); }

    /* Skip duplicate titles: don't create a second post with a title that already
       exists — protects against re-uploading the same file/batch. Only for
       manual/Direct-Publish (source_key empty); the Sheet automation has its own
       row-key dedup and we don't want to fight its retry logic. */
    if ( ! empty( $copts['skip_dupe_titles'] ) && '' === $source_key && wpap_post_title_exists( $title ) ) {
        return new WP_Error( 'wpap_dupe', sprintf( 'skipped: a post titled "%s" already exists.', $title ) );
    }

    /* Minimum word count: skip thin bodies (Unicode-aware; falls back safely). */
    $min_words = (int) ( $copts['min_words'] ?? 0 );
    if ( $min_words > 0 ) {
        $wc = preg_match_all( '/[\p{L}\p{N}]+/u', wp_strip_all_tags( $content ), $m );
        if ( false === $wc ) { $wc = str_word_count( wp_strip_all_tags( $content ) ); }   /* /u failed on bad UTF-8 */
        if ( (int) $wc < $min_words ) {
            return new WP_Error( 'wpap_thin', sprintf( 'skipped: %d words is below the %d-word minimum.', (int) $wc, $min_words ) );
        }
    }

    /* Comments: close them on published posts if the owner opted in (spam hygiene). */
    $comment_status = ! empty( $copts['disable_comments'] ) ? 'closed' : get_default_comment_status( 'post' );

    /* ── split the body into pages (per-item "parts" overrides the global choice) ── */
    $item_parts = isset( $item['parts'] ) ? intval( $item['parts'] ) : $default_parts;
    if ( $item_parts < 1 )  { $item_parts = 1; }
    if ( $item_parts > 10 ) { $item_parts = 10; }
    $content_split = wpap_split_content_into_parts( $content, $item_parts );

    /* Sanitize the body unless this user may post raw HTML — OR the caller forces
       it (the automation does, because Sheet content is outside the trust boundary).
       kses runs per page so the <!--nextpage--> markers survive and the raw
       $wpdb->update below can't smuggle scripts. */
    if ( $force_kses || ! current_user_can( 'unfiltered_html' ) ) {
        $content_split = implode( '<!--nextpage-->', array_map(
            'wp_kses_post',
            explode( '<!--nextpage-->', $content_split )
        ) );
    }

    /* ── decide publish time (immediate, or a random slot in the next hours) ── */
    $sched = wpap_compute_schedule( $schedule_window, $schedule_index, $schedule_total );

    /* Meta description: use the supplied one, else auto-derive from the content. */
    $description = ( '' !== $meta_desc_in ) ? $meta_desc_in : wpap_make_excerpt( $content );

    /* ── create the post with REAL content (no AI) ── */
    $post_id = wp_insert_post( array(
        'post_author'   => $author,
        'post_title'    => wp_slash( $title ),
        'post_content'  => wp_slash( $content_split ),
        'post_excerpt'  => wp_slash( $description ),   /* clean meta description */
        'post_status'    => $sched['status'],
        'post_date'      => $sched['date'],
        'post_date_gmt'  => $sched['date_gmt'],
        'post_type'      => 'post',
        'comment_status' => $comment_status,
    ), true );

    if ( is_wp_error( $post_id ) ) {
        return $post_id;
    }
    if ( ! $post_id ) {
        return new WP_Error( 'wpap_insert_failed', 'post creation failed.' );
    }
    $post_id = (int) $post_id;

    /* Guarantee the <!--nextpage--> markers survive kses (mirrors the AI path,
       which bypasses wp_insert_post for exactly this reason). */
    if ( $content_split !== $content && false !== strpos( $content_split, '<!--nextpage-->' ) ) {
        $wpdb->update( $wpdb->posts, array( 'post_content' => $content_split ), array( 'ID' => $post_id ) );
        clean_post_cache( $post_id );
    }

    /* ── category: item['category'] (name or numeric id) else default_category ── */
    $cat_raw = '';
    if ( isset( $item['category'] ) && is_scalar( $item['category'] ) ) {
        $cat_raw = trim( (string) wp_unslash( $item['category'] ) );
    }
    if ( '' === $cat_raw ) {
        $cat_raw = $default_category;
    }
    if ( '' !== $cat_raw ) {
        $term_id = 0;
        /* A purely-numeric value is FIRST tried as a term ID; if no such category
           ID exists, fall through and treat it as a NAME (so a category literally
           named e.g. "2024" is matched or created, not silently dropped). */
        if ( ctype_digit( $cat_raw ) && term_exists( (int) $cat_raw, 'category' ) ) {
            $term_id = (int) $cat_raw;
        } else {
            $existing = term_exists( $cat_raw, 'category' );
            if ( $existing && ! is_wp_error( $existing ) ) {
                $term_id = (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
            } else {
                $new = wp_insert_term( $cat_raw, 'category' );
                if ( ! is_wp_error( $new ) && ! empty( $new['term_id'] ) ) {
                    $term_id = (int) $new['term_id'];
                }
            }
        }
        if ( $term_id > 0 ) {
            wp_set_object_terms( $post_id, $term_id, 'category' );
        }
    }

    /* ── download image → set as featured (non-fatal: failure never blocks publish) ── */
    $image_url = '';
    if ( '' !== $image_raw && wp_http_validate_url( $image_raw ) ) {
        $attach_id = wpap_import_remote_image_as_attachment( $image_raw, $post_id, $title );
        if ( ! is_wp_error( $attach_id ) && $attach_id ) {
            set_post_thumbnail( $post_id, (int) $attach_id );
            update_post_meta( (int) $attach_id, '_wp_attachment_image_alt', $title );   /* SEO / Google Images */
            $image_url = (string) wp_get_attachment_url( $attach_id );
        }
    }

    if ( '' === $hook ) { $hook = $title; }

    $post_url   = get_permalink( $post_id );
    $smart_link = $post_url;   /* clean permalink (no ?v=) so shared links behave like a manual post */

    /* ── meta (mirror the AI path + import handler) ── */
    update_post_meta( $post_id, '_wpap_image_url',  $image_url );
    update_post_meta( $post_id, '_wpap_fb_hook',    $hook );
    update_post_meta( $post_id, 'ah_social_hook',   $hook );
    update_post_meta( $post_id, '_wpap_smart_link', $smart_link );
    if ( '' !== $source_key ) {
        update_post_meta( $post_id, '_wpap_source_key', $source_key );
    }

    /* ── SEO metadata: meta description / title / focus keyword into whichever
       SEO plugin is active (Yoast, Rank Math). The description also lives in
       post_excerpt, which the plugin's own <head> emitter uses when no SEO
       plugin is installed — so a proper meta description is set either way. ── */
    wpap_set_seo_meta( $post_id, $description, $meta_title, $focus_kw );

    /* ── tags (array or comma-separated string) ── */
    $tags = array();
    if ( is_array( $tags_raw ) ) {
        foreach ( $tags_raw as $tg ) {
            if ( is_scalar( $tg ) ) {
                $t = sanitize_text_field( wp_unslash( (string) $tg ) );
                if ( '' !== $t ) { $tags[] = $t; }
            }
        }
    } elseif ( is_scalar( $tags_raw ) && '' !== trim( (string) $tags_raw ) ) {
        foreach ( explode( ',', (string) wp_unslash( $tags_raw ) ) as $tg ) {
            $t = sanitize_text_field( $tg );
            if ( '' !== $t ) { $tags[] = $t; }
        }
    }
    if ( ! empty( $tags ) ) {
        $tags = array_slice( array_values( array_unique( $tags ) ), 0, 15 );
        wp_set_post_terms( $post_id, $tags, 'post_tag', false );
    }

    /* ── distribution row ── */
    $saved = $wpdb->insert( $table, array(
        'post_id'    => $post_id,
        'title'      => $title,
        'post_url'   => $post_url,
        'image_url'  => $image_url,
        'fb_text'    => $hook,
        'fb_post_id' => '',
        'smart_link' => $smart_link,
    ) );

    if ( false === $saved ) {
        /* The post is already live; a failed Hub-row insert must NOT discard it
           (that would orphan a published post and, for the automation, cause the
           same Sheet row to be republished next run). The post_id is the source
           of truth; the Hub row is best-effort and can self-heal on next listing. */
        error_log( '[Automation Hamri] Distribution row insert failed for post ' . $post_id . ': ' . $wpdb->last_error );
    }

    clean_post_cache( $post_id );

    return $post_id;
}

add_action( 'wp_ajax_wpap_bulk_publish_posts', 'wpap_ajax_bulk_publish_posts' );
function wpap_ajax_bulk_publish_posts() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    @set_time_limit( 600 );
    @ignore_user_abort( true );
    @ini_set( 'max_execution_time', '600' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    $raw_items = trim( (string) wp_unslash( $_POST['items'] ?? '' ) );
    if ( '' === $raw_items ) {
        wp_send_json_error( 'Paste a JSON array first.' );
    }

    if ( strlen( $raw_items ) > wpap_bulk_max_bytes() ) {
        wp_send_json_error( sprintf(
            'Payload too large (%d KB). Maximum is %d KB — split it into smaller batches.',
            (int) round( strlen( $raw_items ) / 1024 ),
            (int) round( wpap_bulk_max_bytes() / 1024 )
        ) );
    }

    $payload = json_decode( $raw_items, true );
    if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $payload ) ) {
        wp_send_json_error( 'Invalid JSON. Expected an array like [{"title":"...","imageUrl":"...","content":"...","hook":"..."}].' );
    }

    /* Accept {items:[...]} wrappers and a single bare object too. */
    if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
        $payload = $payload['items'];
    } elseif ( isset( $payload['title'] ) || isset( $payload['content'] ) || isset( $payload['hook'] ) || isset( $payload['imageUrl'] ) || isset( $payload['image_url'] ) ) {
        $payload = array( $payload );
    }

    $items = array_values( array_filter( $payload, 'is_array' ) );
    if ( empty( $items ) ) {
        wp_send_json_error( 'No valid items found in the JSON payload.' );
    }

    global $wpdb;
    $table    = $wpdb->prefix . WPAP_TABLE;
    $created  = array();
    $messages = array();

    /* Cap batch size to bound worker time on a single request. */
    $wpap_max_items = wpap_bulk_max_items();
    if ( count( $items ) > $wpap_max_items ) {
        $messages[] = sprintf(
            '%d item(s) ignored: this batch is capped at %d items per request.',
            count( $items ) - $wpap_max_items,
            $wpap_max_items
        );
        $items = array_slice( $items, 0, $wpap_max_items );
    }

    /* ── Publish options (apply to every item; a per-item "parts" wins) ── */
    $default_parts = intval( $_POST['num_parts'] ?? 1 );
    if ( $default_parts < 1 )  { $default_parts = 1; }
    if ( $default_parts > 10 ) { $default_parts = 10; }

    $schedule_window = isset( $_POST['schedule_window'] ) ? (float) $_POST['schedule_window'] : 0;
    if ( $schedule_window < 0 )   { $schedule_window = 0; }
    if ( $schedule_window > 168 ) { $schedule_window = 168; }   /* cap at 1 week */

    /* Optional default category (name or id) applied when an item has none. */
    $default_category = sanitize_text_field( wp_unslash( $_POST['default_category'] ?? '' ) );

    /* Concurrency guard: a double-click or a client retry must not publish the
       same batch twice. Hold a short lock for the run; a stale lock auto-expires
       (15 min) so a crash can never wedge publishing permanently. */
    $wpap_lock = 'wpap_bulk_publish_lock';
    if ( get_transient( $wpap_lock ) ) {
        wp_send_json_error( 'A publish run is already in progress — wait for it to finish before starting another.' );
    }
    set_transient( $wpap_lock, 1, 15 * MINUTE_IN_SECONDS );
    /* Guarantee the lock is freed no matter how this request ends — including a
       PHP timeout mid-batch — so a long run can never lock the user out. Runs
       after wp_send_json's exit too; the explicit deletes below just free it a
       moment sooner on the normal paths. */
    register_shutdown_function( function () use ( $wpap_lock ) { delete_transient( $wpap_lock ); } );

    foreach ( $items as $index => $item ) {
        $row_number = $index + 1;

        /* Single publish path shared with the Google-Sheet automation. Each item
           is ISOLATED: an unexpected fatal (bad image, odd content, a plugin on
           a hook throwing) is caught and recorded, so one broken item can NEVER
           abort the whole batch — every other item still publishes. */
        try {
            $result = wpap_publish_article( $item, array(
                'default_parts'    => $default_parts,
                'schedule_window'  => $schedule_window,
                'default_category' => $default_category,
                /* Ordered scheduling: this item's position + the batch size, so a
                   scheduled batch goes live in the exact order it was submitted. */
                'schedule_index'   => $index,
                'schedule_total'   => count( $items ),
            ) );
        } catch ( \Throwable $e ) {
            error_log( '[Automation Hamri] Publish crashed on row ' . $row_number . ': ' . $e->getMessage() );
            $messages[] = sprintf( 'Row %d failed (skipped): %s', $row_number, $e->getMessage() );
            continue;
        }

        if ( is_wp_error( $result ) ) {
            $messages[] = sprintf( 'Row %d skipped: %s', $row_number, $result->get_error_message() );
            continue;
        }

        $post_id = (int) $result;
        $post    = get_post( $post_id );

        /* Rebuild the response row from the freshly published post. */
        $post_url = get_permalink( $post_id );

        $smart_link = (string) get_post_meta( $post_id, '_wpap_smart_link', true );
        if ( '' === $smart_link ) { $smart_link = (string) $post_url; }

        $image_url = (string) get_post_meta( $post_id, '_wpap_image_url', true );
        if ( '' === $image_url ) { $image_url = (string) get_the_post_thumbnail_url( $post_id, 'full' ); }

        $status = $post ? $post->post_status : 'publish';
        $label  = ( $post && 'future' === $status ) ? mysql2date( 'M j, Y g:i A', $post->post_date ) : '';

        $content_now = $post ? (string) $post->post_content : '';
        $parts = ( false !== strpos( $content_now, '<!--nextpage-->' ) )
            ? ( substr_count( $content_now, '<!--nextpage-->' ) + 1 )
            : 1;

        /* Non-fatal image warning, re-derived (specific error text isn't available here). */
        $image_raw = $item['imageUrl'] ?? $item['image_url'] ?? $item['image'] ?? '';
        $image_raw = is_scalar( $image_raw ) ? trim( (string) $image_raw ) : '';
        if ( '' !== $image_raw && '' === $image_url ) {
            $messages[] = sprintf( 'Row %d: image could not be attached — published without a featured image.', $row_number );
        }

        /* Distribution-row id for the row just written by wpap_publish_article(). */
        $dist_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE post_id = %d ORDER BY id DESC LIMIT 1",
            $post_id
        ) );

        $created[] = array(
            'id'              => $dist_id,
            'post_id'         => $post_id,
            'title'           => $post ? $post->post_title : '',
            'post_url'        => (string) $post_url,
            'image_url'       => $image_url,
            'smart_link'      => $smart_link,
            'has_image'       => $image_url ? 1 : 0,
            'post_status'     => $status,
            'scheduled_label' => $label,
            'parts'           => $parts,
        );
    }

    if ( empty( $created ) ) {
        delete_transient( $wpap_lock );
        wp_send_json_error( array(
            'message'  => 'No posts were published.',
            'messages' => $messages,
        ) );
    }

    /* Purge page caches so freshly published posts appear on the blog immediately. */
    wpap_purge_caches();

    delete_transient( $wpap_lock );
    wp_send_json_success( array(
        'created'  => count( $created ),
        'skipped'  => count( $items ) - count( $created ),
        'total'    => count( $items ),
        'messages' => $messages,
        'rows'     => $created,
        'nonce'    => wp_create_nonce( 'wpap_nonce' ),
    ) );
}

add_action( 'wp_ajax_wpap_export_distribution_json', 'wpap_ajax_export_distribution_json' );
function wpap_ajax_export_distribution_json() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    global $wpdb;
    $table  = $wpdb->prefix . WPAP_TABLE;

    /* Export ONLY the current page (mirrors wpap_ajax_get_posts: 10 per page,
       same id-DESC order and title search) so the JSON matches what's on screen. */
    $page   = max( 1, intval( $_GET['page']   ?? 1 ) );
    $search = sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) );
    $per    = 10;
    $offset = ( $page - 1 ) * $per;

    if ( $search ) {
        $like = '%' . $wpdb->esc_like( $search ) . '%';
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, title, post_url, image_url, fb_text, smart_link FROM {$table} WHERE title LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d", $like, $per, $offset ), ARRAY_A );
    } else {
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, post_id, title, post_url, image_url, fb_text, smart_link FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per, $offset ), ARRAY_A );
    }

    $items = array();
    foreach ( (array) $rows as $row ) {
        $pid = intval( $row['post_id'] ?? 0 );

        /* caption = the hook (table → post-meta fallback) */
        $hook = (string) ( $row['fb_text'] ?? '' );
        if ( '' === $hook && $pid ) { $hook = (string) get_post_meta( $pid, '_wpap_fb_hook', true ); }

        /* comment = the post link (smart link → meta → plain permalink) */
        $link = trim( (string) ( $row['smart_link'] ?? '' ) );
        if ( '' === $link && $pid ) { $link = (string) get_post_meta( $pid, '_wpap_smart_link', true ); }
        if ( '' === $link ) { $link = (string) ( $row['post_url'] ?? '' ); }

        /* imageUrl = the original image (table → meta → featured image) */
        $img = (string) ( $row['image_url'] ?? '' );
        if ( '' === $img && $pid ) {
            $img = (string) get_post_meta( $pid, '_wpap_image_url', true );
            if ( '' === $img ) { $img = (string) get_the_post_thumbnail_url( $pid, 'full' ); }
        }

        $items[] = array(
            'caption'  => $hook,
            'comment'  => $link,
            'imageUrl' => $img,
        );
    }

    wp_send_json_success( array(
        'items' => $items,
        'count' => count( $items ),
        'page'  => $page,
    ) );
}

add_action( 'wp_ajax_wpap_process_title', 'wpap_ajax_process_title' );
function wpap_ajax_process_title() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    @set_time_limit( 300 );
    @ignore_user_abort( true );
    @ini_set( 'max_execution_time', '300' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    /* Cost circuit-breaker: one AI generation per request, before any API call. */
    if ( ! wpap_rate_limit_ok( 1 ) ) {
        wp_send_json_error( 'Rate limit reached. Please wait a while before generating more articles.' );
    }

    $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
    $fallback_attach_id_early = intval( $_POST['fallback_attach_id'] ?? 0 );

    $claude_key = wpap_get_api_key( 'claude' );
    $gemini_key = wpap_get_api_key( 'gemini' );

    /* ── Vision fallback: no title but an image was provided ── */
    if ( ! $title && $fallback_attach_id_early > 0 ) {
        $image_path = get_attached_file( $fallback_attach_id_early );
        $mime_type  = get_post_mime_type( $fallback_attach_id_early );
        if ( $image_path && file_exists( $image_path ) && $mime_type ) {
            $image_data   = base64_encode( file_get_contents( $image_path ) );
            $vision_prompt = 'Look at this image and generate ONE catchy, SEO-friendly article title (max 12 words). Output only the title — no quotes, no punctuation at the end, no extra text.';
            $vision_title  = '';

            /* Step 1: Gemini Flash vision */
            if ( $gemini_key ) {
                $gem_body = wp_json_encode( array(
                    'contents' => array( array( 'parts' => array(
                        array( 'inlineData' => array( 'mimeType' => $mime_type, 'data' => $image_data ) ),
                        array( 'text'       => $vision_prompt ),
                    ) ) ),
                    'generationConfig' => array( 'maxOutputTokens' => 30, 'temperature' => 0.7 ),
                ) );
                $gem_r = wp_remote_post(
                    'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $gemini_key,
                    array( 'timeout' => 20, 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => $gem_body )
                );
                if ( ! is_wp_error( $gem_r ) && 200 === (int) wp_remote_retrieve_response_code( $gem_r ) ) {
                    $gem_json    = json_decode( wp_remote_retrieve_body( $gem_r ), true );
                    $vision_title = trim( $gem_json['candidates'][0]['content']['parts'][0]['text'] ?? '' );
                }
            }

            /* Step 2: Claude Haiku fallback if Gemini failed or no key */
            if ( ! $vision_title && $claude_key ) {
                $cl_body = wp_json_encode( array(
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 40,
                    'messages'   => array( array( 'role' => 'user', 'content' => array(
                        array( 'type' => 'image', 'source' => array( 'type' => 'base64', 'media_type' => $mime_type, 'data' => $image_data ) ),
                        array( 'type' => 'text',  'text'   => $vision_prompt ),
                    ) ) ),
                ) );
                $cl_r = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
                    'timeout' => 20,
                    'headers' => array(
                        'x-api-key'         => $claude_key,
                        'anthropic-version' => '2023-06-01',
                        'content-type'      => 'application/json',
                    ),
                    'body' => $cl_body,
                ) );
                if ( ! is_wp_error( $cl_r ) && 200 === (int) wp_remote_retrieve_response_code( $cl_r ) ) {
                    $cl_json      = json_decode( wp_remote_retrieve_body( $cl_r ), true );
                    $vision_title = trim( $cl_json['content'][0]['text'] ?? '' );
                }
            }

            if ( $vision_title ) {
                /* Strip any surrounding quotes the model may have added */
                $vision_title = preg_replace( '/^[\'"""«»]+|[\'"""«»]+$/u', '', $vision_title );
                $title = sanitize_text_field( $vision_title );
            }
        }
    }

    if ( ! $title ) wp_send_json_error( 'No title provided and vision analysis could not generate one.' );
    $pexels_key = wpap_get_api_key( 'pexels' );
    /* Key validation happens per-engine in STEP A below */

    /* ── Read target language from front-end ── */
    $allowed_langs  = array(
        'auto','en','fr','es','de','it','pt','nl','pl','ro','hu','bg',
        'cs','sk','hr','sv','da','fi','el','ru','uk','tr',
        'ar','he','fa','zh','ja','ko','hi','id','vi','th',
    );
    $target_lang = sanitize_text_field( wp_unslash( $_POST['target_lang'] ?? 'auto' ) );
    if ( ! in_array( $target_lang, $allowed_langs, true ) ) {
        $target_lang = 'auto';
    }

    /* ── Read page count (2-5) ── */
    $num_pages = intval( $_POST['num_pages'] ?? 2 );
    if ( $num_pages < 2 || $num_pages > 5 ) { $num_pages = 2; }

    /* ── Read schedule window (hours; 0 = publish immediately) ── */
    $schedule_window = isset( $_POST['schedule_window'] ) ? (float) $_POST['schedule_window'] : 0;
    if ( $schedule_window < 0 )   { $schedule_window = 0; }
    if ( $schedule_window > 168 ) { $schedule_window = 168; }   /* cap at 1 week */

    /* ── Read image engine (gemini | claude | pexels | manual_only) ── */
    $valid_img = array( 'gemini_flash', 'gemini_pro', 'claude', 'pexels', 'manual_only' );
    $image_engine = sanitize_text_field( wp_unslash( $_POST['image_engine'] ?? 'gemini_flash' ) );
    if ( ! in_array( $image_engine, $valid_img, true ) ) { $image_engine = 'gemini_flash'; }

    /* ── Read content engine (3-tier) ── */
    $valid_cnt = array( 'claude_haiku', 'gemini_flash', 'gemini_pro' );
    $content_engine = sanitize_text_field( wp_unslash( $_POST['content_engine'] ?? 'claude_haiku' ) );
    if ( ! in_array( $content_engine, $valid_cnt, true ) ) { $content_engine = 'claude_haiku'; }

    /* ── STEP A: Generate content via selected engine ── */
    if ( $content_engine === 'gemini_flash' || $content_engine === 'gemini_pro' ) {
        $gemini_key_c = wpap_get_api_key( 'gemini' );
        if ( ! $gemini_key_c ) wp_send_json_error( 'Gemini API key missing — go to Settings.' );
        $content = wpap_generate_content_gemini( $title, $gemini_key_c, $target_lang, $num_pages );
    } else {
        if ( ! $claude_key ) wp_send_json_error( 'Claude API key missing — go to Settings.' );
        $content = wpap_generate_content( $title, $claude_key, $target_lang, $num_pages );
    }
    if ( is_wp_error( $content ) ) wp_send_json_error( $content->get_error_message() );

    $page1   = $content['page1'];
    $page2   = $content['page2'];
    $fb_text = $content['fb_text'];
    $lang    = $content['lang'];

    /* ── STEP B: Build translated labels ── */
    $next_map = array(
        'ar' => 'لا تفوّت الباقي! اضغط على الزر التالي لمواصلة القراءة',
        'fr' => 'Ne manquez pas la suite ! Appuyez sur Suivant pour continuer',
        'es' => '¡No te pierdas el resto! Presiona Siguiente para continuar',
        'de' => 'Verpassen Sie nicht den Rest! Weiter drücken um weiterzulesen',
        'it' => 'Non perdere il resto! Premi Avanti per continuare',
        'pt' => 'Não perca o resto! Pressione Próximo para continuar',
        'tr' => "Geri kalanı kaçırmayın! Devam için İleri'ye basın",
        'nl' => 'Mis de rest niet! Druk op Volgende om verder te lezen',
        'ru' => 'Не пропустите остальное! Нажмите Далее чтобы продолжить',
        'hu' => 'Ne maradj le a többiről! A folytatáshoz nyomja meg a Következő gombot',
        'en' => "Don't Miss The Rest! Press Next Button Below To Continue Reading",
    );
    $share_map = array(
        'ar' => 'إذا أعجبتك الوصفة، شاركها مع أصدقائك!',
        'fr' => 'Si vous avez aimé la recette, partagez-la avec vos amis !',
        'es' => '¡Si te gustó la receta, compártela con tus amigos!',
        'de' => 'Wenn dir das Rezept gefallen hat, teile es mit deinen Freunden!',
        'it' => 'Se ti è piaciuta la ricetta, condividila con i tuoi amici!',
        'pt' => 'Se gostou da receita, partilhe com os seus amigos!',
        'tr' => 'Tarifi beğendiyseniz arkadaşlarınızla paylaşın!',
        'nl' => 'Als je het recept lekker vond, deel het dan met je vrienden!',
        'ru' => 'Если вам понравился рецепт, поделитесь им с друзьями!',
        'hu' => 'Ha tetszett a recept, oszd meg barátaiddal!',
        'en' => 'If you liked the recipe, share it with your friends!',
    );
    /* If user forced a language, use it for UI labels; else use Claude-detected $lang */
    $ui_lang     = ( $target_lang !== 'auto' ) ? $target_lang : $lang;
    $next_label  = $next_map[ $ui_lang ]  ?? $next_map['en'];
    $share_label = $share_map[ $ui_lang ] ?? $share_map['en'];

    /* ── STEP C: Build full post content with <!--nextpage--> (N pages) ── */
    /* Red 'Next' button removed — theme provides its own navigation button.
       Only the translated teaser text is preserved. */
    $next_block = '<p class="wpap-next-teaser">' . esc_html( $next_label ) . '</p>';
    $share_block = '<p class="wpap-share-cta">' . esc_html( $share_label ) . '</p>';

    /* Split raw pages array from content and assemble with nextpage tags */
    $raw_pages    = $content['pages'];   /* array of HTML strings, length = $num_pages */
    $full_content = '';
    foreach ( $raw_pages as $idx => $pg_html ) {
        $full_content .= $pg_html;
        if ( $idx < count( $raw_pages ) - 1 ) {
            /* Add Next button + page break between every pair of pages */
            $full_content .= "\n\n" . $next_block . "\n\n<!--nextpage-->\n\n";
        }
    }
    $full_content .= "\n\n" . $share_block;

    /* ═══ NUCLEAR CONTENT CLEAN ═══
     * Strip ALL markdown artifacts right before saving to DB.
     * This runs AFTER page assembly so it catches anything
     * the AI smuggled into PAGE1...PAGEn blocks.
     */
    $full_content = str_replace( array( '```html', '```' ), '', $full_content );
    $full_content = trim( $full_content, " `\t\n\r\0\x0B" );
    /* Also kill any "html" that appears as the very first word (leftover label) */
    $full_content = preg_replace( '/^html\s*/i', '', $full_content );
    /* Remove any backtick sequences anywhere in the content */
    $full_content = str_replace( '`', '', $full_content );

    /* ── STEP D: Insert post directly via $wpdb (bypasses kses filter → preserves <!--nextpage-->) ── */
    global $wpdb;
    /* Publish now, or schedule to a random slot in the next hours (spreads a batch out). */
    $sched   = wpap_compute_schedule( $schedule_window );
    $now     = current_time( 'mysql' );      /* post_modified = actual write time */
    $now_gmt = current_time( 'mysql', 1 );
    $slug    = sanitize_title( $title );

    $inserted = $wpdb->insert( $wpdb->posts, array(
        'post_author'           => get_current_user_id(),
        'post_date'             => $sched['date'],
        'post_date_gmt'         => $sched['date_gmt'],
        'post_content'          => $full_content,
        'post_title'            => $title,
        'post_excerpt'          => '',
        'post_status'           => $sched['status'],
        'comment_status'        => 'open',
        'ping_status'           => 'open',
        'post_name'             => $slug,
        'post_modified'         => $now,
        'post_modified_gmt'     => $now_gmt,
        'post_type'             => 'post',
        'to_ping'               => '',
        'pinged'                => '',
        'post_content_filtered' => '',
        'guid'                  => home_url( '/?p=0' ),
    ) );

    if ( ! $inserted ) {
        wp_send_json_error( 'DB insert failed: ' . $wpdb->last_error );
    }
    $post_id = (int) $wpdb->insert_id;

    /* Raw insert skips wp_insert_post's future-post wiring, so schedule the
       auto-publish cron ourselves when this is a scheduled ("future") post. */
    if ( 'future' === $sched['status'] && $sched['ts_gmt'] ) {
        wpap_schedule_future_publish( $post_id, $sched['ts_gmt'] );
    }

    /* Fix slug and guid */
    $unique_slug = wp_unique_post_slug( $slug, $post_id, $sched['status'], 'post', 0 );
    $post_url    = get_permalink( $post_id );
    $wpdb->update( $wpdb->posts,
        array( 'post_name' => $unique_slug, 'guid' => $post_url ),
        array( 'ID' => $post_id )
    );
    clean_post_cache( $post_id );
    /* ── Internal link injection: embed 2-3 links to existing posts ── */
    $il_posts = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_status='publish' AND post_type='post' AND ID!=%d ORDER BY post_date DESC LIMIT 5",
        $post_id
    ) );
    if ( ! empty( $il_posts ) ) {
        $il_pool = array();
        foreach ( $il_posts as $ip ) {
            $il_pool[] = array( 'title' => $ip->post_title, 'url' => get_permalink( $ip->ID ) );
        }
        $il_updated = wpap_inject_internal_links( $full_content, $il_pool, $content_engine, $claude_key, $gemini_key );
        if ( $il_updated && $il_updated !== $full_content && strpos( $il_updated, '<a href' ) !== false ) {
            $wpdb->update( $wpdb->posts, array( 'post_content' => $il_updated ), array( 'ID' => $post_id ) );
            clean_post_cache( $post_id );
            $full_content = $il_updated;
        }
    }

    /* ── STEP E: IMAGE LOGIC (Manual takes 100% priority over AI) ──
     * sleep(2): small delay prevents bulk-mode HTTP blocking from
     * external image APIs (Pexels, Gemini, Pollinations).
     */
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) sleep( 2 );  /* 2s gap: rate-limit protection */
    $fallback_attach_id = intval( $_POST['fallback_attach_id'] ?? 0 );
    $attach_id          = 0;
    $image_url          = '';
    $image_debug        = '';

    if ( $fallback_attach_id > 0 ) {
        /*
         * MANUAL IMAGE — uploaded directly in the grid row.
         * This ALWAYS takes priority. No API calls. No exceptions.
         */
        $attach_id   = (int) $fallback_attach_id;
        $image_debug = 'manual_upload';
        /* Re-parent the attachment to this post */
        $wpdb->update( $wpdb->posts, array( 'post_parent' => $post_id ), array( 'ID' => $attach_id ) );
        clean_post_cache( $attach_id );
    } elseif ( $image_engine === 'pexels' ) {
        if ( ! $pexels_key ) { $image_debug = 'pexels:no_key'; }
        else {
            $r = wpap_generate_image_pexels( $title, $post_id, $pexels_key );
            if ( ! is_wp_error( $r ) ) { $attach_id = $r; $image_debug = 'pexels:ok'; }
            else { $image_debug = 'pexels:' . $r->get_error_message(); }
        }
    } elseif ( $image_engine === 'claude' ) {
        if ( ! $claude_key ) { $image_debug = 'claude_img:no_key'; }
        else {
            $r = wpap_generate_image_claude( $title, $post_id, $claude_key );
            if ( ! is_wp_error( $r ) ) { $attach_id = $r; $image_debug = 'claude:ok'; }
            else { $image_debug = 'claude:' . $r->get_error_message(); }
        }
    } elseif ( $image_engine === 'gemini_pro' ) {
        if ( ! $gemini_key ) { $image_debug = 'gemini_pro:no_key'; }
        else {
            $r = wpap_generate_image_gemini( $title, $post_id, $gemini_key, 'pro' );
            if ( ! is_wp_error( $r ) ) { $attach_id = $r; $image_debug = 'gemini_pro:ok'; }
            else { $image_debug = 'gemini_pro:' . $r->get_error_message(); }
        }
    } elseif ( $image_engine === 'manual_only' ) {
        /*
         * MANUAL ONLY — no AI/Pexels call at all.
         * If a fallback_attach_id was provided it was already handled above.
         * If not, the post simply has no featured image — intentional.
         */
        $image_debug = 'manual_only:skip_ai';
    } else { /* gemini_flash — default with 30s retry */
        if ( ! $gemini_key ) { $image_debug = 'gemini_flash:no_key'; }
        else {
            $r = wpap_generate_image_gemini( $title, $post_id, $gemini_key, 'flash' );
            if ( ! is_wp_error( $r ) ) { $attach_id = $r; $image_debug = 'gemini_flash:ok'; }
            else { $image_debug = 'gemini_flash:' . $r->get_error_message(); }
        }
    }
    /* Set featured image and get URL */
    if ( $attach_id > 0 ) {
        set_post_thumbnail( $post_id, $attach_id );
        $image_url = wp_get_attachment_url( $attach_id );
    }

    /* ── STEP F: Build smart link (clean permalink, no ?v= — matches a manual post) ── */
    $smart_link = $post_url;


/* ════════════════════════════════════════════════════════
   LANGUAGE-AWARE HOOK BUILDER
   Called right before STEP G.
   Detects content type (recipe vs article) from the AI's
   fb_text, then appends the correctly translated CTA line
   from a hardcoded 30-language map — no extra API call.
════════════════════════════════════════════════════════ */
/* ════════════════════════════════════════════════════════
   AI HOOK GENERATOR
   Separate lightweight API call AFTER the article is written.
   Sends the actual page1 text so the AI writes a real
   2-sentence teaser in the correct language — not just the title.
   Falls back silently if the call fails (queue never stops).
════════════════════════════════════════════════════════ */
function wpap_generate_hook_via_ai( $title, $page1_html, $lang, $content_engine, $claude_key, $gemini_key ) {

    $lang_names = array(
        'en'=>'English','fr'=>'French','es'=>'Spanish','de'=>'German',
        'it'=>'Italian','pt'=>'Portuguese','nl'=>'Dutch','pl'=>'Polish',
        'ro'=>'Romanian','hu'=>'Hungarian','bg'=>'Bulgarian','cs'=>'Czech',
        'sk'=>'Slovak','hr'=>'Croatian','sv'=>'Swedish','da'=>'Danish',
        'fi'=>'Finnish','el'=>'Greek','ru'=>'Russian','uk'=>'Ukrainian',
        'tr'=>'Turkish','ar'=>'Arabic','he'=>'Hebrew','fa'=>'Persian',
        'zh'=>'Chinese (Simplified)','ja'=>'Japanese','ko'=>'Korean',
        'hi'=>'Hindi','id'=>'Indonesian','vi'=>'Vietnamese','th'=>'Thai',
    );
    $lang_name = isset( $lang_names[ $lang ] ) ? $lang_names[ $lang ] : 'the same language as the article';

    /* Strip HTML, limit to 800 chars of real article text for richer context */
    $excerpt = mb_substr( strip_tags( $page1_html ), 0, 800 );

    $prompt = "You are a viral social media copywriter. Your job is to write a short, punchy teaser that makes people STOP scrolling.\n\n"
            . "Article title: \"" . addslashes( $title ) . "\"\n\n"
            . "Article excerpt (use this content to craft the teaser):\n" . $excerpt . "\n\n"
            . "TASK: Write a catchy 2-sentence viral teaser that summarises the KEY insight or benefit from the article excerpt above.\n"
            . "STRICT RULES — violating any rule means failure:\n"
            . "- FORBIDDEN: Do NOT copy, repeat, or paraphrase the title. The hook must feel completely different from the title.\n"
            . "- REQUIRED: Base your hook on the actual article excerpt content, not the title.\n"
            . "- REQUIRED: Write in " . $lang_name . " ONLY. Every single word must be in " . $lang_name . ".\n"
            . "- FORBIDDEN: No CTA, no hashtags, no emojis, no 'comment', no 'link in bio'.\n"
            . "- FORMAT: Exactly 2 sentences. No bullet points. No labels. No preamble.\n"
            . "OUTPUT: Your 2 sentences in " . $lang_name . ". Nothing else.";

    $result = '';

    if ( $content_engine === 'claude_haiku' && $claude_key ) {
        $r = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 25,
            'headers' => array(
                'x-api-key'         => $claude_key,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 100,
                'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
            ) ),
        ) );
        if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
            $b      = json_decode( wp_remote_retrieve_body( $r ), true );
            $result = trim( isset( $b['content'][0]['text'] ) ? $b['content'][0]['text'] : '' );
        }

    } elseif ( $gemini_key ) {
        $model    = ( $content_engine === 'gemini_pro' ) ? 'gemini-1.5-pro' : 'gemini-2.0-flash';
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $gemini_key;
        $r = wp_remote_post( $endpoint, array(
            'timeout' => 25,
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array(
                'contents'         => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
                'generationConfig' => array( 'maxOutputTokens' => 100, 'temperature' => 0.8 ),
            ) ),
        ) );
        if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
            $b      = json_decode( wp_remote_retrieve_body( $r ), true );
            $result = trim( isset( $b['candidates'][0]['content']['parts'][0]['text'] )
                ? $b['candidates'][0]['content']['parts'][0]['text'] : '' );
        }
    }

    /* Strip any markdown/backticks the AI added */
    $result = str_replace( array( '```html', '```', '`' ), '', $result );
    $result = trim( $result );
    return $result;
}

function wpap_build_clean_hook( string $fb_text, string $lang, string $title = '', string $page1 = '' ): string {

    $thumb = "\xf0\x9f\x91\x87";   /* 👇 emoji */

    /* ════════════════════════════════════════════
       CTA MAPS — 30 languages × 2 types
    ════════════════════════════════════════════ */
    $recipe_cta = array(
        'en' => "Full recipe in the first comment {$thumb}",
        'fr' => "Recette complète en premier commentaire {$thumb}",
        'es' => "Receta completa en el primer comentario {$thumb}",
        'de' => "Vollständiges Rezept im ersten Kommentar {$thumb}",
        'it' => "Ricetta completa nel primo commento {$thumb}",
        'pt' => "Receita completa no primeiro comentário {$thumb}",
        'nl' => "Volledig recept in de eerste reactie {$thumb}",
        'pl' => "Pełny przepis w pierwszym komentarzu {$thumb}",
        'ro' => "Rețeta completă în primul comentariu {$thumb}",
        'hu' => "A teljes recept az első kommentben {$thumb}",
        'bg' => "Пълната рецепта в първия коментар {$thumb}",
        'cs' => "Celý recept v prvním komentáři {$thumb}",
        'sk' => "Celý recept v prvom komentári {$thumb}",
        'hr' => "Cijeli recept u prvom komentaru {$thumb}",
        'sv' => "Hela receptet i den första kommentaren {$thumb}",
        'da' => "Hele opskriften i den første kommentar {$thumb}",
        'fi' => "Koko resepti ensimmäisessä kommentissa {$thumb}",
        'el' => "Σύνολη συνταγή στο πρώτο σχόλιο {$thumb}",
        'ru' => "Полный рецепт в первом комментарии {$thumb}",
        'uk' => "Повний рецепт у першому коментарі {$thumb}",
        'tr' => "Tam tarif ilk yorumda {$thumb}",
        'ar' => "الوصفة الكاملة في أول تعليق {$thumb}",
        'he' => "המתכון המלא בתגובה הראשונה {$thumb}",
        'fa' => "دستور پخت کامل در اولین نظر {$thumb}",
        'zh' => "完整食谱在第一条评论 {$thumb}",
        'ja' => "完全なレシピは最初のコメントに {$thumb}",
        'ko' => "전체 레시피는 첫 번째 댓글에 {$thumb}",
        'hi' => "पूरी रेसिपी पहली कमेंट में {$thumb}",
        'id' => "Resep lengkap di komentar pertama {$thumb}",
        'vi' => "Công thức đầy đủ trong bình luận đầu tiên {$thumb}",
        'th' => "สูตรครบถ้วนในความคิดเห็นแรก {$thumb}",
    );

    $article_cta = array(
        'en' => "Details in the first comment {$thumb}",
        'fr' => "Détails en premier commentaire {$thumb}",
        'es' => "Detalles en el primer comentario {$thumb}",
        'de' => "Details im ersten Kommentar {$thumb}",
        'it' => "Dettagli nel primo commento {$thumb}",
        'pt' => "Detalhes no primeiro comentário {$thumb}",
        'nl' => "Details in de eerste reactie {$thumb}",
        'pl' => "Szczegóły w pierwszym komentarzu {$thumb}",
        'ro' => "Detalii în primul comentariu {$thumb}",
        'hu' => "Részletek az első kommentben {$thumb}",
        'bg' => "Подробности в първия коментар {$thumb}",
        'cs' => "Podrobnosti v prvním komentáři {$thumb}",
        'sk' => "Podrobnosti v prvom komentári {$thumb}",
        'hr' => "Detalji u prvom komentaru {$thumb}",
        'sv' => "Detaljer i den första kommentaren {$thumb}",
        'da' => "Detaljer i den første kommentar {$thumb}",
        'fi' => "Yksityiskohdat ensimmäisessä kommentissa {$thumb}",
        'el' => "Λεπτομέρειες στο πρώτο σχόλιο {$thumb}",
        'ru' => "Подробности в первом комментарии {$thumb}",
        'uk' => "Деталі у першому коментарі {$thumb}",
        'tr' => "Ayrıntılar ilk yorumda {$thumb}",
        'ar' => "التفاصيل في أول تعليق {$thumb}",
        'he' => "פרטים בתגובה הראשונה {$thumb}",
        'fa' => "جزئیات در اولین نظر {$thumb}",
        'zh' => "详情在第一条评论 {$thumb}",
        'ja' => "詳細は最初のコメントに {$thumb}",
        'ko' => "자세한 내용은 첫 번째 댓글에 {$thumb}",
        'hi' => "विवरण पहली कमेंट में {$thumb}",
        'id' => "Detail di komentar pertama {$thumb}",
        'vi' => "Chi tiết trong bình luận đầu tiên {$thumb}",
        'th' => "รายละเอียดในความคิดเห็นแรก {$thumb}",
    );

    /* ════════════════════════════════════════════
       SMART RECIPE DETECTION
       Check title first (most reliable), then content.
       Recipe keywords in major languages.
    ════════════════════════════════════════════ */
    $recipe_keywords = array(
        /* English */
        'recipe','recipes','cook','cooking','bake','baking','ingredient','ingredients',
        'dish','meal','dessert','cake','pie','bread','soup','salad','sauce','stew',
        'roast','grill','fry','casserole','muffin','cookie','brownie','cheesecake',
        'pancake','waffle','pasta','noodle','curry','taco','pizza','sandwich','roll',
        /* French */
        'recette','cuisiner','cuire','ingrédient','pâtisserie','gâteau','tarte',
        /* Spanish */
        'receta','cocinar','ingrediente','pastel','tarta','bizcocho','sopa',
        /* German */
        'rezept','kochen','backen','zutat','kuchen','suppe','braten',
        /* Italian */
        'ricetta','cucinare','ingrediente','torta','dolce','pasta','zuppa',
        /* Portuguese */
        'receita','cozinhar','ingrediente','bolo','torta','sopa',
        /* Dutch */
        'recept','koken','bakken','ingrediënt','taart','soep',
        /* Polish */
        'przepis','gotować','piec','składnik','ciasto','zupa',
        /* Hungarian */
        'recept','főzni','sütni','hozzávaló','torta','leves',
        /* Bulgarian */
        'рецепта','готвя','пека','съставка','торта','супа',
        /* Russian */
        'рецепт','готовить','печь','ингредиент','торт','суп',
        /* Turkish */
        'tarif','pişirmek','malzeme','kek','çorba',
        /* Arabic */
        'وصفة','طبخ','مكونات','كعكة','حلوى','شوربة',
    );

    /* Build search corpus: title + first 500 chars of page1 */
    $corpus = mb_strtolower( $title . ' ' . mb_substr( strip_tags( $page1 ), 0, 500 ) );

    $is_recipe = false;
    foreach ( $recipe_keywords as $kw ) {
        if ( mb_strpos( $corpus, mb_strtolower( $kw ) ) !== false ) {
            $is_recipe = true;
            break;
        }
    }

    /* ════════════════════════════════════════════
       BUILD THE HOOK
    ════════════════════════════════════════════ */
    $use_lang = ( strlen( $lang ) === 2 ) ? strtolower( $lang ) : 'en';
    $cta_map  = $is_recipe ? $recipe_cta : $article_cta;
    $cta      = $cta_map[ $use_lang ] ?? $cta_map['en'];

    /* Clean the AI-generated 2-sentence hook:
       Remove [POST_URL] and any CTA the AI may have accidentally added */
    $clean = trim( str_replace( '[POST_URL]', '', $fb_text ) );
    $clean = str_replace( array( '```html', '```', '`' ), '', $clean );

    /* Strip any line that already contains a 👇 emoji or comment-related CTA */
    $lines = array_filter( array_map( 'trim', explode( "\n", $clean ) ) );
    $lines = array_values( $lines );
    $filtered = array();
    foreach ( $lines as $line ) {
        /* Skip lines that look like CTA lines */
        if ( mb_strpos( $line, "\xf0\x9f\x91\x87" ) !== false ) continue; /* 👇 */
        if ( preg_match( '/\b(comment|koment|yorum|تعليق|коммент|komentár)\b/ui', $line ) ) continue;
        if ( preg_match( '/\b(first comment|premier commentaire|primer comentario|ersten kommentar)\b/ui', $line ) ) continue;
        $filtered[] = $line;
    }
    $clean = implode( "\n", $filtered );
    $clean = trim( $clean );

    /* Safety: if AI returned empty text, build a generic hook from title */
    if ( strlen( $clean ) < 10 ) {
        $clean = $title . '.';
    }

    /* Final hook = 2-sentence AI summary + correct translated CTA */
    return $clean . "\n" . $cta;
}


    /* ── STEP G: Build language-aware hook ──
     *
     * Step 1: wpap_generate_hook_via_ai()
     *   Sends the real page1 content to AI → gets a genuine 2-sentence
     *   teaser in the correct language (not just the title paraphrased).
     *   If this API call fails for any reason → falls back to $fb_text.
     *
     * Step 2: wpap_build_clean_hook()
     *   Detects recipe vs article (title + page1 corpus),
     *   strips any duplicate CTA, appends the translated CTA.
     */
    $ai_hook_text = wpap_generate_hook_via_ai(
        $title, $page1, $lang, $content_engine, $claude_key, $gemini_key
    );
    /* Use real AI hook if valid (>20 chars); fall back to main-call fb_text */
    $hook_source  = ( strlen( $ai_hook_text ) > 20 )
        ? $ai_hook_text
        : trim( str_replace( '[POST_URL]', '', $fb_text ) );
    $fb_hook_clean = wpap_build_clean_hook( $hook_source, $lang, $title, $page1 );
    $fb_text_with_link = $fb_hook_clean . "

" . $smart_link;

    /* ── STEP H: Save to post meta ── */
    update_post_meta( $post_id, '_wpap_image_url',  $image_url );
    update_post_meta( $post_id, '_wpap_fb_hook',    $fb_hook_clean );
    update_post_meta( $post_id, 'ah_social_hook',   $fb_hook_clean );  /* ah_social_hook — requested field */
    update_post_meta( $post_id, '_wpap_smart_link', $smart_link );

    /* ── STEP I: Save to plugin custom table ── */
    $wpdb->insert( $wpdb->prefix . WPAP_TABLE, array(
        'post_id'    => $post_id,
        'title'      => $title,
        'post_url'   => $post_url,
        'image_url'  => $image_url,
        'fb_text'    => $fb_hook_clean,
        'fb_post_id' => '',
        'smart_link' => $smart_link,
    ) );
    $wpap_row_id = (int) $wpdb->insert_id;

    /* Facebook posting removed — use Distribution Hub for manual sharing */
    wp_send_json_success( array(
        'title'       => $title,
        'post_url'    => $post_url,
        'image_url'   => $image_url,
        'smart_link'  => $smart_link,
        'fb_post_id'  => '',
        'fb_status'   => 'disabled',
        'fb_text'     => $fb_hook_clean,
        'image_debug' => $image_debug,
        'lang'        => $lang,
        'row_id'      => $wpap_row_id,
        'post_status' => $sched['status'],
        'scheduled_label' => $sched['label'],
        'nonce'       => wp_create_nonce( 'wpap_nonce' ),
    ) );
}

/* ════════════════════════════════════════════
   8. AJAX: GET POSTS TABLE
   Reads image_url and fb_text from post meta
   (_wpap_image_url, _wpap_fb_hook) as primary source,
   with plugin table as fallback.
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_get_posts', 'wpap_ajax_get_posts' );
function wpap_ajax_get_posts() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    global $wpdb;
    $table  = $wpdb->prefix . WPAP_TABLE;
    $page   = max( 1, intval( $_GET['page']   ?? 1 ) );
    $search = sanitize_text_field( wp_unslash( $_GET['search'] ?? '' ) );
    $status = sanitize_key( $_GET['status'] ?? 'all' );
    if ( ! in_array( $status, array( 'all', 'publish', 'future', 'draft' ), true ) ) { $status = 'all'; }
    /* Page size is user-selectable (fewer pages to click through). Whitelisted. */
    $per = (int) ( $_GET['per_page'] ?? 10 );
    if ( ! in_array( $per, array( 10, 25, 50, 100 ), true ) ) { $per = 10; }
    $offset = ( $page - 1 ) * $per;

    /* Build WHERE + an optional JOIN to wp_posts for the status filter. */
    $where  = array( '1=1' );
    $params = array();
    $join   = '';
    if ( '' !== $search ) {
        $where[]  = 't.title LIKE %s';
        $params[] = '%' . $wpdb->esc_like( $search ) . '%';
    }
    if ( 'all' !== $status ) {
        $join     = "INNER JOIN {$wpdb->posts} p ON p.ID = t.post_id";
        $where[]  = 'p.post_status = %s';
        $params[] = $status;
    }
    $where_sql = implode( ' AND ', $where );

    $count_sql = "SELECT COUNT(*) FROM {$table} t {$join} WHERE {$where_sql}";
    $total = empty( $params )
        ? (int) $wpdb->get_var( $count_sql )
        : (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) );

    $rows_sql    = "SELECT t.* FROM {$table} t {$join} WHERE {$where_sql} ORDER BY t.id DESC LIMIT %d OFFSET %d";
    $rows_params = array_merge( $params, array( $per, $offset ) );
    $rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, $rows_params ), ARRAY_A );

    /* Enrich each row from post meta */
    foreach ( $rows as &$row ) {
        $pid          = intval( $row['post_id'] ?? 0 );
        $needs_update = false;

        if ( $pid ) {
            /* image_url: post meta → plugin table → WP featured image */
            if ( empty( $row['image_url'] ) ) {
                $meta_img = get_post_meta( $pid, '_wpap_image_url', true );
                if ( $meta_img ) {
                    $row['image_url'] = $meta_img;
                    $needs_update     = true;
                } else {
                    $thumb = get_the_post_thumbnail_url( $pid, 'full' );
                    if ( $thumb ) {
                        $row['image_url'] = $thumb;
                        update_post_meta( $pid, '_wpap_image_url', $thumb );
                        $needs_update = true;
                    }
                }
            }

            /* fb_text: post meta → plugin table */
            if ( empty( $row['fb_text'] ) ) {
                $meta_hook = get_post_meta( $pid, '_wpap_fb_hook', true );
                if ( $meta_hook ) {
                    $row['fb_text'] = $meta_hook;
                    $needs_update   = true;
                }
            }

            /* smart_link: post meta → generate from post_url */
            if ( empty( $row['smart_link'] ) ) {
                $meta_link = get_post_meta( $pid, '_wpap_smart_link', true );
                if ( $meta_link ) {
                    $row['smart_link'] = $meta_link;
                } elseif ( ! empty( $row['post_url'] ) ) {
                    $row['smart_link'] = $row['post_url'];
                }
                $needs_update = true;
            }
        }

        /* Always hand back a clean permalink — strip any legacy ?v= from old rows. */
        if ( ! empty( $row['smart_link'] ) ) {
            $row['smart_link'] = remove_query_arg( 'v', $row['smart_link'] );
        }

        /* Write repairs back to plugin table */
        if ( $needs_update ) {
            $wpdb->update( $table, array(
                'image_url'  => $row['image_url']  ?? '',
                'fb_text'    => $row['fb_text']    ?? '',
                'smart_link' => $row['smart_link'] ?? '',
            ), array( 'id' => $row['id'] ) );
        }
    }
    unset( $row );

    wp_send_json_success( array(
        'rows'        => $rows,
        'total'       => (int) $total,
        'per_page'    => $per,
        'page'        => $page,
        'total_pages' => (int) ceil( $total / $per ),
    ) );
}

/* ════════════════════════════════════════════
   8a2. AJAX: DASHBOARD STATS (plugin posts at a glance)
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_dashboard_stats', 'wpap_ajax_dashboard_stats' );
function wpap_ajax_dashboard_stats() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    global $wpdb;
    /* Plugin posts = posts carrying the _wpap_smart_link meta. */
    $base = "FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_wpap_smart_link' WHERE p.post_type = 'post'";

    $counts = array( 'publish' => 0, 'future' => 0, 'draft' => 0, 'pending' => 0, 'trash' => 0 );
    $total  = 0;
    $srows  = $wpdb->get_results( "SELECT p.post_status AS st, COUNT(*) AS c {$base} GROUP BY p.post_status", ARRAY_A );
    foreach ( (array) $srows as $r ) {
        $st = (string) $r['st'];
        $c  = (int) $r['c'];
        if ( isset( $counts[ $st ] ) ) { $counts[ $st ] = $c; }
        if ( 'trash' !== $st ) { $total += $c; }
    }

    $since = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
    $last7 = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) {$base} AND p.post_status = 'publish' AND p.post_date_gmt >= %s",
        $since
    ) );

    $no_image = (int) $wpdb->get_var(
        "SELECT COUNT(*) {$base} AND p.post_status IN ('publish','future','draft')
         AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} tm WHERE tm.post_id = p.ID AND tm.meta_key = '_thumbnail_id' )"
    );

    wp_send_json_success( array(
        'total'     => (int) $total,
        'published' => (int) $counts['publish'],
        'scheduled' => (int) $counts['future'],
        'drafts'    => (int) ( $counts['draft'] + $counts['pending'] ),
        'last7'     => $last7,
        'no_image'  => $no_image,
    ) );
}

/* ════════════════════════════════════════════
   8a3. AJAX: BULK-DELETE DISTRIBUTION HUB ENTRIES
   Removes the plugin's own rows for the selected ids. Does NOT
   touch the WordPress posts (same as the per-row delete).
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_bulk_delete_distribution', 'wpap_ajax_bulk_delete_distribution' );
function wpap_ajax_bulk_delete_distribution() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
    @set_time_limit( 300 );   /* force-deleting up to 500 posts can take a moment */

    global $wpdb;
    $table = $wpdb->prefix . WPAP_TABLE;

    $raw = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();
    $ids = array();
    foreach ( $raw as $v ) {
        $v = (int) $v;
        if ( $v > 0 ) { $ids[] = $v; }
    }
    $ids = array_values( array_unique( $ids ) );
    if ( empty( $ids ) ) { wp_send_json_error( 'No rows selected.' ); }
    if ( count( $ids ) > 500 ) { $ids = array_slice( $ids, 0, 500 ); }

    /* SAFETY: Hub delete is Hub-ONLY by default (the JS never sends delete_posts).
       If a legacy/cached client ever does, we move the post to the TRASH — which
       is recoverable — NEVER a permanent force-delete. Cap re-checked per post. */
    $post_ids_deleted = 0;
    if ( ! empty( $_POST['delete_posts'] ) ) {
        $ph       = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$table} WHERE id IN ($ph)", $ids ) );
        foreach ( (array) $post_ids as $pid ) {
            $pid = (int) $pid;
            try {
                if ( $pid > 0 && current_user_can( 'delete_post', $pid ) && wp_trash_post( $pid ) ) {   /* Trash, recoverable — never force-delete */
                    $post_ids_deleted++;
                }
            } catch ( \Throwable $e ) {
                error_log( '[Automation Hamri] Bulk post trash failed for #' . $pid . ': ' . $e->getMessage() );
                continue;
            }
        }
    }

    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
    $deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE id IN ($placeholders)", $ids ) );

    wp_send_json_success( array( 'deleted' => (int) $deleted, 'posts_deleted' => (int) $post_ids_deleted ) );
}

/* ════════════════════════════════════════════
   8b. AJAX: DELETE A DISTRIBUTION HUB ENTRY
   Removes the plugin's own record for a row. Does NOT touch
   the WordPress post (delete that from Posts if you want it gone —
   the before_delete_post hook below then removes the Hub row too).
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_delete_distribution', 'wpap_ajax_delete_distribution' );
function wpap_ajax_delete_distribution() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    global $wpdb;
    $table   = $wpdb->prefix . WPAP_TABLE;
    $id      = intval( $_POST['id'] ?? 0 );
    $post_id = intval( $_POST['post_id'] ?? 0 );

    /* SAFETY: Hub-only by default. If a legacy client sends delete_post, move the
       post to the TRASH (recoverable) — never a permanent force-delete. */
    $post_deleted = 0;
    if ( ! empty( $_POST['delete_post'] ) ) {
        if ( $post_id <= 0 && $id > 0 ) {
            $post_id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$table} WHERE id = %d", $id ) );
        }
        if ( $post_id > 0 && current_user_can( 'delete_post', $post_id ) && wp_trash_post( $post_id ) ) {   /* Trash, recoverable */
            $post_deleted = 1;
        }
    }

    if ( $id > 0 ) {
        $deleted = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
    } elseif ( $post_id > 0 ) {
        $deleted = $wpdb->delete( $table, array( 'post_id' => $post_id ), array( '%d' ) );
    } else {
        wp_send_json_error( 'Missing row id.' );
    }

    if ( false === $deleted ) {
        error_log( '[Automation Hamri] Distribution row delete failed: ' . $wpdb->last_error );
        wp_send_json_error( 'Could not delete the entry — see the server error log.' );
    }
    wp_send_json_success( array( 'id' => $id, 'post_id' => $post_id, 'deleted' => (int) $deleted, 'post_deleted' => $post_deleted ) );
}

/* ════════════════════════════════════════════
   8c. AJAX: CLEAN UP ORPHANED HUB ENTRIES
   Removes every Hub row whose linked post has been deleted or trashed.
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_cleanup_distribution', 'wpap_ajax_cleanup_distribution' );
function wpap_ajax_cleanup_distribution() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

    global $wpdb;
    $table   = $wpdb->prefix . WPAP_TABLE;
    $rows    = $wpdb->get_results( "SELECT id, post_id FROM {$table}", ARRAY_A );
    $removed = 0;

    foreach ( (array) $rows as $r ) {
        $pid = intval( $r['post_id'] ?? 0 );
        if ( $pid <= 0 ) {
            continue;   /* not linked to a post — leave it for manual review */
        }
        $status = get_post_status( $pid );   /* false when the post no longer exists */
        if ( false === $status || 'trash' === $status ) {
            $wpdb->delete( $table, array( 'id' => intval( $r['id'] ) ), array( '%d' ) );
            $removed++;
        }
    }
    wp_send_json_success( array( 'removed' => $removed ) );
}

/* Keep the Hub in sync with WordPress: drop a post's Hub row when it is either
   permanently deleted OR moved to the Trash. The trashed_post hook fixes the
   long-standing complaint that removing a post from WordPress left a ghost row
   in the Distribution Hub. */
add_action( 'before_delete_post', 'wpap_remove_distribution_row_for_post' );
add_action( 'trashed_post',       'wpap_remove_distribution_row_for_post' );
function wpap_remove_distribution_row_for_post( $post_id ) {
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . WPAP_TABLE, array( 'post_id' => (int) $post_id ), array( '%d' ) );
}

/* ════════════════════════════════════════════
   CACHE PURGE — make new/scheduled posts appear on cached blog pages.
   Scheduled posts publish silently via WP-Cron, so a page cache (LiteSpeed,
   etc.) keeps serving a stale homepage/blog until it is purged. These hooks
   purge automatically whenever one of THIS plugin's posts goes live.
════════════════════════════════════════════ */
function wpap_purge_caches() {
    /* LiteSpeed Cache — official integration action (no-op if not installed). */
    do_action( 'litespeed_purge_all', 'WP Automator Pro published a post' );
    /* Other common page caches. */
    if ( function_exists( 'rocket_clean_domain' ) )  { rocket_clean_domain(); }          /* WP Rocket */
    if ( function_exists( 'w3tc_flush_all' ) )       { w3tc_flush_all(); }               /* W3 Total Cache */
    if ( function_exists( 'wp_cache_clear_cache' ) ) { wp_cache_clear_cache(); }         /* WP Super Cache */
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) { sg_cachepress_purge_cache(); } /* SiteGround */
}

/* Fires when any post transitions INTO the published state — including a
   scheduled post going live via cron. Scoped to this plugin's posts. */
add_action( 'transition_post_status', 'wpap_purge_cache_on_publish', 10, 3 );
function wpap_purge_cache_on_publish( $new_status, $old_status, $post ) {
    if ( 'publish' !== $new_status || 'publish' === $old_status ) return;
    if ( empty( $post->ID ) || 'post' !== $post->post_type )      return;
    /* Only react to posts this plugin created (they carry a smart-link meta). */
    if ( ! get_post_meta( $post->ID, '_wpap_smart_link', true ) ) return;
    wpap_purge_caches();
}

/* ════════════════════════════════════════════
   9. AJAX: IMAGE PROXY (clipboard CORS fix)
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_proxy_image', 'wpap_ajax_proxy_image' );
function wpap_ajax_proxy_image() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized', 403 );
    $url = esc_url_raw( wp_unslash( $_GET['url'] ?? '' ) );
    if ( ! $url ) wp_die( 'No URL', 400 );
    if ( wp_parse_url( $url, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) wp_die( 'Forbidden', 403 );
    if ( false !== strpos( $url, '..' ) ) wp_die( 'Forbidden', 403 );   /* reject path traversal outright */

    $upload = wp_upload_dir();
    $base   = trailingslashit( $upload['baseurl'] );
    if ( 0 !== strpos( $url, $base ) ) wp_die( 'Forbidden', 403 );      /* must be an uploads URL */

    $file = str_replace( $base, trailingslashit( $upload['basedir'] ), $url );

    /* Containment check: the resolved path MUST sit inside the uploads dir.
       realpath() collapses any ../ and resolves symlinks; wp_normalize_path()
       makes the prefix comparison correct on Windows servers too. */
    $real = realpath( $file );
    $root = realpath( $upload['basedir'] );
    if ( false === $real || false === $root ) wp_die( 'Not found', 404 );
    if ( 0 !== strpos( wp_normalize_path( $real ), trailingslashit( wp_normalize_path( $root ) ) ) ) {
        wp_die( 'Forbidden', 403 );
    }

    /* Resolve MIME safely. fileinfo (mime_content_type) is disabled on some
       hosts and would fatal here, white-screening the proxy. Prefer WP's
       extension-based lookup, fall back to fileinfo if present, then default. */
    $ct   = wp_check_filetype( $real );
    $mime = ! empty( $ct['type'] ) ? $ct['type'] : '';
    if ( ! $mime && function_exists( 'mime_content_type' ) ) {
        $detected = @mime_content_type( $real );
        if ( $detected ) { $mime = $detected; }
    }
    if ( ! $mime ) { $mime = 'application/octet-stream'; }
    header( 'Content-Type: ' . $mime );
    header( 'Cache-Control: max-age=86400' );
    readfile( $real );
    exit;
}

/* ════════════════════════════════════════════
   8b. GOOGLE-SHEET AUTO-PUBLISH AUTOMATION
   Keyless: the user "Publishes" their Google Sheet
   to the web as CSV; we fetch, dedup, and publish
   ready-made rows via wpap_publish_article().
   Options (all autoload=no): wpap_automation (settings),
   wpap_automation_seen (dedup key=>ts), wpap_automation_count
   ({date,count}), wpap_automation_status (readout),
   wpap_automation_fails (key=>fail count).
   NOTE: this module does NOT touch AI content/image generation.
════════════════════════════════════════════ */

/* ── Settings accessor with typed defaults ── */
function wpap_get_automation() {
    $raw = get_option( 'wpap_automation', array() );
    if ( ! is_array( $raw ) ) { $raw = array(); }
    $d = array(
        'enabled'          => false,
        'sheet_url'        => '',
        'per_day'          => 12,
        'per_run'          => 3,
        'default_category' => '',
        'schedule_window'  => 0.0,
    );
    $a = array_merge( $d, $raw );
    return array(
        'enabled'          => (bool) $a['enabled'],
        'sheet_url'        => trim( (string) $a['sheet_url'] ),
        'per_day'          => max( 0, (int) $a['per_day'] ),
        'per_run'          => max( 1, (int) $a['per_run'] ),
        'default_category' => trim( (string) $a['default_category'] ),
        'schedule_window'  => max( 0.0, (float) $a['schedule_window'] ),
    );
}

/* ── Robust RFC-4180 CSV parser (CRLF, quoted fields, embedded newlines/commas,
   doubled "" quotes, UTF-8 BOM). Version-independent. ── */
function wpap_automation_parse_csv( $text ) {
    $text = (string) $text;
    if ( "\xEF\xBB\xBF" === substr( $text, 0, 3 ) ) {   /* strip UTF-8 BOM */
        $text = substr( $text, 3 );
    }
    $rows      = array();
    $row       = array();
    $field     = '';
    $in_quotes = false;
    $started   = false;
    $len       = strlen( $text );
    $max_rows  = 20000;   /* bound memory on a pathological/huge CSV (all-\n or all-,) */
    $max_cols  = 500;

    for ( $i = 0; $i < $len; $i++ ) {
        $ch = $text[ $i ];

        if ( $in_quotes ) {
            if ( '"' === $ch ) {
                if ( $i + 1 < $len && '"' === $text[ $i + 1 ] ) {   /* "" => literal " */
                    $field .= '"';
                    $i++;
                } else {
                    $in_quotes = false;
                }
            } else {
                $field .= $ch;
            }
            continue;
        }

        if ( '"' === $ch ) {
            $in_quotes = true;
            $started   = true;
        } elseif ( ',' === $ch ) {
            $row[]   = $field;
            $field   = '';
            $started = true;
            if ( count( $row ) > $max_cols ) { break; }   /* runaway columns */
        } elseif ( "\n" === $ch ) {
            $row[]   = $field;
            $rows[]  = $row;
            $row     = array();
            $field   = '';
            $started = false;
            if ( count( $rows ) >= $max_rows ) { break; }   /* runaway rows */
        } elseif ( "\r" === $ch ) {
            if ( $i + 1 < $len && "\n" === $text[ $i + 1 ] ) {
                continue;   /* CRLF: let the \n branch close the line */
            }
            $row[]   = $field;
            $rows[]  = $row;
            $row     = array();
            $field   = '';
            $started = false;
            if ( count( $rows ) >= $max_rows ) { break; }
        } else {
            $field  .= $ch;
            $started = true;
        }
    }
    if ( '' !== $field || ! empty( $row ) || $started ) {
        $row[]  = $field;
        $rows[] = $row;
    }
    return $rows;
}

/* ── Block a URL that resolves to a private/reserved/link-local address
   (basic SSRF guard; mirrors the remote-image importer). ── */
function wpap_remote_host_is_public( $url ) {
    $host = wp_parse_url( (string) $url, PHP_URL_HOST );
    if ( ! $host ) { return false; }
    $ips = array();
    if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
        $ips[] = $host;
    } else {
        $records = @dns_get_record( $host, DNS_A + DNS_AAAA );
        if ( is_array( $records ) ) {
            foreach ( $records as $r ) {
                if ( ! empty( $r['ip'] ) )   { $ips[] = $r['ip']; }
                if ( ! empty( $r['ipv6'] ) ) { $ips[] = $r['ipv6']; }
            }
        }
        $v4 = gethostbynamel( $host );
        if ( is_array( $v4 ) ) { $ips = array_merge( $ips, $v4 ); }
    }
    if ( empty( $ips ) ) { return true; }   /* couldn't resolve → let WP handle it */
    foreach ( $ips as $ip ) {
        if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
            return false;
        }
    }
    return true;
}

/* ── Fetch the published CSV and map rows to publishable items. Returns array|WP_Error. ── */
function wpap_automation_fetch_rows( $url ) {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return new WP_Error( 'wpap_no_url', 'No Google Sheet CSV URL configured.' );
    }
    /* Fetch with per-hop SSRF validation: check EVERY redirect target (not just
       the initial host) so a public URL can't bounce the request to an internal
       address. We follow redirects manually (redirection=0) and re-validate. */
    $current  = $url;
    $response = null;
    $followed = 0;
    while ( true ) {
        if ( ! wpap_remote_host_is_public( $current ) ) {
            return new WP_Error( 'wpap_blocked', 'The Sheet URL resolves to a private/reserved address and was blocked.' );
        }
        $response = wp_remote_get( $current, array(
            'timeout'             => 10,
            'redirection'         => 0,            /* follow manually, re-validating each hop */
            'sslverify'           => true,
            'reject_unsafe_urls'  => true,
            'limit_response_size' => 5 * 1024 * 1024,
            'headers'             => array( 'Accept' => 'text/csv, text/plain, */*' ),
        ) );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        $rcode = (int) wp_remote_retrieve_response_code( $response );
        if ( $rcode < 300 || $rcode >= 400 ) {
            break;   /* not a redirect — done */
        }
        if ( ++$followed > 5 ) {
            return new WP_Error( 'wpap_redirects', 'Too many redirects fetching the Sheet.' );
        }
        $loc = trim( (string) wp_remote_retrieve_header( $response, 'location' ) );
        if ( '' === $loc ) {
            return new WP_Error( 'wpap_redirect', 'Sheet redirect had no destination.' );
        }
        if ( preg_match( '#^https?://#i', $loc ) ) {
            $current = $loc;
        } elseif ( 0 === strpos( $loc, '/' ) ) {
            $p = wp_parse_url( $current );
            if ( empty( $p['scheme'] ) || empty( $p['host'] ) ) {
                return new WP_Error( 'wpap_redirect', 'Could not resolve the Sheet redirect.' );
            }
            $current = $p['scheme'] . '://' . $p['host'] . $loc;
        } else {
            return new WP_Error( 'wpap_redirect', 'Unsupported Sheet redirect target.' );
        }
    }
    $code = (int) wp_remote_retrieve_response_code( $response );
    if ( 200 !== $code ) {
        return new WP_Error( 'wpap_http_' . $code, sprintf( 'Sheet fetch failed (HTTP %d). Check the published-CSV URL.', $code ) );
    }

    $body = (string) wp_remote_retrieve_body( $response );
    if ( '' === trim( $body ) ) {
        return new WP_Error( 'wpap_empty', 'The published Sheet returned no data.' );
    }

    /* Guard against the wrong URL (edit/view page instead of the CSV export). */
    $ctype = (string) wp_remote_retrieve_header( $response, 'content-type' );
    if ( false !== stripos( $ctype, 'text/html' ) || 0 === strncmp( ltrim( $body ), '<', 1 ) ) {
        return new WP_Error( 'wpap_not_csv', 'That URL returned a web page, not CSV. Use File -> Share -> Publish to web -> CSV (the link should end in output=csv).' );
    }

    $table = wpap_automation_parse_csv( $body );
    if ( count( $table ) < 2 ) {
        return new WP_Error( 'wpap_no_rows', 'The Sheet has a header but no data rows.' );
    }

    /* Header row: lowercased + trimmed; first occurrence of each name wins. */
    $header = array_map( function ( $h ) { return strtolower( trim( (string) $h ) ); }, array_shift( $table ) );
    $idx = array();
    foreach ( $header as $pos => $name ) {
        if ( '' !== $name && ! isset( $idx[ $name ] ) ) {
            $idx[ $name ] = $pos;
        }
    }
    $get = function ( array $cols, $names ) use ( $idx ) {
        foreach ( (array) $names as $n ) {
            if ( isset( $idx[ $n ], $cols[ $idx[ $n ] ] ) ) {
                $v = trim( (string) $cols[ $idx[ $n ] ] );
                if ( '' !== $v ) { return $v; }
            }
        }
        return '';
    };

    $items = array();
    foreach ( $table as $cols ) {
        if ( ! is_array( $cols ) ) { continue; }
        $title   = $get( $cols, array( 'title' ) );
        $content = $get( $cols, array( 'content', 'body' ) );
        if ( '' === $title && '' === $content ) {   /* skip empty rows */
            continue;
        }
        $items[] = array(
            'title'           => $title,
            'content'         => $content,
            'imageUrl'        => $get( $cols, array( 'imageurl', 'image_url', 'image' ) ),
            'hook'            => $get( $cols, array( 'hook', 'comment', 'caption' ) ),
            'category'        => $get( $cols, array( 'category' ) ),
            'id'              => $get( $cols, array( 'id' ) ),
            'metaDescription' => $get( $cols, array( 'metadescription', 'meta_description', 'description' ) ),
            'metaTitle'       => $get( $cols, array( 'metatitle', 'meta_title', 'seo_title' ) ),
            'focusKeyword'    => $get( $cols, array( 'focuskeyword', 'focus_keyword', 'keyword' ) ),
            'tags'            => $get( $cols, array( 'tags' ) ),
        );
    }
    if ( empty( $items ) ) {
        return new WP_Error( 'wpap_no_valid', 'No rows with a title or content were found.' );
    }
    return $items;
}

/* ── Dedup key: trimmed id if present, else md5(lower(title)|md5(content)) ── */
function wpap_automation_row_key( array $item ) {
    $id = isset( $item['id'] ) ? trim( (string) $item['id'] ) : '';
    if ( '' !== $id ) {
        return $id;
    }
    $title = (string) ( $item['title'] ?? '' );
    $title = function_exists( 'mb_strtolower' ) ? mb_strtolower( trim( $title ) ) : strtolower( trim( $title ) );
    return md5( $title . '|' . md5( (string) ( $item['content'] ?? '' ) ) );
}

function wpap_automation_get_seen() {
    $seen = get_option( 'wpap_automation_seen', array() );
    return is_array( $seen ) ? $seen : array();
}
function wpap_automation_prune_seen( array $seen, $max_age_days = 120, $max_entries = 5000 ) {
    $cutoff = time() - ( (int) $max_age_days * DAY_IN_SECONDS );
    foreach ( $seen as $k => $ts ) {
        if ( (int) $ts < $cutoff ) { unset( $seen[ $k ] ); }
    }
    if ( count( $seen ) > $max_entries ) {
        asort( $seen, SORT_NUMERIC );
        $seen = array_slice( $seen, count( $seen ) - $max_entries, null, true );
    }
    return $seen;
}

function wpap_automation_bump_failure( $key ) {
    $f = get_option( 'wpap_automation_fails', array() );
    if ( ! is_array( $f ) ) { $f = array(); }
    $n = isset( $f[ $key ] ) ? (int) $f[ $key ] + 1 : 1;
    $f[ $key ] = $n;
    if ( count( $f ) > 1000 ) { $f = array_slice( $f, -1000, null, true ); }
    update_option( 'wpap_automation_fails', $f, false );
    return $n;
}
function wpap_automation_clear_failure( $key ) {
    $f = get_option( 'wpap_automation_fails', array() );
    if ( ! is_array( $f ) || ! isset( $f[ $key ] ) ) { return; }
    unset( $f[ $key ] );
    update_option( 'wpap_automation_fails', $f, false );
}

function wpap_automation_write_status( array $partial ) {
    $status = array_merge( array(
        'last_run'   => current_time( 'mysql' ),
        'rows_found' => 0,
        'published'  => 0,
        'skipped'    => 0,
        'errors'     => 0,
        'message'    => '',
    ), $partial );
    update_option( 'wpap_automation_status', $status, false );
    return $status;
}

/* ── The run: fetch the Sheet, publish up to the per-run/per-day budget ── */
function wpap_automation_run( $force = false ) {
    @set_time_limit( 300 );
    $auto      = wpap_get_automation();
    $sheet_url = $auto['sheet_url'];

    /* Automation is a licensed-only feature (mirrors the rest of the plugin). */
    if ( ! wpap_is_licensed() ) {
        return wpap_automation_write_status( array( 'message' => 'Plugin not activated.' ) );
    }
    if ( ! $force && ! $auto['enabled'] ) {
        return wpap_automation_write_status( array( 'message' => 'Automation is disabled.' ) );
    }
    if ( '' === $sheet_url ) {
        return wpap_automation_write_status( array( 'message' => 'No Google Sheet CSV URL is configured.' ) );
    }

    /* Atomic mutual exclusion for BOTH cron and manual Run-now. add_option()
       INSERTs a UNIQUE option row, so only ONE concurrent run acquires it — this
       avoids the get-then-set TOCTOU that could let two runs read the same
       seen-set and double-publish. Stale locks (a prior run that died) are
       reclaimed after 10 minutes. */
    $now = time();
    if ( ! add_option( 'wpap_automation_lock', $now, '', 'no' ) ) {
        $held = (int) get_option( 'wpap_automation_lock', 0 );
        if ( $held && ( $now - $held ) < 10 * MINUTE_IN_SECONDS ) {
            return wpap_automation_write_status( array( 'message' => 'A run is already in progress — try again shortly.' ) );
        }
        update_option( 'wpap_automation_lock', $now, false );   /* reclaim a stale lock */
    }
    $locked = true;

    /* Per-day budget (resets when the local date rolls over). */
    $today = current_time( 'Ymd' );
    $count = get_option( 'wpap_automation_count', array() );
    if ( ! is_array( $count ) || ( isset( $count['date'] ) ? $count['date'] : '' ) !== $today ) {
        $count = array( 'date' => $today, 'count' => 0 );
    }
    $todays_count    = (int) $count['count'];
    $remaining_today = max( 0, (int) $auto['per_day'] - $todays_count );

    if ( $remaining_today <= 0 ) {
        if ( $locked ) { delete_option( 'wpap_automation_lock' ); }
        return wpap_automation_write_status( array(
            'message' => sprintf( 'Daily limit reached (%d/%d).', $todays_count, (int) $auto['per_day'] ),
        ) );
    }

    $rows = wpap_automation_fetch_rows( $sheet_url );
    if ( is_wp_error( $rows ) ) {
        if ( $locked ) { delete_option( 'wpap_automation_lock' ); }
        return wpap_automation_write_status( array(
            'errors'  => 1,
            'message' => 'Fetch error: ' . $rows->get_error_message(),
        ) );
    }

    /* Author the posts to an administrator (cron has no logged-in user, which
       would author as user 0). We set post_author explicitly rather than
       wp_set_current_user() so we don't mutate the global current user for other
       cron callbacks — and we force kses on the body below because Sheet content
       is external (outside the trust boundary), regardless of author. */
    $author_id = get_current_user_id();
    if ( ! $author_id ) {
        $admins = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID', 'orderby' => 'ID', 'order' => 'ASC' ) );
        if ( ! empty( $admins ) ) { $author_id = (int) $admins[0]; }
    }

    $rows_found   = count( $rows );
    $budget       = min( (int) $auto['per_run'], $remaining_today );
    $seen         = wpap_automation_get_seen();
    $published    = 0;
    $skipped      = 0;
    $errors       = 0;
    $last_err     = '';
    $attempts     = 0;
    $max_attempts = $budget + 10;
    $deadline     = time() + 240;   /* stop before the 300s time limit to avoid a mid-loop kill */

    foreach ( $rows as $item ) {
        if ( $published >= $budget || $attempts >= $max_attempts || time() > $deadline ) {
            break;
        }
        $key = wpap_automation_row_key( $item );
        if ( isset( $seen[ $key ] ) ) {   /* survives post deletion: never recreated */
            $skipped++;
            continue;
        }
        $attempts++;

        $result = wpap_publish_article( $item, array(
            'default_parts'    => 1,
            'schedule_window'  => (float) $auto['schedule_window'],
            'default_category' => (string) $auto['default_category'],
            'source_key'       => $key,
            'author'           => $author_id,
            'force_kses'       => true,
        ) );

        if ( is_wp_error( $result ) ) {
            $errors++;
            $last_err = $result->get_error_message();
            /* Retry next run, but give up after 3 failures so a permanently
               bad row can't loop forever. */
            if ( wpap_automation_bump_failure( $key ) >= 3 ) {
                $seen[ $key ] = time();
                wpap_automation_clear_failure( $key );
            }
            continue;
        }

        $seen[ $key ] = time();
        $published++;
        $todays_count++;
        wpap_automation_clear_failure( $key );
        /* Persist progress immediately so a mid-run crash can't republish. */
        update_option( 'wpap_automation_seen', $seen, false );
        update_option( 'wpap_automation_count', array( 'date' => $today, 'count' => $todays_count ), false );
    }

    update_option( 'wpap_automation_seen', wpap_automation_prune_seen( $seen ), false );
    $count['date']  = $today;
    $count['count'] = $todays_count;
    update_option( 'wpap_automation_count', $count, false );

    if ( $published > 0 ) {
        wpap_purge_caches();
    }
    if ( $locked ) { delete_option( 'wpap_automation_lock' ); }

    $msg = sprintf( 'Found %d row(s); published %d, skipped %d, error(s) %d.', $rows_found, $published, $skipped, $errors );
    if ( '' !== $last_err ) { $msg .= ' Last error: ' . $last_err; }

    return wpap_automation_write_status( array(
        'rows_found' => $rows_found,
        'published'  => $published,
        'skipped'    => $skipped,
        'errors'     => $errors,
        'message'    => $msg,
    ) );
}

/* Recurring cron → the runner (scheduled in wpap_activate, cleared in wpap_deactivate). */
add_action( 'wpap_automation_cron', 'wpap_automation_run' );

/* AJAX: "Run now" button on the Settings page. */
add_action( 'wp_ajax_wpap_automation_run_now', 'wpap_ajax_automation_run_now' );
function wpap_ajax_automation_run_now() {
    check_ajax_referer( 'wpap_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized' );
    }
    @set_time_limit( 120 );
    $status = wpap_automation_run( true );
    wp_send_json_success( $status );
}

/* ════════════════════════════════════════════
   10. CLAUDE CONTENT GENERATOR
════════════════════════════════════════════ */
function wpap_generate_content( string $title, string $api_key, string $target_lang = 'auto', int $num_pages = 2 ) {

    /* Build language instruction for Claude */
    $lang_map = array(
        'en'=>'English','fr'=>'French','es'=>'Spanish','de'=>'German',
        'it'=>'Italian','pt'=>'Portuguese','nl'=>'Dutch','pl'=>'Polish',
        'ro'=>'Romanian','hu'=>'Hungarian','bg'=>'Bulgarian','cs'=>'Czech',
        'sk'=>'Slovak','hr'=>'Croatian','sv'=>'Swedish','da'=>'Danish',
        'fi'=>'Finnish','el'=>'Greek','ru'=>'Russian','uk'=>'Ukrainian',
        'tr'=>'Turkish','ar'=>'Arabic','he'=>'Hebrew','fa'=>'Persian',
        'zh'=>'Chinese (Simplified)','ja'=>'Japanese','ko'=>'Korean',
        'hi'=>'Hindi','id'=>'Indonesian','vi'=>'Vietnamese','th'=>'Thai',
    );
    $lang_name = ( $target_lang !== 'auto' && isset( $lang_map[ $target_lang ] ) )
        ? $lang_map[ $target_lang ]
        : '';

    /* Language instruction line — empty string when auto-detect */
    $lang_line = $lang_name
        ? "LANGUAGE INSTRUCTION: Write the ENTIRE article in " . $lang_name . ". Translate the title into " . $lang_name . " as well. Every word — including the FB hook and all navigation text — must be in " . $lang_name . ".\n\n"
        : '';

    /* Build dynamic page tags based on $num_pages */
    $words_per_page = (int) round( 600 / $num_pages );
    $page_tags      = '';
    for ( $pg = 1; $pg <= $num_pages; $pg++ ) {
        if ( $pg === 1 ) {
            $page_tags .= "[PAGE{$pg}]\n"
                       . "First ~{$words_per_page} words in the target language. Introduction + 2-3 rich paragraphs.\n\n";
        } elseif ( $pg === $num_pages ) {
            $page_tags .= "[PAGE{$pg}]\n"
                       . "Final ~{$words_per_page} words. Conclusion + call-to-action.\n\n";
        } else {
            $page_tags .= "[PAGE{$pg}]\n"
                       . "~{$words_per_page} words. Continuation with tips and details.\n\n";
        }
    }
    $total_words = 600 + ( ($num_pages - 2) * 150 );  /* More pages = more content */

    $prompt = $lang_line
            . "Write a professional {$total_words}-word SEO article about: \"" . addslashes( $title ) . "\"\n\n"
            . "Divide the article into EXACTLY {$num_pages} pages using these tags:\n\n"
            . $page_tags
            . "[FB_TEXT]\n"
            . "Write a viral Facebook hook of EXACTLY 2 sentences in the SAME language as the article.\n"
            . "The hook MUST be a creative, engaging teaser drawn from the article CONTENT — NOT the title.\n"
            . "STRICTLY FORBIDDEN: Do NOT copy, echo, or paraphrase the article title.\n"
            . "Write a unique summary that highlights a key insight or benefit from the article body.\n"
            . "Max 40 words total. Engaging and conversational tone. No hashtags. No emojis. No CTA.\n"
            . "STOP after the 2 sentences. Do NOT add any call-to-action or comment mention.\n\n"
            . "[LANG]\n"
            . "Write only the 2-letter ISO language code (e.g. en, fr, ar, hu, es, de, it, pt, tr, nl, ru).\n\n"
            . "CRITICAL OUTPUT RULES — READ CAREFULLY:\n"
            . "Respond with raw HTML only.\n"
            . "If you include any backticks or the word html at the start, the system will FAIL.\n"
            . "PURE HTML ONLY. No markdown. No code fences. No backticks. No html label.\n"
            . "DO NOT start your response with ```html or ``` or any backtick character.\n"
            . "Plain text only inside PAGE tags. No bullet points.\n"
            . "Write all content in the target language.";

    $r = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 120,
        'headers' => array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'model'      => 'claude-opus-4-5',
            'max_tokens' => 1400,
            'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
        ) ),
    ) );

    if ( is_wp_error( $r ) ) return $r;

    $code = wp_remote_retrieve_response_code( $r );
    $body = json_decode( wp_remote_retrieve_body( $r ), true );
    if ( $code !== 200 ) {
        return new WP_Error( 'claude', 'Claude API ' . $code . ': ' . ( $body['error']['message'] ?? 'unknown error' ) );
    }

    $text    = $body['content'][0]['text'] ?? '';
    $page1   = '';
    $page2   = '';
    $fb_text = '';
    $lang    = 'en';

    /* Parse N page tags dynamically */
    $pages_arr = array();
    for ( $pg = 1; $pg <= $num_pages; $pg++ ) {
        $next_tag = ( $pg < $num_pages ) ? "\[PAGE" . ( $pg + 1 ) . "\]" : '(?:\[FB_TEXT\]|$)';
        if ( preg_match( '/\[PAGE' . $pg . '\](.*?)' . $next_tag . '/s', $text, $m ) ) {
            $pages_arr[] = wpap_nl2p( wpap_strip_markdown( trim( $m[1] ) ) );
        }
    }
    if ( preg_match( '/\[FB_TEXT\](.*?)(?:\[LANG\]|$)/s',  $text, $m ) ) $fb_text = trim( $m[1] );
    if ( preg_match( '/\[LANG\]\s*([a-z]{2})/i',            $text, $m ) ) $lang    = strtolower( trim( $m[1] ) );

    /* Fallback: if parsing failed, split text evenly */
    if ( empty( $pages_arr ) ) {
        $words    = explode( ' ', strip_tags( $text ) );
        $chunk    = (int) ceil( count( $words ) / $num_pages );
        for ( $pg = 0; $pg < $num_pages; $pg++ ) {
            $pages_arr[] = wpap_nl2p( implode( ' ', array_slice( $words, $pg * $chunk, $chunk ) ) );
        }
        $fb_text  = substr( $text, 0, 300 );
    }

    /* Keep backward-compat vars for page1/page2 */
    $page1 = $pages_arr[0] ?? '';
    $page2 = $pages_arr[1] ?? '';

    /* Language auto-detection from content */
    if ( $lang === 'en' ) {
        $p = $page1;
        if      ( preg_match( '/[\x{0600}-\x{06FF}]/u', $p ) )  $lang = 'ar';
        elseif  ( preg_match( '/[\x{4E00}-\x{9FFF}]/u', $p ) )  $lang = 'zh';
        elseif  ( preg_match( '/[\x{0400}-\x{04FF}]/u', $p ) )  $lang = 'ru';
        elseif  ( preg_match( '/[\x{3040}-\x{30FF}]/u', $p ) )  $lang = 'ja';
        elseif  ( preg_match( '/[\x{AC00}-\x{D7AF}]/u', $p ) )  $lang = 'ko';
        elseif  ( preg_match( '/[őűáéíóöü]/u', $p ) &&
                  preg_match( '/\b(az|egy|van|nem|hogy|és|csak|már)\b/iu', $p ) ) $lang = 'hu';
        elseif  ( preg_match( '/[àâèéêëîïôùûüç]/u', $p ) &&
                  preg_match( '/\b(le|la|les|est|une|avec|pour)\b/i', $p ) )      $lang = 'fr';
        elseif  ( preg_match( '/\b(le|la|les|est|avec|pour|aussi|très)\b/i',$p)) $lang = 'fr';
        elseif  ( preg_match( '/[¿¡ñ]/u', $p ) )                                  $lang = 'es';
        elseif  ( preg_match( '/\b(del|los|las|para|con|pero|más|son)\b/i', $p )) $lang = 'es';
        elseif  ( preg_match( '/[ß]/u', $p ) )                                     $lang = 'de';
        elseif  ( preg_match( '/\b(der|die|das|und|mit|ist|nicht|für)\b/i', $p )) $lang = 'de';
        elseif  ( preg_match( '/\b(della|dello|questo|questa|sono|anche)\b/i',$p)) $lang = 'it';
        elseif  ( preg_match( '/[ãõ]/u', $p ) )                                    $lang = 'pt';
        elseif  ( preg_match( '/\b(não|com|para|uma|dos|mais|seu)\b/i', $p ) )    $lang = 'pt';
        elseif  ( preg_match( '/[şğı]/u', $p ) )                                   $lang = 'tr';
        elseif  ( preg_match( '/\b(van|het|een|zijn|maar|voor|heeft)\b/i', $p ) ) $lang = 'nl';
    }

    return array(
        'page1'   => $page1,        /* already processed by wpap_nl2p */
        'page2'   => $page2,
        'pages'   => $pages_arr,    /* full array of N pages */
        'fb_text' => $fb_text,
        'lang'    => $lang,
    );
}

/* ==============================================
   STRIP MARKDOWN ARTIFACTS
   Removes ```html, ```, and backtick wrappers
   that AI models sometimes add to responses.
============================================== */
function wpap_strip_markdown( string $text ): string {
    $text = trim( $text );

    /* Pass 1: Remove full fenced blocks ```lang ... ``` (multi-line) */
    $text = preg_replace( '/^```[a-zA-Z]*\r?\n?/m', '', $text );
    $text = preg_replace( '/\r?\n?```\s*$/m',        '', $text );

    /* Pass 2: Remove any remaining ``` or `` sequences */
    $text = str_replace( array( '```', '``' ), '', $text );

    /* Pass 3: Remove single-backtick wrappers around the entire string */
    $text = trim( $text );
    if ( strlen( $text ) > 2 && $text[0] === '`' && $text[ strlen( $text ) - 1 ] === '`' ) {
        $text = trim( $text, '`' );
    }

    /* Pass 4: Remove stray lone backticks */
    $text = str_replace( '`', '', $text );

    /* Pass 5: Remove "html" label left at start after stripping fences */
    $text = preg_replace( '/^html\s*\r?\n?/i', '', $text );

    return trim( $text );
}

/* ==============================================
   NUCLEAR HTML CLEANER
   Called on every page segment before saving.
   Handles two opposite failure modes:
     A) AI returned markdown/plain-text with
        literal <p> showing as escaped text.
     B) AI returned valid HTML that must be
        preserved as-is.
============================================== */
function wpap_nuclear_clean( string $text ): string {

    $text = wpap_strip_markdown( $text );
    $text = trim( $text );

    if ( $text === '' ) return '';

    /* ── Detect which failure mode we are in ──
     *
     * Mode A — AI returned the HTML tags as visible escaped text.
     * This happens when the raw string contains &lt;p&gt; or the
     * literal 4-char sequence "<p>" but *also* contains plain prose
     * mixed in, suggesting the AI double-escaped or mixed formats.
     *
     * Heuristic: if the text contains the literal strings "&lt;" or
     * escaped angle brackets as plain visible text characters, decode
     * them first so we end up with real HTML.
     */
    if ( strpos( $text, '&lt;' ) !== false || strpos( $text, '&amp;lt;' ) !== false ) {
        $text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $text = trim( $text );
    }

    /* ── Now decide: does the text already contain real HTML tags? ── */
    $has_html = (bool) preg_match( '/<(p|h[1-6]|ul|ol|li|strong|em|br|div|span|a)\b/i', $text );

    if ( $has_html ) {
        /*
         * Text contains real HTML. Preserve it but strip any
         * stray plain-text artifacts that aren't inside tags
         * (e.g. a lone "&lt;p&gt;" that survived entity-decode).
         * wp_kses_post allows all standard post HTML.
         */
        return wp_kses_post( $text );
    }

    /*
     * Pure plain text — wrap paragraphs in <p> tags.
     * Split on double newlines, nl2br single newlines.
     * Do NOT esc_html here because the AI content is
     * trusted article text, not user input, and escaping
     * would make tags like apostrophes show as &#039;.
     */
    $paras = preg_split( '/\n{2,}/', $text );
    $html  = '';
    foreach ( $paras as $para ) {
        $para = trim( $para );
        if ( $para !== '' ) {
            $html .= '<p>' . nl2br( $para ) . '</p>' . "\n";
        }
    }
    return $html ?: '<p>' . $text . '</p>';
}

function wpap_nl2p( string $text ): string {
    return wpap_nuclear_clean( $text );
}


/* ==============================================
   INTERNAL LINK INJECTION
============================================== */
function wpap_inject_internal_links( string $content, array $pool, string $engine, string $claude_key, string $gemini_key ): string {
    if ( empty( $pool ) ) return $content;
    $list = '';
    foreach ( $pool as $p ) {
        $list .= '  - "' . addslashes( $p['title'] ) . '" -> ' . $p['url'] . "\n";
    }
    $prompt = "INTERNAL LINKING:\nEmbed 2-3 natural HTML anchor links (<a href=\"URL\">keyword</a>) inside the article where contextually relevant.\nPool:\n{$list}Rules: feel natural, no forced links, each URL max once.\n\nBelow is the article. Return ONLY the modified article HTML, no explanation.\n\n" . $content;
    $text = '';
    if ( $engine === 'claude_haiku' && $claude_key ) {
        $r = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'timeout' => 60,
            'headers' => array( 'x-api-key' => $claude_key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json' ),
            'body'    => wp_json_encode( array( 'model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 2048,
                'messages' => array( array( 'role' => 'user', 'content' => $prompt ) ) ) ),
        ) );
        if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
            $b = json_decode( wp_remote_retrieve_body( $r ), true );
            $text = $b['content'][0]['text'] ?? '';
        }
    } elseif ( $gemini_key ) {
        $mdl = ( $engine === 'gemini_pro' ) ? 'gemini-1.5-pro' : 'gemini-2.0-flash';
        $r   = wp_remote_post( "https://generativelanguage.googleapis.com/v1beta/models/{$mdl}:generateContent?key={$gemini_key}",
            array( 'timeout' => 60, 'headers' => array( 'Content-Type' => 'application/json' ),
                   'body' => wp_json_encode( array(
                       'contents' => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
                       'generationConfig' => array( 'maxOutputTokens' => 2048 ),
                   ) ) )
        );
        if ( ! is_wp_error( $r ) && 200 === (int) wp_remote_retrieve_response_code( $r ) ) {
            $b = json_decode( wp_remote_retrieve_body( $r ), true );
            $text = $b['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
    }
    return ( $text && strpos( $text, '<a href' ) !== false && strlen( $text ) > 200 ) ? $text : $content;
}

/* ════════════════════════════════════════════
   GEMINI CONTENT GENERATOR (Free/Fast alternative)
   Uses gemini-2.0-flash to generate multilingual
   SEO articles. Same page-tag format as Claude.
════════════════════════════════════════════ */
function wpap_generate_content_gemini( string $title, string $api_key, string $target_lang = 'auto', int $num_pages = 2 ) {

    /* Reuse same lang_map as Claude engine */
    $lang_map = array(
        'en'=>'English','fr'=>'French','es'=>'Spanish','de'=>'German',
        'it'=>'Italian','pt'=>'Portuguese','nl'=>'Dutch','pl'=>'Polish',
        'ro'=>'Romanian','hu'=>'Hungarian','bg'=>'Bulgarian','cs'=>'Czech',
        'sk'=>'Slovak','hr'=>'Croatian','sv'=>'Swedish','da'=>'Danish',
        'fi'=>'Finnish','el'=>'Greek','ru'=>'Russian','uk'=>'Ukrainian',
        'tr'=>'Turkish','ar'=>'Arabic','he'=>'Hebrew','fa'=>'Persian',
        'zh'=>'Chinese (Simplified)','ja'=>'Japanese','ko'=>'Korean',
        'hi'=>'Hindi','id'=>'Indonesian','vi'=>'Vietnamese','th'=>'Thai',
    );
    $lang_name = ( $target_lang !== 'auto' && isset( $lang_map[ $target_lang ] ) )
        ? $lang_map[ $target_lang ]
        : '';

    $lang_line = $lang_name
        ? "LANGUAGE INSTRUCTION: Write the ENTIRE article in " . $lang_name . ". Translate the title into " . $lang_name . " as well. Every word must be in " . $lang_name . ".\n\n"
        : '';

    /* Build page tags */
    $words_per_page = (int) round( 600 / $num_pages );
    $page_tags      = '';
    for ( $pg = 1; $pg <= $num_pages; $pg++ ) {
        if ( $pg === 1 ) {
            $page_tags .= "[PAGE{$pg}]\nFirst ~{$words_per_page} words. Introduction + 2-3 rich paragraphs.\n\n";
        } elseif ( $pg === $num_pages ) {
            $page_tags .= "[PAGE{$pg}]\nFinal ~{$words_per_page} words. Conclusion + call-to-action.\n\n";
        } else {
            $page_tags .= "[PAGE{$pg}]\n~{$words_per_page} words. Continuation with tips and details.\n\n";
        }
    }
    $total_words = 600 + ( ( $num_pages - 2 ) * 150 );

    $prompt = $lang_line
            . "Write a professional {$total_words}-word SEO article about: \"" . addslashes( $title ) . "\"\n\n"
            . "Divide the article into EXACTLY {$num_pages} pages using these tags:\n\n"
            . $page_tags
            . "[FB_TEXT]\n"
            . "Write a viral Facebook hook of EXACTLY 2 sentences in the SAME language as the article.\n"
            . "The hook MUST be a creative, engaging teaser drawn from the article CONTENT — NOT the title.\n"
            . "STRICTLY FORBIDDEN: Do NOT copy, echo, or paraphrase the article title.\n"
            . "Write a unique summary that highlights a key insight or benefit from the article body.\n"
            . "Max 40 words total. Engaging and conversational tone. No hashtags. No emojis. No CTA.\n"
            . "STOP after the 2 sentences. Do NOT add any call-to-action or comment mention.\n\n"
            . "[LANG]\n"
            . "Write only the 2-letter ISO language code (e.g. en, fr, ar, es, de, it, pt, tr, nl, ru).\n\n"
            . "CRITICAL OUTPUT RULES — READ CAREFULLY:\n"
            . "Respond with raw HTML only.\n"
            . "If you include any backticks or the word html at the start, the system will FAIL.\n"
            . "PURE HTML ONLY. No markdown. No code fences. No backticks. No html label.\n"
            . "DO NOT start your response with ```html or ``` or any backtick character.\n"
            . "Plain text only inside PAGE tags. No bullet points.";

    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=' . $api_key;

    $r = wp_remote_post( $endpoint, array(
        'timeout' => 120,
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( array(
            'contents'         => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
            'generationConfig' => array(
                'temperature'     => 0.7,
                'maxOutputTokens' => 2048,
            ),
        ) ),
    ) );

    if ( is_wp_error( $r ) ) return $r;

    $code = wp_remote_retrieve_response_code( $r );
    $body = json_decode( wp_remote_retrieve_body( $r ), true );
    if ( $code !== 200 ) {
        $msg = $body['error']['message'] ?? ( 'HTTP ' . $code );
        return new WP_Error( 'gemini_content', 'Gemini Content API: ' . $msg );
    }

    $text    = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if ( ! $text ) {
        return new WP_Error( 'gemini_content', 'Gemini returned empty content.' );
    }

    /* Parse pages — identical logic to Claude parser */
    $pages_arr = array();
    $fb_text   = '';
    $lang      = 'en';

    for ( $pg = 1; $pg <= $num_pages; $pg++ ) {
        $next_tag = ( $pg < $num_pages ) ? "\[PAGE" . ( $pg + 1 ) . "\]" : '(?:\[FB_TEXT\]|$)';
        if ( preg_match( '/\[PAGE' . $pg . '\](.*?)' . $next_tag . '/s', $text, $m ) ) {
            $pages_arr[] = wpap_nl2p( wpap_strip_markdown( trim( $m[1] ) ) );
        }
    }
    if ( preg_match( '/\[FB_TEXT\](.*?)(?:\[LANG\]|$)/s',  $text, $m ) ) $fb_text = trim( $m[1] );
    if ( preg_match( '/\[LANG\]\s*([a-z]{2})/i',            $text, $m ) ) $lang    = strtolower( trim( $m[1] ) );

    /* Fallback split */
    if ( empty( $pages_arr ) ) {
        $words = explode( ' ', strip_tags( $text ) );
        $chunk = (int) ceil( count( $words ) / $num_pages );
        for ( $pg = 0; $pg < $num_pages; $pg++ ) {
            $pages_arr[] = wpap_nl2p( implode( ' ', array_slice( $words, $pg * $chunk, $chunk ) ) );
        }
        $fb_text = substr( $text, 0, 300 );
    }

    return array(
        'page1'   => $pages_arr[0] ?? '',
        'page2'   => $pages_arr[1] ?? '',
        'pages'   => $pages_arr,
        'fb_text' => $fb_text,
        'lang'    => $lang,
    );
}

/* ════════════════════════════════════════════
   CLAUDE IMAGE GENERATOR (Premium)
   Uses Anthropic claude-3-5-sonnet to describe → then Pollinations renders
   Falls back to prompt-based generation via DALL-E style description.
   Note: Anthropic API does not natively generate images; we use their
   vision-grade model to craft an optimised prompt, then call Pollinations.
════════════════════════════════════════════ */
function wpap_generate_image_claude( string $title, int $post_id, string $api_key ) {
    /* Step 1: Ask Claude to write the best image-gen prompt for this title */
    $r_prompt = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
        'timeout' => 30,
        'headers' => array(
            'x-api-key'         => $api_key,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 120,
            'messages'   => array( array(
                'role'    => 'user',
                'content' => 'Write a single-line image generation prompt (max 80 words) for a professional food photography photo of: "' . addslashes( $title ) . '". Focus on: lighting, composition, style, colors. Output ONLY the prompt, no labels.',
            ) ),
        ) ),
    ) );

    $img_prompt = '';
    if ( ! is_wp_error( $r_prompt ) && 200 === (int) wp_remote_retrieve_response_code( $r_prompt ) ) {
        $bd         = json_decode( wp_remote_retrieve_body( $r_prompt ), true );
        $img_prompt = trim( $bd['content'][0]['text'] ?? '' );
    }

    /* Fallback prompt if Claude call failed */
    if ( ! $img_prompt ) {
        $img_prompt = 'Professional food photography of ' . $title . ', studio lighting, vibrant colors, appetizing, 4K';
    }

    /* Step 2: Generate image via Pollinations with the Claude-crafted prompt */
    $poll_url = 'https://image.pollinations.ai/prompt/' . urlencode( $img_prompt ) . '?width=800&height=600&nologo=true&enhance=true&model=flux';
    $r_img    = wp_remote_get( $poll_url, array( 'timeout' => 90 ) );

    if ( is_wp_error( $r_img ) ) {
        return new WP_Error( 'claude_img', 'Pollinations error: ' . $r_img->get_error_message() );
    }

    $img_data = wp_remote_retrieve_body( $r_img );
    $ct       = wp_remote_retrieve_header( $r_img, 'content-type' );

    if ( ! $img_data || false === strpos( $ct, 'image' ) ) {
        return new WP_Error( 'claude_img', 'No image returned from Pollinations (Claude engine)' );
    }

    /* Save to WP Media Library */
    $ext      = ( false !== strpos( $ct, 'jpeg' ) ) ? 'jpg' : 'png';
    $mime     = ( $ext === 'jpg' ) ? 'image/jpeg' : 'image/png';
    $filename = sanitize_file_name( $post_id . '-claude-' . sanitize_title( $title ) . '.' . $ext );
    $upload   = wp_upload_bits( $filename, null, $img_data );

    if ( $upload['error'] ) {
        return new WP_Error( 'upload', $upload['error'] );
    }

    require_once ABSPATH . 'wp-admin/includes/image.php';
    $aid = wp_insert_attachment( array(
        'guid'           => $upload['url'],
        'post_mime_type' => $mime,
        'post_title'     => $title,
        'post_status'    => 'inherit',
    ), $upload['file'], $post_id );
    wp_update_attachment_metadata( $aid, wp_generate_attachment_metadata( $aid, $upload['file'] ) );
    return $aid;
}

/* ==============================================
   GEMINI IMAGE ENGINE (Flash+30s retry / Pro)
   mode=flash → gemini-2.0-flash-preview-image-generation
   mode=pro   → imagen-3.0-generate-002
   Fallback: Pollinations
============================================== */
function wpap_generate_image_gemini( string $title, int $post_id, string $api_key, string $mode = 'flash' ) {
    $prompt = 'Professional food photography of "' . $title . '". Studio lighting, vibrant colors, 4K, no text.';
    $b64 = ''; $mime = 'image/jpeg';
    if ( $mode === 'pro' ) {
        $r = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict?key=' . $api_key,
            array( 'timeout'=>90,'headers'=>array('Content-Type'=>'application/json'),
                   'body'=>wp_json_encode(array('instances'=>array(array('prompt'=>$prompt)),'parameters'=>array('sampleCount'=>1,'aspectRatio'=>'4:3'))) )
        );
        if ( !is_wp_error($r) && 200===(int)wp_remote_retrieve_response_code($r) ) {
            $d=$json=json_decode(wp_remote_retrieve_body($r),true);
            $b64=$d['predictions'][0]['bytesBase64Encoded']??''; $mime=$d['predictions'][0]['mimeType']??'image/jpeg';
        }
    } else {
        $ep   = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-preview-image-generation:generateContent?key=' . $api_key;
        $body = wp_json_encode(array('contents'=>array(array('parts'=>array(array('text'=>$prompt)))),'generationConfig'=>array('responseModalities'=>array('TEXT','IMAGE'))));
        for ( $try=1; $try<=2; $try++ ) {
            $r    = wp_remote_post($ep,array('timeout'=>90,'sslverify'=>false,'headers'=>array('Content-Type'=>'application/json'),'body'=>$body));
            $code = is_wp_error($r)?0:(int)wp_remote_retrieve_response_code($r);
            if ( $code===429 && $try===1 ) { sleep(30); continue; }
            if ( !is_wp_error($r) && $code===200 ) {
                $d=json_decode(wp_remote_retrieve_body($r),true);
                foreach($d['candidates'][0]['content']['parts']??array() as $part) {
                    if(isset($part['inlineData'])){$b64=$part['inlineData']['data'];$mime=$part['inlineData']['mimeType']??'image/jpeg';break 2;}
                }
            }
            break;
        }
    }
    if ( !$b64 ) {
        $pr=wp_remote_get('https://image.pollinations.ai/prompt/'.urlencode($prompt).'?width=800&height=600&nologo=true',array('timeout'=>90));
        if(!is_wp_error($pr)&&200===(int)wp_remote_retrieve_response_code($pr)){
            $img=wp_remote_retrieve_body($pr); $ct=wp_remote_retrieve_header($pr,'content-type');
            if($img&&false!==strpos($ct,'image'))return wpap_save_image_to_library($img,$ct,$post_id,$title,'poll');
        }
        return new WP_Error('gemini_img','All image attempts failed.');
    }
    return wpap_save_image_to_library(base64_decode($b64),$mime,$post_id,$title,$mode);
}

/* ==============================================
   PEXELS IMAGE ENGINE
============================================== */
function wpap_generate_image_pexels( string $title, int $post_id, string $api_key ) {
    /* Try up to 2 search queries — exact title then generic "food" fallback */
    $queries = array( $title, 'delicious food dish meal' );
    $url     = '';

    foreach ( $queries as $q ) {
        $r = wp_remote_get(
            'https://api.pexels.com/v1/search?query=' . urlencode( $q ) . '&per_page=5&orientation=landscape',
            array(
                'timeout'    => 25,
                'sslverify'  => false,
                'headers'    => array( 'Authorization' => $api_key ),
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            )
        );
        if ( is_wp_error( $r ) ) continue;
        if ( 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
            /* Rate-limited? Wait 3s and retry once */
            sleep( 3 );
            $r = wp_remote_get(
                'https://api.pexels.com/v1/search?query=' . urlencode( $q ) . '&per_page=5&orientation=landscape',
                array( 'timeout'=>25,'sslverify'=>false,'headers'=>array('Authorization'=>$api_key) )
            );
            if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) continue;
        }
        $d   = json_decode( wp_remote_retrieve_body( $r ), true );
        $url = $d['photos'][0]['src']['large2x'] ?? $d['photos'][0]['src']['large'] ?? '';
        if ( $url ) break; /* Found a photo — stop trying queries */
    }

    if ( ! $url ) {
        /* Final fallback: Pollinations free image */
        return wpap_generate_image_pollinations( $title, $post_id );
    }

    /* Download the photo with retry */
    $img = ''; $ct = '';
    for ( $try = 1; $try <= 2; $try++ ) {
        $r2  = wp_remote_get( $url, array( 'timeout'=>60,'sslverify'=>false ) );
        if ( is_wp_error( $r2 ) ) { sleep(2); continue; }
        $img = wp_remote_retrieve_body( $r2 );
        $ct  = wp_remote_retrieve_header( $r2, 'content-type' );
        if ( $img && false !== strpos( $ct, 'image' ) ) break;
        sleep(2);
    }

    if ( ! $img || false === strpos( $ct, 'image' ) ) {
        return wpap_generate_image_pollinations( $title, $post_id );
    }

    return wpap_save_image_to_library( $img, $ct, $post_id, $title, 'pexels' );
}

/* Pollinations free fallback (no API key needed) */
function wpap_generate_image_pollinations( string $title, int $post_id ) {
    $prompt = 'Professional food photography of ' . $title . ', studio lighting, vibrant colors, 4K';
    $url    = 'https://image.pollinations.ai/prompt/' . urlencode( $prompt ) . '?width=800&height=600&nologo=true';
    $r      = wp_remote_get( $url, array( 'timeout'=>90,'sslverify'=>false ) );
    if ( is_wp_error( $r ) ) return $r;
    $img = wp_remote_retrieve_body( $r );
    $ct  = wp_remote_retrieve_header( $r, 'content-type' );
    if ( ! $img || false === strpos( $ct, 'image' ) ) {
        return new WP_Error( 'poll', 'Pollinations returned no image.' );
    }
    return wpap_save_image_to_library( $img, $ct, $post_id, $title, 'poll' );
}

/* Save image bytes to Media Library */
function wpap_save_image_to_library( string $data, string $mime, int $post_id, string $title, string $prefix='' ) {
    require_once ABSPATH.'wp-admin/includes/image.php';
    $ext=(strpos($mime,'jpeg')!==false||strpos($mime,'jpg')!==false)?'jpg':'png';
    $m=($ext==='jpg')?'image/jpeg':'image/png';
    $fn=sanitize_file_name($post_id.($prefix?'-'.$prefix:'').'-'.sanitize_title($title).'.'.$ext);
    $up=wp_upload_bits($fn,null,$data);
    if($up['error'])return new WP_Error('upload',$up['error']);
    $aid=wp_insert_attachment(array('guid'=>$up['url'],'post_mime_type'=>$m,'post_title'=>$title,'post_status'=>'inherit'),$up['file'],$post_id);
    wp_update_attachment_metadata($aid,wp_generate_attachment_metadata($aid,$up['file']));
    return $aid;
}





/* ════════════════════════════════════════════
   13. FRONT-END: NEXT-PAGE BUTTON + STYLES
════════════════════════════════════════════ */
add_action( 'wp_head', 'wpap_frontend' );
function wpap_frontend() {
    if ( ! is_singular() ) return;
    ?>
    <script>
    function wpapNextPage(e) {
        e.preventDefault();
        var n = document.querySelector('.post-page-numbers:not(.current)');
        if ( n ) { window.location.href = n.href; return; }
        var u = window.location.href.replace(/[?&]page=\d+/, '');
        window.location.href = u + ( u.indexOf('?') > -1 ? '&' : '?' ) + 'page=2';
    }
    </script>
    <style>
    /* .wpap-next-page-wrap and .wpap-next-btn removed — theme handles navigation */
    .wpap-next-teaser{display:block;font-size:1rem;font-weight:700;color:#374151;text-align:center;margin:1.5rem 0;padding:.75rem 1rem;background:#f9fafb;border-radius:6px;border:1px solid #e5e7eb;line-height:1.5}
    .wpap-share-cta{text-align:center;font-size:1.05rem;font-weight:700;color:#e11d48;margin:2rem 0;padding:1rem;border-top:2px dashed #fca5a5}
    .wpap-related{margin:2.5rem 0;padding-top:1rem;border-top:1px solid #e5e7eb}
    .wpap-related-title{font-size:1.15rem;font-weight:800;margin:0 0 1rem}
    .wpap-related-list{list-style:none;margin:0;padding:0;display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:1rem}
    .wpap-related-item a{display:block;text-decoration:none;color:inherit}
    .wpap-related-item img{width:100%;height:110px;object-fit:cover;border-radius:8px;display:block;margin-bottom:.5rem}
    .wpap-related-item span{font-size:.9rem;font-weight:600;line-height:1.35;display:block}
    </style>
    <?php
}

/* ════════════════════════════════════════════
   11. SEO OUTPUT (front-end, non-AI)
   Open Graph + Twitter Cards + Article JSON-LD + a related-posts
   block for the plugin's OWN posts, so shares and SERP snippets use
   the right title/image/description. Self-suppresses when a dedicated
   SEO plugin is active. Render-time hooks only — does NOT touch
   content/image generation.
════════════════════════════════════════════ */

/* A ~155-char plain-text summary derived from HTML content.
   Uses wp_html_excerpt() (multibyte-safe, and always defined — WordPress does
   not polyfill mb_strrpos, so we avoid it). */
function wpap_make_excerpt( $html, $max = 155 ) {
    $text = wp_strip_all_tags( (string) $html );
    $text = trim( preg_replace( '/\s+/', ' ', $text ) );
    if ( '' === $text ) { return ''; }
    $excerpt = wp_html_excerpt( $text, $max, '' );
    if ( $excerpt !== $text ) {
        /* Drop a trailing partial word, then trailing spaces/punctuation.
           Both use UTF-8-aware regexes — a byte-wise rtrim() with the multibyte
           em-dash could corrupt an excerpt ending in a non-Latin character. */
        $trimmed = preg_replace( '/\s+\S*$/u', '', $excerpt );
        if ( null !== $trimmed && '' !== $trimmed ) { $excerpt = $trimmed; }
        $stripped = preg_replace( '/[\s,.;:—-]+$/u', '', $excerpt );
        if ( null !== $stripped && '' !== $stripped ) { $excerpt = $stripped; }
        $excerpt .= '…';
    }
    return $excerpt;
}

/* Write the meta description / SEO title / focus keyword into whichever SEO
   plugin is active. Only the active plugin's keys are written; when no SEO
   plugin is installed, the render-time wpap_seo_head() emits the description
   from post_excerpt instead, so the meta description is covered either way. */
function wpap_set_seo_meta( $post_id, $description, $title = '', $keyword = '' ) {
    $post_id = (int) $post_id;
    if ( $post_id <= 0 ) { return; }
    $description = trim( (string) $description );
    $title       = trim( (string) $title );
    $keyword     = trim( (string) $keyword );

    $yoast = defined( 'WPSEO_VERSION' );
    $rank  = defined( 'RANK_MATH_VERSION' );

    if ( '' !== $description ) {
        if ( $yoast ) { update_post_meta( $post_id, '_yoast_wpseo_metadesc', $description ); }
        if ( $rank )  { update_post_meta( $post_id, 'rank_math_description', $description ); }
    }
    if ( '' !== $title ) {
        if ( $yoast ) { update_post_meta( $post_id, '_yoast_wpseo_title', $title ); }
        if ( $rank )  { update_post_meta( $post_id, 'rank_math_title',   $title ); }
    }
    if ( '' !== $keyword ) {
        if ( $yoast ) { update_post_meta( $post_id, '_yoast_wpseo_focuskw',    $keyword ); }
        if ( $rank )  { update_post_meta( $post_id, 'rank_math_focus_keyword', $keyword ); }
    }
}

/* True when a dedicated SEO plugin is already handling <head> meta. */
function wpap_seo_plugin_active() {
    return ( defined( 'WPSEO_VERSION' )                /* Yoast SEO      */
        || defined( 'RANK_MATH_VERSION' )              /* Rank Math      */
        || defined( 'AIOSEO_VERSION' )                 /* All in One SEO */
        || defined( 'SEOPRESS_VERSION' )               /* SEOPress       */
        || function_exists( 'the_seo_framework' ) );   /* SEO Framework  */
}

add_action( 'wp_head', 'wpap_seo_head', 1 );
function wpap_seo_head() {
    if ( ! is_singular( 'post' ) ) { return; }
    $post_id = (int) get_queried_object_id();
    if ( ! $post_id || ! get_post_meta( $post_id, '_wpap_smart_link', true ) ) { return; }   /* plugin posts only */
    if ( wpap_seo_plugin_active() ) { return; }   /* an SEO plugin owns the head */

    $post = get_post( $post_id );
    if ( ! $post ) { return; }
    $title = get_the_title( $post_id );
    $url   = get_permalink( $post_id );
    $desc  = ( '' !== (string) $post->post_excerpt ) ? $post->post_excerpt : wpap_make_excerpt( $post->post_content );
    $img   = (string) get_post_meta( $post_id, '_wpap_image_url', true );
    if ( '' === $img ) { $img = (string) get_the_post_thumbnail_url( $post_id, 'full' ); }
    $site  = get_bloginfo( 'name' );
    $pub   = get_the_date( 'c', $post_id );
    $mod   = get_the_modified_date( 'c', $post_id );

    $out  = "\n";
    if ( '' !== (string) $desc ) {
        $out .= '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
    }
    $out .= '<meta property="og:type" content="article">' . "\n";
    $out .= '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    if ( '' !== (string) $desc ) { $out .= '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n"; }
    $out .= '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
    $out .= '<meta property="og:site_name" content="' . esc_attr( $site ) . '">' . "\n";
    if ( '' !== $img ) { $out .= '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n"; }
    $out .= '<meta property="article:published_time" content="' . esc_attr( $pub ) . '">' . "\n";
    $out .= '<meta property="article:modified_time" content="' . esc_attr( $mod ) . '">' . "\n";
    $out .= '<meta name="twitter:card" content="' . ( '' !== $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";
    $out .= '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    if ( '' !== (string) $desc ) { $out .= '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n"; }
    if ( '' !== $img ) { $out .= '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n"; }

    $ld = array(
        '@context'         => 'https://schema.org',
        '@type'            => 'Article',
        'headline'         => $title,
        'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => $url ),
        'datePublished'    => $pub,
        'dateModified'     => $mod,
        'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ) ),
        'publisher'        => array( '@type' => 'Organization', 'name' => $site ),
    );
    if ( '' !== (string) $desc ) { $ld['description'] = $desc; }
    if ( '' !== $img )           { $ld['image'] = array( $img ); }
    $out .= '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";

    /* BreadcrumbList JSON-LD: Home › Category › Post (replaces the bare URL in SERPs). */
    $pos    = 1;
    $crumbs = array( array( '@type' => 'ListItem', 'position' => $pos++, 'name' => $site, 'item' => home_url( '/' ) ) );
    $cats   = get_the_category( $post_id );
    if ( ! empty( $cats ) ) {
        $c        = $cats[0];
        $c_link   = get_category_link( $c->term_id );
        $crumbs[] = array( '@type' => 'ListItem', 'position' => $pos++, 'name' => $c->name, 'item' => is_wp_error( $c_link ) ? home_url( '/' ) : $c_link );
    }
    $crumbs[] = array( '@type' => 'ListItem', 'position' => $pos, 'name' => $title, 'item' => $url );
    $bc = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs );
    $out .= '<script type="application/ld+json">' . wp_json_encode( $bc, JSON_HEX_TAG | JSON_HEX_AMP ) . '</script>' . "\n";

    $out .= "";

    echo $out;   /* every dynamic value is individually escaped above */
}

/* Append a crawlable "You may also like" block to the plugin's own posts
   (more internal links + pages-per-session, both good for SEO and RPM). */
add_filter( 'the_content', 'wpap_related_posts_block', 20 );
function wpap_related_posts_block( $content ) {
    if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) { return $content; }
    $post_id = (int) get_the_ID();
    if ( ! $post_id || ! get_post_meta( $post_id, '_wpap_smart_link', true ) ) { return $content; }   /* plugin posts only */

    /* On a paginated post, only append on the final page. */
    global $page, $numpages, $multipage;
    if ( ! empty( $multipage ) && (int) $page !== (int) $numpages ) { return $content; }

    static $done = array();
    if ( isset( $done[ $post_id ] ) ) { return $content; }
    $done[ $post_id ] = true;

    $cats = wp_get_post_categories( $post_id );
    $args = array(
        'post__not_in'        => array( $post_id ),
        'posts_per_page'      => 4,
        'post_status'         => 'publish',
        'ignore_sticky_posts' => 1,
        'no_found_rows'       => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
    );
    if ( ! empty( $cats ) ) { $args['category__in'] = $cats; }
    $q = new WP_Query( $args );
    if ( ! $q->have_posts() ) { wp_reset_postdata(); return $content; }

    $html = '<div class="wpap-related"><h3 class="wpap-related-title">You May Also Like</h3><ul class="wpap-related-list">';
    while ( $q->have_posts() ) {
        $q->the_post();
        $rid   = (int) get_the_ID();
        $thumb = get_the_post_thumbnail_url( $rid, 'medium' );
        $html .= '<li class="wpap-related-item"><a href="' . esc_url( get_permalink( $rid ) ) . '">';
        if ( $thumb ) { $html .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( get_the_title( $rid ) ) . '" loading="lazy">'; }
        $html .= '<span>' . esc_html( get_the_title( $rid ) ) . '</span></a></li>';
    }
    $html .= '</ul></div>';
    wp_reset_postdata();

    return $content . $html;
}

/* ════════════════════════════════════════════
   12. ads.txt MANAGER
   Serves the publisher's ads.txt line(s) at /ads.txt when none exists
   as a physical file — a missing/incorrect ads.txt is a common AdSense
   "earnings at risk" throttle. Configured under Settings.
════════════════════════════════════════════ */
add_action( 'init', 'wpap_serve_ads_txt', 0 );
function wpap_serve_ads_txt() {
    $path = strtok( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', '?' );
    if ( '/ads.txt' !== $path ) { return; }

    $content = trim( (string) get_option( 'wpap_ads_txt', '' ) );
    if ( '' === $content ) { return; }                     /* not configured — let WP handle it */
    if ( @file_exists( ABSPATH . 'ads.txt' ) ) { return; } /* a real root file always wins */

    nocache_headers();
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Content-Type-Options: nosniff' );
    echo $content . "\n";   /* text/plain: the browser never renders this as HTML */
    exit;
}

/* ════════════════════════════════════════════
   12b. INDEXNOW — instant search-engine indexing
   On every new publish (manual editor, Direct Publish, Sheet auto-publish,
   or a scheduled post going live) we ping the IndexNow API so Bing, Yandex,
   Seznam, Naver — and a growing list of others — crawl the URL within
   minutes instead of days. A per-site key is generated once and served as a
   virtual /{key}.txt file (same trick as ads.txt above). The ping is
   fire-and-forget (non-blocking) so it never slows publishing down.
   Note: Google does NOT consume IndexNow — for Google, submit your XML
   sitemap once in Search Console (Yoast/Rank Math generate it for you).
════════════════════════════════════════════ */

/* True unless the user explicitly turned IndexNow off in Settings. */
function wpap_indexnow_enabled() {
    $in = get_option( 'wpap_indexnow', array() );
    if ( ! is_array( $in ) || ! isset( $in['enabled'] ) ) { return true; }   /* default: on */
    return (bool) $in['enabled'];
}

/* The site's IndexNow key (32 hex chars). Generated once, then reused. */
function wpap_indexnow_key() {
    $k = get_option( 'wpap_indexnow_key', '' );
    if ( ! is_string( $k ) || ! preg_match( '/^[a-zA-Z0-9-]{8,128}$/', $k ) ) {
        $k = '';
        if ( function_exists( 'random_bytes' ) ) {
            try { $k = bin2hex( random_bytes( 16 ) ); }
            catch ( Exception $e ) { $k = ''; }
        }
        if ( '' === $k ) {   /* fallback if the CSPRNG is unavailable */
            $k = substr( str_replace( array( '-', '_' ), '', wp_generate_password( 40, false, false ) ) . '00000000', 0, 32 );
        }
        update_option( 'wpap_indexnow_key', $k, false );
    }
    return $k;
}

/* Serve the key verification file at /{key}.txt (virtual, like ads.txt). */
add_action( 'init', 'wpap_serve_indexnow_key', 0 );
function wpap_serve_indexnow_key() {
    $path = strtok( isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '', '?' );
    $path = ltrim( (string) $path, '/' );
    if ( '' === $path || '.txt' !== substr( $path, -4 ) ) { return; }   /* cheap gate before any option read */
    $key = wpap_indexnow_key();
    if ( '' === $key || $path !== $key . '.txt' ) { return; }

    nocache_headers();
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Content-Type-Options: nosniff' );
    echo $key . "\n";
    exit;
}

/* Ping IndexNow when a post/page first goes public (covers every path). */
add_action( 'transition_post_status', 'wpap_indexnow_on_publish', 10, 3 );
function wpap_indexnow_on_publish( $new_status, $old_status, $post ) {
    if ( 'publish' !== $new_status || 'publish' === $old_status ) { return; }
    if ( ! wpap_indexnow_enabled() ) { return; }
    if ( ! is_object( $post ) || empty( $post->ID ) ) { return; }
    if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) { return; }
    if ( '' !== (string) $post->post_password ) { return; }             /* never announce protected URLs */
    $url = get_permalink( $post->ID );
    if ( ! $url ) { return; }
    wpap_indexnow_submit( array( $url ) );
}

/* POST a list of URLs to the IndexNow API (fire-and-forget). */
function wpap_indexnow_submit( $urls ) {
    $urls = array_values( array_unique( array_filter( array_map( 'strval', (array) $urls ) ) ) );
    if ( empty( $urls ) ) { return; }
    $key  = wpap_indexnow_key();
    $host = wp_parse_url( home_url(), PHP_URL_HOST );
    if ( '' === $key || ! $host ) { return; }
    if ( 'localhost' === $host || false === strpos( $host, '.' ) ) { return; }   /* skip local/dev hosts */

    $body = wp_json_encode( array(
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => home_url( '/' . $key . '.txt' ),
        'urlList'     => array_slice( $urls, 0, 100 ),                  /* IndexNow caps at 10k; we send far fewer */
    ) );

    wp_remote_post( 'https://api.indexnow.org/indexnow', array(
        'timeout'   => 5,
        'blocking'  => false,        /* fire-and-forget: the editor never waits on the network */
        'sslverify' => true,
        'headers'   => array( 'Content-Type' => 'application/json; charset=utf-8' ),
        'body'      => $body,
    ) );

    update_option( 'wpap_indexnow_last', array(
        'time'  => time(),
        'count' => count( $urls ),
        'url'   => $urls[0],
    ), false );
}

/* ════════════════════════════════════════════
   ADSENSE AD PLACEMENT (ported from v8.25 onto the v8.9.0 base, 2026-08-08)
   Self-contained: front-end only (the_content prio 15 + wp_head prio 8).
   Does NOT touch the publish path. Configured under Settings → AdSense.
   ════════════════════════════════════════════ */

/* Normalize the wpap_ads_inject option into a stable shape (with a light
   migration from the flat single-unit schema). Used by both the UI and engine. */
function wpap_get_ads() {
    $a = get_option( 'wpap_ads_inject', array() );
    if ( ! is_array( $a ) ) { $a = array(); }
    $slots = ( isset( $a['slots'] ) && is_array( $a['slots'] ) ) ? $a['slots'] : array();

    /* Back-compat: map the old single-unit schema onto the new slots. */
    if ( empty( $slots ) && ! empty( $a['incontent'] ) ) {
        $slots['incontent'] = array( 'on' => 1, 'code' => (string) $a['incontent'], 'after' => (int) ( $a['first_after'] ?? 2 ) );
        if ( ! empty( $a['every'] ) ) {
            $slots['repeat'] = array( 'on' => 1, 'code' => (string) $a['incontent'], 'every' => (int) $a['every'], 'max' => (int) ( $a['max'] ?? 3 ) );
        }
    }

    $g = function ( $slot, $key, $default ) use ( $slots ) {
        return isset( $slots[ $slot ][ $key ] ) ? $slots[ $slot ][ $key ] : $default;
    };

    /* Unlimited custom placements (Option 2): a list of {pos, after, code}.
       Only entries with non-empty code count; capped at 10. */
    $custom     = array();
    $custom_raw = ( isset( $a['custom'] ) && is_array( $a['custom'] ) ) ? $a['custom'] : array();
    foreach ( $custom_raw as $c ) {
        if ( ! is_array( $c ) ) { continue; }
        $code = trim( (string) ( $c['code'] ?? '' ) );
        if ( '' === $code ) { continue; }
        $pos = isset( $c['pos'] ) ? (string) $c['pos'] : 'after';
        if ( ! in_array( $pos, array( 'after', 'top', 'before_related' ), true ) ) { $pos = 'after'; }
        $custom[] = array(
            'pos'   => $pos,
            'after' => max( 1, min( 50, (int) ( $c['after'] ?? 2 ) ) ),
            'code'  => $code,
        );
        if ( count( $custom ) >= 10 ) { break; }
    }

    return array(
        'enabled'   => ! empty( $a['enabled'] ) ? 1 : 0,
        'scope_all' => ! isset( $a['scope_all'] ) ? 1 : ( ! empty( $a['scope_all'] ) ? 1 : 0 ),
        'auto_code' => trim( (string) ( $a['auto_code'] ?? '' ) ),
        /* Density guardrail: min paragraphs between in-content ads (0 = off;
           default 1 blocks two ads landing on the same paragraph) + an optional
           hard cap on in-content ads per post (0 = unlimited). */
        'min_gap'   => ! isset( $a['min_gap'] ) ? 1 : max( 0, min( 20, (int) $a['min_gap'] ) ),
        'max_ads'   => max( 0, min( 20, (int) ( $a['max_ads'] ?? 0 ) ) ),
        'label'     => ! empty( $a['label'] ) ? 1 : 0,
        'zones'     => array(
            'header'  => array( 'on' => ! empty( $a['zones']['header']['on'] ) ? 1 : 0,  'code' => isset( $a['zones']['header']['code'] ) ? trim( (string) $a['zones']['header']['code'] ) : '' ),
            'sidebar' => array( 'on' => ! empty( $a['zones']['sidebar']['on'] ) ? 1 : 0, 'code' => isset( $a['zones']['sidebar']['code'] ) ? trim( (string) $a['zones']['sidebar']['code'] ) : '' ),
            'footer'  => array( 'on' => ! empty( $a['zones']['footer']['on'] ) ? 1 : 0,  'code' => isset( $a['zones']['footer']['code'] ) ? trim( (string) $a['zones']['footer']['code'] ) : '' ),
        ),
        'custom'    => $custom,
        'slots'     => array(
            'top'       => array(
                'on'   => ! empty( $g( 'top', 'on', 0 ) ) ? 1 : 0,
                'code' => trim( (string) $g( 'top', 'code', '' ) ),
            ),
            'incontent' => array(
                'on'    => ! empty( $g( 'incontent', 'on', 0 ) ) ? 1 : 0,
                'code'  => trim( (string) $g( 'incontent', 'code', '' ) ),
                'after' => max( 1, min( 50, (int) $g( 'incontent', 'after', 2 ) ) ),
            ),
            'repeat'    => array(
                'on'    => ! empty( $g( 'repeat', 'on', 0 ) ) ? 1 : 0,
                'code'  => trim( (string) $g( 'repeat', 'code', '' ) ),
                'every' => max( 1, min( 50, (int) $g( 'repeat', 'every', 4 ) ) ),
                'max'   => max( 1, min( 10, (int) $g( 'repeat', 'max', 3 ) ) ),
            ),
            'bottom'    => array(
                'on'   => ! empty( $g( 'bottom', 'on', 0 ) ) ? 1 : 0,
                'code' => trim( (string) $g( 'bottom', 'code', '' ) ),
            ),
        ),
    );
}

/* Wrap an ad snippet in a labelled container. */
function wpap_ad_box( $code, $slot ) {
    static $label = null;
    if ( null === $label ) {
        $ads   = wpap_get_ads();
        $label = ! empty( $ads['label'] );
    }
    $prefix = $label ? '<span class="wpap-ad-label">' . esc_html__( 'Advertisement', 'wpap' ) . '</span>' : '';
    $code   = wpap_cap_ad_code( $code );   /* defense-in-depth: never echo an unbounded blob */
    return '<div class="wpap-ad wpap-ad-' . sanitize_html_class( $slot ) . '">' . $prefix . $code . '</div>';
}

/* Bound any ad-code string before it's echoed. Real AdSense units are < 2 KB;
   20 KB is generous. Guards against option bloat / page-weight amplification if
   a huge string ever ends up stored. Multibyte-safe where available. */
function wpap_cap_ad_code( $code ) {
    $code = (string) $code;
    return function_exists( 'mb_substr' ) ? mb_substr( $code, 0, 20000 ) : substr( $code, 0, 20000 );
}

/* Ad HTML for a page zone (header / sidebar / footer) when it's enabled and has
   code, else ''. The companion theme calls this to fill its zones from the
   plugin settings (so there's no need to use WordPress → Widgets). */
function wpap_zone_html( $which ) {
    $ads = wpap_get_ads();
    if ( empty( $ads['enabled'] ) ) { return ''; }
    $which = (string) $which;
    if ( empty( $ads['zones'][ $which ]['on'] ) || '' === (string) $ads['zones'][ $which ]['code'] ) { return ''; }
    return wpap_ad_box( $ads['zones'][ $which ]['code'], 'zone-' . sanitize_html_class( $which ) );
}

/* <head>: Auto Ads snippet + a preconnect so the first ad paints sooner. */
add_action( 'wp_head', 'wpap_ads_head', 8 );
function wpap_ads_head() {
    if ( is_admin() ) { return; }
    $ads = wpap_get_ads();
    if ( ! $ads['enabled'] ) { return; }

    /* Serve the head block if Auto Ads OR any enabled manual slot has code
       (the manual units need the adsbygoogle loader; the preconnect helps too). */
    $has_manual = ! empty( $ads['custom'] );
    if ( ! $has_manual ) {
        foreach ( array_merge( array_values( $ads['slots'] ), array_values( $ads['zones'] ) ) as $slot ) {
            if ( ! empty( $slot['on'] ) && '' !== (string) $slot['code'] ) { $has_manual = true; break; }
        }
    }
    if ( '' === $ads['auto_code'] && ! $has_manual ) { return; }

    echo "\n";
    echo '<link rel="preconnect" href="https://pagead2.googlesyndication.com" crossorigin>' . "\n";
    echo '<link rel="dns-prefetch" href="//pagead2.googlesyndication.com">' . "\n";
    if ( '' !== $ads['auto_code'] ) {
        echo wpap_cap_ad_code( $ads['auto_code'] ) . "\n";   /* verbatim: owner's own AdSense code (this snippet IS the loader) */
    } elseif ( $has_manual ) {
        /* Manual units need the adsbygoogle loader to fill. When the owner
           didn't paste an Auto Ads snippet (which itself is the loader), ship a
           bare loader so <ins> units still render even if a pasted unit omits
           its own loader line. The data-ad-client on each <ins> supplies the
           publisher id; a duplicate loader (if a unit includes one) is harmless. */
        echo '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js" crossorigin="anonymous"></script>' . "\n";
    }
    echo "";
}

/* the_content: inject the manual slots. Priority 15 — after wpautop (10) wraps
   paragraphs, before the related block (20) appends its list. */
add_filter( 'the_content', 'wpap_inject_in_content_ads', 15 );
function wpap_inject_in_content_ads( $content ) {
    if ( is_admin() || is_feed() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) { return $content; }

    $ads = wpap_get_ads();
    if ( ! $ads['enabled'] ) { return $content; }

    $post_id = (int) get_the_ID();
    if ( ! $post_id ) { return $content; }
    /* Scope: all posts, or only the plugin's own posts. */
    if ( ! $ads['scope_all'] && ! get_post_meta( $post_id, '_wpap_smart_link', true ) ) { return $content; }

    static $done = array();
    if ( isset( $done[ $post_id ] ) ) { return $content; }   /* once per post per request */
    $done[ $post_id ] = true;

    /* AdSense-policy safety net: don't sandwich a near-empty page with ads
       ("low-value content" is a common ground for ad limitations). Count words
       Unicode-safely (works for English / Romanian / Arabic alike) and skip all
       in-content injection on very short pages. 150-word default never trips a
       real article; adjust via the wpap_min_words_for_ads filter. */
    $wpap_plain = trim( wp_strip_all_tags( (string) $content ) );
    if ( '' === $wpap_plain ) {
        $wpap_wc = 0;
    } else {
        $wpap_words = preg_split( '/\s+/u', $wpap_plain, -1, PREG_SPLIT_NO_EMPTY );
        if ( ! is_array( $wpap_words ) ) {                                      /* /u failed on bad UTF-8 */
            $wpap_words = preg_split( '/\s+/', $wpap_plain, -1, PREG_SPLIT_NO_EMPTY );
        }
        /* If both splits fail, err toward showing ads (don't over-suppress). */
        $wpap_wc = is_array( $wpap_words ) ? count( $wpap_words ) : PHP_INT_MAX;
    }
    $wpap_min = (int) apply_filters( 'wpap_min_words_for_ads', 150 );
    if ( $wpap_min > 0 && $wpap_wc < $wpap_min ) { return $content; }

    $s = $ads['slots'];

    /* Prepend (top) + append (before-related) buffers, from named slots and
       any custom placements pointing there. */
    $top = ( ! empty( $s['top']['on'] )    && '' !== $s['top']['code'] )    ? wpap_ad_box( $s['top']['code'], 'top' )       : '';
    $bot = ( ! empty( $s['bottom']['on'] ) && '' !== $s['bottom']['code'] ) ? wpap_ad_box( $s['bottom']['code'], 'bottom' ) : '';

    /* Map: paragraph number => list of ad HTML to place after it (named
       in-content slot + any "after paragraph N" custom placements). */
    $after_map = array();
    if ( ! empty( $s['incontent']['on'] ) && '' !== $s['incontent']['code'] ) {
        $after_map[ (int) $s['incontent']['after'] ][] = wpap_ad_box( $s['incontent']['code'], 'incontent' );
    }
    foreach ( $ads['custom'] as $c ) {
        $box = wpap_ad_box( $c['code'], 'custom' );
        if ( 'top' === $c['pos'] ) {
            $top .= $box;
        } elseif ( 'before_related' === $c['pos'] ) {
            $bot .= $box;
        } else {
            $after_map[ (int) $c['after'] ][] = $box;
        }
    }

    $rep_on   = ! empty( $s['repeat']['on'] ) && '' !== $s['repeat']['code'];
    $every    = (int) $s['repeat']['every'];
    $rep_max  = (int) $s['repeat']['max'];
    $rep_code = $rep_on ? wpap_ad_box( $s['repeat']['code'], 'repeat' ) : '';

    $min_gap = (int) $ads['min_gap'];   /* min paragraphs between in-content ads (0 = off) */
    $max_ads = (int) $ads['max_ads'];   /* hard cap on in-content ads per post (0 = unlimited) */

    $body = $content;
    if ( ! empty( $after_map ) || $rep_on ) {
        $parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
        if ( is_array( $parts ) && count( $parts ) >= 3 ) {   /* need real paragraphs */
            $out       = '';
            $para      = 0;
            $reps      = 0;
            $placed    = 0;      /* in-content ads placed so far (guardrail count) */
            $last_para = null;   /* paragraph index of the last placed ad */
            foreach ( $parts as $chunk ) {
                $out .= $chunk;
                if ( '</p>' === strtolower( (string) $chunk ) ) {
                    $para++;

                    /* Candidates after this paragraph: fixed placements (named
                       in-content + customs), else the repeat slot. Each is
                       array( html, is_repeat ). */
                    $here = array();
                    if ( isset( $after_map[ $para ] ) ) {
                        foreach ( $after_map[ $para ] as $h ) { $here[] = array( $h, false ); }
                    } elseif ( $rep_on && $every > 0 && 0 === ( $para % $every ) && $reps < $rep_max ) {
                        $here[] = array( $rep_code, true );
                    }

                    foreach ( $here as $cand ) {
                        if ( $max_ads > 0 && $placed >= $max_ads ) { break; }                                     /* hard cap reached */
                        if ( $min_gap > 0 && null !== $last_para && ( $para - $last_para ) < $min_gap ) { break; }  /* too close: skip this + siblings at this paragraph */
                        $out      .= $cand[0];
                        $last_para = $para;
                        $placed++;
                        if ( $cand[1] ) { $reps++; }   /* count the repeat toward its own max only when actually placed */
                    }
                }
            }
            $body = $out;
        }
    }

    return $top . $body . $bot;
}

/* ════════ SETTINGS BACKUP/RESTORE + DASHBOARD HEALTH WIDGET (ported) ════════ */
add_action( 'admin_post_wpap_export_settings', 'wpap_handle_export_settings' );
function wpap_handle_export_settings() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized', '', array( 'response' => 403 ) ); }
    check_admin_referer( 'wpap_export_settings' );

    $bundle = array(
        'plugin'   => 'wp-automator-pro',
        'version'  => defined( 'WPAP_VERSION' ) ? WPAP_VERSION : '',
        'exported' => gmdate( 'c' ),
        'options'  => array(
            'wpap_ads_inject'   => get_option( 'wpap_ads_inject', array() ),
            'wpap_content_opts' => get_option( 'wpap_content_opts', array() ),
            'wpap_indexnow'     => get_option( 'wpap_indexnow', array() ),
            'wpap_ads_txt'      => (string) get_option( 'wpap_ads_txt', '' ),
            'wpap_automation'   => get_option( 'wpap_automation', array() ),
            'wpap_utm'          => get_option( 'wpap_utm', array() ),
        ),
        /* Secrets (API keys in wpap_settings, license) are intentionally omitted. */
    );

    $json  = wp_json_encode( $bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
    $fname = 'wp-automator-settings-' . gmdate( 'Ymd-His' ) . '.json';

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename="' . $fname . '"' );
    header( 'Content-Length: ' . strlen( (string) $json ) );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'X-Robots-Tag: noindex, nofollow' );   /* keep a settings dump out of any index if ever linked */
    echo $json;   // JSON download, not rendered as HTML
    exit;
}

add_action( 'admin_post_wpap_import_settings', 'wpap_handle_import_settings' );
function wpap_handle_import_settings() {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Unauthorized', '', array( 'response' => 403 ) ); }
    check_admin_referer( 'wpap_import_settings' );

    $redirect = admin_url( 'admin.php?page=wp-automator-pro-settings' );
    $fail     = function ( $code ) use ( $redirect ) {
        wp_safe_redirect( add_query_arg( 'wpap_import_error', $code, $redirect ) );
        exit;
    };

    if ( empty( $_FILES['wpap_import_file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['wpap_import_file']['tmp_name'] ) ) { $fail( 'nofile' ); }
    $size = (int) ( $_FILES['wpap_import_file']['size'] ?? 0 );
    if ( $size <= 0 || $size > 512 * 1024 ) { $fail( 'size' ); }   /* cap 512 KB */

    $raw  = file_get_contents( $_FILES['wpap_import_file']['tmp_name'] );
    $data = json_decode( (string) $raw, true );
    if ( ! is_array( $data ) || empty( $data['options'] ) || ! is_array( $data['options'] ) ) { $fail( 'parse' ); }
    $opts = $data['options'];

    if ( isset( $opts['wpap_ads_inject'] ) && is_array( $opts['wpap_ads_inject'] ) ) {
        update_option( 'wpap_ads_inject', wpap_sanitize_ads_import( $opts['wpap_ads_inject'] ), false );
    }
    if ( isset( $opts['wpap_content_opts'] ) && is_array( $opts['wpap_content_opts'] ) ) {
        $c = $opts['wpap_content_opts'];
        update_option( 'wpap_content_opts', array(
            'min_words'        => max( 0, min( 5000, (int) ( $c['min_words'] ?? 0 ) ) ),
            'skip_dupe_titles' => ! empty( $c['skip_dupe_titles'] ) ? 1 : 0,
            'disable_comments' => ! empty( $c['disable_comments'] ) ? 1 : 0,
        ), false );
    }
    if ( isset( $opts['wpap_indexnow'] ) && is_array( $opts['wpap_indexnow'] ) ) {
        update_option( 'wpap_indexnow', array( 'enabled' => ! empty( $opts['wpap_indexnow']['enabled'] ) ? 1 : 0 ), false );
    }
    if ( isset( $opts['wpap_ads_txt'] ) && is_scalar( $opts['wpap_ads_txt'] ) ) {
        update_option( 'wpap_ads_txt', sanitize_textarea_field( (string) $opts['wpap_ads_txt'] ), false );
    }
    if ( isset( $opts['wpap_automation'] ) && is_array( $opts['wpap_automation'] ) ) {
        $a = $opts['wpap_automation'];
        update_option( 'wpap_automation', array(
            'enabled'          => ! empty( $a['enabled'] ) ? 1 : 0,
            'sheet_url'        => esc_url_raw( (string) ( $a['sheet_url'] ?? '' ) ),
            'per_day'          => max( 0, min( 500, (int) ( $a['per_day'] ?? 12 ) ) ),
            'per_run'          => max( 1, min( 50, (int) ( $a['per_run'] ?? 3 ) ) ),
            'default_category' => sanitize_text_field( (string) ( $a['default_category'] ?? '' ) ),
            'schedule_window'  => max( 0, min( 168, (float) ( $a['schedule_window'] ?? 0 ) ) ),
            'alert_email'      => ! empty( $a['alert_email'] ) ? 1 : 0,
        ), false );
    }
    if ( isset( $opts['wpap_utm'] ) && is_array( $opts['wpap_utm'] ) ) {
        $u = $opts['wpap_utm'];
        update_option( 'wpap_utm', array(
            'enabled'  => ! empty( $u['enabled'] ) ? 1 : 0,
            'source'   => sanitize_text_field( (string) ( $u['source'] ?? 'facebook' ) ),
            'medium'   => sanitize_text_field( (string) ( $u['medium'] ?? 'social' ) ),
            'campaign' => sanitize_text_field( (string) ( $u['campaign'] ?? '{slug}' ) ),
            'groups'   => sanitize_textarea_field( (string) ( $u['groups'] ?? '' ) ),
        ), false );
    }

    wp_safe_redirect( add_query_arg( 'wpap_imported', '1', $redirect ) );
    exit;
}

/* Re-sanitize an imported ad-placement structure into the storable shape
   (mirrors the settings-save + normalizer). Ad code stays raw — same trust
   model as the settings form; the file is admin-uploaded and nonce-guarded. */
function wpap_sanitize_ads_import( $a ) {
    if ( ! is_array( $a ) ) { $a = array(); }
    $slots_in = ( isset( $a['slots'] ) && is_array( $a['slots'] ) ) ? $a['slots'] : array();

    /* Back-compat: map the old single-unit schema onto the new slots (mirrors
       wpap_get_ads) so importing a legacy config that was never re-saved on the
       new settings page doesn't silently drop the in-content / repeat ad. */
    if ( empty( $slots_in ) && ! empty( $a['incontent'] ) ) {
        $slots_in['incontent'] = array( 'on' => 1, 'code' => (string) $a['incontent'], 'after' => (int) ( $a['first_after'] ?? 2 ) );
        if ( ! empty( $a['every'] ) ) {
            $slots_in['repeat'] = array( 'on' => 1, 'code' => (string) $a['incontent'], 'every' => (int) $a['every'], 'max' => (int) ( $a['max'] ?? 3 ) );
        }
    }

    $gs = function ( $slot, $key, $default ) use ( $slots_in ) {
        return isset( $slots_in[ $slot ][ $key ] ) ? $slots_in[ $slot ][ $key ] : $default;
    };

    $custom = array();
    if ( isset( $a['custom'] ) && is_array( $a['custom'] ) ) {
        foreach ( $a['custom'] as $c ) {
            if ( ! is_array( $c ) ) { continue; }
            $code = trim( (string) ( $c['code'] ?? '' ) );
            if ( '' === $code ) { continue; }
            $pos = isset( $c['pos'] ) ? (string) $c['pos'] : 'after';
            if ( ! in_array( $pos, array( 'after', 'top', 'before_related' ), true ) ) { $pos = 'after'; }
            $custom[] = array( 'pos' => $pos, 'after' => max( 1, min( 50, (int) ( $c['after'] ?? 2 ) ) ), 'code' => $code );
            if ( count( $custom ) >= 10 ) { break; }
        }
    }

    return array(
        'enabled'   => ! empty( $a['enabled'] ) ? 1 : 0,
        'scope_all' => ! isset( $a['scope_all'] ) ? 1 : ( ! empty( $a['scope_all'] ) ? 1 : 0 ),
        'auto_code' => trim( (string) ( $a['auto_code'] ?? '' ) ),
        'min_gap'   => ! isset( $a['min_gap'] ) ? 1 : max( 0, min( 20, (int) $a['min_gap'] ) ),
        'max_ads'   => max( 0, min( 20, (int) ( $a['max_ads'] ?? 0 ) ) ),
        'label'     => ! empty( $a['label'] ) ? 1 : 0,
        'zones'     => array(
            'header'  => array( 'on' => ! empty( $a['zones']['header']['on'] ) ? 1 : 0,  'code' => isset( $a['zones']['header']['code'] ) ? trim( (string) $a['zones']['header']['code'] ) : '' ),
            'sidebar' => array( 'on' => ! empty( $a['zones']['sidebar']['on'] ) ? 1 : 0, 'code' => isset( $a['zones']['sidebar']['code'] ) ? trim( (string) $a['zones']['sidebar']['code'] ) : '' ),
            'footer'  => array( 'on' => ! empty( $a['zones']['footer']['on'] ) ? 1 : 0,  'code' => isset( $a['zones']['footer']['code'] ) ? trim( (string) $a['zones']['footer']['code'] ) : '' ),
        ),
        'custom'    => $custom,
        'slots'     => array(
            'top'       => array( 'on' => ! empty( $gs( 'top', 'on', 0 ) ) ? 1 : 0, 'code' => trim( (string) $gs( 'top', 'code', '' ) ) ),
            'incontent' => array( 'on' => ! empty( $gs( 'incontent', 'on', 0 ) ) ? 1 : 0, 'code' => trim( (string) $gs( 'incontent', 'code', '' ) ), 'after' => max( 1, min( 50, (int) $gs( 'incontent', 'after', 2 ) ) ) ),
            'repeat'    => array( 'on' => ! empty( $gs( 'repeat', 'on', 0 ) ) ? 1 : 0, 'code' => trim( (string) $gs( 'repeat', 'code', '' ) ), 'every' => max( 1, min( 50, (int) $gs( 'repeat', 'every', 4 ) ) ), 'max' => max( 1, min( 10, (int) $gs( 'repeat', 'max', 3 ) ) ) ),
            'bottom'    => array( 'on' => ! empty( $gs( 'bottom', 'on', 0 ) ) ? 1 : 0, 'code' => trim( (string) $gs( 'bottom', 'code', '' ) ) ),
        ),
    );
}

/* ════════════════════════════════════════════
   5c. WP-ADMIN DASHBOARD HEALTH WIDGET
   A native dashboard widget on the wp-admin home so publishing health
   (WP-Cron, automation, post counts) is visible at a glance.
════════════════════════════════════════════ */
add_action( 'wp_dashboard_setup', 'wpap_register_dashboard_widget' );
function wpap_register_dashboard_widget() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    wp_add_dashboard_widget( 'wpap_health_widget', 'WP Automator — Health', 'wpap_render_dashboard_widget' );
}

function wpap_render_dashboard_widget() {
    if ( ! current_user_can( 'manage_options' ) ) { return; }

    $auto     = wpap_get_automation();
    $status   = get_option( 'wpap_automation_status', array() );
    if ( ! is_array( $status ) ) { $status = array(); }
    $counts   = wp_count_posts( 'post' );
    $next     = wp_next_scheduled( 'wpap_automation_cron' );
    $cron_off = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;

    $row = function ( $label, $value, $color = '' ) {
        $style = $color ? ' style="color:' . esc_attr( $color ) . ';"' : '';
        echo '<li style="display:flex;justify-content:space-between;gap:12px;padding:7px 0;border-bottom:1px solid #f0f0f1;">'
            . '<span>' . esc_html( $label ) . '</span>'
            . '<strong' . $style . '>' . wp_kses_post( $value ) . '</strong></li>';
    };

    echo '<ul style="margin:0;">';
    $row( 'WP-Cron', $cron_off ? 'Disabled &#9888;' : 'Active', $cron_off ? '#b32d2e' : '#1a7f37' );
    $row( 'Published', number_format_i18n( isset( $counts->publish ) ? (int) $counts->publish : 0 ) );
    $row( 'Scheduled', number_format_i18n( isset( $counts->future ) ? (int) $counts->future : 0 ) );
    $row( 'Drafts', number_format_i18n( isset( $counts->draft ) ? (int) $counts->draft : 0 ) );

    if ( ! empty( $auto['enabled'] ) ) {
        $row( 'Sheet automation', 'On', '#1a7f37' );
        $row( 'Next run', $next ? esc_html( human_time_diff( time(), $next ) . ' from now' ) : '&mdash;' );
        if ( ! empty( $status['last_run'] ) ) {
            $row( 'Last run', esc_html( (string) $status['last_run'] ) );
        }
    } else {
        $row( 'Sheet automation', 'Off', '#8a8a8a' );
    }
    echo '</ul>';

    if ( $cron_off && ! empty( $auto['enabled'] ) ) {
        echo '<p style="margin:10px 0 0;color:#b32d2e;">WP-Cron is off but auto-publish is on — scheduled &amp; automated posts won\'t fire until a real server cron calls <code>wp-cron.php</code>.</p>';
    }

    echo '<p style="margin:12px 0 0;">'
        . '<a href="' . esc_url( admin_url( 'admin.php?page=wp-automator-pro' ) ) . '">Open dashboard</a> &middot; '
        . '<a href="' . esc_url( admin_url( 'admin.php?page=wp-automator-pro-settings' ) ) . '">Settings</a></p>';
}

/* ════════════════════════════════════════════
   AUTHOR TOOLS — in-editor authoring assistant
   ────────────────────────────────────────────
   A self-contained, additive module: one meta box on the post editor plus a
   save_post pipeline that auto-fills EMPTY fields only (never clobbering your
   text), applies featured-image metadata, syncs SEO to Yoast / Rank Math, can
   paginate long posts, and (opt-in) marks the post so the plugin's own SEO
   JSON-LD + related-links + ads apply to it. It reuses existing helpers
   (wpap_make_excerpt, wpap_set_seo_meta, wpap_split_content_into_parts) and
   NEVER touches the AI generation pipeline. Works in Classic and Block editors
   (the live checklist / auto-fill buttons enhance the Classic editor; the Block
   editor still gets full server-side auto-fill on save).
════════════════════════════════════════════ */

/* mbstring-safe string helpers (some hosts ship without mbstring). */
function wpap_ed_lower( $s ) {
    $s = (string) $s;
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
}
function wpap_ed_len( $s ) {
    $s = (string) $s;
    return function_exists( 'mb_strlen' ) ? mb_strlen( $s, 'UTF-8' ) : strlen( $s );
}

/* English stop-words for niche-agnostic tag / category suggestion. */
function wpap_ed_stopwords() {
    static $s = null;
    if ( null !== $s ) { return $s; }
    $s = array_flip( array(
        'the','and','for','are','but','not','you','all','any','can','had','her','was','one',
        'our','out','day','get','has','him','his','how','man','new','now','old','see','two',
        'way','who','boy','did','its','let','put','say','she','too','use','that','this','with',
        'have','from','they','will','would','there','their','what','about','which','when','make',
        'like','time','just','know','take','into','your','some','them','than','then','look',
        'only','come','over','also','back','after','work','first','well','even','want','because',
        'these','give','most','made','more','such','very','here','through','being','while','where',
        'been','were','does','did','done','using','used','onto','off',
    ) );
    return $s;
}

/* Top keywords: unicode letter-words 3+ chars, stop-words removed, by frequency. */
function wpap_ed_keywords( $text, $limit = 5 ) {
    $text = wpap_ed_lower( wp_strip_all_tags( (string) $text ) );
    if ( ! preg_match_all( '/[\p{L}]{3,}/u', $text, $m ) ) { return array(); }
    $stop = wpap_ed_stopwords();
    $freq = array();
    foreach ( $m[0] as $w ) {
        if ( isset( $stop[ $w ] ) ) { continue; }
        $freq[ $w ] = isset( $freq[ $w ] ) ? $freq[ $w ] + 1 : 1;
    }
    if ( empty( $freq ) ) { return array(); }
    arsort( $freq );
    return array_slice( array_keys( $freq ), 0, max( 0, (int) $limit ) );
}

/* Truncate a one-line string to a character budget on a word boundary. */
function wpap_ed_clip( $str, $max ) {
    $str = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $str ) ) );
    if ( wpap_ed_len( $str ) <= $max ) { return $str; }
    $cut = function_exists( 'mb_substr' ) ? mb_substr( $str, 0, $max, 'UTF-8' ) : substr( $str, 0, $max );
    $sp  = function_exists( 'mb_strrpos' ) ? mb_strrpos( $cut, ' ', 0, 'UTF-8' ) : strrpos( $cut, ' ' );
    if ( $sp && $sp > (int) ( $max * 0.5 ) ) {
        $cut = function_exists( 'mb_substr' ) ? mb_substr( $cut, 0, $sp, 'UTF-8' ) : substr( $cut, 0, $sp );
    }
    return rtrim( $cut, " \t\n\r\0\x0B,.;:—-" );
}

/* Clean a single extracted recipe line: strip tags, collapse whitespace, drop a
   leading list marker ("1.", "2)", bullet). */
function wpap_ed_clean_item( $raw ) {
    $t = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $raw ) ) );
    $t = preg_replace( '/^\s*(?:\d+\s*[.\)\-:]|[\x{2022}\x{2023}\x{25E6}\x{2043}\-\*])\s*/u', '', $t );
    return trim( (string) $t );
}

/* Collect items from a section body: prefer <li> items, else split paragraphs
   / <br> lines. Caps at 60 items so a runaway body can't bloat the meta. */
function wpap_ed_list_items( $body ) {
    $items = array();
    if ( preg_match_all( '/<li[^>]*>(.*?)<\/li>/is', (string) $body, $mm ) ) {
        foreach ( $mm[1] as $li ) {
            $t = wpap_ed_clean_item( $li );
            if ( '' !== $t ) { $items[] = $t; }
        }
    } else {
        $b = preg_replace( '/<\/(p|div|h[1-6])>/i', "\n", (string) $body );
        $b = preg_replace( '/<br\s*\/?>/i', "\n", $b );
        foreach ( preg_split( '/\r\n|\r|\n/', wp_strip_all_tags( $b ) ) as $line ) {
            $t = wpap_ed_clean_item( $line );
            if ( '' !== $t ) { $items[] = $t; }
        }
    }
    return array_slice( $items, 0, 60 );
}

/* Pull ingredients + steps out of post HTML by locating the usual section
   headers ("Ingredients", "Instructions"/"Directions"/"Steps"/…) — either real
   headings (h2-h4) or a paragraph that's essentially one bold label — and taking
   the list (or paragraphs) that follow, up to the next header. Mirrors the JS
   extractor. Returns array( 'ingredients' => [...], 'steps' => [...] ). */
function wpap_ed_extract_recipe( $html ) {
    $out  = array( 'ingredients' => array(), 'steps' => array() );
    $html = (string) $html;
    if ( '' === trim( $html ) ) { return $out; }

    $marker = '/<h[2-4][^>]*>(.*?)<\/h[2-4]>|<p[^>]*>\s*<(?:strong|b)>(.*?)<\/(?:strong|b)>\s*:?\s*<\/p>/is';
    if ( ! preg_match_all( $marker, $html, $mm, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
        return $out;
    }

    $n = count( $mm );
    for ( $i = 0; $i < $n; $i++ ) {
        $g1    = ( isset( $mm[ $i ][1] ) && '' !== $mm[ $i ][1][0] ) ? $mm[ $i ][1][0] : '';
        $g2    = ( isset( $mm[ $i ][2] ) ) ? $mm[ $i ][2][0] : '';
        $label = wp_strip_all_tags( '' !== $g1 ? $g1 : $g2 );
        $start = $mm[ $i ][0][1] + strlen( $mm[ $i ][0][0] );
        $end   = ( $i + 1 < $n ) ? $mm[ $i + 1 ][0][1] : strlen( $html );
        $body  = substr( $html, $start, $end - $start );

        if ( empty( $out['ingredients'] ) && preg_match( '/ingredient/i', $label ) ) {
            $out['ingredients'] = wpap_ed_list_items( $body );
        } elseif ( empty( $out['steps'] ) && preg_match( '/instruction|direction|step|method|preparation|how to make|how to prepare/i', $label ) ) {
            $out['steps'] = wpap_ed_list_items( $body );
        }
    }
    return $out;
}

/* Resolve the split selector into a part count (0 = don't split). */
function wpap_ed_resolve_split_parts( $mode, $content ) {
    if ( '2' === $mode ) { return 2; }
    if ( '3' === $mode ) { return 3; }
    if ( 'smart' === $mode ) {
        $words = str_word_count( wp_strip_all_tags( (string) $content ) );
        if ( $words >= 1300 ) { return 3; }
        if ( $words >= 650 )  { return 2; }
    }
    return 0;
}

/* Assign a best-match existing category (if the post has none) and keyword tags
   (if the post has none). Only ever ADDS — never removes your selections. */
function wpap_ed_maybe_assign_terms( $post_id, $title, $content ) {
    $default_cat = (int) get_option( 'default_category' );

    $cur      = wp_get_post_categories( $post_id );
    $has_real = false;
    foreach ( $cur as $cid ) { if ( (int) $cid !== $default_cat ) { $has_real = true; break; } }

    if ( ! $has_real ) {
        $text = wpap_ed_lower( $title . ' ' . $title . ' ' . wp_strip_all_tags( (string) $content ) );
        $cats = get_categories( array( 'hide_empty' => false, 'number' => 300 ) );
        $best = 0; $best_score = 0;
        foreach ( $cats as $c ) {
            if ( (int) $c->term_id === $default_cat ) { continue; }
            $words = preg_split( '/[^\p{L}]+/u', wpap_ed_lower( $c->name . ' ' . $c->slug ) );
            $score = 0;
            foreach ( (array) $words as $nw ) {
                if ( wpap_ed_len( $nw ) < 3 ) { continue; }
                $score += substr_count( $text, $nw );
            }
            if ( $score > $best_score ) { $best_score = $score; $best = (int) $c->term_id; }
        }
        if ( $best > 0 ) {
            wp_set_post_categories( $post_id, array( $best ), true );
            update_post_meta( $post_id, '_wpap_ed_auto_category', '1' );
        }
    }

    $cur_tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
    if ( empty( $cur_tags ) ) {
        $kw = wpap_ed_keywords( $title . ' ' . $title . ' ' . $content, 5 );
        if ( ! empty( $kw ) ) {
            wp_set_post_terms( $post_id, $kw, 'post_tag', true );
            update_post_meta( $post_id, '_wpap_ed_auto_tags', '1' );
        }
    }
}

/* Push the entered image metadata onto the featured-image attachment. */
function wpap_ed_apply_image_meta( $post_id ) {
    $thumb = (int) get_post_thumbnail_id( $post_id );
    if ( $thumb <= 0 ) { return; }
    $alt     = (string) get_post_meta( $post_id, '_wpap_ed_img_alt', true );
    $title   = (string) get_post_meta( $post_id, '_wpap_ed_img_title', true );
    $caption = (string) get_post_meta( $post_id, '_wpap_ed_img_caption', true );
    $desc    = (string) get_post_meta( $post_id, '_wpap_ed_img_desc', true );
    if ( '' !== $alt ) { update_post_meta( $thumb, '_wp_attachment_image_alt', $alt ); }
    $up = array();
    if ( '' !== $title )   { $up['post_title']   = $title; }
    if ( '' !== $caption ) { $up['post_excerpt'] = $caption; }
    if ( '' !== $desc )    { $up['post_content'] = $desc; }
    if ( ! empty( $up ) ) {
        $up['ID'] = $thumb;
        wp_update_post( $up );   /* attachment save — does not fire save_post_post */
    }
}

/* ── Meta box ── */
add_action( 'add_meta_boxes_post', 'wpap_ed_add_meta_box' );
function wpap_ed_add_meta_box() {
    add_meta_box(
        'wpap-author-tools',
        __( 'WP Automator — Author Tools', 'wp-automator-pro' ),
        'wpap_ed_render_meta_box',
        'post',
        'normal',
        'high'
    );
}

function wpap_ed_render_meta_box( $post ) {
    wp_nonce_field( 'wpap_ed_save', 'wpap_ed_nonce' );
    $m = function ( $k, $d = '' ) use ( $post ) {
        $v = get_post_meta( $post->ID, $k, true );
        return ( '' !== $v && null !== $v ) ? $v : $d;
    };
    $is_new      = ( 'auto-draft' === $post->post_status );
    $autofill_on = $is_new ? true : ( '1' === (string) $m( '_wpap_ed_autofill', '1' ) );
    $manage_on   = $is_new ? true : ( '1' === (string) $m( '_wpap_ed_manage', '1' ) );
    $split       = (string) $m( '_wpap_ed_split', '0' );

    $ta = 'style="width:100%;box-sizing:border-box;" rows="2"';
    $tx = 'style="width:100%;box-sizing:border-box;"';
    ?>
    <div class="wpap-ed">
        <div class="wpap-ed-actions">
            <button type="button" class="button button-primary" id="wpap-ed-autofill"><?php esc_html_e( 'Auto-fill empty fields', 'wp-automator-pro' ); ?></button>
            <button type="button" class="button" id="wpap-ed-prepare"><?php esc_html_e( 'Prepare for publishing', 'wp-automator-pro' ); ?></button>
            <span class="wpap-ed-note"><?php esc_html_e( 'Auto-fill only touches EMPTY fields — it never overwrites what you typed.', 'wp-automator-pro' ); ?></span>
        </div>

        <div class="wpap-ed-checklist" id="wpap-ed-checklist" aria-live="polite"></div>

        <div class="wpap-ed-grid">
            <p>
                <label for="wpap_ed_seo_title"><strong><?php esc_html_e( 'SEO title', 'wp-automator-pro' ); ?></strong></label>
                <input type="text" <?php echo $tx; // phpcs:ignore ?> id="wpap_ed_seo_title" name="_wpap_ed_seo_title" value="<?php echo esc_attr( $m( '_wpap_ed_seo_title' ) ); ?>" maxlength="120" />
            </p>
            <p>
                <label for="wpap_ed_meta_desc"><strong><?php esc_html_e( 'Meta description', 'wp-automator-pro' ); ?></strong></label>
                <textarea <?php echo $ta; // phpcs:ignore ?> id="wpap_ed_meta_desc" name="_wpap_ed_meta_desc" maxlength="180"><?php echo esc_textarea( $m( '_wpap_ed_meta_desc' ) ); ?></textarea>
            </p>
        </div>

        <details class="wpap-ed-details">
            <summary><?php esc_html_e( 'Featured-image metadata', 'wp-automator-pro' ); ?></summary>
            <p><label><?php esc_html_e( 'Alt text', 'wp-automator-pro' ); ?><br><input type="text" <?php echo $tx; // phpcs:ignore ?> name="_wpap_ed_img_alt" value="<?php echo esc_attr( $m( '_wpap_ed_img_alt' ) ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Title', 'wp-automator-pro' ); ?><br><input type="text" <?php echo $tx; // phpcs:ignore ?> name="_wpap_ed_img_title" value="<?php echo esc_attr( $m( '_wpap_ed_img_title' ) ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Caption', 'wp-automator-pro' ); ?><br><textarea <?php echo $ta; // phpcs:ignore ?> name="_wpap_ed_img_caption"><?php echo esc_textarea( $m( '_wpap_ed_img_caption' ) ); ?></textarea></label></p>
            <p><label><?php esc_html_e( 'Description', 'wp-automator-pro' ); ?><br><textarea <?php echo $ta; // phpcs:ignore ?> name="_wpap_ed_img_desc"><?php echo esc_textarea( $m( '_wpap_ed_img_desc' ) ); ?></textarea></label></p>
        </details>

        <details class="wpap-ed-details">
            <summary><?php esc_html_e( 'Recipe details (adds Recipe rich-result schema)', 'wp-automator-pro' ); ?></summary>
            <p><label><input type="checkbox" name="wpap_recipe_on" value="1" <?php checked( '1' === (string) $m( '_wpap_recipe_on' ) ); ?> /> <strong><?php esc_html_e( 'This post is a recipe', 'wp-automator-pro' ); ?></strong></label></p>
            <p class="wpap-ed-recipe-row">
                <label><?php esc_html_e( 'Servings', 'wp-automator-pro' ); ?><br><input type="text" name="_wpap_recipe_servings" value="<?php echo esc_attr( $m( '_wpap_recipe_servings' ) ); ?>" style="width:110px;" /></label>
                <label><?php esc_html_e( 'Prep (min)', 'wp-automator-pro' ); ?><br><input type="number" min="0" max="1440" name="_wpap_recipe_prep" value="<?php echo esc_attr( $m( '_wpap_recipe_prep' ) ); ?>" style="width:90px;" /></label>
                <label><?php esc_html_e( 'Cook (min)', 'wp-automator-pro' ); ?><br><input type="number" min="0" max="1440" name="_wpap_recipe_cook" value="<?php echo esc_attr( $m( '_wpap_recipe_cook' ) ); ?>" style="width:90px;" /></label>
                <label><?php esc_html_e( 'Total (min)', 'wp-automator-pro' ); ?><br><input type="number" min="0" max="2880" name="_wpap_recipe_total" value="<?php echo esc_attr( $m( '_wpap_recipe_total' ) ); ?>" style="width:90px;" /></label>
            </p>
            <p><label><?php esc_html_e( 'Ingredients (one per line)', 'wp-automator-pro' ); ?><br><textarea name="_wpap_recipe_ingredients" rows="5" style="width:100%;box-sizing:border-box;"><?php echo esc_textarea( $m( '_wpap_recipe_ingredients' ) ); ?></textarea></label></p>
            <p><label><?php esc_html_e( 'Steps (one per line)', 'wp-automator-pro' ); ?><br><textarea name="_wpap_recipe_steps" rows="6" style="width:100%;box-sizing:border-box;"><?php echo esc_textarea( $m( '_wpap_recipe_steps' ) ); ?></textarea></label></p>
            <p class="wpap-ed-note"><?php esc_html_e( 'The Viral Reader theme shows a recipe card and emits matching Recipe schema (JSON-LD). Total auto-fills from prep + cook if left blank.', 'wp-automator-pro' ); ?></p>
        </details>

        <div class="wpap-ed-controls">
            <p>
                <label for="wpap_ed_split"><strong><?php esc_html_e( 'Split into pages', 'wp-automator-pro' ); ?></strong></label>
                <select id="wpap_ed_split" name="wpap_ed_split">
                    <option value="0" <?php selected( $split, '0' ); ?>><?php esc_html_e( 'Off — single page', 'wp-automator-pro' ); ?></option>
                    <option value="smart" <?php selected( $split, 'smart' ); ?>><?php esc_html_e( 'Smart (long posts only)', 'wp-automator-pro' ); ?></option>
                    <option value="2" <?php selected( $split, '2' ); ?>><?php esc_html_e( '2 pages', 'wp-automator-pro' ); ?></option>
                    <option value="3" <?php selected( $split, '3' ); ?>><?php esc_html_e( '3 pages', 'wp-automator-pro' ); ?></option>
                </select>
                <span class="wpap-ed-note"><?php esc_html_e( 'Skipped if the post already has manual page breaks.', 'wp-automator-pro' ); ?></span>
            </p>
            <p><label><input type="checkbox" name="wpap_ed_autofill" value="1" <?php checked( $autofill_on ); ?> /> <?php esc_html_e( 'Auto-fill empty fields when I save', 'wp-automator-pro' ); ?></label></p>
            <p><label><input type="checkbox" name="wpap_ed_manage" value="1" <?php checked( $manage_on ); ?> /> <?php esc_html_e( 'Let WP Automator manage this post (SEO schema, related links, ad zones)', 'wp-automator-pro' ); ?></label></p>
        </div>
    </div>
    <?php
}

/* ── Save pipeline ── */
add_action( 'save_post_post', 'wpap_ed_save', 20, 3 );
function wpap_ed_save( $post_id, $post, $update ) {
    if ( empty( $_POST['wpap_ed_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpap_ed_nonce'] ) ), 'wpap_ed_save' ) ) { return; }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
    if ( ! $post instanceof WP_Post || 'post' !== $post->post_type ) { return; }
    if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }

    static $busy = false;
    if ( $busy ) { return; }
    $busy = true;

    /* 1. Persist the editable meta-box fields. */
    $text_fields = array( '_wpap_ed_seo_title', '_wpap_ed_img_alt', '_wpap_ed_img_title' );
    foreach ( $text_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ); }
    }
    $area_fields = array( '_wpap_ed_meta_desc', '_wpap_ed_img_caption', '_wpap_ed_img_desc' );
    foreach ( $area_fields as $key ) {
        if ( isset( $_POST[ $key ] ) ) { update_post_meta( $post_id, $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) ); }
    }
    $split_mode = isset( $_POST['wpap_ed_split'] ) ? sanitize_text_field( wp_unslash( $_POST['wpap_ed_split'] ) ) : '0';
    update_post_meta( $post_id, '_wpap_ed_split', $split_mode );
    $autofill = isset( $_POST['wpap_ed_autofill'] ) ? 1 : 0;
    update_post_meta( $post_id, '_wpap_ed_autofill', $autofill );
    $manage = isset( $_POST['wpap_ed_manage'] ) ? 1 : 0;
    update_post_meta( $post_id, '_wpap_ed_manage', $manage );

    /* Recipe details (feeds the theme's Recipe card + JSON-LD). */
    update_post_meta( $post_id, '_wpap_recipe_on', isset( $_POST['wpap_recipe_on'] ) ? '1' : '' );
    if ( isset( $_POST['_wpap_recipe_servings'] ) ) { update_post_meta( $post_id, '_wpap_recipe_servings', sanitize_text_field( wp_unslash( $_POST['_wpap_recipe_servings'] ) ) ); }
    foreach ( array( '_wpap_recipe_prep', '_wpap_recipe_cook', '_wpap_recipe_total' ) as $rk ) {
        if ( isset( $_POST[ $rk ] ) ) { update_post_meta( $post_id, $rk, max( 0, min( 2880, (int) $_POST[ $rk ] ) ) ); }
    }
    foreach ( array( '_wpap_recipe_ingredients', '_wpap_recipe_steps' ) as $rk ) {
        if ( isset( $_POST[ $rk ] ) ) { update_post_meta( $post_id, $rk, sanitize_textarea_field( wp_unslash( $_POST[ $rk ] ) ) ); }
    }

    /* Auto-extract ingredients/steps from the post body — only when this is a
       recipe, auto-fill is on, and the field was left empty. Provenance flags let
       a later manual edit stick. */
    if ( $autofill && '1' === (string) get_post_meta( $post_id, '_wpap_recipe_on', true ) ) {
        $need_ing  = ( '' === trim( (string) get_post_meta( $post_id, '_wpap_recipe_ingredients', true ) ) );
        $need_step = ( '' === trim( (string) get_post_meta( $post_id, '_wpap_recipe_steps', true ) ) );
        if ( $need_ing || $need_step ) {
            $rx = wpap_ed_extract_recipe( (string) $post->post_content );
            if ( $need_ing && ! empty( $rx['ingredients'] ) ) {
                update_post_meta( $post_id, '_wpap_recipe_ingredients', sanitize_textarea_field( implode( "\n", $rx['ingredients'] ) ) );
                update_post_meta( $post_id, '_wpap_recipe_auto_ingredients', 1 );
            }
            if ( $need_step && ! empty( $rx['steps'] ) ) {
                update_post_meta( $post_id, '_wpap_recipe_steps', sanitize_textarea_field( implode( "\n", $rx['steps'] ) ) );
                update_post_meta( $post_id, '_wpap_recipe_auto_steps', 1 );
            }
        }
    }

    $content = (string) $post->post_content;
    $title   = (string) $post->post_title;

    /* 2. Auto-fill EMPTY fields only. */
    if ( $autofill ) {
        if ( '' === trim( (string) get_post_meta( $post_id, '_wpap_ed_seo_title', true ) ) && '' !== $title ) {
            update_post_meta( $post_id, '_wpap_ed_seo_title', wpap_ed_clip( $title, 60 ) );
            update_post_meta( $post_id, '_wpap_ed_auto_seo_title', '1' );
        }
        if ( '' === trim( (string) get_post_meta( $post_id, '_wpap_ed_meta_desc', true ) ) ) {
            $md = wpap_make_excerpt( $content, 155 );
            if ( '' !== $md ) {
                update_post_meta( $post_id, '_wpap_ed_meta_desc', $md );
                update_post_meta( $post_id, '_wpap_ed_auto_meta_desc', '1' );
            }
        }
        if ( '' === trim( (string) get_post_meta( $post_id, '_wpap_ed_img_alt', true ) ) && '' !== $title ) {
            update_post_meta( $post_id, '_wpap_ed_img_alt', $title );
        }
        if ( '' === trim( (string) get_post_meta( $post_id, '_wpap_ed_img_title', true ) ) && '' !== $title ) {
            update_post_meta( $post_id, '_wpap_ed_img_title', $title );
        }
        wpap_ed_maybe_assign_terms( $post_id, $title, $content );
    }

    /* 3. Sync SEO to Yoast / Rank Math. */
    $seo_title = (string) get_post_meta( $post_id, '_wpap_ed_seo_title', true );
    $meta_desc = (string) get_post_meta( $post_id, '_wpap_ed_meta_desc', true );
    if ( '' !== $seo_title || '' !== $meta_desc ) {
        wpap_set_seo_meta( $post_id, $meta_desc, $seo_title );
    }

    /* 4. Featured-image metadata. */
    wpap_ed_apply_image_meta( $post_id );

    /* 5. Plugin-managed flag (enables our SEO JSON-LD + related links + ads). */
    if ( $manage ) {
        $perma = get_permalink( $post_id );
        if ( $perma ) { update_post_meta( $post_id, '_wpap_smart_link', $perma ); }
    } else {
        delete_post_meta( $post_id, '_wpap_smart_link' );
    }

    /* 6. Content pagination + excerpt (single wp_update_post, self-hook removed). */
    $new_content = $content;
    $parts = wpap_ed_resolve_split_parts( $split_mode, $content );
    if ( $parts >= 2 && false === strpos( $content, '<!--nextpage-->' ) ) {
        $split = wpap_split_content_into_parts( $content, $parts );
        if ( is_string( $split ) && '' !== $split ) { $new_content = $split; }
    }
    $cur_excerpt = (string) $post->post_excerpt;
    $new_excerpt = null;
    if ( $autofill && '' === trim( $cur_excerpt ) ) {
        $auto_ex = wpap_make_excerpt( $content, 200 );
        if ( '' !== $auto_ex ) { $new_excerpt = $auto_ex; }
    }

    $changes = array();
    if ( $new_content !== $content ) { $changes['post_content'] = $new_content; }
    if ( null !== $new_excerpt && $new_excerpt !== $cur_excerpt ) { $changes['post_excerpt'] = $new_excerpt; }
    if ( ! empty( $changes ) ) {
        $changes['ID'] = $post_id;
        remove_action( 'save_post_post', 'wpap_ed_save', 20 );
        wp_update_post( $changes );
        add_action( 'save_post_post', 'wpap_ed_save', 20, 3 );
    }

    $busy = false;
}

/* ── Editor-only assets (separate enqueuer; does NOT relax the dashboard guard) ── */
add_action( 'admin_enqueue_scripts', 'wpap_ed_enqueue' );
function wpap_ed_enqueue( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) { return; }
    $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
    if ( ! $screen || 'post' !== $screen->post_type ) { return; }

    wp_enqueue_style( 'wpap-editor-tools', plugins_url( 'assets/editor-tools.css', __FILE__ ), array(), WPAP_VERSION );
    wp_enqueue_script( 'wpap-editor-tools', plugins_url( 'assets/editor-tools.js', __FILE__ ), array( 'jquery' ), WPAP_VERSION, true );
    wp_localize_script( 'wpap-editor-tools', 'wpapEd', array(
        'minWords' => 150,
        'i18n'     => array(
            'ready'      => __( 'ready', 'wp-automator-pro' ),
            'title'      => __( 'Title', 'wp-automator-pro' ),
            'body'       => __( 'Enough content', 'wp-automator-pro' ),
            'metaDesc'   => __( 'Meta description', 'wp-automator-pro' ),
            'category'   => __( 'Category', 'wp-automator-pro' ),
            'tags'       => __( 'Tags', 'wp-automator-pro' ),
            'featured'   => __( 'Featured image', 'wp-automator-pro' ),
            'imageAlt'   => __( 'Image alt text', 'wp-automator-pro' ),
            'filled'     => __( 'Filled empty fields from the title and content.', 'wp-automator-pro' ),
        ),
    ) );
}
