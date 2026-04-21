<?php
/**
 * Kepoli theme functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : trim((string) $value);
}

function kepoli_asset_uri(string $basename, string $fallback_extension = 'svg'): string
{
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
        $path = "/assets/img/{$basename}.{$extension}";
        if (file_exists($dir . $path)) {
            return $uri . $path;
        }
    }
    return $uri . "/assets/img/{$basename}.{$fallback_extension}";
}

function kepoli_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 120, 'width' => 320, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => __('Primary navigation', 'kepoli'),
        'footer' => __('Footer navigation', 'kepoli'),
    ]);
}
add_action('after_setup_theme', 'kepoli_setup');

function kepoli_scripts(): void
{
    wp_enqueue_style('kepoli-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version'));
}
add_action('wp_enqueue_scripts', 'kepoli_scripts');

function kepoli_register_sidebars(): void
{
    register_sidebar([
        'name' => __('Recipe sidebar', 'kepoli'),
        'id' => 'recipe-sidebar',
        'before_widget' => '<section class="sidebar-section widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2>',
        'after_title' => '</h2>',
    ]);
}
add_action('widgets_init', 'kepoli_register_sidebars');

function kepoli_meta_description(): void
{
    $description = get_bloginfo('description');
    if (is_singular()) {
        $meta = get_post_meta(get_the_ID(), '_kepoli_meta_description', true);
        $description = $meta ?: wp_strip_all_tags(get_the_excerpt());
    } elseif (is_category()) {
        $description = category_description() ?: single_cat_title('', false);
    }

    $description = trim(wp_strip_all_tags((string) $description));
    if ($description !== '') {
        printf("<meta name=\"description\" content=\"%s\">\n", esc_attr(wp_trim_words($description, 28, '')));
    }

    $verification = kepoli_env('SEARCH_CONSOLE_VERIFICATION');
    if ($verification !== '') {
        printf("<meta name=\"google-site-verification\" content=\"%s\">\n", esc_attr($verification));
    }

    printf("<link rel=\"icon\" href=\"%s\" type=\"image/svg+xml\">\n", esc_url(kepoli_asset_uri('kepoli-icon')));
}
add_action('wp_head', 'kepoli_meta_description', 2);

function kepoli_adsense_head(): void
{
    $client = kepoli_env('ADSENSE_CLIENT_ID');
    if ($client === '') {
        return;
    }

    printf(
        "<script async src=\"https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=%s\" crossorigin=\"anonymous\"></script>\n",
        esc_attr($client)
    );
}
add_action('wp_head', 'kepoli_adsense_head', 8);

function kepoli_ga_head(): void
{
    $measurement_id = kepoli_env('GA_MEASUREMENT_ID');
    if ($measurement_id === '') {
        return;
    }
    ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($measurement_id); ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo esc_js($measurement_id); ?>');
    </script>
    <?php
}
add_action('wp_head', 'kepoli_ga_head', 9);

function kepoli_ad_slot(string $slot, string $class = ''): string
{
    $client = kepoli_env('ADSENSE_CLIENT_ID');
    $slot_key = 'ADSENSE_SLOT_' . strtoupper($slot);
    $slot_id = kepoli_env($slot_key);
    $classes = trim('ad-slot ad-slot--' . sanitize_html_class(str_replace('_', '-', $slot)) . ' ' . $class);

    if ($client !== '' && $slot_id !== '') {
        return sprintf(
            '<div class="%1$s"><ins class="adsbygoogle" style="display:block" data-ad-client="%2$s" data-ad-slot="%3$s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>',
            esc_attr($classes . ' ad-slot--live'),
            esc_attr($client),
            esc_attr($slot_id)
        );
    }

    return sprintf(
        '<div class="%s" aria-label="%s"><span>%s</span></div>',
        esc_attr($classes . ' ad-slot--placeholder'),
        esc_attr__('Advertising space', 'kepoli'),
        esc_html__('Spatiu publicitar', 'kepoli')
    );
}

function kepoli_ad_shortcode(array $atts): string
{
    $atts = shortcode_atts(['slot' => 'mid_content'], $atts, 'kepoli_ad');
    return kepoli_ad_slot((string) $atts['slot']);
}
add_shortcode('kepoli_ad', 'kepoli_ad_shortcode');

function kepoli_read_time(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $words = str_word_count(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
    $minutes = max(1, (int) ceil($words / 220));
    return sprintf(_n('%d min read', '%d min read', $minutes, 'kepoli'), $minutes);
}

function kepoli_post_kind(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    return (string) get_post_meta($post_id, '_kepoli_post_kind', true);
}

function kepoli_recipe_data(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();
    $json = (string) get_post_meta($post_id, '_kepoli_recipe_json', true);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function kepoli_recipe_json_ld(): void
{
    if (!is_singular('post') || kepoli_post_kind() !== 'recipe') {
        return;
    }

    $data = kepoli_recipe_data();
    if (!$data) {
        return;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Recipe',
        'name' => get_the_title(),
        'description' => wp_strip_all_tags(get_the_excerpt()),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name'),
        ],
        'datePublished' => get_the_date('c'),
        'recipeCategory' => $data['category'] ?? '',
        'recipeCuisine' => 'Romanian',
        'recipeYield' => $data['servings'] ?? '',
        'prepTime' => $data['prep_iso'] ?? '',
        'cookTime' => $data['cook_iso'] ?? '',
        'totalTime' => $data['total_iso'] ?? '',
        'recipeIngredient' => $data['ingredients'] ?? [],
        'recipeInstructions' => array_map(static function ($step) {
            return ['@type' => 'HowToStep', 'text' => $step];
        }, $data['steps'] ?? []),
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_recipe_json_ld', 20);

function kepoli_breadcrumbs(): void
{
    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'kepoli') . '">';
    echo '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Acasa', 'kepoli') . '</a>';

    if (is_singular('post')) {
        $category = get_the_category();
        if ($category) {
            echo ' / <a href="' . esc_url(get_category_link($category[0])) . '">' . esc_html($category[0]->name) . '</a>';
        }
        echo ' / <span>' . esc_html(get_the_title()) . '</span>';
    } elseif (is_category()) {
        echo ' / <span>' . esc_html(single_cat_title('', false)) . '</span>';
    } elseif (is_page()) {
        echo ' / <span>' . esc_html(get_the_title()) . '</span>';
    }

    echo '</nav>';
}

function kepoli_get_posts_by_slugs(array $slugs): array
{
    $posts = [];
    foreach ($slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');
        if ($post && $post->post_status === 'publish') {
            $posts[] = $post;
        }
    }
    return $posts;
}
