<?php
/**
 * Plugin Name: Kepoli Schema (Organization + WebSite)
 * Description: Emits site-wide Organization and WebSite (SearchAction) JSON-LD to
 *   complete the structured-data set. The theme emits Recipe + BreadcrumbList and the
 *   automation-hamri plugin emits Article/BlogPosting on managed posts; neither emits
 *   a site-level Organization or WebSite node, so this fills that gap. Defers entirely
 *   to a dedicated SEO plugin (Yoast/Rank Math/AIOSEO/SEOPress) if one is ever active.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', 'kepoli_schema_jsonld', 5);
function kepoli_schema_jsonld(): void
{
    // A dedicated SEO plugin would own these nodes — don't double up.
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || function_exists('seopress_init') || defined('AIOSEO_VERSION')) {
        return;
    }
    if (is_admin() || is_feed() || is_search() || is_404()) {
        return;
    }

    $name = get_bloginfo('name');
    $home = home_url('/');
    $org_id = $home . '#organization';
    $site_id = $home . '#website';

    $logo = function_exists('get_site_icon_url') ? get_site_icon_url(512) : '';
    if (!$logo) {
        $logo = get_template_directory_uri() . '/assets/img/kepoli-icon.png';
    }

    $graph = [
        [
            '@type'       => 'Organization',
            '@id'         => $org_id,
            'name'        => $name,
            'url'         => $home,
            'description' => get_bloginfo('description'),
            'logo'        => [
                '@type'  => 'ImageObject',
                'url'    => $logo,
                'width'  => 512,
                'height' => 512,
            ],
        ],
        [
            '@type'      => 'WebSite',
            '@id'        => $site_id,
            'name'       => $name,
            'url'        => $home,
            'publisher'  => ['@id' => $org_id],
            'inLanguage' => str_replace('_', '-', get_locale()),
            'potentialAction' => [
                '@type'  => 'SearchAction',
                'target' => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => $home . '?s={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];

    $data = ['@context' => 'https://schema.org', '@graph' => $graph];

    echo "\n" . '<script type="application/ld+json">'
        . wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}
