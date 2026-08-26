<?php
/**
 * Scheduling, public permalinks, content splitting
 *
 * Extracted verbatim from wp-automator-pro.php (single-file → modular).
 * Load order is fixed by the main file; every hook self-registers here.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function wpap_last_scheduled_ts_gmt() {
    global $wpdb;
    $max = $wpdb->get_var( "SELECT MAX(post_date_gmt) FROM {$wpdb->posts} WHERE post_status = 'future' AND post_type = 'post'" );
    return $max ? (int) strtotime( $max . ' GMT' ) : 0;
}

function wpap_compute_schedule( $window_hours, $index = null, $total = null ) {
    $window_hours = (float) $window_hours;

    if ( $window_hours <= 0 ) {
        /* Publish-now does NOT touch the ordered-schedule anchor: an immediate post
           doesn't consume the FUTURE queue, so the next scheduled batch must still
           chain after the real latest scheduled post (wpap_last_scheduled_ts_gmt). */
        return array(
            'status'   => 'publish',
            'date'     => current_time( 'mysql' ),
            'date_gmt' => current_time( 'mysql', 1 ),
            'ts_gmt'   => null,
            'label'    => '',
        );
    }

    $min_offset = 5 * MINUTE_IN_SECONDS;                        /* never exactly "now" */
    $max_offset = (int) round( $window_hours * HOUR_IN_SECONDS );
    if ( $max_offset < $min_offset ) {
        $max_offset = $min_offset;
    }

    /* ORDERED mode: when a batch index + total are supplied, spread the posts
       EVENLY across the window in submission order (post 1 earliest, post 2 next,
       …) so they go live in the exact order they were added — not random times.
       Without index/total we keep the old random spread (single posts, Sheet). */
    if ( null !== $index && null !== $total && (int) $total > 0 ) {
        $index = max( 0, (int) $index );
        $total = max( 1, (int) $total );
        $step  = ( $total <= 1 ) ? 0 : ( ( $max_offset - $min_offset ) / ( $total - 1 ) );
        $ts_gmt = time() + $min_offset + (int) round( $index * $step );

        $gap  = ( $step >= 60 ) ? (int) round( $step ) : 60;   /* ≥ 1 min apart */
        /* Running high-water-mark, anchored to the REAL 'future' queue: never schedule
           at or before the last post already scheduled, so posts stay in submission
           order both WITHIN a batch (even with a tiny window) and ACROSS batches (batch
           2 chains after batch 1's still-pending tail). Seed ONCE per request from the
           actual latest scheduled post, then advance in memory per item. Using the live
           queue — not a persisted transient — makes it SELF-CORRECTING: when scheduled
           posts publish or are deleted the anchor drops automatically, so a later batch
           is never over-delayed by a stale far-future value. Each item is inserted as
           'future' before the next item's compute_schedule runs, so the in-memory
           advance and the queried queue agree. */
        static $wpap_sched_hw = null;
        if ( null === $wpap_sched_hw ) { $wpap_sched_hw = wpap_last_scheduled_ts_gmt(); }
        if ( $wpap_sched_hw > 0 && $ts_gmt <= $wpap_sched_hw ) {
            $ts_gmt = $wpap_sched_hw + $gap;
        }
        $wpap_sched_hw = max( (int) $wpap_sched_hw, $ts_gmt );
    } else {
        $ts_gmt = time() + wp_rand( $min_offset, $max_offset );   /* legacy random spread */
    }

    $date_gmt = gmdate( 'Y-m-d H:i:s', $ts_gmt );

    return array(
        'status'   => 'future',
        'date'     => get_date_from_gmt( $date_gmt ),           /* site-local time */
        'date_gmt' => $date_gmt,
        'ts_gmt'   => $ts_gmt,
        'label'    => get_date_from_gmt( $date_gmt, 'M j, Y g:i A' ),
    );
}

/**
 * The PUBLIC (pretty) permalink for a post — even while it is still `future`/`draft`/`pending`.
 *
 * WordPress's get_permalink() returns the UGLY `https://site/?p=<id>` form for any post that is NOT `publish`.
 * So a SCHEDULED post (schedule window > 0 → status `future`) hands back `?p=<id>` at creation time — and that URL
 * 301-REDIRECTS to the real pretty permalink. A redirected / query-string entry is poorly monetised (near-zero ad
 * RPM) and is not the clean link we want to share. This returns the permalink as if the post were already published
 * (the exact technique WordPress core uses in get_sample_permalink), so a SCHEDULED post and an IMMEDIATE post share
 * ONE clean, canonical URL. A no-op for already-published posts (returns their real permalink unchanged).
 */
function wpap_public_permalink( $post_id ) {
    $post = get_post( $post_id );
    if ( ! $post instanceof WP_Post ) {
        return get_permalink( $post_id );
    }
    if ( ! in_array( $post->post_status, array( 'future', 'draft', 'pending', 'auto-draft' ), true ) ) {
        return get_permalink( $post ); // already public → its real permalink
    }
    /* Temporarily present the post as published so get_permalink() builds the pretty form from its slug, then restore
       the in-memory object immediately so no other code observes the flip (mirrors WP core get_sample_permalink). */
    $saved_status = $post->post_status;
    $saved_name   = $post->post_name;
    /* (#7) try/finally so an exception inside get_permalink() (e.g. a rogue
       post_link filter) can't skip the restore and leave the cached WP_Post stuck
       at post_status='publish' for the rest of the request. */
    $url = '';
    try {
        $post->post_status = 'publish';
        if ( '' === (string) $post->post_name ) {
            $post->post_name = sanitize_title( $post->post_title ? $post->post_title : (string) $post->ID, $post->ID );
        }
        $url = get_permalink( $post, false );
    } finally {
        $post->post_status = $saved_status;
        $post->post_name   = $saved_name;
    }
    return $url ? $url : get_permalink( $post_id );
}

/**
 * Wire the auto-publish cron for a post inserted straight into $wpdb->posts.
 *
 * wp_insert_post() schedules the "publish_future_post" event automatically, but
 * the Bulk Generator writes directly to $wpdb->posts (to keep <!--nextpage-->
 * out of kses), which skips that wiring. Call this after such an insert or the
 * "future" post would sit there unpublished forever.
 *
 * @param int $post_id
 * @param int $ts_gmt  UTC timestamp the post should go live.
 */
function wpap_schedule_future_publish( $post_id, $ts_gmt ) {
    $post_id = (int) $post_id;
    $ts_gmt  = (int) $ts_gmt;
    if ( $post_id <= 0 || $ts_gmt <= 0 ) {
        return;
    }
    wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );
    wp_schedule_single_event( $ts_gmt, 'publish_future_post', array( $post_id ) );
}

/**
 * Split an HTML article body into N paginated parts joined with <!--nextpage-->.
 *
 * WordPress paginates a post wherever it finds a <!--nextpage--> comment, so
 * "click Next → jump to the next page" works out of the box once the markers
 * are in place. Splitting happens on block-level boundaries only (closing </p>
 * and heading tags), so tags are never cut in half, and parts are balanced by
 * length so the pages feel evenly sized.
 *
 * Safe fallbacks: a body that already contains <!--nextpage--> is returned
 * untouched; a body with too few blocks to reach $parts is split into as many
 * pages as it safely can (down to returning it unchanged).
 *
 * @param string $html
 * @param int    $parts Desired number of pages (1 = don't split).
 * @return string
 */
function wpap_split_content_into_parts( $html, $parts ) {
    $parts = (int) $parts;
    $html  = (string) $html;

    if ( $parts < 2 || '' === trim( $html ) ) {
        return $html;
    }
    /* Respect page breaks the author already placed. */
    if ( false !== strpos( $html, '<!--nextpage-->' ) ) {
        return $html;
    }

    /* Break the body into block-level chunks, keeping each closing tag intact.
       Only paragraphs and headings are split points — lists, figures, tables and
       other containers stay whole so nested tags never straddle a page break. */
    $tokens = preg_split(
        '#(</(?:p|h[1-6])>)#i',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );

    $blocks = array();
    for ( $i = 0, $n = count( $tokens ); $i < $n; $i += 2 ) {
        $chunk = $tokens[ $i ] . ( isset( $tokens[ $i + 1 ] ) ? $tokens[ $i + 1 ] : '' );
        if ( '' !== trim( $chunk ) ) {
            $blocks[] = $chunk;
        }
    }

    /* Fallback: no block tags found — split on blank lines, keeping the blank
       line attached to each chunk so paragraph spacing is never lost when two
       chunks end up on the same page. */
    if ( count( $blocks ) < 2 ) {
        $blocks = array();
        $bits   = preg_split( '/(\n\s*\n)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
        for ( $j = 0, $m = count( $bits ); $j < $m; $j += 2 ) {
            $chunk = $bits[ $j ] . ( isset( $bits[ $j + 1 ] ) ? $bits[ $j + 1 ] : '' );
            if ( '' !== trim( $chunk ) ) {
                $blocks[] = $chunk;
            }
        }
    }

    $total_blocks = count( $blocks );
    if ( $total_blocks < 2 ) {
        return $html;                       /* nothing safe to split on */
    }
    if ( $parts > $total_blocks ) {
        $parts = $total_blocks;             /* can't have more pages than blocks */
    }

    /* Balance blocks across the pages by character length (greedy fill). */
    $total_len = 0;
    foreach ( $blocks as $b ) {
        $total_len += strlen( $b );
    }
    $target = $total_len / $parts;

    $pages       = array();
    $current     = '';
    $current_len = 0;
    $blocks_left = $total_blocks;

    foreach ( $blocks as $b ) {
        $current     .= $b;
        $current_len += strlen( $b );
        $blocks_left--;

        $pages_done      = count( $pages );
        $parts_remaining = $parts - $pages_done - 1;          /* pages still to open after this one */
        $reached_target  = ( $current_len >= $target );
        $must_close_now  = ( $blocks_left <= $parts_remaining ); /* leave >=1 block per remaining page */

        if ( $pages_done < $parts - 1 && ( $must_close_now || ( $reached_target && $blocks_left > $parts_remaining ) ) ) {
            $pages[]     = $current;
            $current     = '';
            $current_len = 0;
        }
    }
    if ( '' !== $current ) {
        $pages[] = $current;
    }

    if ( count( $pages ) < 2 ) {
        return $html;
    }

    return implode( "\n\n<!--nextpage-->\n\n", $pages );
}

/* ════════════════════════════════════════════
   6. AJAX: UPLOAD FALLBACK IMAGE
   Accepts any image type (jpg/png/gif/webp/avif)
════════════════════════════════════════════ */
add_action( 'wp_ajax_wpap_upload_fallback', 'wpap_ajax_upload_fallback' );
