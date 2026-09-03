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
/* Any in-page jump (FAQ anchor, jump-to-section) clears the 69px sticky header. */
html{scroll-padding-top:88px}
/* MOBILE — let the header row wrap so the OPEN hamburger nav drops to its own full-width row. The theme
   intends this (.site-nav{order:5;width:100%} in the <=900 block) but never set flex-wrap, so without this
   the nav is squeezed to a ~157px column pinned right instead of a full-width dropdown. */
@media(max-width:900px){.site-header__inner{flex-wrap:wrap}}
/* MOBILE — the homepage "Explore by topic" band stacked all 8 cards (~1.8 screens), pushing the actual post
   grid ~3.3 screens down. Turn it into a horizontal swipe strip so latest stories surface far sooner. */
@media(max-width:640px){
  .category-list{display:flex;flex-wrap:nowrap;overflow-x:auto;scroll-snap-type:x proximity;gap:12px;padding-bottom:4px;-webkit-overflow-scrolling:touch}
  .category-card{flex:0 0 78%;scroll-snap-align:start}
}
/* MOBILE — secondary-list links carried their spacing on the <li>, not the <a>, so the real tap target was
   only the ~20px text line-box. Make the link itself a full 44px hit area. Recent-stories rows (56px thumb)
   and the inline copyright link are left alone. */
@media(max-width:640px){
  .site-footer__explore li,.site-footer__info li{padding:0}
  .site-footer__explore a,.site-footer__info a{display:flex;align-items:center;min-height:44px;padding:6px 0}
  .sidebar__list li:not(:has(.sidebar__recent)){padding:0}
  .sidebar__list a:not(.sidebar__recent){display:flex;align-items:center;min-height:44px;padding:6px 0}
}
CSS;
    echo "\n<style id=\"kepoli-polish\">" . $css . "</style>\n";
}
