<?php
/**
 * Plugin Name: Kepoli Recipe Backfill (one-time)
 * Description: The 58 "old-fashioned recipes" were published from a pre-recipe bundle version, so they carry no
 *   _wpap_recipe_* meta — the theme renders no recipe card and the page emits Article instead of Recipe schema
 *   (no rich results). Re-publishing can't fix it (the duplicate-title guard skips them). This one-time migration
 *   reads the bundled recipe data (kepoli-recipe-data.json: slug/title → ingredients/steps/servings/course) and
 *   writes the SAME _wpap_recipe_* meta a fresh Bulk-ZIP publish would, so the theme's vr_recipe_card renders the
 *   card and vr_recipe_jsonld emits schema.org/Recipe. Matches each post by slug, falling back to exact title.
 *   NEVER overwrites a post that already has a recipe (_wpap_recipe_on=1). Runs once (option-guarded), off the
 *   front-end hot path, per-item isolated. Safe to leave in place; delete the file + option after it has run.
 *
 * @package Kepoli
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'kepoli_recipe_backfill', 27 );
function kepoli_recipe_backfill(): void {
    if ( ! is_admin() && ! wp_doing_cron() ) {
        return; // keep this one-time maintenance off the front-end hot path
    }
    $marker = 'kepoli_recipe_backfill_v1';
    if ( '' !== (string) get_option( $marker, '' ) ) {
        return; // already applied
    }
    $file = __DIR__ . '/kepoli-recipe-data.json';
    if ( ! is_readable( $file ) ) {
        return;
    }
    $data = json_decode( (string) file_get_contents( $file ), true );
    if ( ! is_array( $data ) || empty( $data ) ) {
        return;
    }

    $applied = 0;
    $missing = 0;
    $skipped = 0;
    foreach ( $data as $r ) {
        try {
            $slug = isset( $r['slug'] ) ? (string) $r['slug'] : '';
            if ( '' === $slug ) {
                continue;
            }
            $post = get_page_by_path( $slug, OBJECT, 'post' );
            if ( ! ( $post instanceof WP_Post ) && ! empty( $r['title'] ) ) {
                // slug drifted on publish — match by exact title as a fallback.
                $q = new WP_Query( array(
                    'post_type'      => 'post',
                    'post_status'    => 'publish',
                    'title'          => (string) $r['title'],
                    'posts_per_page' => 1,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                ) );
                if ( ! empty( $q->posts ) ) {
                    $post = get_post( (int) $q->posts[0] );
                }
            }
            if ( ! ( $post instanceof WP_Post ) ) {
                $missing++;
                continue;
            }
            $pid = (int) $post->ID;
            if ( '1' === (string) get_post_meta( $pid, '_wpap_recipe_on', true ) ) {
                $skipped++; // already a recipe — don't touch
                continue;
            }
            $clean = static function ( $arr ) {
                return array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $arr ) ), 'strlen' ) );
            };
            $ing = $clean( $r['ingredients'] ?? array() );
            $stp = $clean( $r['steps'] ?? array() );
            if ( empty( $ing ) || empty( $stp ) ) {
                continue; // an image-less/step-less recipe is invalid — skip
            }
            update_post_meta( $pid, '_wpap_recipe_on', '1' );
            update_post_meta( $pid, '_wpap_recipe_ingredients', implode( "\n", $ing ) );
            update_post_meta( $pid, '_wpap_recipe_steps', implode( "\n", $stp ) );
            if ( ! empty( $r['servings'] ) ) {
                update_post_meta( $pid, '_wpap_recipe_servings', sanitize_text_field( (string) $r['servings'] ) );
            }
            if ( ! empty( $r['course'] ) ) {
                update_post_meta( $pid, '_wpap_recipe_course', sanitize_text_field( (string) $r['course'] ) );
            }
            $applied++;
        } catch ( \Throwable $e ) {
            error_log( '[kepoli] recipe-backfill: item failed: ' . $e->getMessage() );
        }
    }
    update_option( $marker, gmdate( 'c' ) . " applied=$applied missing=$missing skipped=$skipped", false );
    error_log( "[kepoli] recipe-backfill: applied=$applied missing=$missing skipped=$skipped" );
}
