<?php
/**
 * Plugin Name: Kepoli Remedy Top Notice
 * Description: Prepends a short medical-disclaimer notice to the TOP of single posts in the
 *   home-remedy categories. Google's YMYL guidance favours a disclaimer near the top of health
 *   content, not only in the footer; the full disclaimer still renders in the body and links to
 *   /medical-disclaimer/. Scoped to the remedy category (kepoli-shared.php — 'natural-remedies' after
 *   the 2026-09-01 merge) so recipes/tips/stories are untouched, and applies automatically to future
 *   remedy posts.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The remedy category slug(s) — single source of truth in kepoli-shared.php (one 'natural-remedies'
 * category after the 2026-09-01 merge). The fallback keeps the disclaimer working even if the shared
 * helper ever fails to load.
 */
function kepoli_remedy_category_slugs(): array
{
    return function_exists('kepoli_remedy_slugs') ? kepoli_remedy_slugs() : ['natural-remedies'];
}

/*
 * Priority 8 → runs before the plugin's recipe card (9) and related-posts (20) and before
 * wpautop (10), so the notice lands at the very top of the article. The string is already a
 * valid <p>, so wpautop leaves it intact.
 */
add_filter('the_content', 'kepoli_remedy_top_notice', 8);
function kepoli_remedy_top_notice(string $content): string
{
    if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    if (!has_category(kepoli_remedy_category_slugs())) {
        return $content;
    }
    // Guard against a double-prepend if the filter somehow runs twice on one render.
    if (false !== strpos($content, 'kepoli-remedy-topnotice')) {
        return $content;
    }

    $url = esc_url(home_url('/medical-disclaimer/'));
    $notice = '<p class="kepoli-remedy-topnotice"><em><strong>A quick note:</strong> this is a traditional '
        . 'home remedy shared for general interest — not medical advice. See our '
        . '<a href="' . $url . '" rel="nofollow">medical disclaimer</a>, and please see a doctor for severe, '
        . 'worsening, or persistent symptoms.</em></p>' . "\n";

    return $notice . $content;
}

/*
 * A little breathing room + a soft left rule so the notice reads as an aside, not body copy.
 * Emitted once, only on a remedy single post (where the notice actually renders).
 */
add_action('wp_head', 'kepoli_remedy_top_notice_css', 20);
function kepoli_remedy_top_notice_css(): void
{
    if (!is_singular('post') || !has_category(kepoli_remedy_category_slugs())) {
        return;
    }
    echo "\n<style>.kepoli-remedy-topnotice{margin:0 0 1.2em;padding:.6em 0 .6em 1em;"
        . "border-left:3px solid rgba(0,0,0,.18);font-size:.94em;line-height:1.5;opacity:.85}</style>\n";
}
