<?php
/**
 * Plugin Name: Kepoli Legacy-URL Sunset (410 Gone for the old KepoliRadio site)
 * Description: kepoli.com was previously "KepoliRadio", a global radio-station directory with a large set of
 *   /listen/<station> pages (+ a /faq page and a radio homepage). The domain is now a food/wellness blog and
 *   those URLs 404 — but Google STILL has the old radio pages indexed (its sitemap read was stale for months).
 *   Hundreds of thin, auto-generated directory pages sitting in the index pollute the domain and work directly
 *   against AdSense "low value content" review. A 404 reads as "maybe temporary"; this returns HTTP **410 Gone**
 *   for the legacy radio paths — the strongest signal to PERMANENTLY de-index them — so Google drops them far
 *   faster than passive 404 attrition. Front-end only; matches just the legacy patterns (no real blog content
 *   lives under /listen/); runs at template_redirect priority -1 so it fires before kepoli-link-recovery and
 *   those URLs are 410'd, never redirected. Pair with a Search Console "Removals" prefix request on
 *   kepoli.com/listen/ for near-immediate suppression.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'kepoli_sunset_legacy_urls', -1);
function kepoli_sunset_legacy_urls(): void
{
    if (is_admin()) {
        return;
    }
    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = strtolower(trim((string) parse_url($uri, PHP_URL_PATH), '/'));
    if ($path === '') {
        return;
    }

    // Legacy KepoliRadio surfaces: the /listen/ station directory and the browse paths (genre / country /
    // language / station), plus a couple of standalone radio pages. None of these are real blog URLs (the
    // blog lives under its 8 categories + the trust pages), and the is_404() guard below is the real safety
    // net — so matching a prefix here only ever upgrades a genuine legacy 404 into a 410.
    $legacy_prefixes = ['listen/', 'genre/', 'country/', 'language/', 'station/', 'stations/'];
    $is_legacy = in_array($path, ['listen', 'genre', 'country', 'language', 'station', 'stations', 'faq'], true);
    if (!$is_legacy) {
        foreach ($legacy_prefixes as $pre) {
            if (strncmp($path, $pre, strlen($pre)) === 0) {
                $is_legacy = true;
                break;
            }
        }
    }
    if (!$is_legacy) {
        return;
    }

    // Safety: only sunset genuinely-missing URLs — never override a real current page/post that
    // happens to share a path. Every legacy radio path 404s today, so this always holds for them.
    if (!is_404()) {
        return;
    }

    status_header(410);
    nocache_headers();
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="robots" content="noindex,follow"><title>410 Gone</title></head><body>'
        . '<h1>410 Gone</h1><p>This page is no longer available. Visit '
        . '<a href="/">kepoli.com</a> for recipes, food &amp; nutrition, and everyday wellness.</p>'
        . '</body></html>';
    exit;
}
