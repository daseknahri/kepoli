<?php
/**
 * Plugin Name: Kepoli Content Cleanup (AdSense)
 * Description: One-time cleanup for AdSense readiness + persistent render-time hygiene.
 *   ONCE (guarded): trashes flagged posts (policy violations, duplicates, thin clickbait —
 *   recoverable from Trash) and applies English translations of the French recipes (old
 *   slug auto-redirects via _wp_old_slug; prior content saved to _kepoli_pre_en).
 *   PERSISTENT (render-time, non-destructive, reversible by removing this file): strips
 *   leftover engagement-bait ("…open button (>)… SHARE with your Facebook friends"),
 *   strips leaked AI scaffolding ("Sure! Here's…", "Option 2/3"), and appends a health/
 *   info disclaimer to single posts.
 *
 * @package Kepoli
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const KEPOLI_CLEANUP_FLAG    = 'kepoli_cleanup_done';
const KEPOLI_CLEANUP_VERSION = '1';

function kepoli_cleanup_read_json( $names ) {
	foreach ( (array) $names as $path ) {
		if ( is_readable( $path ) ) {
			$d = json_decode( (string) file_get_contents( $path ), true );
			if ( is_array( $d ) ) { return $d; }
		}
	}
	return array();
}

/* ─────────────────────────────────────────────
   One-time data pass (guarded).
───────────────────────────────────────────── */
add_action( 'init', 'kepoli_cleanup_run', 100 );
function kepoli_cleanup_run() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) { return; }
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) { return; }
	if ( (string) get_option( KEPOLI_CLEANUP_FLAG ) === KEPOLI_CLEANUP_VERSION ) { return; }
	if ( get_transient( 'kepoli_cleanup_lock' ) ) { return; }
	set_transient( 'kepoli_cleanup_lock', '1', 5 * MINUTE_IN_SECONDS );

	/* 1) Trash flagged posts (recoverable). */
	$remove = kepoli_cleanup_read_json( array( '/content/cleanup-remove.json', ABSPATH . '../content/cleanup-remove.json' ) );
	foreach ( $remove as $row ) {
		$id = (int) ( $row['id'] ?? 0 );
		if ( ! $id ) { continue; }
		$p = get_post( $id );
		if ( $p && 'trash' !== $p->post_status ) { wp_trash_post( $id ); }
	}

	/* 2) Apply English translations of the French recipes. */
	$fr = kepoli_cleanup_read_json( array( '/content/french-translations.json', ABSPATH . '../content/french-translations.json' ) );
	foreach ( $fr as $t ) {
		$id = (int) ( $t['id'] ?? 0 );
		if ( ! $id ) { continue; }
		$p = get_post( $id );
		if ( ! $p || 'post' !== $p->post_type ) { continue; }
		if ( '' === (string) get_post_meta( $id, '_kepoli_pre_en', true ) ) {
			update_post_meta( $id, '_kepoli_pre_en', wp_json_encode( array( 'title' => $p->post_title, 'name' => $p->post_name, 'content' => $p->post_content ) ) );
		}
		$new_slug = sanitize_title( (string) ( $t['new_slug'] ?? '' ) );
		$arr = array( 'ID' => $id );
		if ( '' !== trim( (string) ( $t['title'] ?? '' ) ) )   { $arr['post_title'] = (string) $t['title']; }
		if ( '' !== trim( (string) ( $t['content'] ?? '' ) ) ) { $arr['post_content'] = (string) $t['content']; }
		if ( $new_slug && $new_slug !== $p->post_name ) {
			$arr['post_name'] = $new_slug;
			add_post_meta( $id, '_wp_old_slug', $p->post_name ); // WP auto-301s the old slug
		}
		wp_update_post( wp_slash( $arr ) );
	}

	update_option( KEPOLI_CLEANUP_FLAG, KEPOLI_CLEANUP_VERSION, true );
	delete_transient( 'kepoli_cleanup_lock' );
}

/* ─────────────────────────────────────────────
   Persistent render-time hygiene (non-destructive).
───────────────────────────────────────────── */
add_filter( 'the_content', 'kepoli_cleanup_filter_content', 20 );
function kepoli_cleanup_filter_content( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$content = kepoli_cleanup_strip( $content );
	$content .= kepoli_cleanup_disclaimer();
	return $content;
}

function kepoli_cleanup_strip( $html ) {
	// Delimiter ~; `u` flag so a smart-quote apostrophe (’) counts as one char.
	// The <p> patterns are "tempered" — (?:(?!</p>).)*? — so they match a whole
	// paragraph that contains the phrase even when it has nested <strong>/<a>,
	// without ever crossing a </p> boundary.
	$patterns = array(
		// engagement-bait paragraph (nested tags allowed)
		'~<p\b[^>]*>(?:(?!</p>).)*?(?:For\s+Complete\s+Cooking\s+STEPS|Open\s+button|SHARE\s+with\s+your\s+Facebook|check\s+the\s+first\s+comment|continue\s+(?:reading\s+)?on\s+the\s+next\s+page)(?:(?!</p>).)*?</p>~isu',
		// loose bait sentences not wrapped in a matchable <p>
		'~For\s+Complete\s+Cooking\s+STEPS[\s\S]{0,220}?(?:Facebook\s+friends|next\s+page)[.!\)]*~isu',
		'~(?:and\s+)?don.?t\s+forget\s+to\s+SHARE\s+with\s+your\s+Facebook\s+friends[.!]*~isu',
		// "Option 2/3" scaffolding paragraph (nested tags allowed)
		'~<p\b[^>]*>(?:(?!</p>).)*?Option\s*[23]\b(?:(?!</p>).)*?</p>~isu',
		// leaked AI scaffolding at the very start (tight: "Sure! Here's ... article/version ...:")
		'~\A\s*(?:<p\b[^>]*>\s*)?Sure[!,.]\s+Here.{0,3}s\s+[^<]{0,150}?(?:article|presentation|version|blog\s+post)[^<]{0,40}?[:.]\s*(?:</p>)?~iu',
	);
	$out = (string) $html;
	foreach ( $patterns as $re ) {
		$r = preg_replace( $re, '', $out );
		if ( null !== $r ) { $out = $r; } // skip a pattern that errors; never blank the content
	}
	return $out;
}

function kepoli_cleanup_disclaimer() {
	return '<p class="kepoli-disclaimer" style="margin-top:2em;padding-top:1em;border-top:1px solid #ece3d5;font-size:.85em;color:#6a6353;font-style:italic">'
		. 'The information on Kepoli is provided for general informational purposes only and is not professional medical, health, nutritional, or safety advice. '
		. 'Always use your own judgment and consult a qualified professional before acting on it.'
		. '</p>';
}
