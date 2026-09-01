<?php
/**
 * Plugin Name: Kepoli Link Recovery (404 → canonical)
 * Description: kepoli permalinks are /%category%/%postname%/. WordPress already 301-redirects
 *   wrong-category and ?p=ID links to canonical, but NOT a bare-slug link, nor a slug that doesn't match
 *   the post's actual (sometimes length-capped/truncated) slug — those hard-404. Facebook / campaign
 *   links are frequently built from the post TITLE, so a title-derived slug (old title OR the current
 *   rewritten title) rarely equals the stored slug, and the link dies. On ANY 404 this finds the intended
 *   post by, in order: (1) EXACT match of the last URL segment against every post's actual slug AND its
 *   title-slug, then (2) strong, unambiguous token-overlap against those slugs — and 301s to the canonical
 *   permalink. Recovers every title-based or bare-slug share, past or future. Read-only, cached, and only
 *   runs on a 404, so it adds nothing to normal page loads.
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

    $maps = kepoli_link_recovery_maps();

    // 1) Exact match — the last segment equals a real slug OR a post's title-slug.
    if (isset($maps['exact'][$attempt])) {
        kepoli_link_recovery_go((int) $maps['exact'][$attempt]);
        return;
    }

    // 2) Fuzzy — best token-overlap of the attempted slug against each post's actual slug and title-slug.
    //    Only redirect on a strong, clearly unambiguous match, so a genuine 404 is never mis-sent.
    $aTok = array_values(array_filter(explode('-', $attempt), static fn($t) => strlen($t) > 2));
    if (count($aTok) < 3) {
        return;
    }
    $aSet = array_flip($aTok);

    $bestId = 0;
    $best   = 0.0;
    $second = 0.0;
    foreach ($maps['candidates'] as $id => $slugs) {
        $top = 0.0;
        foreach ($slugs as $name) {
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
            if ($score > $top) {
                $top = $score;
            }
        }
        if ($top > $best) {
            $second = $best;
            $best   = $top;
            $bestId = (int) $id;
        } elseif ($top > $second) {
            $second = $top;
        }
    }

    if ($bestId > 0 && $best >= 0.6 && $best >= $second + 0.15) {
        kepoli_link_recovery_go($bestId);
    }
}

/**
 * Cached lookup maps: exact slug/title-slug → id, and id → [slug, title-slug] for fuzzy scoring.
 */
function kepoli_link_recovery_maps(): array
{
    $maps = get_transient('kepoli_link_recovery_maps');
    if (is_array($maps)) {
        return $maps;
    }
    global $wpdb;
    $rows = $wpdb->get_results(
        "SELECT ID, post_name, post_title FROM {$wpdb->posts}
          WHERE post_status='publish' AND post_type IN ('post','page') AND post_name<>''"
    );
    $exact = [];
    $cand  = [];
    foreach ((array) $rows as $r) {
        $id    = (int) $r->ID;
        $slug  = (string) $r->post_name;
        $tslug = sanitize_title((string) $r->post_title);
        $slugs = [];
        if ($slug !== '') {
            $slugs[] = $slug;
            if (!isset($exact[$slug])) {
                $exact[$slug] = $id;
            }
        }
        if ($tslug !== '' && $tslug !== $slug) {
            $slugs[] = $tslug;
            if (!isset($exact[$tslug])) {
                $exact[$tslug] = $id; // first (oldest) post wins a shared title-slug
            }
        }
        $cand[$id] = $slugs;
    }
    // Old slugs kept as aliases when a post was renamed (kepoli-slug-cleanup) — exact-map them so a link to
    // the pre-rename slug still 301s to the post (token-overlap alone can't bridge an honest rename).
    $arows = $wpdb->get_results(
        "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_kepoli_slug_aliases' AND meta_value <> ''"
    );
    foreach ((array) $arows as $ar) {
        $id = (int) $ar->post_id;
        foreach (explode(',', (string) $ar->meta_value) as $al) {
            $al = sanitize_title(trim($al));
            if ($al !== '' && !isset($exact[$al])) {
                $exact[$al] = $id;
            }
        }
    }
    $maps = ['exact' => $exact, 'candidates' => $cand];
    set_transient('kepoli_link_recovery_maps', $maps, 10 * MINUTE_IN_SECONDS);
    return $maps;
}

function kepoli_link_recovery_go(int $id): void
{
    $url = get_permalink($id);
    if ($url) {
        wp_safe_redirect($url, 301);
        exit;
    }
}

/* Refresh the cached maps when posts change (cheap; rebuilds lazily on the next 404). */
add_action('save_post', static function (): void {
    delete_transient('kepoli_link_recovery_maps');
});
