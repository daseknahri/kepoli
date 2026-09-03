<?php
/**
 * Plugin Name: Kepoli Front-end Polish (nav labels + targeted UX/CLS CSS)
 * Description: Small, safe front-end UX fixes from a 2026-09-03 UI/UX audit of the viral-reader theme (which is
 *   otherwise strong — AA contrast, focus rings, skip-link, 44px tap targets, CLS-safe ad slots). All done via
 *   hooks so the parallel-session-owned theme is untouched:
 *     1. Shorten the 8 primary-nav labels (the full category names overflow the desktop header row and were
 *        squeezing the "Kepoli" brand to "Kep…"); category NAMES stay full — only the menu display text shrinks.
 *     2. CSS: pin the brand so it can never collapse again (nav wraps instead); reserve an aspect box on the
 *        single-post featured image's external-URL fallback (no width/height there → LCP-slot layout shift);
 *        reorder the end-of-article blocks so the "Keep reading" related grid (the internal-click driver) sits
 *        right where the content ends, above the author bio.
 *   Front-end only; reversible (delete the file).
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/* --- 1) Shorten the primary-nav labels (display only; the underlying categories keep their full names). ---- */
add_filter('wp_nav_menu_objects', static function ($items, $args) {
    if (!is_object($args) || ($args->theme_location ?? '') !== 'primary') {
        return $items;
    }
    $short = [
        'Food & Nutrition'      => 'Nutrition',
        'Wellness & Habits'     => 'Wellness',
        'Skin & Beauty'         => 'Beauty',
        'Home & Natural Living' => 'Natural Living',
        'Natural Remedies'      => 'Remedies',
        'About Kepoli'          => 'About',
    ];
    foreach ($items as $item) {
        // Match against the decoded title so a stored "&amp;" still maps.
        $key = html_entity_decode((string) $item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (isset($short[$key])) {
            $item->title = $short[$key];
        }
    }
    return $items;
}, 10, 2);

/* --- 2) Targeted CSS. Emitted late in <head> so it wins the cascade tie against the theme's inlined CSS. ---- */
add_action('wp_head', 'kepoli_polish_css', 99);
function kepoli_polish_css(): void
{
    if (is_admin() || is_feed()) {
        return;
    }
    $css = <<<'CSS'
/* Brand can never be the flex "pressure-release valve" again: the nav wraps before the name clips. */
.site-brand{flex-shrink:0}
.site-brand__name{overflow:visible;text-overflow:clip}
/* Single-post featured image: the external-URL fallback ships no width/height, so reserve its box to stop a
   header reflow on the LCP slot. Local thumbnails carry .wp-post-image (already sized), so leave them alone. */
.entry-featured-media img:not(.wp-post-image){aspect-ratio:16/10;object-fit:cover}
/* Put the "Keep reading" related grid first — highest-intent moment is right where the article ends. */
.single-extras{display:flex;flex-direction:column}
.single-extras .related-posts{order:1}
.single-extras .author-box{order:2}
.single-extras .post-navigation-simple{order:3}
.single-extras .comments-area{order:4}
CSS;
    echo "\n<style id=\"kepoli-polish\">" . $css . "</style>\n";
}
