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

/**
 * Meta description + Open Graph + Twitter Card for the FRONT PAGE and static
 * PAGES. The automation-hamri plugin already emits these on managed posts
 * (_wpap_smart_link); pages and the home page are not managed, so without this
 * they ship with no description/OG. Defers to a real SEO plugin if present, and
 * skips plugin-managed posts to avoid duplicate tags.
 */
add_action('wp_head', 'kepoli_seo_meta_head', 4);
function kepoli_seo_meta_head(): void
{
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || function_exists('seopress_init') || defined('AIOSEO_VERSION')) {
        return;
    }
    if (is_admin() || is_feed()) {
        return;
    }
    // Only home + static pages here; posts are handled by the plugin.
    if (!is_front_page() && !is_page()) {
        return;
    }
    $qid = get_queried_object_id();
    if ($qid && get_post_meta($qid, '_wpap_smart_link', true)) {
        return; // a managed post standing in as the front page — let the plugin own it
    }

    $desc = '';
    if ($qid) {
        $desc = (string) get_post_meta($qid, '_kepoli_meta_description', true);
        if ($desc === '') {
            $desc = wp_strip_all_tags((string) get_the_excerpt($qid));
        }
        if ($desc === '') {
            $p = get_post($qid);
            $desc = $p ? wp_trim_words(wp_strip_all_tags($p->post_content), 30, '') : '';
        }
    }
    if (trim($desc) === '') {
        $desc = (string) get_bloginfo('description');
    }
    $desc = trim(preg_replace('/\s+/', ' ', $desc));

    $title = wp_get_document_title();
    $url   = is_front_page() ? home_url('/') : get_permalink($qid);
    $img   = function_exists('get_site_icon_url') ? get_site_icon_url(512) : '';
    if (!$img) {
        $img = get_template_directory_uri() . '/assets/img/kepoli-icon.png';
    }

    $out  = "\n";
    $out .= '<meta name="description" content="' . esc_attr($desc) . '">' . "\n";
    $out .= '<meta property="og:type" content="website">' . "\n";
    $out .= '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    $out .= '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    $out .= '<meta property="og:description" content="' . esc_attr($desc) . '">' . "\n";
    $out .= '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    $out .= '<meta property="og:image" content="' . esc_url($img) . '">' . "\n";
    $out .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $out .= '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    $out .= '<meta name="twitter:description" content="' . esc_attr($desc) . '">' . "\n";
    $out .= '<meta name="twitter:image" content="' . esc_url($img) . '">' . "\n";
    echo $out;
}
