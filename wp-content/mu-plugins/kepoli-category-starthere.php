<?php
/**
 * Plugin Name: Kepoli Category "Start Here" (curated hub entry-points)
 * Description: A category archive was a bare reverse-chronological grid — a reader landing on /category/recipes/
 *   saw whatever's newest, not the strongest way in. This appends a small hand-curated "Start here" line of
 *   cornerstone posts to the top of each main category page (via WP core's get_the_archive_description filter,
 *   which archive.php already renders — so NO theme edit), turning bare archives into real entry points and
 *   deepening internal linking (a mild lift against the "thin archive" perception too). Links resolve live by
 *   slug, so titles/permalinks stay correct if a post changes. kses-safe: uses a class, never an inline style
 *   (wp_kses_post keeps class, strips style). Front-end only; reversible.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/** category slug => curated cornerstone post slugs (3-4 strong, evergreen, on-brand entry points). */
function kepoli_start_here_map(): array
{
    return [
        'food-nutrition' => [
            'a-bowl-of-oatmeal-every-day-can-really',
            'black-seed-what-the-evidence-actually-shows',
            '6-everyday-foods-that-support-healthy-circulation',
            'saffron-for-eye-strain-and-screen-fatigue',
        ],
        'recipes' => [
            'classic-cabbage-rolls',
            'real-ukrainian-borscht-the-soup-thats-even-better-the-next-day',
            'homestyle-apple-pie',
            'white-bean-stew',
        ],
        'wellness-habits' => [
            'what-magnesium-really-does-for-circulation',
            'a-calm-bedtime-habit-for-steadier-daytime-energy',
            'celery-juice-what-it-actually-does-for-you',
            'the-quiet-power-of-small-everyday-kindness',
        ],
        'skin-beauty' => [
            'rice-water-for-skin-and-hair-a-simple-kitchen-habit',
            'honey-face-masks-what-they-really-do-for-skin',
            'a-simple-overnight-routine-for-softer-looking-skin',
            'simple-2-ingredient-face-masks-that-are-actually-gentle',
        ],
        'home-natural-living' => [
            'how-to-clean-burnt-pots-and-pans-naturally',
            '18-things-you-should-never-put-in-your-dishwasher',
            'how-to-revive-a-struggling-orchid-at-home',
            'smart-storage-ideas-for-the-cabinets-above-the-fridge',
        ],
        'tips' => [
            'store-cooked-food-safely',
            'stock-a-practical-pantry',
            'choose-fresh-ingredients',
            'home-pickling-basics',
        ],
    ];
}

add_filter('get_the_archive_description', 'kepoli_start_here_block');
function kepoli_start_here_block($desc)
{
    if (!is_category()) {
        return $desc;
    }
    $term = get_queried_object();
    $slug = ($term && isset($term->slug)) ? $term->slug : '';
    $picks = kepoli_start_here_map()[$slug] ?? [];
    if (empty($picks)) {
        return $desc;
    }
    $links = [];
    foreach ($picks as $ps) {
        $post = get_page_by_path($ps, OBJECT, 'post');
        if ($post && $post->post_status === 'publish') {
            $links[] = '<a href="' . esc_url(get_permalink($post)) . '">' . esc_html(get_the_title($post)) . '</a>';
        }
    }
    if (empty($links)) {
        return $desc;
    }
    $block = '<p class="kepoli-start-here"><strong>Start here:</strong> ' . implode(' &middot; ', $links) . '</p>';
    return $desc . $block;
}

/* Minimal styling for the block (class-based; kept out of the kses'd description string). */
add_action('wp_head', static function (): void {
    if (is_admin() || !is_category()) {
        return;
    }
    echo "\n<style id=\"kepoli-start-here-css\">.kepoli-start-here{margin:.75rem 0 0;font-size:.95rem;line-height:1.6}"
        . ".kepoli-start-here strong{color:var(--eyebrow,#8a5a44)}"
        . ".kepoli-start-here a{text-decoration:underline;text-underline-offset:2px}</style>\n";
}, 99);
