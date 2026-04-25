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

function kepoli_mu_redirect_hosts(string $canonical_host): array
{
    $hosts = [
        'www.' . $canonical_host,
        'api.' . $canonical_host,
        'recipe.' . $canonical_host,
    ];

    $configured_hosts = array_filter(array_map(
        'trim',
        explode(',', kepoli_mu_env('CANONICAL_REDIRECT_HOSTS'))
    ));

    return array_values(array_unique(array_map('strtolower', array_merge($hosts, $configured_hosts))));
}

add_action('template_redirect', static function (): void {
    $site_url = kepoli_mu_env('SITE_URL', home_url('/'));
    $canonical_host = strtolower((string) parse_url($site_url, PHP_URL_HOST));
    if ($canonical_host === '') {
        return;
    }

    $current_host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $current_host = preg_replace('/:\d+$/', '', $current_host) ?: '';
    if ($current_host === '' || $current_host === $canonical_host) {
        return;
    }

    if (!in_array($current_host, kepoli_mu_redirect_hosts($canonical_host), true)) {
        return;
    }

    $scheme = (string) parse_url($site_url, PHP_URL_SCHEME);
    $scheme = $scheme !== '' ? $scheme : 'https';
    $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $request_uri = str_starts_with($request_uri, '/') ? $request_uri : '/';

    wp_redirect($scheme . '://' . $canonical_host . $request_uri, 301, 'Kepoli');
    exit;
}, 0);

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

add_action('template_redirect', static function (): void {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($path !== '/.well-known/security.txt') {
        return;
    }

    $site_url = trailingslashit(kepoli_mu_env('SITE_URL', home_url('/')));
    $contact_email = sanitize_email(kepoli_mu_env('SITE_EMAIL', 'contact@kepoli.com'));
    $contact_page = trailingslashit(home_url('/contact/'));
    $expires = gmdate('Y-m-d\T00:00:00\Z', strtotime('+12 months'));

    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');

    echo 'Contact: mailto:' . esc_html($contact_email) . "\n";
    echo 'Contact: ' . esc_url_raw($contact_page) . "\n";
    echo 'Canonical: ' . esc_url_raw($site_url . '.well-known/security.txt') . "\n";
    echo "Preferred-Languages: ro, en\n";
    echo 'Expires: ' . esc_html($expires) . "\n";

    exit;
});

add_action('template_redirect', static function (): void {
    $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if ($path !== '/site.webmanifest') {
        return;
    }

    status_header(200);
    header('Content-Type: application/manifest+json; charset=utf-8');

    $manifest = [
        'name' => 'Kepoli',
        'short_name' => 'Kepoli',
        'description' => 'Retete romanesti si ghiduri pentru gatit acasa.',
        'lang' => get_bloginfo('language') ?: 'ro-RO',
        'start_url' => home_url('/'),
        'scope' => home_url('/'),
        'display' => 'standalone',
        'background_color' => '#fbf7ef',
        'theme_color' => '#252416',
        'icons' => [
            [
                'src' => get_template_directory_uri() . '/assets/img/kepoli-icon.svg',
                'sizes' => 'any',
                'type' => 'image/svg+xml',
                'purpose' => 'any',
            ],
        ],
    ];

    echo wp_json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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

add_filter('xmlrpc_enabled', '__return_false');

add_filter('wp_headers', static function (array $headers): array {
    unset($headers['X-Pingback']);
    return $headers;
});

add_filter('wp_sitemaps_add_provider', static function ($provider, string $name) {
    if ($name === 'users') {
        return false;
    }

    return $provider;
}, 10, 2);

add_filter('rest_endpoints', static function (array $endpoints): array {
    if (is_user_logged_in()) {
        return $endpoints;
    }

    unset(
        $endpoints['/wp/v2/users'],
        $endpoints['/wp/v2/users/(?P<id>[\\d]+)']
    );

    return $endpoints;
});
