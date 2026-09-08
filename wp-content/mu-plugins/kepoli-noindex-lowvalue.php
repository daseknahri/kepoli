<?php
/**
 * Plugin Name: Kepoli Noindex Low-Value (AdSense-review cleanup)
 * Description: Removes a set of thin / clickbait-titled YMYL wellness-and-beauty posts from kepoli's INDEXED,
 *   crawlable footprint while the site is prepped for AdSense. These posts are honest and disclaimed (they debunk
 *   the very myths their titles tease — "does a morning drink really flush your body", etc.), so they are NOT
 *   deleted: they keep earning from Facebook traffic (noindex ≠ no ads). But their thin length is what Google
 *   reported as "Soft 404", and their curiosity/YMYL titles read as low-value to an AdSense reviewer — so they are
 *   pulled OUT of the index + XML sitemap so the reviewed footprint is the ~90 solid recipe/food posts. Actions
 *   (all gated on env KEPOLI_NOINDEX_LOWVALUE, default ON; set to 0 after approval to fully reverse):
 *     1. sets Automation Hamri's _wpap_noindex marker on each listed post (its wp_robots filter then adds
 *        `noindex` to that post's robots meta),
 *     2. drops those posts from the core XML sitemap so crawlers don't rediscover them there.
 *   Fully reversible: KEPOLI_NOINDEX_LOWVALUE=0 clears the markers + restores the sitemap entries on the next
 *   admin/cron tick. The list is exact slugs — edit KEPOLI_LOWVALUE_SLUGS to add/remove.
 *
 * @package Kepoli
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** The exact post slugs to pull from the index (thin + clickbait YMYL; honest bodies, kept live for FB). */
function kepoli_lowvalue_slugs(): array {
    return array(
        // Tier 1 — thin AND clickbait-YMYL
        'garlic-and-honey-at-night-what-it-really-does',
        'honey-face-masks-what-they-really-do-for-skin',
        'mix-these-2-things-before-bed-for-baby-soft-skin',
        'does-a-morning-drink-really-flush-your-body',
        'can-a-nightly-drink-flatten-your-belly-an-honest-look',
        'eating-a-banana-every-day-after-60-what-to-know',
        'why-i-dont-drink-salt-water-before-bed',
        // Tier 3 — clickbait-YMYL framing (substantial + honest, but title/topic risk)
        'what-eating-an-avocado-every-day-really-does',
        'what-magnesium-really-does-for-circulation',
        'a-simple-overnight-routine-for-softer-looking-skin',
        'my-warm-cinnamon-milk-routine-before-bed',
        'what-drinking-coconut-water-every-day-really-does',
        'a-warm-cayenne-drink-before-bed-for-circulation',
    );
}

/** Post IDs for the low-value slugs (memoized per request). */
function kepoli_lowvalue_ids(): array {
    static $ids = null;
    if ( null !== $ids ) {
        return $ids;
    }
    $ids = array();
    foreach ( kepoli_lowvalue_slugs() as $slug ) {
        $p = get_page_by_path( $slug, OBJECT, 'post' );
        if ( $p instanceof WP_Post ) {
            $ids[] = (int) $p->ID;
        }
    }
    return $ids;
}

/** ON by default; disabled only when KEPOLI_NOINDEX_LOWVALUE is explicitly 0/false/no/off. */
function kepoli_noindex_lowvalue_on(): bool {
    $raw = strtolower( trim( (string) getenv( 'KEPOLI_NOINDEX_LOWVALUE' ) ) );
    return ! in_array( $raw, array( '0', 'false', 'no', 'off' ), true );
}

/* (2) Drop the low-value posts from the core XML sitemap so crawlers don't rediscover them there. */
add_filter( 'wp_sitemaps_posts_query_args', static function ( $args, $post_type ) {
    if ( 'post' !== $post_type || ! kepoli_noindex_lowvalue_on() ) {
        return $args;
    }
    $ids = kepoli_lowvalue_ids();
    if ( $ids ) {
        $exclude          = isset( $args['post__not_in'] ) && is_array( $args['post__not_in'] ) ? $args['post__not_in'] : array();
        $args['post__not_in'] = array_values( array_unique( array_map( 'intval', array_merge( $exclude, $ids ) ) ) );
    }
    return $args;
}, 10, 2 );

/* (1) Set/clear the _wpap_noindex marker on the listed posts. Runs once per flag STATE (the marker option stores
   the applied state, so flipping the env re-runs it), off the front-end hot path, per-item isolated. */
add_action( 'init', 'kepoli_noindex_lowvalue_sync', 26 );
function kepoli_noindex_lowvalue_sync(): void {
    if ( ! is_admin() && ! wp_doing_cron() ) {
        return; // keep one-time maintenance off the front-end hot path
    }
    $want   = kepoli_noindex_lowvalue_on() ? '1' : '0';
    $marker = 'kepoli_noindex_lowvalue_state_v1';
    if ( (string) get_option( $marker, '' ) === $want ) {
        return; // already applied for this state
    }
    $ids = kepoli_lowvalue_ids();
    if ( empty( $ids ) ) {
        return; // posts not found yet (not published?) — retry next tick, don't set the marker
    }
    $n = 0;
    foreach ( $ids as $pid ) {
        try {
            if ( '1' === $want ) {
                update_post_meta( $pid, '_wpap_noindex', 1 );
            } else {
                delete_post_meta( $pid, '_wpap_noindex' );
            }
            $n++;
        } catch ( \Throwable $e ) {
            error_log( '[kepoli] noindex-lowvalue: post ' . (int) $pid . ' failed: ' . $e->getMessage() );
        }
    }
    update_option( $marker, $want, false );
    error_log( '[kepoli] noindex-lowvalue: applied state=' . $want . ' to ' . $n . ' post(s).' );
}
