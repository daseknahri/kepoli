<?php
/**
 * Plugin Name: Kepoli Shared Helpers
 * Description: Single source of truth for values shared across kepoli mu-plugins. Currently the home-remedy
 *   (YMYL folk-cure) category slug set — referenced by kepoli-remedy-notice (the medical disclaimer),
 *   kepoli-noindex-remedies (the review-time noindex shield), and kepoli-autoseed. Consolidating it here
 *   prevents the drift that silently killed the disclaimer + noindex after the 2026-09-01 category merge
 *   (colds-respiratory + skin-wounds-teeth + aches-pains-fever -> natural-remedies), and the earlier
 *   fatal-redeclare 'site down' incident from the same list living in two files. Define-guarded so it is
 *   safe regardless of mu-plugin load order (all callers use it at hook/runtime, after every mu-plugin loads).
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('kepoli_remedy_slugs')) {
    /**
     * The home-remedy (YMYL folk-cure) category slug(s) — the only content the disclaimer + noindex shield
     * touch. After the 2026-09-01 merge the three original remedy categories are one: 'natural-remedies'.
     */
    function kepoli_remedy_slugs(): array
    {
        return ['natural-remedies'];
    }
}
