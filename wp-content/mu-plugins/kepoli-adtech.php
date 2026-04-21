<?php
/**
 * Plugin Name: Kepoli Ad and Verification Helpers
 * Description: Handles ads.txt and lightweight verification output for the Kepoli Docker deployment.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_mu_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : trim((string) $value);
}

add_action('template_redirect', static function (): void {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($path !== '/ads.txt') {
        return;
    }

    $publisher_id = kepoli_mu_env('ADSENSE_PUB_ID');
    status_header($publisher_id === '' ? 404 : 200);
    header('Content-Type: text/plain; charset=utf-8');

    if ($publisher_id !== '') {
        $publisher_id = str_starts_with($publisher_id, 'pub-') ? $publisher_id : 'pub-' . $publisher_id;
        echo 'google.com, ' . esc_html($publisher_id) . ", DIRECT, f08c47fec0942fa0\n";
    }

    exit;
});

add_filter('robots_txt', static function (string $output, bool $public): string {
    if (!$public) {
        return $output;
    }

    if (!str_contains($output, 'Sitemap:')) {
        $output .= "\nSitemap: " . home_url('/wp-sitemap.xml') . "\n";
    }

    return $output;
}, 10, 2);
