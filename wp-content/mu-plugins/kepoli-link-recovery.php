<?php
/**
 * Plugin Name: Kepoli Link Recovery (404 → canonical)
 * Description: kepoli permalinks are /%category%/%postname%/. WordPress already 301-redirects
 *   wrong-category and ?p=ID links to the canonical URL, but NOT a bare-slug link, nor a title-derived
 *   slug that doesn't match the post's actual (sometimes length-capped/truncated) slug — those hard-404.
 *   Facebook / campaign links shared in those forms then die and the traffic is lost. On ANY 404 this
 *   finds the intended post — first by EXACT slug (the last URL path segment), then by the best
 *   token-overlap of that segment against published post/page slugs — and 301-redirects to its canonical
 *   permalink. Recovers already-shared links and any future mis-slugged share. Read-only, cached, and
 *   only ever runs on a 404, so it adds nothing to normal page loads.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'kepoli_link_recovery', 0);
function kepoli_link_recovery(): void
{
    if (is_admin() || !is_404()) {
        return;
    }
    $uri  = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = trim((string) parse_url($uri, PHP_URL_PATH), '/');
    if ($path === '' || preg_match('#^(wp-|feed|xmlrpc|wp-json|sitemap|robots|ads\.txt|favicon|comments)#i', $path)) {
        return;
    }
    $segs    = explode('/', $path);
    $attempt = sanitize_title((string) end($segs));
    if ($attempt === '' || strlen($attempt) < 4) {
        return;
    }

    // 1) Exact slug match — a flat /slug/ link, or the right slug under a wrong path prefix.
    $exact = get_posts([
        'name'           => $attempt,
        'post_type'      => ['post', 'page'],
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ]);
    if (!empty($exact)) {
        kepoli_link_recovery_go((int) $exact[0]);
        return;
    }

    // 2) Fuzzy match — the shared slug differs from the actual slug (e.g. title-slug vs a truncated slug).
    //    Pick the published post/page whose slug shares the most tokens with the attempted slug, but only
    //    when the match is strong AND clearly unambiguous, so a genuine 404 is never mis-redirected.
    $aTok = array_values(array_filter(explode('-', $attempt), static fn($t) => strlen($t) > 2));
    if (count($aTok) < 3) {
        return; // too little signal to match safely
    }
    $aSet = array_flip($aTok);

    $map = get_transient('kepoli_slug_map');
    if (!is_array($map)) {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT ID, post_name FROM {$wpdb->posts}
              WHERE post_status='publish' AND post_type IN ('post','page') AND post_name<>''"
        );
        $map = [];
        foreach ((array) $rows as $r) {
            $map[(int) $r->ID] = (string) $r->post_name;
        }
        set_transient('kepoli_slug_map', $map, 10 * MINUTE_IN_SECONDS);
    }

    $bestId = 0;
    $best   = 0.0;
    $second = 0.0;
    foreach ($map as $id => $name) {
        $bTok = array_filter(explode('-', $name), static fn($t) => strlen($t) > 2);
        if (!$bTok) {
            continue;
        }
        $inter = 0;
        foreach ($bTok as $t) {
            if (isset($aSet[$t])) {
                $inter++;
            }
        }
        $union = count($aSet) + count($bTok) - $inter;
        $score = $union > 0 ? $inter / $union : 0.0;
        if ($score > $best) {
            $second = $best;
            $best   = $score;
            $bestId = (int) $id;
        } elseif ($score > $second) {
            $second = $score;
        }
    }

    if ($bestId > 0 && $best >= 0.6 && $best >= $second + 0.15) {
        kepoli_link_recovery_go($bestId);
    }
}

function kepoli_link_recovery_go(int $id): void
{
    $url = get_permalink($id);
    if ($url) {
        wp_safe_redirect($url, 301);
        exit;
    }
}

/* Keep the cached slug map fresh when posts change (cheap; the map rebuilds lazily on the next 404). */
add_action('save_post', static function (): void {
    delete_transient('kepoli_slug_map');
});
