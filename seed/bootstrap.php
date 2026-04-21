<?php
/**
 * Idempotent content and site bootstrap for Kepoli.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_seed_env(string $key, string $default = ''): string
{
    $value = getenv($key);
    return $value === false || $value === '' ? $default : trim((string) $value);
}

function kepoli_seed_json(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException("Cannot read {$path}");
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        throw new RuntimeException("Invalid JSON in {$path}: " . json_last_error_msg());
    }

    return $data;
}

function kepoli_seed_slug_to_title(string $slug): string
{
    return ucwords(str_replace('-', ' ', $slug));
}

function kepoli_seed_duration_minutes(string $value): int
{
    $minutes = 0;
    if (preg_match('/(\d+)\s*ora/', $value, $matches)) {
        $minutes += ((int) $matches[1]) * 60;
    }
    if (preg_match('/(\d+)\s*min/', $value, $matches)) {
        $minutes += (int) $matches[1];
    }
    return max(1, $minutes);
}

function kepoli_seed_iso_duration(string $value): string
{
    $minutes = kepoli_seed_duration_minutes($value);
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    $duration = 'PT';
    if ($hours > 0) {
        $duration .= $hours . 'H';
    }
    if ($mins > 0) {
        $duration .= $mins . 'M';
    }
    return $duration;
}

function kepoli_seed_upsert_page(array $page, int $author_id): int
{
    $existing = get_page_by_path($page['slug'], OBJECT, 'page');
    $postarr = [
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => $author_id,
        'post_name' => $page['slug'],
        'post_title' => $page['title'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_content' => str_replace(
            ['{{SITE_EMAIL}}', '{{WRITER_EMAIL}}'],
            [kepoli_seed_env('SITE_EMAIL', 'contact@kepoli.com'), kepoli_seed_env('WRITER_EMAIL', 'isalunemerovik@gmail.com')],
            $page['content']
        ),
    ];

    if ($existing) {
        $postarr['ID'] = $existing->ID;
    }

    $id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }

    return (int) $id;
}

function kepoli_seed_ensure_category(array $category): int
{
    $term = term_exists($category['slug'], 'category');
    if (!$term) {
        $term = wp_insert_term($category['name'], 'category', [
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
        ]);
    } else {
        wp_update_term((int) $term['term_id'], 'category', [
            'name' => $category['name'],
            'slug' => $category['slug'],
            'description' => $category['description'] ?? '',
        ]);
    }

    if (is_wp_error($term)) {
        throw new RuntimeException($term->get_error_message());
    }

    return (int) $term['term_id'];
}

function kepoli_seed_ensure_author(): int
{
    $email = kepoli_seed_env('WRITER_EMAIL', 'isalunemerovik@gmail.com');
    $username = 'isalune-merovik';
    $user = get_user_by('email', $email);

    if (!$user) {
        $user_id = username_exists($username);
        if (!$user_id) {
            $user_id = wp_create_user($username, wp_generate_password(32, true), $email);
        }
        $user = get_user_by('id', $user_id);
    }

    if (!$user || is_wp_error($user)) {
        throw new RuntimeException('Could not create author user.');
    }

    wp_update_user([
        'ID' => $user->ID,
        'display_name' => 'Isalune Merovik',
        'nickname' => 'Isalune Merovik',
        'first_name' => 'Isalune',
        'last_name' => 'Merovik',
        'user_url' => home_url('/despre-autor/'),
        'description' => 'Autoare Kepoli. Scrie retete romanesti, articole culinare si ghiduri practice pentru gatit acasa.',
        'role' => 'administrator',
    ]);

    return (int) $user->ID;
}

function kepoli_seed_link(string $slug, array $post_ids): string
{
    return isset($post_ids[$slug]) ? get_permalink($post_ids[$slug]) : home_url('/');
}

function kepoli_seed_recipe_content(array $post, array $post_ids, array $category_ids): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $category_name = $category_id ? get_cat_name($category_id) : kepoli_seed_slug_to_title($post['category']);

    $prep_minutes = kepoli_seed_duration_minutes($post['prep']);
    $cook_minutes = kepoli_seed_duration_minutes($post['cook']);
    $total = $prep_minutes + $cook_minutes;
    $total_label = $total >= 60 ? floor($total / 60) . ' ora ' . ($total % 60 ? ($total % 60) . ' min' : '') : $total . ' min';

    $html = '';
    $html .= '<p>' . esc_html($post['excerpt']) . '</p>';
    $html .= '<p>Reteta face parte din categoria <a href="' . esc_url($category_link) . '">' . esc_html($category_name) . '</a> si este scrisa pentru gatit acasa, cu pasi clari si ingrediente usor de verificat.</p>';
    $html .= '[kepoli_ad slot="after_intro"]';
    $html .= '<section class="kepoli-recipe-box">';
    $html .= '<h2>Pe scurt</h2>';
    $html .= '<div class="kepoli-recipe-meta">';
    $html .= '<div><span>Pregatire</span><strong>' . esc_html($post['prep']) . '</strong></div>';
    $html .= '<div><span>Gatire</span><strong>' . esc_html($post['cook']) . '</strong></div>';
    $html .= '<div><span>Total</span><strong>' . esc_html(trim($total_label)) . '</strong></div>';
    $html .= '<div><span>Portii</span><strong>' . esc_html($post['servings']) . '</strong></div>';
    $html .= '</div>';
    $html .= '<h2>Ingrediente</h2><ul>';
    foreach ($post['ingredients'] as $ingredient) {
        $html .= '<li>' . esc_html($ingredient) . '</li>';
    }
    $html .= '</ul>';
    $html .= '<h2>Mod de preparare</h2><ol>';
    foreach ($post['steps'] as $step) {
        $html .= '<li>' . esc_html($step) . '</li>';
    }
    $html .= '</ol>';
    $html .= '</section>';
    $html .= '[kepoli_ad slot="mid_content"]';
    $html .= '<h2>Sfaturi pentru reusita</h2>';
    $html .= '<p>' . esc_html($post['notes']) . '</p>';
    $html .= '<p>Gusta pe parcurs si ajusteaza sarea, aciditatea sau timpul de gatire in functie de ingredientele folosite. Pentru o masa mai ampla, combina reteta cu una dintre recomandarile de mai jos.</p>';
    $html .= '<section class="related-posts"><h2>Legaturi utile</h2><ul>';
    $html .= '<li><a href="' . esc_url($category_link) . '">Mai multe retete din ' . esc_html($category_name) . '</a></li>';
    foreach (array_merge($post['related'] ?? [], $post['related_articles'] ?? []) as $slug) {
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html(kepoli_seed_slug_to_title($slug)) . '</a></li>';
    }
    $html .= '</ul></section>';
    $html .= '<p><em>Nota: verifica mereu alergenii si adapteaza reteta la ingredientele tale.</em></p>';

    return $html;
}

function kepoli_seed_article_content(array $post, array $post_ids, array $category_ids): string
{
    $category_id = $category_ids[$post['category']] ?? 0;
    $category_link = $category_id ? get_category_link($category_id) : home_url('/');
    $html = '<p>' . esc_html($post['excerpt']) . '</p>';
    $html .= '<p>Acest ghid completeaza colectia de <a href="' . esc_url(home_url('/retete/')) . '">retete Kepoli</a> si arhiva de <a href="' . esc_url($category_link) . '">articole culinare</a>.</p>';

    $index = 0;
    foreach ($post['sections'] as $section) {
        $html .= '<h2>' . esc_html($section['heading']) . '</h2>';
        $html .= '<p>' . esc_html($section['body']) . '</p>';
        $index++;
        if ($index === 1) {
            $html .= '[kepoli_ad slot="mid_content"]';
        }
    }

    $html .= '<section class="related-posts"><h2>Retete mentionate</h2><ul>';
    foreach ($post['related'] ?? [] as $slug) {
        $html .= '<li><a href="' . esc_url(kepoli_seed_link($slug, $post_ids)) . '">' . esc_html(kepoli_seed_slug_to_title($slug)) . '</a></li>';
    }
    $html .= '</ul></section>';

    return $html;
}

function kepoli_seed_reset_menu(string $name, string $location): int
{
    $menu = wp_get_nav_menu_object($name);
    $menu_id = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu($name);

    foreach ((array) wp_get_nav_menu_items($menu_id) as $item) {
        wp_delete_post($item->ID, true);
    }

    $locations = get_theme_mod('nav_menu_locations', []);
    $locations[$location] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);

    return $menu_id;
}

function kepoli_seed_menu_page(int $menu_id, string $title, int $page_id): void
{
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title' => $title,
        'menu-item-object' => 'page',
        'menu-item-object-id' => $page_id,
        'menu-item-type' => 'post_type',
        'menu-item-status' => 'publish',
    ]);
}

function kepoli_seed_menu_category(int $menu_id, string $title, int $category_id): void
{
    wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title' => $title,
        'menu-item-object' => 'category',
        'menu-item-object-id' => $category_id,
        'menu-item-type' => 'taxonomy',
        'menu-item-status' => 'publish',
    ]);
}

if (wp_get_theme()->get_stylesheet() !== 'kepoli' && wp_get_theme('kepoli')->exists()) {
    switch_theme('kepoli');
}

update_option('blogname', 'Kepoli');
update_option('blogdescription', 'Retete romanesti si articole de bucatarie pentru acasa');
update_option('admin_email', kepoli_seed_env('SITE_EMAIL', 'contact@kepoli.com'));
update_option('blog_public', '1');
update_option('timezone_string', 'Europe/Bucharest');
update_option('date_format', 'j F Y');
update_option('time_format', 'H:i');
update_option('posts_per_page', 9);
update_option('default_role', 'subscriber');
update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');
update_option('require_name_email', '1');
update_option('close_comments_for_old_posts', '1');
update_option('close_comments_days_old', '14');

global $wp_rewrite;
if ($wp_rewrite instanceof WP_Rewrite) {
    $wp_rewrite->set_permalink_structure('/%category%/%postname%/');
}

$author_id = kepoli_seed_ensure_author();
$categories = kepoli_seed_json('/content/categories.json');
$pages = kepoli_seed_json('/content/pages.json');
$posts = kepoli_seed_json('/content/posts.json');

$category_ids = [];
foreach ($categories as $category) {
    $category_ids[$category['slug']] = kepoli_seed_ensure_category($category);
}

$page_ids = [];
foreach ($pages as $page) {
    $page_ids[$page['slug']] = kepoli_seed_upsert_page($page, $author_id);
}

update_option('show_on_front', 'page');
update_option('page_on_front', $page_ids['acasa'] ?? 0);

$sample = get_page_by_path('hello-world', OBJECT, 'post');
if ($sample) {
    wp_delete_post($sample->ID, true);
}
$sample_page = get_page_by_path('sample-page', OBJECT, 'page');
if ($sample_page) {
    wp_delete_post($sample_page->ID, true);
}

$post_ids = [];
foreach ($posts as $index => $post) {
    $existing = get_page_by_path($post['slug'], OBJECT, 'post');
    $date = gmdate('Y-m-d H:i:s', strtotime('2026-03-01 +' . $index . ' days'));
    $postarr = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_author' => $author_id,
        'post_name' => $post['slug'],
        'post_title' => $post['title'],
        'post_excerpt' => $post['excerpt'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_content' => '<p>' . esc_html($post['excerpt']) . '</p>',
        'post_date' => $date,
        'post_date_gmt' => get_gmt_from_date($date),
    ];
    if ($existing) {
        $postarr['ID'] = $existing->ID;
        unset($postarr['post_date'], $postarr['post_date_gmt']);
    }

    $post_id = wp_insert_post(wp_slash($postarr), true);
    if (is_wp_error($post_id)) {
        throw new RuntimeException($post_id->get_error_message());
    }

    $post_ids[$post['slug']] = (int) $post_id;
    if (isset($category_ids[$post['category']])) {
        wp_set_post_terms($post_id, [$category_ids[$post['category']]], 'category', false);
    }
    wp_set_post_terms($post_id, $post['tags'] ?? [], 'post_tag', false);
    update_post_meta($post_id, '_kepoli_post_kind', $post['kind']);
    update_post_meta($post_id, '_kepoli_related_slugs', $post['related'] ?? []);
    update_post_meta($post_id, '_kepoli_meta_description', $post['meta_description'] ?? $post['excerpt']);
    update_post_meta($post_id, '_kepoli_seo_title', $post['seo_title'] ?? $post['title']);
}

foreach ($posts as $post) {
    $post_id = $post_ids[$post['slug']];
    if ($post['kind'] === 'recipe') {
        $content = kepoli_seed_recipe_content($post, $post_ids, $category_ids);
        $prep_minutes = kepoli_seed_duration_minutes($post['prep']);
        $cook_minutes = kepoli_seed_duration_minutes($post['cook']);
        update_post_meta($post_id, '_kepoli_recipe_json', wp_json_encode([
            'category' => get_cat_name($category_ids[$post['category']] ?? 0),
            'servings' => $post['servings'],
            'prep_iso' => kepoli_seed_iso_duration($post['prep']),
            'cook_iso' => kepoli_seed_iso_duration($post['cook']),
            'total_iso' => 'PT' . ($prep_minutes + $cook_minutes) . 'M',
            'ingredients' => $post['ingredients'],
            'steps' => $post['steps'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        $content = kepoli_seed_article_content($post, $post_ids, $category_ids);
    }

    wp_update_post(wp_slash([
        'ID' => $post_id,
        'post_content' => $content,
    ]), true);
}

$primary_menu = kepoli_seed_reset_menu('Primary', 'primary');
kepoli_seed_menu_page($primary_menu, 'Acasa', $page_ids['acasa']);
kepoli_seed_menu_page($primary_menu, 'Retete', $page_ids['retete']);
kepoli_seed_menu_category($primary_menu, 'Ciorbe si supe', $category_ids['ciorbe-si-supe']);
kepoli_seed_menu_category($primary_menu, 'Feluri principale', $category_ids['feluri-principale']);
kepoli_seed_menu_category($primary_menu, 'Deserturi', $category_ids['patiserie-si-deserturi']);
kepoli_seed_menu_page($primary_menu, 'Articole', $page_ids['articole']);
kepoli_seed_menu_page($primary_menu, 'Despre', $page_ids['despre-kepoli']);

$footer_menu = kepoli_seed_reset_menu('Footer', 'footer');
foreach (['despre-kepoli', 'despre-autor', 'contact', 'politica-de-confidentialitate', 'politica-de-cookies', 'termeni-si-conditii', 'disclaimer-culinar'] as $slug) {
    kepoli_seed_menu_page($footer_menu, $pages[array_search($slug, array_column($pages, 'slug'), true)]['title'], $page_ids[$slug]);
}

update_option('default_category', $category_ids['ciorbe-si-supe'] ?? 1);
update_option('posts_per_page', 9);
update_option('kepoli_seed_version', '2026-04-21-official-pages-copy');
flush_rewrite_rules(false);

echo "Seeded " . count($posts) . " posts and " . count($pages) . " pages.\n";
