<?php
/**
 * Viral Reader — theme functions.
 *
 * A warm editorial blog theme for viral story / health content. Standalone and
 * self-contained: no external services, no custom post meta, no mu-plugin
 * dependencies. Pairs with the WP Automator Pro plugin for ads (header/sidebar/
 * footer zones via wpap_zone_html(); in-content ads are the plugin's job).
 *
 * @package Viral_Reader
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined( 'VR_VERSION' ) ) {
	define( 'VR_VERSION', '1.9.1' );
}

/* ─────────────────────────────────────────────
   Setup
───────────────────────────────────────────── */
function vr_setup() {
	load_theme_textdomain( 'viral-reader', get_template_directory() . '/languages' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 48, 'width' => 200, 'flex-height' => true, 'flex-width' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	register_nav_menus( array(
		'primary' => __( 'Primary Menu', 'viral-reader' ),
		'footer'  => __( 'Footer Menu', 'viral-reader' ),
	) );
}
add_action( 'after_setup_theme', 'vr_setup' );

function vr_content_width() {
	$GLOBALS['content_width'] = 680;
}
add_action( 'after_setup_theme', 'vr_content_width', 0 );

/* ─────────────────────────────────────────────
   Assets (fast: system fonts, tiny deferred JS, no block CSS)
───────────────────────────────────────────── */
function vr_scripts() {
	/* Inline the whole stylesheet into <head> instead of loading it as a
	   render-blocking <link>. The CSS is ~8KB gzipped, so inlining removes an
	   entire blocking round-trip on the critical path — the single biggest
	   FCP/LCP win on mobile. We still register the 'viral-reader' handle (with
	   no src) so any wp_add_inline_style() from child themes/plugins keeps
	   working. Falls back to the normal <link> if the file can't be read. */
	$css_path = get_stylesheet_directory() . '/style.css';
	$css      = ( ! is_admin() && is_readable( $css_path ) ) ? file_get_contents( $css_path ) : false;

	if ( false !== $css && strlen( $css ) > 0 && strlen( $css ) < 100000 ) {
		wp_register_style( 'viral-reader', false, array(), VR_VERSION );
		wp_enqueue_style( 'viral-reader' );
		wp_add_inline_style( 'viral-reader', $css );
	} else {
		wp_enqueue_style( 'viral-reader', get_stylesheet_uri(), array(), VR_VERSION );
	}

	wp_enqueue_script( 'viral-reader', get_template_directory_uri() . '/assets/js/site.js', array(), VR_VERSION, true );
	wp_localize_script( 'viral-reader', 'vrL10n', array(
		'copied'     => __( 'Link copied', 'viral-reader' ),
		'copyPrompt' => __( 'Copy this link:', 'viral-reader' ),
	) );
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'vr_scripts' );

function vr_defer_js( $tag, $handle ) {
	if ( 'viral-reader' === $handle && false === strpos( $tag, ' defer' ) ) {
		$tag = str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}
add_filter( 'script_loader_tag', 'vr_defer_js', 10, 2 );

/* Drop block-editor front-end CSS for a lighter page. */
function vr_dequeue_block_css() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'vr_dequeue_block_css', 100 );

/* LCP hint: the post's own featured image on singular views loads eagerly, high
   priority. Guarded to the queried post's thumbnail so a sidebar/related image
   can't grab the hint when the post itself has no featured image. */
function vr_lcp_priority( $attr, $attachment, $size ) {
	if ( ! is_admin() && is_singular() && in_the_loop() && is_main_query() ) {
		static $done = false;
		if ( ! $done && $attachment && (int) get_post_thumbnail_id( get_the_ID() ) === (int) $attachment->ID ) {
			$attr['fetchpriority'] = 'high';
			$attr['loading']       = 'eager';
			$done                  = true;
		}
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'vr_lcp_priority', 10, 3 );

/* Preload the LCP image in <head>, before the ~KB of inlined CSS, so the preload
   scanner starts the fetch ~1 RTT sooner — the biggest remaining LCP lever on
   mobile precisely because the CSS is inlined. Handles the external-image
   fallback (_wpap_image_url) with a preconnect too. */
function vr_preload_lcp() {
	$pid = 0;
	if ( is_front_page() && ! is_paged() ) {
		$q   = new WP_Query( array( 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'ignore_sticky_posts' => 1, 'no_found_rows' => true, 'fields' => 'ids' ) );
		$pid = ! empty( $q->posts ) ? (int) $q->posts[0] : 0;
	} elseif ( is_singular( 'post' ) ) {
		$pid = get_queried_object_id();
	}
	if ( ! $pid ) { return; }

	$thumb_id = get_post_thumbnail_id( $pid );
	$sizes    = is_front_page() ? '100vw' : '(max-width:720px) 100vw, 680px';
	if ( $thumb_id ) {
		$src = wp_get_attachment_image_src( $thumb_id, 'large' );
		if ( $src ) {
			$srcset = wp_get_attachment_image_srcset( $thumb_id, 'large' );
			echo '<link rel="preload" as="image" href="' . esc_url( $src[0] ) . '"';
			if ( $srcset ) {
				echo ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '"';
			}
			echo ' fetchpriority="high">' . "\n";
		}
	} else {
		$ext = get_post_meta( $pid, '_wpap_image_url', true );
		if ( is_string( $ext ) && preg_match( '#^https?://#i', $ext ) ) {
			$host = wp_parse_url( $ext, PHP_URL_HOST );
			if ( $host ) {
				echo '<link rel="preconnect" href="' . esc_url( 'https://' . $host ) . '" crossorigin>' . "\n";
			}
			echo '<link rel="preload" as="image" href="' . esc_url( $ext ) . '" fetchpriority="high">' . "\n";
		}
	}
}
add_action( 'wp_head', 'vr_preload_lcp', 1 );

/* ─────────────────────────────────────────────
   Admin: featured-image column in the Posts list
───────────────────────────────────────────── */
function vr_posts_columns( $cols ) {
	$out = array();
	foreach ( $cols as $key => $label ) {
		if ( 'title' === $key ) {
			$out['vr_thumb'] = __( 'Image', 'viral-reader' );
		}
		$out[ $key ] = $label;
	}
	return $out;
}
add_filter( 'manage_post_posts_columns', 'vr_posts_columns' );

function vr_posts_column_content( $column, $post_id ) {
	if ( 'vr_thumb' !== $column ) { return; }
	if ( has_post_thumbnail( $post_id ) ) {
		echo get_the_post_thumbnail( $post_id, array( 60, 60 ), array( 'class' => 'vr-admin-thumb', 'loading' => 'lazy', 'alt' => '' ) );
	} else {
		echo '<span class="vr-admin-thumb vr-admin-thumb--empty" aria-hidden="true"></span>';
	}
}
add_action( 'manage_post_posts_custom_column', 'vr_posts_column_content', 10, 2 );

function vr_posts_column_style() {
	$screen = get_current_screen();
	if ( $screen && 'edit-post' === $screen->id ) {
		echo '<style>.column-vr_thumb{width:72px}.vr-admin-thumb{display:block;width:56px;height:56px;object-fit:cover;border-radius:4px}.vr-admin-thumb--empty{background:#f0e9dc;border:1px solid #e6d9c8}</style>';
	}
}
add_action( 'admin_head', 'vr_posts_column_style' );

/* ─────────────────────────────────────────────
   Widget areas (ad zones + sidebar)
───────────────────────────────────────────── */
function vr_widgets_init() {
	$d = array(
		'before_widget' => '<section id="%1$s" class="sidebar__block %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h2 class="sidebar__title">',
		'after_title'   => '</h2>',
	);
	register_sidebar( array_merge( $d, array( 'name' => __( 'Sidebar', 'viral-reader' ), 'id' => 'sidebar-1', 'description' => __( 'Shown beside single posts (sticky on desktop).', 'viral-reader' ) ) ) );
	register_sidebar( array_merge( $d, array( 'name' => __( 'Header Ad', 'viral-reader' ), 'id' => 'header-ad', 'description' => __( 'Full-width strip under the header. Fallback if the plugin Header zone is empty.', 'viral-reader' ) ) ) );
	/* No 'footer-1' area: the footer columns are theme-rendered (categories +
	   menu), so a widget area there would never render — kept out to avoid a dead
	   admin surface. */
}
add_action( 'widgets_init', 'vr_widgets_init' );

/* Full-width ad strip: plugin zone first (wpap_zone_html), else the widget area. */
function vr_ad_strip( $plugin_zone, $sidebar_id ) {
	$html = ( function_exists( 'wpap_zone_html' ) ) ? wpap_zone_html( $plugin_zone ) : '';
	$has  = is_active_sidebar( $sidebar_id );
	if ( '' === $html && ! $has ) { return; }
	echo '<div class="vr-ad-strip"><div class="vr-ad-strip__inner">';
	if ( '' !== $html ) {
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- owner's own ad markup from the plugin, by design
	} else {
		dynamic_sidebar( $sidebar_id );
	}
	echo '</div></div>';
}

/* ─────────────────────────────────────────────
   Helpers
───────────────────────────────────────────── */

/* Estimated reading time (~220 wpm), Unicode-aware. Uses the word count cached
   on save (_vr_words); only falls back to a live count on legacy posts, so list
   pages don't regex every card's full body on each cache-miss regeneration. */
function vr_reading_time( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$words   = (int) get_post_meta( $post_id, '_vr_words', true );
	if ( $words < 1 ) {
		$text  = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
		$words = preg_match_all( '/[\p{L}\p{N}]+/u', $text, $m );
		if ( false === $words || $words < 1 ) { $words = str_word_count( $text ); }
	}
	$mins = max( 1, (int) ceil( $words / 220 ) );
	/* translators: %d: minutes */
	return sprintf( _n( '%d min read', '%d min read', $mins, 'viral-reader' ), $mins );
}

/* Cache the word count on save so vr_reading_time() stays O(1) at render. */
function vr_cache_reading_time( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || 'post' !== $post->post_type ) { return; }
	$words = preg_match_all( '/[\p{L}\p{N}]+/u', wp_strip_all_tags( (string) $post->post_content ), $m );
	update_post_meta( $post_id, '_vr_words', (int) $words );
}
add_action( 'save_post', 'vr_cache_reading_time', 10, 2 );

/* First category as a linked chip (or ''). */
function vr_primary_category_chip( $post_id = null ) {
	$cats = get_the_category( $post_id );
	if ( empty( $cats ) ) { return ''; }
	$c    = $cats[0];
	$link = get_category_link( $c->term_id );
	return '<a class="content-chip content-chip--category" href="' . esc_url( is_wp_error( $link ) ? '#' : $link ) . '">' . esc_html( $c->name ) . '</a>';
}

/* Breadcrumbs: Home › Category › Title. The final crumb carries aria-current. */
function vr_breadcrumbs() {
	echo '<nav class="vr-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'viral-reader' ) . '">';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'viral-reader' ) . '</a>';
	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$link = get_category_link( $cats[0]->term_id );
			echo '<span class="sep">&rsaquo;</span><a href="' . esc_url( is_wp_error( $link ) ? '#' : $link ) . '">' . esc_html( $cats[0]->name ) . '</a>';
		}
		echo '<span class="sep">&rsaquo;</span><span aria-current="page">' . esc_html( wp_trim_words( get_the_title(), 8, '…' ) ) . '</span>';
	} elseif ( is_category() || is_tag() || is_archive() ) {
		echo '<span class="sep">&rsaquo;</span><span aria-current="page">' . esc_html( wp_strip_all_tags( get_the_archive_title() ) ) . '</span>';
	} elseif ( is_search() ) {
		echo '<span class="sep">&rsaquo;</span><span aria-current="page">' . esc_html__( 'Search', 'viral-reader' ) . '</span>';
	} elseif ( is_home() ) {
		echo '<span class="sep">&rsaquo;</span><span aria-current="page">' . esc_html( single_post_title( '', false ) ) . '</span>';
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $vr_anc ) {
			echo '<span class="sep">&rsaquo;</span><a href="' . esc_url( get_permalink( $vr_anc ) ) . '">' . esc_html( get_the_title( $vr_anc ) ) . '</a>';
		}
		echo '<span class="sep">&rsaquo;</span><span aria-current="page">' . esc_html( get_the_title() ) . '</span>';
	}
	echo '</nav>';
}

/* BreadcrumbList JSON-LD mirroring the visible trail (for breadcrumb rich results).
   Emitted only where a visible trail renders; skip if an SEO plugin already owns it. */
function vr_breadcrumb_jsonld() {
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || function_exists( 'seopress_init' ) ) {
		return;
	}
	/* WP Automator Pro's wpap_seo_head() emits its own BreadcrumbList on the posts
	   it manages (those carrying _wpap_smart_link). Defer there so the two don't
	   double up; still emit on archives/pages/unmanaged posts (and standalone). */
	if ( function_exists( 'wpap_seo_head' ) && is_singular( 'post' ) && get_post_meta( get_queried_object_id(), '_wpap_smart_link', true ) ) {
		return;
	}
	$crumbs = array( array( home_url( '/' ), get_bloginfo( 'name' ) ) );
	if ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( ! empty( $cats ) ) {
			$link = get_category_link( $cats[0]->term_id );
			if ( ! is_wp_error( $link ) ) { $crumbs[] = array( $link, $cats[0]->name ); }
		}
		$crumbs[] = array( get_permalink(), get_the_title() );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $vr_anc ) {
			$crumbs[] = array( get_permalink( $vr_anc ), get_the_title( $vr_anc ) );
		}
		$crumbs[] = array( get_permalink(), get_the_title() );
	} elseif ( is_category() || is_tag() || is_archive() ) {
		$crumbs[] = array( '', wp_strip_all_tags( get_the_archive_title() ) );
	} else {
		return;
	}
	$elements = array();
	foreach ( $crumbs as $i => $c ) {
		$item = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( (string) $c[1] ) );
		if ( ! empty( $c[0] ) ) { $item['item'] = $c[0]; }
		$elements[] = $item;
	}
	$data = array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $elements );
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vr_breadcrumb_jsonld', 21 );

/* Related posts (same category, newest), excluding current. */
function vr_related_posts( $post_id, $limit = 4 ) {
	$cats = wp_get_post_categories( $post_id );
	$args = array(
		'post__not_in'        => array( $post_id ),
		'posts_per_page'      => $limit,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => 1,
		'no_found_rows'       => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);
	if ( ! empty( $cats ) ) { $args['category__in'] = $cats; }
	$q = new WP_Query( $args );
	$posts = $q->posts;
	wp_reset_postdata();
	return $posts;
}

/* Share links for the current post. */
function vr_share_links() {
	$url   = get_permalink();
	$title = get_the_title();
	return array(
		array( 'type' => 'facebook', 'label' => __( 'Share on Facebook', 'viral-reader' ), 'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
		array( 'type' => 'whatsapp', 'label' => __( 'Share on WhatsApp', 'viral-reader' ), 'url' => 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ) ),
		array( 'type' => 'email',    'label' => __( 'Share by email', 'viral-reader' ),    'url' => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ) ),
		array( 'type' => 'copy',     'label' => __( 'Copy link', 'viral-reader' ),         'url' => $url ),
		array( 'type' => 'print',    'label' => __( 'Print', 'viral-reader' ),             'url' => '' ),
	);
}

/* Inline SVG icons (static, safe to echo). */
function vr_icon( $name ) {
	$p = array(
		'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>',
		'facebook' => '<path d="M14 9h3V6h-3c-2 0-3 1-3 3v2H8v3h3v7h3v-7h3l1-3h-4V9c0-.6.4-1 1-1z"/>',
		'whatsapp' => '<path d="M12 3a9 9 0 0 0-7.7 13.6L3 21l4.5-1.3A9 9 0 1 0 12 3z"/><path d="M8.5 8.5c-.3 0-.7.1-1 .5s-1 1-1 2.3 1 2.7 1.2 2.9 2 3.1 4.9 4.3c2.4.9 2.9.8 3.4.7s1.6-.6 1.8-1.3.2-1.2.2-1.3-.3-.3-.6-.4z"/>',
		'email'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
		'link'     => '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>',
		'print'    => '<path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M8 17h8v4H8z"/>',
	);
	$d = isset( $p[ $name ] ) ? $p[ $name ] : '';
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
}

/* Fallback nav: Home + top categories. */
function vr_fallback_menu() {
	echo '<ul>';
	echo '<li class="menu-item"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'viral-reader' ) . '</a></li>';
	foreach ( get_categories( array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 6, 'hide_empty' => true ) ) as $cat ) {
		$link = get_category_link( $cat->term_id );
		if ( is_wp_error( $link ) ) { continue; }
		echo '<li class="menu-item"><a href="' . esc_url( $link ) . '">' . esc_html( $cat->name ) . '</a></li>';
	}
	echo '</ul>';
}

/* Card meta line (date · reading time). */
function vr_card_meta( $post_id = null ) {
	echo '<div class="post-card__meta">' . esc_html( get_the_date( '', $post_id ) ) . ' &middot; ' . esc_html( vr_reading_time( $post_id ) ) . '</div>';
}

/* Numbered pagination. */
function vr_pagination() {
	$links = paginate_links( array( 'type' => 'list', 'mid_size' => 1, 'prev_text' => '&lsaquo;', 'next_text' => '&rsaquo;' ) );
	if ( $links ) {
		echo '<nav class="vr-pagination" aria-label="' . esc_attr__( 'Posts navigation', 'viral-reader' ) . '">' . wp_kses_post( $links ) . '</nav>';
	}
}

/* Excerpt tweaks. */
add_filter( 'excerpt_more', function () { return '…'; } );
add_filter( 'excerpt_length', function () { return 30; } );

/* Body classes. */
function vr_body_classes( $classes ) {
	$classes[] = is_active_sidebar( 'sidebar-1' ) ? 'vr-has-sidebar' : 'vr-no-sidebar';
	return $classes;
}
add_filter( 'body_class', 'vr_body_classes' );

/* ─────────────────────────────────────────────
   Recipe schema + card
   Reads the recipe fields entered in WP Automator Pro's Author Tools box
   (_wpap_recipe_*). Renders a visible recipe card AND emits matching Recipe
   JSON-LD — Google requires structured-data content to be visible on the page,
   so the card and the schema are built from the same data. No-ops on any post
   that isn't flagged as a recipe or has no ingredients/steps.
───────────────────────────────────────────── */
function vr_recipe_data( $post_id ) {
	if ( '1' !== (string) get_post_meta( $post_id, '_wpap_recipe_on', true ) ) {
		return null;
	}
	/* Don't leak a password-protected post's ingredients/steps through the card or
	   the <head> JSON-LD — the body only shows the password form. Guarding here
	   covers both vr_recipe_card() and vr_recipe_jsonld() (both call this). */
	if ( post_password_required( $post_id ) ) {
		return null;
	}
	$split = function ( $raw ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $raw );
		return array_values( array_filter( array_map( 'trim', (array) $lines ), 'strlen' ) );
	};
	$ingredients = $split( get_post_meta( $post_id, '_wpap_recipe_ingredients', true ) );
	$steps       = $split( get_post_meta( $post_id, '_wpap_recipe_steps', true ) );
	if ( empty( $ingredients ) && empty( $steps ) ) {
		return null;
	}
	$prep  = (int) get_post_meta( $post_id, '_wpap_recipe_prep', true );
	$cook  = (int) get_post_meta( $post_id, '_wpap_recipe_cook', true );
	$total = (int) get_post_meta( $post_id, '_wpap_recipe_total', true );
	if ( $total <= 0 ) { $total = $prep + $cook; }
	return array(
		'servings'    => (string) get_post_meta( $post_id, '_wpap_recipe_servings', true ),
		'prep'        => $prep,
		'cook'        => $cook,
		'total'       => $total,
		'ingredients' => $ingredients,
		'steps'       => $steps,
	);
}

/* Whole minutes → ISO-8601 duration (e.g. 90 → PT1H30M). */
function vr_iso_minutes( $min ) {
	$min = (int) $min;
	if ( $min <= 0 ) { return ''; }
	$h = intdiv( $min, 60 );
	$m = $min % 60;
	return 'PT' . ( $h ? $h . 'H' : '' ) . ( $m ? $m . 'M' : ( $h ? '' : '0M' ) );
}

/* Human minutes → "1 hr 30 min". */
function vr_human_minutes( $min ) {
	$min = (int) $min;
	if ( $min <= 0 ) { return ''; }
	$h = intdiv( $min, 60 );
	$m = $min % 60;
	$out = array();
	if ( $h ) { $out[] = sprintf( _n( '%d hr', '%d hr', $h, 'viral-reader' ), $h ); }
	if ( $m ) { $out[] = sprintf( _n( '%d min', '%d min', $m, 'viral-reader' ), $m ); }
	return implode( ' ', $out );
}

function vr_recipe_jsonld() {
	if ( ! is_singular( 'post' ) ) { return; }
	$id = get_queried_object_id();
	$r  = vr_recipe_data( $id );
	if ( ! $r ) { return; }

	$data = array(
		'@context'      => 'https://schema.org/',
		'@type'         => 'Recipe',
		'name'          => wp_strip_all_tags( get_the_title( $id ) ),
		'author'        => vr_author_person_node( (int) get_post_field( 'post_author', $id ) ),
		'datePublished' => get_the_date( 'c', $id ),
	);
	$mod = get_the_modified_date( 'c', $id );
	if ( $mod ) { $data['dateModified'] = $mod; }
	$desc = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $id ) ), 40, '' );
	if ( '' !== trim( (string) $desc ) ) { $data['description'] = $desc; }
	$thumb_id = get_post_thumbnail_id( $id );
	$img      = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'large' ) : '';
	if ( $img ) {
		/* Carry the featured image's alt text into the schema as an ImageObject
		   caption (falls back to a bare URL when there's no alt). */
		$alt = trim( (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) );
		if ( '' !== $alt ) {
			$data['image'] = array(
				'@type'   => 'ImageObject',
				'url'     => $img,
				'caption' => $alt,
			);
		} else {
			$data['image'] = array( $img );
		}
	}
	if ( '' !== trim( $r['servings'] ) ) { $data['recipeYield'] = $r['servings']; }
	if ( $r['prep'] > 0 )  { $data['prepTime']  = vr_iso_minutes( $r['prep'] ); }
	if ( $r['cook'] > 0 )  { $data['cookTime']  = vr_iso_minutes( $r['cook'] ); }
	if ( $r['total'] > 0 ) { $data['totalTime'] = vr_iso_minutes( $r['total'] ); }
	if ( ! empty( $r['ingredients'] ) ) { $data['recipeIngredient'] = $r['ingredients']; }
	if ( ! empty( $r['steps'] ) ) {
		$data['recipeInstructions'] = array_map(
			function ( $s ) { return array( '@type' => 'HowToStep', 'text' => $s ); },
			$r['steps']
		);
	}
	/* JSON_HEX_TAG|JSON_HEX_AMP so a stray </script> in any field can't break out of
	   the JSON-LD block; dropped JSON_UNESCAPED_SLASHES (its /-escaping also helps). */
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vr_recipe_jsonld', 20 );

/* ─────────────────────────────────────────────
   Author identity / E-E-A-T: social profile links.
   Stored as standard user contact-method meta (editable on the user's profile
   screen); rendered as visible rel="me" links + emitted as Person `sameAs` in
   the Recipe schema and a ProfilePage on the author archive. A named author
   whose byline leads to verifiable off-site profiles is a core E-E-A-T signal.
───────────────────────────────────────────── */
function vr_author_social_labels() {
	return array(
		'vr_social_x'         => 'X',
		'vr_social_instagram' => 'Instagram',
		'vr_social_facebook'  => 'Facebook',
		'vr_social_youtube'   => 'YouTube',
		'vr_social_pinterest' => 'Pinterest',
		'vr_social_linkedin'  => 'LinkedIn',
		'vr_social_tiktok'    => 'TikTok',
		'vr_social_mastodon'  => 'Mastodon',
		'vr_social_website'   => 'Website',
	);
}

add_filter( 'user_contactmethods', function ( $methods ) {
	foreach ( vr_author_social_labels() as $key => $label ) {
		$methods[ $key ] = sprintf( '%s URL', $label );
	}
	return $methods;
} );

function vr_author_social_links( $user_id ) {
	$out = array();
	foreach ( vr_author_social_labels() as $key => $name ) {
		$url = trim( (string) get_the_author_meta( $key, (int) $user_id ) );
		if ( '' !== $url ) {
			$out[] = array( 'name' => $name, 'url' => $url );
		}
	}
	return $out;
}

function vr_author_social_html( $user_id ) {
	$links = vr_author_social_links( $user_id );
	if ( empty( $links ) ) { return ''; }
	$items = '';
	foreach ( $links as $l ) {
		$items .= '<li><a class="vr-author-social__link" href="' . esc_url( $l['url'] ) . '" rel="me noopener nofollow" target="_blank">' . esc_html( $l['name'] ) . '</a></li>';
	}
	return '<ul class="vr-author-social" aria-label="' . esc_attr__( 'Author profiles', 'viral-reader' ) . '">' . $items . '</ul>';
}

function vr_author_person_node( $user_id ) {
	$user_id = (int) $user_id;
	$node    = array(
		'@type' => 'Person',
		'name'  => get_the_author_meta( 'display_name', $user_id ),
		'url'   => get_author_posts_url( $user_id ),
	);
	$avatar = get_avatar_url( $user_id, array( 'size' => 512 ) );
	if ( $avatar ) { $node['image'] = $avatar; }
	$same = array();
	foreach ( vr_author_social_links( $user_id ) as $l ) { $same[] = $l['url']; }
	if ( ! empty( $same ) ) { $node['sameAs'] = $same; }
	return $node;
}

function vr_author_profile_jsonld() {
	if ( ! is_author() ) { return; }
	if ( defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || function_exists( 'seopress_init' ) || defined( 'AIOSEO_VERSION' ) ) { return; }
	$uid = (int) get_queried_object_id();
	if ( ! $uid ) { return; }
	$person = vr_author_person_node( $uid );
	$bio    = trim( (string) get_the_author_meta( 'description', $uid ) );
	if ( '' !== $bio ) { $person['description'] = $bio; }
	$avatar = get_avatar_url( $uid, array( 'size' => 256 ) );
	if ( $avatar ) { $person['image'] = $avatar; }
	$data = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'ProfilePage',
		'mainEntity' => $person,
	);
	echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vr_author_profile_jsonld', 22 );

/* ─────────────────────────────────────────────
   Author identity / E-E-A-T: custom author photo. Overrides the Gravatar so a
   real headshot flows into the byline, author box, author archive, and the
   Person `image` schema. Stored in user meta `vr_author_avatar` (a media
   attachment ID or an image URL); editable on the profile screen and populated
   by the seed from the site profile.
───────────────────────────────────────────── */
function vr_resolve_user_id( $id_or_email ) {
	if ( is_numeric( $id_or_email ) ) { return (int) $id_or_email; }
	if ( $id_or_email instanceof WP_User ) { return (int) $id_or_email->ID; }
	if ( $id_or_email instanceof WP_Post ) { return (int) $id_or_email->post_author; }
	if ( $id_or_email instanceof WP_Comment ) { return (int) ( $id_or_email->user_id ?? 0 ); }
	if ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
		$u = get_user_by( 'email', $id_or_email );
		return $u ? (int) $u->ID : 0;
	}
	return 0;
}

function vr_author_avatar_url( $user_id ) {
	$meta = get_user_meta( (int) $user_id, 'vr_author_avatar', true );
	if ( ! $meta ) { return ''; }
	if ( is_numeric( $meta ) ) {
		$url = wp_get_attachment_image_url( (int) $meta, 'medium' );
		if ( ! $url ) { $url = wp_get_attachment_image_url( (int) $meta, 'full' ); }
		return $url ? $url : '';
	}
	return esc_url_raw( (string) $meta );
}

add_filter( 'pre_get_avatar_data', function ( $args, $id_or_email ) {
	$user_id = vr_resolve_user_id( $id_or_email );
	if ( ! $user_id ) { return $args; }
	$url = vr_author_avatar_url( $user_id );
	if ( '' !== $url ) {
		$args['url']          = $url;
		$args['found_avatar'] = true;
	}
	return $args;
}, 10, 2 );

function vr_author_avatar_field( $user ) {
	$val = (string) get_user_meta( $user->ID, 'vr_author_avatar', true );
	?>
	<h2><?php esc_html_e( 'Author photo', 'viral-reader' ); ?></h2>
	<table class="form-table" role="presentation"><tr>
		<th><label for="vr_author_avatar"><?php esc_html_e( 'Photo (image URL or media ID)', 'viral-reader' ); ?></label></th>
		<td>
			<input type="text" name="vr_author_avatar" id="vr_author_avatar" value="<?php echo esc_attr( $val ); ?>" class="regular-text" />
			<p class="description"><?php esc_html_e( 'Overrides the Gravatar. Paste an image URL or a Media Library attachment ID; leave blank for the default.', 'viral-reader' ); ?></p>
		</td>
	</tr></table>
	<?php
}
add_action( 'show_user_profile', 'vr_author_avatar_field' );
add_action( 'edit_user_profile', 'vr_author_avatar_field' );

function vr_author_avatar_save( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) { return; }
	if ( ! isset( $_POST['vr_author_avatar'] ) ) { return; }
	$raw = trim( wp_unslash( (string) $_POST['vr_author_avatar'] ) );
	if ( '' === $raw ) {
		delete_user_meta( $user_id, 'vr_author_avatar' );
	} elseif ( is_numeric( $raw ) ) {
		update_user_meta( $user_id, 'vr_author_avatar', (int) $raw );
	} else {
		update_user_meta( $user_id, 'vr_author_avatar', esc_url_raw( $raw ) );
	}
}
add_action( 'personal_options_update', 'vr_author_avatar_save' );
add_action( 'edit_user_profile_update', 'vr_author_avatar_save' );

function vr_recipe_card( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$r = vr_recipe_data( get_the_ID() );
	if ( ! $r ) { return $content; }

	$meta = array();
	if ( '' !== trim( $r['servings'] ) )      { $meta[] = array( __( 'Servings', 'viral-reader' ), $r['servings'] ); }
	if ( $r['prep'] > 0 && vr_human_minutes( $r['prep'] ) )   { $meta[] = array( __( 'Prep', 'viral-reader' ), vr_human_minutes( $r['prep'] ) ); }
	if ( $r['cook'] > 0 && vr_human_minutes( $r['cook'] ) )   { $meta[] = array( __( 'Cook', 'viral-reader' ), vr_human_minutes( $r['cook'] ) ); }
	if ( $r['total'] > 0 && vr_human_minutes( $r['total'] ) ) { $meta[] = array( __( 'Total', 'viral-reader' ), vr_human_minutes( $r['total'] ) ); }

	ob_start();
	?>
	<div class="vr-recipe-card">
		<h2 class="vr-recipe-card__title"><?php echo esc_html( get_the_title() ); ?></h2>
		<?php if ( $meta ) : ?>
			<ul class="vr-recipe-card__meta">
				<?php foreach ( $meta as $row ) : ?>
					<li><span class="vr-recipe-card__k"><?php echo esc_html( $row[0] ); ?></span><span class="vr-recipe-card__v"><?php echo esc_html( $row[1] ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( ! empty( $r['ingredients'] ) ) : ?>
			<div class="vr-recipe-card__sec">
				<h3><?php esc_html_e( 'Ingredients', 'viral-reader' ); ?></h3>
				<ul class="vr-recipe-card__ing">
					<?php foreach ( $r['ingredients'] as $ing ) : ?>
						<li><?php echo esc_html( $ing ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $r['steps'] ) ) : ?>
			<div class="vr-recipe-card__sec">
				<h3><?php esc_html_e( 'Instructions', 'viral-reader' ); ?></h3>
				<ol class="vr-recipe-card__steps">
					<?php foreach ( $r['steps'] as $step ) : ?>
						<li><?php echo esc_html( $step ); ?></li>
					<?php endforeach; ?>
				</ol>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return $content . ob_get_clean();
}
add_filter( 'the_content', 'vr_recipe_card', 9 );
