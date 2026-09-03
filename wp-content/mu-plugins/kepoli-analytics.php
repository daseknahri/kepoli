<?php
/**
 * Plugin Name: Kepoli Analytics & Verification
 * Description: Consumes the measurement/verification env vars that were passed to the
 *   container but previously read by nothing: search-engine + platform site-verification
 *   meta tags (Google Search Console, Bing, Pinterest), Google Analytics 4 (Consent Mode v2
 *   aware, gated behind the same denied-by-default state as ads), and Histats. Everything is
 *   env-gated and off until configured. Loads after kepoli-adtech (alphabetical mu-plugin
 *   order), so kepoli_mu_env() and kepoli_mu_consent_default() are already defined.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/** Truthy env check ("1"/"true"/"on"/"yes"); anything else (incl. empty) is false. */
if (!function_exists('kepoli_mu_env_bool')) {
    function kepoli_mu_env_bool(string $key, bool $default = false): bool
    {
        $v = strtolower(trim(kepoli_mu_env($key)));
        if ($v === '') {
            return $default;
        }
        return in_array($v, ['1', 'true', 'on', 'yes'], true);
    }
}

/**
 * Site-ownership verification meta tags. Each emits only when its env var is set, so an
 * empty value leaves nothing in the <head>. Search Console is the one that matters for the
 * campaign (submit the sitemap, watch indexing); Bing + Pinterest are there for the other
 * discovery/organic channels in the launch plan.
 */
add_action('wp_head', 'kepoli_site_verifications', 1);
function kepoli_site_verifications(): void
{
    if (is_admin() || is_feed()) {
        return;
    }
    $tags = [
        'google-site-verification' => kepoli_mu_env('SEARCH_CONSOLE_VERIFICATION'),
        'msvalidate.01'            => kepoli_mu_env('BING_SITE_VERIFICATION'),
        'p:domain_verify'          => kepoli_mu_env('PINTEREST_SITE_VERIFICATION'),
    ];
    $out = '';
    foreach ($tags as $name => $value) {
        $value = trim((string) $value);
        if ($value !== '') {
            $out .= '<meta name="' . esc_attr($name) . '" content="' . esc_attr($value) . '">' . "\n";
        }
    }
    if ($out !== '') {
        echo "\n" . $out;
    }
}

/**
 * Google Analytics 4 — Consent Mode v2 aware. Emitted only when GA_ENABLE is on and a
 * well-formed Measurement ID (G-XXXX) is set. Calls kepoli_mu_consent_default() first so the
 * denied-by-default state exists even when ad serving is off (the AdSense loader would
 * otherwise be the only thing that emits it); analytics_storage stays 'denied' for EEA/UK/CH
 * until the CMP grants, so GA runs cookielessly (modelled) there — compliant during the
 * approval window and after. Priority 11 keeps it after the AdSense/consent block (9).
 */
add_action('wp_head', 'kepoli_ga4_head', 11);
function kepoli_ga4_head(): void
{
    if (is_admin() || is_feed()) {
        return;
    }
    if (!kepoli_mu_env_bool('GA_ENABLE')) {
        return;
    }
    $id = trim(kepoli_mu_env('GA_MEASUREMENT_ID'));
    if (!preg_match('/^G-[A-Z0-9]{4,}$/i', $id)) {
        return; // never emit a malformed measurement id
    }

    if (function_exists('kepoli_mu_consent_default')) {
        kepoli_mu_consent_default();
    }

    echo '<link rel="preconnect" href="https://www.googletagmanager.com">' . "\n";
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . rawurlencode($id) . '"></script>' . "\n";
    echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
        . "gtag('js',new Date());gtag('config','" . esc_js($id) . "');</script>" . "\n";
}

/**
 * Histats — a lightweight live visitor counter. Emitted in the footer only when
 * HISTATS_ENABLE is on and a base64-encoded snippet is provided. Logged-in managers are
 * excluded (HISTATS_EXCLUDE_ADMINS, default on) so admin visits don't inflate the count.
 *
 * Note: Histats predates Consent Mode and sets its own cookies, so treat it as a
 * non-essential tracker your consent notice/privacy policy must cover for EEA/UK/CH — it is
 * off by default here for that reason. The snippet is your own, base64-encoded env value
 * (owner-controlled), so it is emitted verbatim.
 */
add_action('wp_footer', 'kepoli_histats_footer', 20);
function kepoli_histats_footer(): void
{
    // HARD-DISABLED 2026-09-03. Live network inspection showed the Histats snippet (its js15_as.js) injects,
    // at RUNTIME, a chain of unvetted third-party DATA-BROKER trackers that are NOT present in the served HTML
    // and fire before the consent gate: DTScout (e./t.dtscout.com, t.dtscdn.com), Lotame Crowd Control
    // (tags./bcp.crwdcntrl.net), OnAudience (pixel.onaudience.com) and Market Metrics (p.mrktmtrcs.net). On a
    // site seeking AdSense approval this is a triple liability — privacy/GDPR (undisclosed pre-consent data
    // harvesting), page speed (10+ extra third-party origins + a render-blocking inject), and an "unvetted
    // third-party ad/data script" signal to review. Basic visitor stats are already covered by GA4 / Site Kit,
    // so Histats adds no value that justifies the cost. Left OFF unconditionally regardless of the env flags;
    // also clear HISTATS_ENABLE / HISTATS_CODE_BASE64 in the Coolify env for tidiness. If a live counter is
    // ever wanted again, use a privacy-respecting, consent-gated one — not Histats.
    return;
}
