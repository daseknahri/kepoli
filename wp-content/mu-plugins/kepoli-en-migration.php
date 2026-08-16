<?php
/**
 * Plugin Name: Kepoli English Migration
 * Description: One-time, idempotent Romanian->English switch for the live site: English
 *   tagline + admin language, category names/slugs, page content/slugs, and primary nav —
 *   with 301 redirects from every old URL so nothing indexed breaks. All posts are kept
 *   and stay in their categories. The data pass runs once (guarded by an option); the
 *   redirect map is served on every request. Reversible: prior page content is saved to
 *   post meta (_kepoli_pre_en) and the old slugs live in the redirect option.
 *
 * @package Kepoli
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const KEPOLI_EN_FLAG      = 'kepoli_en_migrated';   // stores the version once done
const KEPOLI_EN_VERSION   = '2';
const KEPOLI_EN_CAT_REDIR = 'kepoli_en_cat_redir';  // old cat slug => new cat slug
const KEPOLI_EN_PG_REDIR  = 'kepoli_en_pg_redir';   // old page slug => new page slug

/* Category slug (old Romanian) => [new English name, new English slug, English description]. */
function kepoli_en_categories() {
	return array(
		'ciorbe-si-supe'         => array( 'Soups & Stews', 'soups', 'Comforting soups and stews — clear broths, hearty pots, and the warming bowls that anchor a home-cooked meal.' ),
		'feluri-principale'      => array( 'Main Dishes', 'main-dishes', 'Satisfying main courses for everyday dinners — the centerpiece dishes that bring everyone to the table.' ),
		'patiserie-si-deserturi' => array( 'Pastry & Desserts', 'desserts', 'Cakes, pastries, and sweet bakes — home-friendly desserts worth turning the oven on for.' ),
	);
}

/* Old Romanian page slug => new English page slug (content sourced from /content/pages.json
   by the new slug, or from kepoli_en_extra_pages() for listing pages). */
function kepoli_en_pages() {
	return array(
		'despre-kepoli'                 => 'about-kepoli',
		'despre-autor'                  => 'about-the-author',
		'contact'                       => 'contact',
		'politica-de-confidentialitate' => 'privacy-policy',
		'politica-de-cookies'           => 'cookie-policy',
		'termeni-si-conditii'           => 'terms-and-conditions',
		'disclaimer-culinar'            => 'disclaimer',
		'publicitate-si-consimtamant'   => 'advertising-and-consent',
		'politica-editoriala'           => 'editorial-policy',
		'retete'                        => 'recipes',
		'articole'                      => 'articles',
	);
}

/* English content for listing pages that are not in content/pages.json. */
function kepoli_en_extra_pages() {
	return array(
		'recipes'  => array(
			'title'   => 'Recipes',
			'content' => '<p>Every recipe on Kepoli — clear, tested dishes for everyday home cooking. Browse by course: <a href="/category/soups/">Soups &amp; Stews</a>, <a href="/category/main-dishes/">Main Dishes</a>, and <a href="/category/desserts/">Pastry &amp; Desserts</a>.</p>',
		),
		'articles' => array(
			'title'   => 'Articles',
			'content' => '<p>Food writing, kitchen guides, and practical notes from Kepoli — the stories and the know-how behind the recipes.</p>',
		),
	);
}

function kepoli_en_email( $key ) {
	$map = array( 'SITE_EMAIL' => 'contact@kepoli.com', 'WRITER_EMAIL' => 'isalunemerovik@gmail.com' );
	return $map[ $key ] ?? '';
}

/* Load the authored English page bodies from the deployed /content/pages.json. */
function kepoli_en_load_page_content() {
	$out = array();
	$candidates = array( '/content/pages.json', ABSPATH . '../content/pages.json' );
	foreach ( $candidates as $path ) {
		if ( is_readable( $path ) ) {
			$data = json_decode( (string) file_get_contents( $path ), true );
			if ( is_array( $data ) ) {
				foreach ( $data as $p ) {
					if ( isset( $p['slug'], $p['content'] ) ) {
						$body = str_replace(
							array( '{{SITE_EMAIL}}', '{{WRITER_EMAIL}}' ),
							array( kepoli_en_email( 'SITE_EMAIL' ), kepoli_en_email( 'WRITER_EMAIL' ) ),
							(string) $p['content']
						);
						$out[ $p['slug'] ] = array( 'title' => (string) ( $p['title'] ?? '' ), 'content' => $body );
					}
				}
			}
			break;
		}
	}
	return $out;
}

/* ─────────────────────────────────────────────
   One-time data migration (guarded to run once).
───────────────────────────────────────────── */
add_action( 'init', 'kepoli_en_run_migration', 99 );
function kepoli_en_run_migration() {
	if ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) { return; }
	if ( ! function_exists( 'is_blog_installed' ) || ! is_blog_installed() ) { return; }
	if ( (string) get_option( KEPOLI_EN_FLAG ) === KEPOLI_EN_VERSION ) { return; }
	if ( get_transient( 'kepoli_en_lock' ) ) { return; }
	set_transient( 'kepoli_en_lock', '1', 5 * MINUTE_IN_SECONDS );

	require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

	/* 1) Brand + language. */
	update_option( 'blogname', 'Kepoli' );
	update_option( 'blogdescription', 'Stories, recipes, and practical kitchen tips for home cooks.' );
	if ( in_array( (string) get_option( 'WPLANG' ), array( 'ro_RO', 'ro' ), true ) ) {
		update_option( 'WPLANG', '' );
	}

	/* 2) Categories — rename name + slug + English description, keep the term
	   (posts stay assigned). Idempotent: finds the term by old OR new slug, and
	   merges into the existing redirect map so re-runs never drop redirects. */
	$cat_redir = (array) get_option( KEPOLI_EN_CAT_REDIR, array() );
	foreach ( kepoli_en_categories() as $old_slug => $info ) {
		list( $new_name, $new_slug, $new_desc ) = $info;
		$term = get_term_by( 'slug', $old_slug, 'category' );
		if ( ! $term || is_wp_error( $term ) ) { $term = get_term_by( 'slug', $new_slug, 'category' ); }
		if ( ! $term || is_wp_error( $term ) ) { continue; }
		$slug_to = $new_slug;
		$holder  = get_term_by( 'slug', $new_slug, 'category' );
		if ( $holder && (int) $holder->term_id !== (int) $term->term_id ) {
			$slug_to = $term->slug; // another term already owns the English slug; keep current
		}
		wp_update_term( (int) $term->term_id, 'category', array( 'name' => $new_name, 'slug' => $slug_to, 'description' => $new_desc ) );
		if ( $slug_to !== $old_slug ) { $cat_redir[ $old_slug ] = $slug_to; }
	}
	update_option( KEPOLI_EN_CAT_REDIR, $cat_redir, false );

	/* 3) Pages — English content + English slug (301 old->new). */
	$authored = kepoli_en_load_page_content();
	$extra    = kepoli_en_extra_pages();
	$pg_redir = (array) get_option( KEPOLI_EN_PG_REDIR, array() );
	foreach ( kepoli_en_pages() as $old_slug => $new_slug ) {
		$page = get_page_by_path( $old_slug, OBJECT, 'page' );
		if ( ! $page ) { $page = get_page_by_path( $new_slug, OBJECT, 'page' ); }
		if ( ! $page ) { continue; }

		// Free the target slug if a DIFFERENT, non-published page holds it (e.g.
		// WordPress's default "Privacy Policy" draft), so our page gets the clean slug.
		if ( $new_slug !== $old_slug ) {
			$conflict = get_page_by_path( $new_slug, OBJECT, 'page' );
			if ( $conflict && (int) $conflict->ID !== (int) $page->ID && 'publish' !== $conflict->post_status ) {
				wp_delete_post( (int) $conflict->ID, true );
			}
		}

		$src = $authored[ $new_slug ] ?? $extra[ $new_slug ] ?? null;
		$arr = array( 'ID' => $page->ID, 'post_name' => $new_slug );
		if ( $src ) {
			// keep a rollback copy of the pre-migration content, once
			if ( '' === (string) get_post_meta( $page->ID, '_kepoli_pre_en', true ) ) {
				update_post_meta( $page->ID, '_kepoli_pre_en', wp_json_encode( array( 'title' => $page->post_title, 'name' => $page->post_name, 'content' => $page->post_content ) ) );
			}
			if ( '' !== trim( (string) $src['title'] ) )   { $arr['post_title'] = $src['title']; }
			if ( '' !== trim( (string) $src['content'] ) ) { $arr['post_content'] = $src['content']; }
		}
		wp_update_post( wp_slash( $arr ) );
		if ( $new_slug !== $old_slug ) { $pg_redir[ $old_slug ] = $new_slug; }
	}
	update_option( KEPOLI_EN_PG_REDIR, $pg_redir, false );

	/* Point WordPress + the theme at the real privacy page, and clear default junk. */
	$priv = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
	if ( $priv ) { update_option( 'wp_page_for_privacy_policy', (int) $priv->ID ); }
	$sample = get_page_by_path( 'sample-page', OBJECT, 'page' );
	if ( $sample ) { wp_delete_post( (int) $sample->ID, true ); }
	$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
	if ( $hello && 'post' === $hello->post_type ) { wp_delete_post( (int) $hello->ID, true ); }

	/* 3b) Static front page — replace the Romanian welcome with an English intro
	   that points at the live English categories. */
	if ( 'page' === get_option( 'show_on_front' ) ) {
		$front_id = (int) get_option( 'page_on_front' );
		$fp       = $front_id ? get_post( $front_id ) : null;
		if ( $fp && 'page' === $fp->post_type ) {
			if ( '' === (string) get_post_meta( $front_id, '_kepoli_pre_en', true ) ) {
				update_post_meta( $front_id, '_kepoli_pre_en', wp_json_encode( array( 'title' => $fp->post_title, 'name' => $fp->post_name, 'content' => $fp->post_content ) ) );
			}
			$home_html = '<p>Welcome to Kepoli — recipes and kitchen know-how for cooking at home with more confidence and less guesswork.</p>'
				. '<p>Browse by course — <a href="/category/soups/">Soups &amp; Stews</a>, <a href="/category/main-dishes/">Main Dishes</a>, and <a href="/category/desserts/">Pastry &amp; Desserts</a> — or scroll down for the latest recipes and kitchen tips.</p>';
			wp_update_post( wp_slash( array( 'ID' => $front_id, 'post_content' => $home_html ) ) );
		}
	}

	/* 4) Primary nav — rebuild in English (Home, the three categories, About). */
	kepoli_en_build_menu();

	flush_rewrite_rules( false );
	delete_transient( 'kepoli_en_lock' );
	update_option( KEPOLI_EN_FLAG, KEPOLI_EN_VERSION, true );
}

function kepoli_en_build_menu() {
	$name = 'Primary';
	$menu = wp_get_nav_menu_object( $name );
	$menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );
	if ( is_wp_error( $menu_id ) || ! $menu_id ) { return; }
	// clear existing items
	foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $it ) { wp_delete_post( (int) $it->ID, true ); }

	wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'Home', 'menu-item-url' => home_url( '/' ), 'menu-item-status' => 'publish' ) );
	foreach ( kepoli_en_categories() as $info ) {
		$term = get_term_by( 'name', $info[0], 'category' );
		if ( $term && ! is_wp_error( $term ) ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $info[0],
				'menu-item-object'    => 'category',
				'menu-item-object-id' => (int) $term->term_id,
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish',
			) );
		}
	}
	$about = get_page_by_path( 'about-kepoli', OBJECT, 'page' );
	if ( $about ) {
		wp_update_nav_menu_item( $menu_id, 0, array( 'menu-item-title' => 'About', 'menu-item-object' => 'page', 'menu-item-object-id' => (int) $about->ID, 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' ) );
	}
	$locations = (array) get_theme_mod( 'nav_menu_locations' );
	$locations['primary'] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/* ─────────────────────────────────────────────
   Persistent 301 redirects: old Romanian URLs -> new English URLs.
───────────────────────────────────────────── */
add_action( 'template_redirect', 'kepoli_en_redirects', 1 );
function kepoli_en_redirects() {
	if ( is_admin() ) { return; }
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/';
	$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
	if ( '' === $path ) { return; }
	$segs = explode( '/', $path );
	$cat  = (array) get_option( KEPOLI_EN_CAT_REDIR, array() );
	$pg   = (array) get_option( KEPOLI_EN_PG_REDIR, array() );

	// /category/{old}/... -> /category/{new}/...
	if ( 'category' === $segs[0] && isset( $segs[1], $cat[ $segs[1] ] ) ) {
		$segs[1] = $cat[ $segs[1] ];
		kepoli_en_go( $segs );
	}
	// {old-category}/{post}/ -> {new-category}/{post}/  (post permalinks under a renamed category)
	if ( isset( $cat[ $segs[0] ] ) ) {
		$segs[0] = $cat[ $segs[0] ];
		kepoli_en_go( $segs );
	}
	// single page slug: {old} -> {new}
	if ( 1 === count( $segs ) && isset( $pg[ $segs[0] ] ) ) {
		$segs[0] = $pg[ $segs[0] ];
		kepoli_en_go( $segs );
	}
}
function kepoli_en_go( array $segs ) {
	$target = home_url( '/' . implode( '/', array_map( 'rawurlencode', $segs ) ) . '/' );
	// keep the query string, if any
	if ( ! empty( $_SERVER['QUERY_STRING'] ) ) { $target .= '?' . $_SERVER['QUERY_STRING']; }
	wp_safe_redirect( $target, 301 );
	exit;
}
