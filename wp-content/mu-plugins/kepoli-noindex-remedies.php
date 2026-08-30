<?php
/**
 * Plugin Name: Kepoli Noindex Remedies (AdSense-review YMYL shield)
 * Description: Keeps the folk-remedy (YMYL) content out of the crawlable/indexed footprint while
 *   kepoli is under AdSense review, so the site reads as a food + kitchen-wellness blog. Four
 *   coordinated actions, all gated on ONE env toggle KEPOLI_NOINDEX_REMEDIES (default ON; set to 0
 *   after approval to fully reverse):
 *     1. sets Automation Hamri's _wpap_noindex marker on every post in the remedy categories
 *        (its 9.27.0 wp_robots filter then adds `noindex` to those pages' robots meta),
 *     2. noindexes the remedy CATEGORY ARCHIVE pages (the plugin's own filter covers singular only),
 *     3. drops remedy posts from the core XML sitemap (so crawlers don't discover them there),
 *     4. hides the remedy categories from the theme footer's "Explore" list.
 *   Fully reversible: KEPOLI_NOINDEX_REMEDIES=0 re-indexes the posts + archives, restores the
 *   sitemap entries, and restores the footer links on the next admin/cron tick.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/** The remedy (YMYL folk-cure) category slugs — the only content this shield touches. */
function kepoli_remedy_category_slugs(): array
{
    return ['colds-respiratory', 'skin-wounds-teeth', 'aches-pains-fever'];
}

/** Remedy category term IDs (memoized per request). */
function kepoli_remedy_category_ids(): array
{
    static $ids = null;
    if (null !== $ids) {
        return $ids;
    }
    $ids = [];
    foreach (kepoli_remedy_category_slugs() as $slug) {
        $term = get_term_by('slug', $slug, 'category');
        if ($term instanceof WP_Term) {
            $ids[] = (int) $term->term_id;
        }
    }
    return $ids;
}

/** Is the YMYL shield ON? Default ON; disabled only when KEPOLI_NOINDEX_REMEDIES is explicitly 0/false/no/off. */
function kepoli_noindex_remedies_on(): bool
{
    $raw = strtolower(trim((string) getenv('KEPOLI_NOINDEX_REMEDIES')));
    return !in_array($raw, ['0', 'false', 'no', 'off'], true);
}

/* (4) Hide remedy categories from the theme footer's Explore list (cheap front-end filter). */
add_filter('vr_hide_footer_categories', static function (array $slugs): array {
    return kepoli_noindex_remedies_on() ? array_merge($slugs, kepoli_remedy_category_slugs()) : $slugs;
});

/* (2) Noindex the remedy CATEGORY ARCHIVE pages via WP core's wp_robots API. */
add_filter('wp_robots', static function ($robots) {
    if (!is_array($robots) || is_admin()) {
        return $robots;
    }
    if (kepoli_noindex_remedies_on() && is_category(kepoli_remedy_category_slugs())) {
        unset($robots['index']);
        $robots['noindex'] = true;
    }
    return $robots;
}, 25);

/* (3) Drop remedy posts from the core XML sitemap so crawlers don't discover them there. */
add_filter('wp_sitemaps_posts_query_args', static function ($args, $post_type) {
    if ('post' !== $post_type || !kepoli_noindex_remedies_on()) {
        return $args;
    }
    $ids = kepoli_remedy_category_ids();
    if ($ids) {
        $tax = isset($args['tax_query']) && is_array($args['tax_query']) ? $args['tax_query'] : [];
        $tax[] = [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $ids,
            'operator' => 'NOT IN',
        ];
        $args['tax_query'] = $tax;
    }
    return $args;
}, 10, 2);

/* (1) Set/clear the _wpap_noindex marker on remedy-category POSTS. Runs once per flag STATE (the
   marker stores the applied state, so flipping the env re-runs it), off the front-end hot path,
   per-item isolated so one bad post can't wedge the pass. */
add_action('init', 'kepoli_noindex_remedies_sync', 25);
function kepoli_noindex_remedies_sync(): void
{
    if (!is_admin() && !wp_doing_cron()) {
        return; // keep one-time maintenance off the front-end hot path (runs on admin load / cron tick)
    }
    if (!class_exists('WP_Query')) {
        return;
    }
    $want   = kepoli_noindex_remedies_on() ? '1' : '0';
    $marker = 'kepoli_noindex_remedies_state';
    if ((string) get_option($marker, '') === $want) {
        return; // already applied for this state
    }
    $cat_ids = kepoli_remedy_category_ids();
    if (empty($cat_ids)) {
        return; // remedy categories not created yet — retry next tick (don't set the marker)
    }

    try {
        $q = new WP_Query([
            'post_type'      => 'post',
            'post_status'    => 'any',
            'category__in'   => $cat_ids,
            'posts_per_page' => 500,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        $n = 0;
        foreach ($q->posts as $pid) {
            try {
                if ('1' === $want) {
                    update_post_meta((int) $pid, '_wpap_noindex', 1);
                } else {
                    delete_post_meta((int) $pid, '_wpap_noindex');
                }
                $n++;
            } catch (\Throwable $e) {
                error_log('[kepoli] noindex-remedies: post ' . (int) $pid . ' failed: ' . $e->getMessage());
            }
        }
        update_option($marker, $want, false);
        error_log('[kepoli] noindex-remedies: applied state=' . $want . ' to ' . $n . ' remedy post(s).');
    } catch (\Throwable $e) {
        error_log('[kepoli] noindex-remedies sync failed: ' . $e->getMessage());
    }
}
