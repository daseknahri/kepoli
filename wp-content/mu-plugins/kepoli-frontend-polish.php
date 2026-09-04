<?php
/**
 * Plugin Name: Kepoli Front-end Polish (primary-nav labels)
 * Description: kepoli-SPECIFIC glue: shorten the primary-nav labels to kepoli's own category short-names so the
 *   8-item bar fits one desktop row (the full names — "Food & Nutrition", "Home & Natural Living", … — overflow
 *   and used to squeeze the brand). The underlying categories keep their full names; only the menu display text
 *   changes. This is site-specific (it hard-codes kepoli's category names), so it correctly stays a mu-plugin.
 *
 *   The theme-generic UX/CLS fixes that used to live here (brand flex-shrink, header flex-wrap for the mobile
 *   nav dropdown, external-image aspect-ratio, 44px secondary-list tap targets, scroll-padding-top, the homepage
 *   category swipe-strip, and the "Keep reading"-first single-extras reorder) were folded into viral-reader's
 *   own style.css in v1.9.10 so every site on the theme inherits them — per the reusability routing rule.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/* Shorten the primary-nav labels (display only; the underlying categories keep their full names). */
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
