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

function kepoli_env_bool(string $key, bool $default = false): bool
{
    $value = strtolower(kepoli_env($key, $default ? '1' : '0'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function kepoli_asset_uri(string $basename, string $fallback_extension = 'svg'): string
{
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    foreach (['webp', 'jpg', 'jpeg', 'png', 'svg'] as $extension) {
        $path = "/assets/img/{$basename}.{$extension}";
        if (file_exists($dir . $path)) {
            return $uri . $path;
        }
    }
    return $uri . "/assets/img/{$basename}.{$fallback_extension}";
}

function kepoli_asset_dimensions(string $basename): array
{
    $dimensions = [
        'hero-homepage' => [1536, 1024],
        'kepoli-social-cover' => [1536, 1024],
        'writer-photo' => [1024, 1024],
        'kepoli-wordmark' => [760, 360],
        'kepoli-icon' => [512, 512],
    ];

    return $dimensions[$basename] ?? [];
}

function kepoli_dimension_attributes(array $item): string
{
    $width = isset($item['width']) ? (int) $item['width'] : 0;
    $height = isset($item['height']) ? (int) $item['height'] : 0;

    if ($width <= 0 || $height <= 0) {
        return '';
    }

    return sprintf(' width="%d" height="%d"', $width, $height);
}

function kepoli_asset_dimension_attributes(string $basename): string
{
    [$width, $height] = array_pad(kepoli_asset_dimensions($basename), 2, 0);
    return kepoli_dimension_attributes(['width' => $width, 'height' => $height]);
}

function kepoli_icon(string $name): string
{
    $icons = [
        'calendar' => '<path d="M7 3v3M17 3v3M4.5 9h15M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 13h2M8 16h2M14 13h2M14 16h2"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3.2 2"/>',
        'user' => '<circle cx="12" cy="8.5" r="3.2"/><path d="M5.5 20a6.5 6.5 0 0 1 13 0"/>',
        'refresh' => '<path d="M19 7.5A8 8 0 0 0 5.6 6.2L4 8"/><path d="M4 4v4h4"/><path d="M5 16.5a8 8 0 0 0 13.4 1.3L20 16"/><path d="M20 20v-4h-4"/>',
        'facebook' => '<path d="M14 8h2V4.8A11 11 0 0 0 13.2 4C10.4 4 9 5.7 9 8.8V11H6v3.6h3V21h3.7v-6.4h3L16.2 11h-3.5V9.1c0-.8.3-1.1 1.3-1.1Z"/>',
        'whatsapp' => '<path d="M5.2 19 6 16.1A7.2 7.2 0 1 1 8.8 18Z"/><path d="M9.2 8.6c-.2-.5-.4-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.4-1 1-1 2.3 0 1.4 1 2.7 1.1 2.9.2.2 2 3.2 5 4.2 2.5.8 3 .4 3.5.4s1.7-.7 1.9-1.4.2-1.2.1-1.4c-.1-.1-.3-.2-.7-.4l-2-.9c-.3-.1-.6-.2-.8.2l-.6.8c-.2.3-.4.3-.7.1-1-.4-1.9-1-2.6-1.8-.6-.7-1-1.3-1.1-1.6-.1-.3 0-.5.2-.7l.5-.6c.2-.2.2-.4.3-.6.1-.2 0-.4 0-.6Z"/>',
        'email' => '<path d="M4.5 6.5h15v11h-15Z"/><path d="m5 7 7 6 7-6"/>',
        'link' => '<path d="M10.5 13.5a3 3 0 0 0 4.2 0l3-3a3 3 0 0 0-4.2-4.2l-1.1 1.1"/><path d="M13.5 10.5a3 3 0 0 0-4.2 0l-3 3a3 3 0 0 0 4.2 4.2l1.1-1.1"/>',
        'print' => '<path d="M7 8V4h10v4"/><path d="M7 17H5a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2"/><path d="M7 14h10v7H7Z"/><path d="M17.5 12.5h.01"/>',
        'ingredients' => '<path d="M7 10h10l-1 10H8Z"/><path d="M9 10V8a3 3 0 0 1 6 0v2"/><path d="M9.5 14h5M9.5 17h4"/>',
        'steps' => '<path d="M8 6h12M8 12h12M8 18h12"/><path d="M4 6h.01M4 12h.01M4 18h.01"/>',
        'prep' => '<path d="M4 20h16"/><path d="M6 20V9a6 6 0 0 1 12 0v11"/><path d="M8 12h8"/><path d="M9 5.2A5.8 5.8 0 0 1 12 4a5.8 5.8 0 0 1 3 1.2"/>',
        'tips' => '<path d="M9 18h6"/><path d="M10 21h4"/><path d="M8.5 14.5a5.5 5.5 0 1 1 7 0c-.8.6-1.2 1.3-1.3 2.2H9.8c-.1-.9-.5-1.6-1.3-2.2Z"/>',
        'storage' => '<path d="M6 7h12v14H6Z"/><path d="M8 7V4h8v3"/><path d="M9 11h6M9 15h6"/>',
        'question' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.7 2.7 0 0 1 5.1 1.2c0 1.8-1.8 2.2-2.4 3.3"/><path d="M12 17h.01"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'search' => '<circle cx="10.7" cy="10.7" r="5.7"/><path d="m15 15 4.2 4.2"/>',
    ];

    if (!isset($icons[$name])) {
        return '';
    }

    return '<svg class="kepoli-icon kepoli-icon--' . esc_attr($name) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $icons[$name] . '</svg>';
}

function kepoli_author_page_url(): string
{
    $page = get_page_by_path('despre-autor', OBJECT, 'page');
    return $page ? get_permalink($page) : home_url('/despre-autor/');
}

function kepoli_brand_description(): string
{
    return 'Kepoli publica retete romanesti, articole culinare si ghiduri practice pentru gatit acasa.';
}

function kepoli_current_description(): string
{
    $description = get_bloginfo('description');
    if (is_singular()) {
        $meta = get_post_meta(get_the_ID(), '_kepoli_meta_description', true);
        $description = $meta ?: wp_strip_all_tags(get_the_excerpt());
    } elseif (is_category()) {
        $description = category_description() ?: single_cat_title('', false);
    } elseif (is_front_page()) {
        $description = kepoli_brand_description();
    }

    return trim(wp_strip_all_tags((string) $description));
}

function kepoli_current_seo_title(): string
{
    if (is_singular('post')) {
        $seo_title = trim((string) get_post_meta(get_the_ID(), '_kepoli_seo_title', true));
        $title = $seo_title !== '' ? $seo_title : single_post_title('', false);
    } elseif (is_front_page()) {
        $title = 'Kepoli - Retete romanesti si articole culinare';
    } elseif (is_page('retete')) {
        $title = 'Retete romanesti pentru acasa | Kepoli';
    } elseif (is_page('articole')) {
        $title = 'Articole culinare si ghiduri practice | Kepoli';
    } elseif (is_category()) {
        $title = single_cat_title('', false) . ' - Retete si articole | Kepoli';
    } elseif (is_search()) {
        $title = sprintf('Cautare: %s | Kepoli', get_search_query());
    } elseif (is_404()) {
        $title = 'Pagina negasita | Kepoli';
    } elseif (is_page()) {
        $title = single_post_title('', false);
    } elseif (is_archive()) {
        $title = get_the_archive_title();
    } else {
        $title = get_bloginfo('name');
    }

    $paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
    if ($paged > 1) {
        $title .= ' - Pagina ' . $paged;
    }

    if (!str_contains($title, 'Kepoli')) {
        $title .= ' | Kepoli';
    }

    return trim($title);
}

function kepoli_document_title(string $title): string
{
    return kepoli_current_seo_title();
}
add_filter('pre_get_document_title', 'kepoli_document_title');

function kepoli_social_image_url(): string
{
    if (is_singular()) {
        $image = kepoli_post_featured_image_url(get_the_ID(), 'large');
        if ($image !== '') {
            return $image;
        }
    }

    $social_cover = get_template_directory() . '/assets/img/kepoli-social-cover.jpg';
    if (file_exists($social_cover)) {
        return kepoli_asset_uri('kepoli-social-cover', 'jpg');
    }

    return kepoli_asset_uri('writer-photo', 'jpg');
}

function kepoli_social_image_alt(): string
{
    if (is_singular()) {
        $alt = kepoli_post_featured_image_alt(get_the_ID());
        if ($alt !== '') {
            return $alt;
        }
    }

    return kepoli_current_description() ?: kepoli_brand_description();
}

function kepoli_schema_image_object(string $url, array $dimensions = [], string $caption = ''): array
{
    $image = [
        '@type' => 'ImageObject',
        'url' => $url,
    ];

    [$width, $height] = array_pad($dimensions, 2, 0);
    if ((int) $width > 0 && (int) $height > 0) {
        $image['width'] = (int) $width;
        $image['height'] = (int) $height;
    }

    if (trim($caption) !== '') {
        $image['caption'] = trim($caption);
    }

    return $image;
}

function kepoli_schema_asset_image_object(string $basename, string $fallback_extension = 'svg', string $caption = ''): array
{
    return kepoli_schema_image_object(
        kepoli_asset_uri($basename, $fallback_extension),
        kepoli_asset_dimensions($basename),
        $caption
    );
}

function kepoli_social_image_schema_object(): array
{
    if (is_singular('post')) {
        $image_id = kepoli_post_featured_image_id(get_the_ID());
        if ($image_id) {
            $image = wp_get_attachment_image_src($image_id, 'large');
            $url = is_array($image) ? (string) $image[0] : kepoli_post_featured_image_url(get_the_ID(), 'large');
            $dimensions = is_array($image) ? [(int) $image[1], (int) $image[2]] : [];
            $caption = kepoli_post_featured_image_caption(get_the_ID()) ?: kepoli_post_featured_image_alt(get_the_ID());

            if ($url !== '') {
                return kepoli_schema_image_object($url, $dimensions, $caption);
            }
        }
    }

    return kepoli_schema_asset_image_object('kepoli-social-cover', 'jpg', kepoli_current_description());
}

function kepoli_schema_publisher(): array
{
    return [
        '@type' => 'Organization',
        '@id' => home_url('/#organization'),
        'name' => 'Kepoli',
        'url' => home_url('/'),
        'logo' => kepoli_schema_asset_image_object('kepoli-icon', 'svg', 'Kepoli'),
    ];
}

function kepoli_current_url(): string
{
    if (is_singular()) {
        return get_permalink();
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
    return home_url($request_uri);
}

function kepoli_ads_enabled(): bool
{
    return kepoli_env_bool('ADSENSE_ENABLE', false);
}

function kepoli_ga_enabled(): bool
{
    return kepoli_env_bool('GA_ENABLE', false);
}

function kepoli_primary_category(int $post_id = 0): ?WP_Term
{
    $post_id = $post_id ?: get_the_ID();
    $categories = get_the_category($post_id);
    return !empty($categories) ? $categories[0] : null;
}

function kepoli_tone_class(string $slug = ''): string
{
    $map = [
        'ciorbe-si-supe' => 'tone-soups',
        'feluri-principale' => 'tone-mains',
        'patiserie-si-deserturi' => 'tone-sweets',
        'conserve-si-garnituri' => 'tone-pantry',
        'articole' => 'tone-guides',
    ];

    return $map[$slug] ?? 'tone-default';
}

function kepoli_post_tone_class(int $post_id = 0): string
{
    $category = kepoli_primary_category($post_id);
    return kepoli_tone_class($category ? $category->slug : '');
}

function kepoli_post_kind_label(int $post_id = 0): string
{
    return kepoli_post_kind($post_id) === 'article' ? __('Articol', 'kepoli') : __('Reteta', 'kepoli');
}

function kepoli_browse_items(): array
{
    $items = [
        [
            'label' => __('Toate retetele', 'kepoli'),
            'url' => home_url('/retete/'),
            'meta' => __('Retete organizate pe categorii', 'kepoli'),
            'class' => 'tone-mains',
        ],
        [
            'label' => __('Articole utile', 'kepoli'),
            'url' => home_url('/articole/'),
            'meta' => __('Ghiduri, tehnici si ingrediente', 'kepoli'),
            'class' => 'tone-guides',
        ],
    ];

    foreach (['ciorbe-si-supe', 'feluri-principale', 'patiserie-si-deserturi', 'conserve-si-garnituri'] as $slug) {
        $category = get_category_by_slug($slug);
        if (!$category instanceof WP_Term) {
            continue;
        }

        $items[] = [
            'label' => $category->name,
            'url' => get_category_link($category),
            'meta' => sprintf(_n('%d articol', '%d articole', $category->count, 'kepoli'), $category->count),
            'class' => kepoli_tone_class($slug),
        ];
    }

    return $items;
}

function kepoli_render_browse_links(string $class = 'browse-links'): void
{
    echo '<div class="' . esc_attr($class) . '" aria-label="' . esc_attr__('Descoperire rapida', 'kepoli') . '">';
    foreach (kepoli_browse_items() as $item) {
        echo '<a class="browse-link ' . esc_attr($item['class']) . '" href="' . esc_url($item['url']) . '">';
        echo '<strong>' . esc_html($item['label']) . '</strong>';
        echo '<span>' . esc_html($item['meta']) . '</span>';
        echo '</a>';
    }
    echo '</div>';
}

function kepoli_editorial_paths(): array
{
    $definitions = [
        [
            'eyebrow' => __('Camara si ingrediente', 'kepoli'),
            'title' => __('Incepe cu baza care te ajuta toata saptamana', 'kepoli'),
            'summary' => __('Ghiduri pentru cumparaturi mai clare, ingrediente mai potrivite si o camara care chiar sustine mesele de acasa.', 'kepoli'),
            'class' => 'tone-pantry',
            'slugs' => ['ghidul-camarii-romanesti', 'cum-alegi-ingredientele-pentru-retete-romanesti'],
        ],
        [
            'eyebrow' => __('Sezon si planificare', 'kepoli'),
            'title' => __('Leaga pofta de sezon cu mesele care ies usor', 'kepoli'),
            'summary' => __('Pentru zilele in care vrei sa alegi mai bine ce merita gatit acum si cum construiesti un meniu coerent.', 'kepoli'),
            'class' => 'tone-mains',
            'slugs' => ['calendarul-gusturilor-de-sezon', 'meniu-romanesc-de-duminica'],
        ],
        [
            'eyebrow' => __('Tehnica si pastrare', 'kepoli'),
            'title' => __('Ghiduri pentru rezultate mai previzibile', 'kepoli'),
            'summary' => __('Explicatii practice despre aluaturi, baze si pastrarea mancarii gatite fara improvizatii care complica lucrurile.', 'kepoli'),
            'class' => 'tone-guides',
            'slugs' => ['tehnici-simple-pentru-aluaturi-si-baze', 'cum-pastrezi-mancarea-gatita'],
        ],
    ];

    $paths = [];

    foreach ($definitions as $definition) {
        $articles = [];

        foreach ($definition['slugs'] as $slug) {
            $post = get_page_by_path($slug, OBJECT, 'post');
            if (!$post instanceof WP_Post || kepoli_post_kind($post->ID) !== 'article') {
                continue;
            }

            $articles[] = [
                'title' => get_the_title($post),
                'url' => get_permalink($post),
            ];
        }

        if ($articles === []) {
            continue;
        }

        $paths[] = [
            'eyebrow' => $definition['eyebrow'],
            'title' => $definition['title'],
            'summary' => $definition['summary'],
            'class' => $definition['class'],
            'articles' => $articles,
        ];
    }

    return $paths;
}

function kepoli_reader_trust_items(): array
{
    return [
        [
            'label' => __('Despre Kepoli', 'kepoli'),
            'url' => home_url('/despre-kepoli/'),
            'meta' => __('Cine scrie si cum lucram', 'kepoli'),
            'class' => 'tone-guides',
        ],
        [
            'label' => __('Isalune Merovik', 'kepoli'),
            'url' => kepoli_author_page_url(),
            'meta' => __('Pagina autoarei si date editoriale', 'kepoli'),
            'class' => 'tone-default',
        ],
        [
            'label' => __('Politica editoriala', 'kepoli'),
            'url' => home_url('/politica-editoriala/'),
            'meta' => __('Originalitate, corecturi si independenta', 'kepoli'),
            'class' => 'tone-guides',
        ],
        [
            'label' => __('Contact', 'kepoli'),
            'url' => home_url('/contact/'),
            'meta' => __('Intrebari, corecturi si colaborari', 'kepoli'),
            'class' => 'tone-default',
        ],
    ];
}

function kepoli_render_reader_trust_links(string $class = 'browse-links browse-links--trust'): void
{
    echo '<div class="' . esc_attr($class) . '" aria-label="' . esc_attr__('Transparenta Kepoli', 'kepoli') . '">';
    foreach (kepoli_reader_trust_items() as $item) {
        echo '<a class="browse-link ' . esc_attr($item['class']) . '" href="' . esc_url($item['url']) . '">';
        echo '<strong>' . esc_html($item['label']) . '</strong>';
        echo '<span>' . esc_html($item['meta']) . '</span>';
        echo '</a>';
    }
    echo '</div>';
}

function kepoli_category_card_meta(WP_Term $category): array
{
    $map = [
        'ciorbe-si-supe' => [
            'icon' => '🍲',
            'description' => __('Ciorbe, supe clare si boluri calde pentru mese de familie.', 'kepoli'),
        ],
        'feluri-principale' => [
            'icon' => '🍽️',
            'description' => __('Mancaruri romanesti satioase, bune pentru pranz sau cina.', 'kepoli'),
        ],
        'patiserie-si-deserturi' => [
            'icon' => '🥐',
            'description' => __('Aluaturi, prajituri si deserturi simple pentru pofta de dulce.', 'kepoli'),
        ],
        'conserve-si-garnituri' => [
            'icon' => '🫙',
            'description' => __('Zacusca, muraturi, salate si garnituri care completeaza masa.', 'kepoli'),
        ],
        'articole' => [
            'icon' => '📖',
            'description' => __('Ghiduri pentru ingrediente, organizare si gatit mai clar acasa.', 'kepoli'),
        ],
    ];

    return $map[$category->slug] ?? [
        'icon' => '🍴',
        'description' => __('Idei Kepoli organizate pentru rasfoire rapida.', 'kepoli'),
    ];
}

function kepoli_category_card_image_data(WP_Term $category): array
{
    static $cache = [];

    if (array_key_exists($category->term_id, $cache)) {
        return $cache[$category->term_id];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 3,
        'cat' => $category->term_id,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
        'meta_query' => [
            [
                'key' => '_thumbnail_id',
                'compare' => 'EXISTS',
            ],
        ],
    ]);

    $data = [];

    if ($query->have_posts()) {
        $gallery = [];

        foreach ($query->posts as $index => $post) {
            $cover_size = $index === 0 ? 'medium_large' : 'thumbnail';
            $thumbnail_id = (int) get_post_thumbnail_id($post);
            $image = $thumbnail_id ? wp_get_attachment_image_src($thumbnail_id, $cover_size) : false;
            $image_url = is_array($image) ? (string) $image[0] : '';
            if (!$image_url) {
                continue;
            }

            $item = [
                'url' => $image_url,
                'alt' => kepoli_post_featured_image_alt($post->ID),
                'title' => get_the_title($post),
                'width' => is_array($image) ? (int) $image[1] : 0,
                'height' => is_array($image) ? (int) $image[2] : 0,
            ];

            if ($index === 0) {
                $data = [
                    'url' => $item['url'],
                    'alt' => $item['alt'],
                    'sample' => $item['title'],
                ];
            }

            $gallery[] = $item;
        }

        if ($gallery !== []) {
            $data['gallery'] = $gallery;
        }
    }

    wp_reset_postdata();
    $cache[$category->term_id] = $data;

    return $data;
}

function kepoli_archive_count_label(WP_Term $category): string
{
    if ($category->slug === 'articole') {
        return sprintf(_n('%d ghid publicat', '%d ghiduri publicate', $category->count, 'kepoli'), $category->count);
    }

    return sprintf(_n('%d reteta publicata', '%d retete publicate', $category->count, 'kepoli'), $category->count);
}

function kepoli_archive_guidance_items(): array
{
    $category = get_queried_object();
    if (!$category instanceof WP_Term || $category->taxonomy !== 'category') {
        return [];
    }

    $map = [
        'ciorbe-si-supe' => [
            [
                'title' => __('Ce compari aici', 'kepoli'),
                'body' => __('Uita-te la dreseala, aciditate si timpul de fierbere blanda ca sa alegi reteta potrivita pentru masa ta.', 'kepoli'),
            ],
            [
                'title' => __('Cand alegi reteta', 'kepoli'),
                'body' => __('Supele mai limpezi merg bine pentru zile obisnuite, iar ciorbele bogate merita cand vrei ceva mai satios si de stat la masa.', 'kepoli'),
            ],
            [
                'title' => __('Ce gasesti in pagina', 'kepoli'),
                'body' => __('Fiecare reteta include imagine, timpi clari, repere de textura si recomandari utile pentru servire sau pastrare.', 'kepoli'),
            ],
        ],
        'feluri-principale' => [
            [
                'title' => __('Cum te orientezi', 'kepoli'),
                'body' => __('Alege dupa timpul real pe care il ai: unele retete cer foc lung si rabdare, altele merg direct pentru pranz sau cina.', 'kepoli'),
            ],
            [
                'title' => __('Ce conteaza cel mai mult', 'kepoli'),
                'body' => __('Rumenirea, consistenta sosului si garnitura potrivita schimba mai mult rezultatul decat adaugarea de ingrediente in plus.', 'kepoli'),
            ],
            [
                'title' => __('Ce gasesti in pagina', 'kepoli'),
                'body' => __('Retetele vin cu timpi, portii, repere de servire si trimiteri utile catre alte feluri care merg natural impreuna.', 'kepoli'),
            ],
        ],
        'patiserie-si-deserturi' => [
            [
                'title' => __('Ce urmaresti', 'kepoli'),
                'body' => __('La deserturi conteaza textura: dospirea, temperatura ingredientelor si focul bun fac diferenta mai repede decat pare.', 'kepoli'),
            ],
            [
                'title' => __('Cum alegi mai usor', 'kepoli'),
                'body' => __('Daca vrei ceva rapid, mergi spre compozitii simple; pentru aluaturi si cozonaci, cauta retetele cu timp de odihna mai generos.', 'kepoli'),
            ],
            [
                'title' => __('Ce gasesti in pagina', 'kepoli'),
                'body' => __('Kepoli marcheaza reperele de framantare, prajire, coacere si pastrare ca sa nu ramai doar cu o lista scurta de pasi.', 'kepoli'),
            ],
        ],
        'conserve-si-garnituri' => [
            [
                'title' => __('Ce conteaza aici', 'kepoli'),
                'body' => __('Sezonul, sarea, borcanele curate si ritmul de lucru sunt mai importante decat sa incarci reteta cu prea multe artificii.', 'kepoli'),
            ],
            [
                'title' => __('Cum alegi reteta', 'kepoli'),
                'body' => __('Porneste de la ce vrei sa completezi la masa: unele merg langa mancaruri grele, altele rezolva o gustare sau o masa rece.', 'kepoli'),
            ],
            [
                'title' => __('Ce gasesti in pagina', 'kepoli'),
                'body' => __('Fiecare preparat are repere de echilibru, servire si pastrare, nu doar lista de ingrediente si un timp orientativ.', 'kepoli'),
            ],
        ],
        'articole' => [
            [
                'title' => __('De unde sa pornesti', 'kepoli'),
                'body' => __('Alege ghidul dupa problema reala pe care o ai: ingrediente, planificare, tehnici sau pastrarea mancarii gata facute.', 'kepoli'),
            ],
            [
                'title' => __('Cum folosesti categoria', 'kepoli'),
                'body' => __('Articolele sunt gandite sa te duca apoi spre retete potrivite, nu sa ramana texte izolate fara aplicare practica.', 'kepoli'),
            ],
            [
                'title' => __('Ce vezi in jurul continutului', 'kepoli'),
                'body' => __('Linkurile de autor, politica editoriala si contactul raman vizibile pentru ca cititorul sa stie cine raspunde de continut.', 'kepoli'),
            ],
        ],
    ];

    return $map[$category->slug] ?? [
        [
            'title' => __('Ce gasesti aici', 'kepoli'),
            'body' => __('Continutul este organizat pentru rasfoire rapida, cu imagini, context practic si trimiteri utile spre pagini inrudite.', 'kepoli'),
        ],
        [
            'title' => __('Cum te orientezi', 'kepoli'),
            'body' => __('Porneste de la descrierea categoriei si apoi compara titlurile, timpii si extrasele ca sa alegi pagina potrivita.', 'kepoli'),
        ],
        [
            'title' => __('Transparenta', 'kepoli'),
            'body' => __('Linkurile de autor, contact si politica editoriala raman aproape de continut pentru orientare rapida.', 'kepoli'),
        ],
    ];
}

function kepoli_page_resource_links(): array
{
    if (!is_page()) {
        return [];
    }

    $page_id = get_queried_object_id();
    if (!$page_id) {
        return [];
    }

    $slug = (string) get_post_field('post_name', $page_id);
    $clusters = [
        'despre-autor' => ['despre-kepoli', 'politica-editoriala', 'contact', 'publicitate-si-consimtamant'],
        'contact' => ['despre-kepoli', 'despre-autor', 'politica-editoriala', 'politica-de-confidentialitate', 'politica-de-cookies'],
        'politica-de-confidentialitate' => ['politica-de-cookies', 'publicitate-si-consimtamant', 'termeni-si-conditii', 'contact'],
        'politica-de-cookies' => ['politica-de-confidentialitate', 'publicitate-si-consimtamant', 'contact'],
        'publicitate-si-consimtamant' => ['politica-de-confidentialitate', 'politica-de-cookies', 'politica-editoriala', 'contact'],
        'politica-editoriala' => ['despre-kepoli', 'despre-autor', 'publicitate-si-consimtamant', 'contact'],
        'disclaimer-culinar' => ['politica-editoriala', 'termeni-si-conditii', 'contact'],
        'termeni-si-conditii' => ['politica-de-confidentialitate', 'politica-de-cookies', 'contact'],
    ];

    if (!isset($clusters[$slug])) {
        return [];
    }

    $items = [];
    foreach ($clusters[$slug] as $target_slug) {
        $page = get_page_by_path($target_slug, OBJECT, 'page');
        if (!$page instanceof WP_Post) {
            continue;
        }

        $items[] = [
            'label' => get_the_title($page),
            'url' => get_permalink($page),
        ];
    }

    return $items;
}

function kepoli_attachment_image_url(int $attachment_id, string $size = 'large'): string
{
    if ($attachment_id <= 0) {
        return '';
    }

    $url = wp_get_attachment_image_url($attachment_id, $size);
    if (!is_string($url) || $url === '') {
        $url = wp_get_attachment_url($attachment_id);
    }

    return is_string($url) ? $url : '';
}

function kepoli_post_featured_image_id(int $post_id = 0): int
{
    static $cache = [];

    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return 0;
    }

    if (isset($cache[$post_id])) {
        return $cache[$post_id];
    }

    $thumbnail_id = (int) get_post_thumbnail_id($post_id);
    if ($thumbnail_id && kepoli_attachment_image_url($thumbnail_id, 'full') !== '') {
        $cache[$post_id] = $thumbnail_id;
        return $cache[$post_id];
    }

    $filename = sanitize_file_name((string) get_post_meta($post_id, '_kepoli_image_plan_filename', true));
    if ($filename !== '') {
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_key' => '_kepoli_seed_image_filename',
            'meta_value' => $filename,
        ]);

        if (!empty($ids) && kepoli_attachment_image_url((int) $ids[0], 'full') !== '') {
            $cache[$post_id] = (int) $ids[0];
            return $cache[$post_id];
        }
    }

    $slug = sanitize_title((string) get_post_field('post_name', $post_id));
    if ($slug !== '') {
        $ids = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'meta_key' => '_kepoli_seed_image_slug',
            'meta_value' => $slug,
        ]);

        if (!empty($ids) && kepoli_attachment_image_url((int) $ids[0], 'full') !== '') {
            $cache[$post_id] = (int) $ids[0];
            return $cache[$post_id];
        }
    }

    $attached_images = get_posts([
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'post_parent' => $post_id,
        'post_mime_type' => 'image',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'orderby' => 'ID',
        'order' => 'ASC',
        'no_found_rows' => true,
    ]);

    $cache[$post_id] = !empty($attached_images) && kepoli_attachment_image_url((int) $attached_images[0], 'full') !== ''
        ? (int) $attached_images[0]
        : 0;
    return $cache[$post_id];
}

function kepoli_post_featured_image_url(int $post_id = 0, string $size = 'large'): string
{
    return kepoli_attachment_image_url(kepoli_post_featured_image_id($post_id), $size);
}

function kepoli_post_featured_image_alt(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return '';
    }

    $image_id = kepoli_post_featured_image_id($post_id);
    if ($image_id) {
        $alt = trim((string) get_post_meta($image_id, '_wp_attachment_image_alt', true));
        if ($alt !== '') {
            return $alt;
        }
    }

    return trim((string) get_post_meta($post_id, '_kepoli_image_plan_alt', true));
}

function kepoli_post_featured_image_caption(int $post_id = 0): string
{
    $image_id = kepoli_post_featured_image_id($post_id);
    if ($image_id) {
        $caption = wp_get_attachment_caption($image_id);
        if (is_string($caption) && $caption !== '') {
            return $caption;
        }
    }

    $post_id = $post_id ?: get_the_ID();
    return $post_id ? trim((string) get_post_meta($post_id, '_kepoli_image_plan_caption', true)) : '';
}

function kepoli_post_featured_image_markup(int $post_id = 0, string $size = 'large', array $attr = []): string
{
    $post_id = $post_id ?: get_the_ID();
    $image_id = kepoli_post_featured_image_id($post_id);
    if (!$image_id) {
        return '';
    }

    $markup = wp_get_attachment_image($image_id, $size, false, $attr);
    if (is_string($markup) && $markup !== '') {
        return $markup;
    }

    $url = kepoli_attachment_image_url($image_id, $size);
    if ($url === '') {
        return '';
    }

    $fallback_alt = isset($attr['alt']) ? (string) $attr['alt'] : kepoli_post_featured_image_alt($post_id);
    unset($attr['alt'], $attr['src']);
    $attributes = '';
    foreach ($attr as $name => $value) {
        if ($value === false || $value === null || $value === '') {
            continue;
        }
        $attributes .= sprintf(' %s="%s"', esc_attr((string) $name), esc_attr((string) $value));
    }

    return sprintf(
        '<img src="%1$s" alt="%2$s"%3$s>',
        esc_url($url),
        esc_attr($fallback_alt),
        $attributes
    );
}

function kepoli_post_media_image_attrs(string $context = 'card', string $class = 'post-media__image'): array
{
    $sizes = match ($context) {
        'sidebar' => '(max-width: 640px) 82px, 96px',
        'related' => '(max-width: 640px) 96px, (max-width: 980px) 50vw, 360px',
        default => '(max-width: 640px) 104px, (max-width: 980px) 50vw, 360px',
    };

    return [
        'class' => $class,
        'loading' => 'lazy',
        'decoding' => 'async',
        'sizes' => $sizes,
    ];
}

function kepoli_post_card_media_markup(int $post_id = 0, string $context = 'card'): string
{
    $post_id = $post_id ?: get_the_ID();
    $size = match ($context) {
        'related' => 'large',
        'sidebar' => 'thumbnail',
        default => 'medium_large',
    };
    $featured_image = kepoli_post_featured_image_markup($post_id, $size, kepoli_post_media_image_attrs($context));

    if ($featured_image !== '') {
        return sprintf(
            '%1$s<span class="post-media__shade"></span><img class="post-media__mark" src="%2$s" alt="" loading="lazy" decoding="async">',
            $featured_image,
            esc_url(kepoli_asset_uri('kepoli-icon'))
        );
    }

    return kepoli_post_media_markup($post_id, $context);
}

function kepoli_post_media_mode(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();

    if (kepoli_post_featured_image_id($post_id)) {
        return 'photo';
    }

    return kepoli_post_kind($post_id) === 'article' ? 'photo' : 'mark';
}

function kepoli_post_media_url(int $post_id = 0, string $size = 'large'): string
{
    $post_id = $post_id ?: get_the_ID();

    $image = kepoli_post_featured_image_url($post_id, $size);
    if ($image !== '') {
        return $image;
    }

    if (kepoli_post_kind($post_id) === 'article') {
        return kepoli_asset_uri('writer-photo', 'svg');
    }

    return kepoli_asset_uri('kepoli-icon');
}

function kepoli_post_media_markup(int $post_id = 0, string $context = 'card'): string
{
    $post_id = $post_id ?: get_the_ID();
    $mode = kepoli_post_media_mode($post_id);
    $media_class = 'post-media post-media--' . sanitize_html_class($context) . ' post-media--' . sanitize_html_class($mode) . ' ' . kepoli_post_tone_class($post_id);
    $size = $context === 'related' ? 'large' : 'medium_large';
    $image = kepoli_post_media_url($post_id, $size);
    $image_alt = $mode === 'photo' && kepoli_post_featured_image_id($post_id) ? kepoli_post_featured_image_alt($post_id) : '';

    if ($mode === 'photo') {
        $featured_image = kepoli_post_featured_image_markup($post_id, $size, kepoli_post_media_image_attrs($context));
        if ($featured_image !== '') {
            return sprintf(
                '<div class="%1$s">%2$s<span class="post-media__shade"></span><img class="post-media__mark" src="%3$s" alt="" loading="lazy" decoding="async"></div>',
                esc_attr($media_class),
                $featured_image,
                esc_url(kepoli_asset_uri('kepoli-icon'))
            );
        }

        return sprintf(
            '<div class="%1$s"><img class="post-media__image" src="%2$s" alt="%3$s" loading="lazy" decoding="async"><span class="post-media__shade"></span><img class="post-media__mark" src="%4$s" alt="" loading="lazy" decoding="async"></div>',
            esc_attr($media_class),
            esc_url($image),
            esc_attr($image_alt),
            esc_url(kepoli_asset_uri('kepoli-icon'))
        );
    }

    return sprintf(
        '<div class="%1$s"><span class="post-media__fill"></span><img class="post-media__icon" src="%2$s" alt="" loading="lazy" decoding="async"></div>',
        esc_attr($media_class),
        esc_url($image)
    );
}

function kepoli_related_posts_by_kind(int $post_id = 0, string $kind = 'recipe'): array
{
    $post_id = $post_id ?: get_the_ID();
    $meta_key = $kind === 'article' ? '_kepoli_related_article_slugs' : '_kepoli_related_recipe_slugs';
    $slugs = get_post_meta($post_id, $meta_key, true);
    $slugs = is_array($slugs) ? $slugs : [];

    if (!$slugs) {
        $fallback = get_post_meta($post_id, '_kepoli_related_slugs', true);
        $fallback = is_array($fallback) ? $fallback : [];
        foreach ($fallback as $slug) {
            $candidate = get_page_by_path($slug, OBJECT, 'post');
            if ($candidate && get_post_meta($candidate->ID, '_kepoli_post_kind', true) === $kind) {
                $slugs[] = $slug;
            }
        }
    }

    return kepoli_get_posts_by_slugs(array_slice($slugs, 0, 3));
}

function kepoli_related_card_reason(int $current_post_id = 0, int $related_post_id = 0): string
{
    $current_post_id = $current_post_id ?: get_the_ID();
    $related_post_id = $related_post_id ?: get_the_ID();

    if (!$current_post_id || !$related_post_id || $current_post_id === $related_post_id) {
        return '';
    }

    $current_kind = kepoli_post_kind($current_post_id);
    $related_kind = kepoli_post_kind($related_post_id);
    $current_category = kepoli_primary_category($current_post_id);
    $related_category = kepoli_primary_category($related_post_id);

    if ($current_kind === 'recipe' && $related_kind === 'article') {
        if ($current_category && $current_category->slug !== 'articole') {
            return sprintf(
                __('Ghid ales pentru ingredientele, pasii si contextul din zona %s.', 'kepoli'),
                $current_category->name
            );
        }

        return __('Ghid ales pentru ingredientele si pasii care completeaza reteta aceasta.', 'kepoli');
    }

    if ($current_kind === 'article' && $related_kind === 'recipe') {
        if ($related_category && $related_category->slug !== 'articole') {
            return sprintf(
                __('Reteta din %s care pune in practica ideile din articol.', 'kepoli'),
                $related_category->name
            );
        }

        return __('Reteta aleasa ca sa pui imediat in practica ideile din articol.', 'kepoli');
    }

    if ($current_category && $related_category && $current_category->term_id === $related_category->term_id) {
        return sprintf(
            __('Din aceeasi zona culinara: %s.', 'kepoli'),
            $current_category->name
        );
    }

    if ($related_category && $related_category->slug !== 'articole') {
        return sprintf(
            __('Ales din zona %s pentru un pas firesc mai departe.', 'kepoli'),
            $related_category->name
        );
    }

    return __('Ales editorial pentru a continua lectura intr-un mod util.', 'kepoli');
}

function kepoli_post_next_steps(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();
    if (!$post_id) {
        return ['items' => []];
    }

    $is_recipe = kepoli_post_kind($post_id) === 'recipe';
    $category = kepoli_primary_category($post_id);
    $related_posts = kepoli_related_posts_by_kind($post_id, $is_recipe ? 'article' : 'recipe');
    $items = [];
    $seen = [];

    $push_item = static function (string $url, string $eyebrow, string $label, string $meta, string $class = 'tone-default') use (&$items, &$seen): void {
        $url = trim($url);
        if ($url === '' || isset($seen[$url])) {
            return;
        }

        $seen[$url] = true;
        $items[] = [
            'url' => $url,
            'eyebrow' => $eyebrow,
            'label' => $label,
            'meta' => $meta,
            'class' => $class,
        ];
    };

    if ($related_posts) {
        $primary = $related_posts[0];
        $push_item(
            get_permalink($primary),
            $is_recipe ? __('Ghid recomandat', 'kepoli') : __('Reteta de incercat', 'kepoli'),
            get_the_title($primary),
            wp_trim_words(get_the_excerpt($primary), 18, '...'),
            kepoli_post_tone_class($primary->ID)
        );
    }

    if ($category && $category->slug !== 'articole') {
        $push_item(
            get_category_link($category),
            __('Din aceeasi categorie', 'kepoli'),
            sprintf(__('Mai multe din %s', 'kepoli'), $category->name),
            kepoli_archive_count_label($category),
            kepoli_tone_class($category->slug)
        );
    }

    if ($is_recipe) {
        $push_item(
            home_url('/retete/'),
            __('Rasfoire rapida', 'kepoli'),
            __('Toate retetele', 'kepoli'),
            __('Mergi spre alte retete romanesti pentru urmatoarea masa, desert sau garnitura.', 'kepoli'),
            'tone-mains'
        );
        $push_item(
            home_url('/articole/'),
            __('Mai mult context', 'kepoli'),
            __('Articole utile', 'kepoli'),
            __('Ingrediente, tehnici si organizare pentru bucataria de acasa.', 'kepoli'),
            'tone-guides'
        );
    } else {
        $push_item(
            home_url('/retete/'),
            __('Pune in practica', 'kepoli'),
            __('Retete de incercat', 'kepoli'),
            __('Retete romanesti alese pentru a transforma lectura in ceva concret de pus pe masa.', 'kepoli'),
            'tone-mains'
        );
        $push_item(
            home_url('/articole/'),
            __('Continua lectura', 'kepoli'),
            __('Mai multe ghiduri', 'kepoli'),
            __('Mai multe articole despre ingrediente, tehnici si planificare simpla.', 'kepoli'),
            'tone-guides'
        );
    }

    return [
        'eyebrow' => $is_recipe ? __('Dupa reteta', 'kepoli') : __('Dupa articol', 'kepoli'),
        'title' => $is_recipe ? __('Alege urmatorul pas', 'kepoli') : __('Pune ideile in practica', 'kepoli'),
        'description' => $is_recipe
            ? __('Continua cu un ghid util, cu mai multe retete din aceeasi zona sau cu o rasfoire mai larga prin site.', 'kepoli')
            : __('Treci direct spre o reteta relevanta, spre mai multe retete sau spre alte ghiduri utile.', 'kepoli'),
        'items' => array_slice($items, 0, 3),
    ];
}

function kepoli_post_updated_label(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $published = get_post_time('U', true, $post_id);
    $modified = get_post_modified_time('U', true, $post_id);

    if (!$published || !$modified || ($modified - $published) < DAY_IN_SECONDS) {
        return '';
    }

    return sprintf(__('Actualizat %s', 'kepoli'), get_the_modified_date('', $post_id));
}

function kepoli_article_freshness_label(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $updated = kepoli_post_updated_label($post_id);
    if ($updated !== '') {
        return $updated;
    }

    return sprintf(__('Publicat %s', 'kepoli'), get_the_date('j M Y', $post_id));
}

function kepoli_article_collection_meta_items(int $count = 0): array
{
    $items = [];

    if ($count > 0) {
        $items[] = sprintf(_n('%d ghid publicat', '%d ghiduri publicate', $count, 'kepoli'), $count);
    }

    $items[] = __('Ghiduri revizuite periodic cand apar clarificari utile', 'kepoli');
    $items[] = __('Cardurile si paginile arata data de actualizare atunci cand un ghid este revizuit', 'kepoli');

    return $items;
}

function kepoli_article_heading_index(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();

    if (!$post_id || kepoli_post_kind($post_id) !== 'article') {
        return [];
    }

    $content = (string) get_post_field('post_content', $post_id);
    if ($content === '') {
        return [];
    }

    preg_match_all('/<h2(?:\s[^>]*)?>(.*?)<\/h2>/i', $content, $matches);
    if (empty($matches[1])) {
        return [];
    }

    $headings = [];
    $seen = [];

    foreach ($matches[1] as $heading_html) {
        $label = trim(wp_strip_all_tags($heading_html));
        if ($label === '') {
            continue;
        }

        $base = sanitize_title($label);
        if ($base === '') {
            $base = 'sectiune';
        }

        $id = $base;
        $suffix = 2;
        while (isset($seen[$id])) {
            $id = $base . '-' . $suffix;
            $suffix++;
        }

        $seen[$id] = true;
        $headings[] = [
            'id' => $id,
            'label' => $label,
        ];
    }

    return $headings;
}

function kepoli_share_links(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();
    $url = get_permalink($post_id);
    $title = html_entity_decode(wp_strip_all_tags(get_the_title($post_id)), ENT_QUOTES, 'UTF-8');
    $text = rawurlencode($title . ' - ' . $url);
    $subject = rawurlencode($title);
    $body = rawurlencode($title . "\n\n" . $url);

    $links = [
        [
            'type' => 'facebook',
            'label' => __('Facebook', 'kepoli'),
            'url' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode($url),
        ],
        [
            'type' => 'whatsapp',
            'label' => __('WhatsApp', 'kepoli'),
            'url' => 'https://wa.me/?text=' . $text,
        ],
        [
            'type' => 'email',
            'label' => __('Email', 'kepoli'),
            'url' => 'mailto:?subject=' . $subject . '&body=' . $body,
        ],
        [
            'type' => 'copy',
            'label' => __('Copiaza linkul', 'kepoli'),
            'url' => $url,
        ],
    ];

    if (kepoli_post_kind($post_id) === 'recipe') {
        $links[] = [
            'type' => 'print',
            'label' => __('Printeaza', 'kepoli'),
            'url' => '#print',
        ];
    }

    return $links;
}

function kepoli_published_kind_count(string $kind = ''): int
{
    global $wpdb;

    $meta_key = '_kepoli_post_kind';

    if ($kind === '') {
        return (int) $wpdb->get_var(
            "SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish'"
        );
    }

    return (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(p.ID)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
            WHERE p.post_type = 'post'
              AND p.post_status = 'publish'
              AND pm.meta_key = %s
              AND pm.meta_value = %s",
            $meta_key,
            $kind
        )
    );
}

function kepoli_latest_post_by_kind(string $kind): ?WP_Post
{
    $posts = get_posts([
        'post_type' => 'post',
        'posts_per_page' => 1,
        'ignore_sticky_posts' => true,
        'meta_key' => '_kepoli_post_kind',
        'meta_value' => $kind,
    ]);

    return $posts ? $posts[0] : null;
}

function kepoli_post_count_by_kind(string $kind): int
{
    static $cache = [];

    if (isset($cache[$kind])) {
        return $cache[$kind];
    }

    $query = new WP_Query([
        'post_type' => 'post',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'no_found_rows' => false,
        'ignore_sticky_posts' => true,
        'meta_key' => '_kepoli_post_kind',
        'meta_value' => $kind,
    ]);

    $cache[$kind] = (int) $query->found_posts;

    return $cache[$kind];
}

function kepoli_recently_touched_posts_by_kind(string $kind, int $limit = 3, array $exclude_ids = []): array
{
    return get_posts([
        'post_type' => 'post',
        'posts_per_page' => $limit,
        'ignore_sticky_posts' => true,
        'post__not_in' => array_map('intval', $exclude_ids),
        'orderby' => 'modified',
        'order' => 'DESC',
        'meta_key' => '_kepoli_post_kind',
        'meta_value' => $kind,
    ]);
}

function kepoli_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('custom-logo', ['height' => 120, 'width' => 320, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary' => __('Navigatie principala', 'kepoli'),
        'footer' => __('Navigatie footer', 'kepoli'),
    ]);
}
add_action('after_setup_theme', 'kepoli_setup');

function kepoli_trim_wordpress_frontend_output(): void
{
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_action('template_redirect', 'rest_output_link_header', 11);
    add_filter('emoji_svg_url', '__return_false');
    add_filter('show_recent_comments_widget_style', '__return_false');
}
add_action('after_setup_theme', 'kepoli_trim_wordpress_frontend_output');

function kepoli_scripts(): void
{
    $style_path = get_template_directory() . '/style.min.css';
    $style_uri = get_template_directory_uri() . '/style.min.css';
    if (!file_exists($style_path)) {
        $style_path = get_stylesheet_directory() . '/style.css';
        $style_uri = get_stylesheet_uri();
    }
    wp_enqueue_style('kepoli-style', $style_uri, [], (string) filemtime($style_path));

    $script = get_template_directory() . '/assets/js/site.min.js';
    $script_uri = get_template_directory_uri() . '/assets/js/site.min.js';
    if (!file_exists($script)) {
        $script = get_template_directory() . '/assets/js/site.js';
        $script_uri = get_template_directory_uri() . '/assets/js/site.js';
    }

    if (file_exists($script)) {
        wp_enqueue_script(
            'kepoli-site',
            $script_uri,
            [],
            (string) filemtime($script),
            true
        );
        wp_script_add_data('kepoli-site', 'strategy', 'defer');
    }
}
add_action('wp_enqueue_scripts', 'kepoli_scripts');

function kepoli_dequeue_unused_frontend_assets(): void
{
    if (is_admin()) {
        return;
    }

    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('global-styles');
    wp_dequeue_style('classic-theme-styles');

    if (!is_user_logged_in()) {
        wp_deregister_style('dashicons');
    }
}
add_action('wp_enqueue_scripts', 'kepoli_dequeue_unused_frontend_assets', 20);

function kepoli_resource_hints(array $urls, string $relation_type): array
{
    $hints = [];

    if (kepoli_ga_enabled() && kepoli_env('GA_MEASUREMENT_ID') !== '') {
        $hints[] = 'https://www.googletagmanager.com';
    }

    if (kepoli_ads_enabled() && kepoli_env('ADSENSE_CLIENT_ID') !== '') {
        $hints[] = 'https://pagead2.googlesyndication.com';
        $hints[] = 'https://googleads.g.doubleclick.net';
    }

    if ($relation_type === 'dns-prefetch' || $relation_type === 'preconnect') {
        $urls = array_merge($urls, $hints);
    }

    return array_values(array_unique($urls));
}
add_filter('wp_resource_hints', 'kepoli_resource_hints', 10, 2);

function kepoli_register_sidebars(): void
{
    register_sidebar([
        'name' => __('Bara laterala reteta', 'kepoli'),
        'id' => 'recipe-sidebar',
        'before_widget' => '<section class="sidebar-section widget %2$s">',
        'after_widget' => '</section>',
        'before_title' => '<h2>',
        'after_title' => '</h2>',
    ]);
}
add_action('widgets_init', 'kepoli_register_sidebars');

function kepoli_robots_content(): string
{
    if (is_search() || is_404()) {
        return 'noindex,follow,max-image-preview:large';
    }

    return 'index,follow,max-image-preview:large';
}

function kepoli_meta_description(): void
{
    $description = kepoli_current_description();
    if ($description !== '') {
        printf("<meta name=\"description\" content=\"%s\">\n", esc_attr(wp_trim_words($description, 28, '')));
    }

    printf("<meta name=\"robots\" content=\"%s\">\n", esc_attr(kepoli_robots_content()));
    printf("<link rel=\"canonical\" href=\"%s\">\n", esc_url(kepoli_current_url()));

    if (is_singular('post')) {
        printf("<meta name=\"author\" content=\"%s\">\n", esc_attr(get_the_author()));
    }

    $verification = kepoli_env('SEARCH_CONSOLE_VERIFICATION');
    if ($verification !== '') {
        printf("<meta name=\"google-site-verification\" content=\"%s\">\n", esc_attr($verification));
    }

    printf("<link rel=\"icon\" href=\"%s\" type=\"image/svg+xml\">\n", esc_url(kepoli_asset_uri('kepoli-icon')));
}
add_action('wp_head', 'kepoli_meta_description', 2);

function kepoli_priority_image_preloads(): void
{
    if (is_front_page()) {
        printf(
            "<link rel=\"preload\" as=\"image\" href=\"%s\" fetchpriority=\"high\">\n",
            esc_url(kepoli_asset_uri('hero-homepage', 'jpg'))
        );
        return;
    }

    if (!is_singular('post')) {
        return;
    }

    $image_id = kepoli_post_featured_image_id(get_the_ID());
    if (!$image_id) {
        return;
    }

    $href = wp_get_attachment_image_url($image_id, 'large');
    if (!is_string($href) || $href === '') {
        return;
    }

    $srcset = wp_get_attachment_image_srcset($image_id, 'large');
    $sizes = '(max-width: 760px) 100vw, 760px';

    printf(
        "<link rel=\"preload\" as=\"image\" href=\"%1\$s\"%2\$s imagesizes=\"%3\$s\" fetchpriority=\"high\">\n",
        esc_url($href),
        is_string($srcset) && $srcset !== '' ? ' imagesrcset="' . esc_attr($srcset) . '"' : '',
        esc_attr($sizes)
    );
}
add_action('wp_head', 'kepoli_priority_image_preloads', 1);

function kepoli_social_meta(): void
{
    $title = kepoli_current_seo_title();
    $description = wp_trim_words(kepoli_current_description(), 28, '');
    $url = kepoli_current_url();
    $type = is_singular('post') ? 'article' : 'website';
    $image = kepoli_social_image_url();
    $image_alt = kepoli_social_image_alt();

    printf("<meta property=\"og:locale\" content=\"%s\">\n", esc_attr(str_replace('-', '_', get_bloginfo('language'))));
    printf("<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr(get_bloginfo('name')));
    printf("<meta property=\"og:title\" content=\"%s\">\n", esc_attr($title));
    printf("<meta property=\"og:description\" content=\"%s\">\n", esc_attr($description));
    printf("<meta property=\"og:url\" content=\"%s\">\n", esc_url($url));
    printf("<meta property=\"og:type\" content=\"%s\">\n", esc_attr($type));
    printf("<meta property=\"og:image\" content=\"%s\">\n", esc_url($image));
    printf("<meta property=\"og:image:alt\" content=\"%s\">\n", esc_attr($image_alt));
    printf("<meta name=\"twitter:card\" content=\"summary_large_image\">\n");
    printf("<meta name=\"twitter:title\" content=\"%s\">\n", esc_attr($title));
    printf("<meta name=\"twitter:description\" content=\"%s\">\n", esc_attr($description));
    printf("<meta name=\"twitter:image\" content=\"%s\">\n", esc_url($image));

    if (is_singular('post')) {
        printf("<meta property=\"article:published_time\" content=\"%s\">\n", esc_attr(get_the_date('c')));
        printf("<meta property=\"article:modified_time\" content=\"%s\">\n", esc_attr(get_the_modified_date('c')));
        printf("<meta property=\"article:author\" content=\"%s\">\n", esc_attr(get_the_author()));

        $category = kepoli_primary_category();
        if ($category) {
            printf("<meta property=\"article:section\" content=\"%s\">\n", esc_attr($category->name));
        }

        foreach (wp_get_post_tags(get_the_ID(), ['fields' => 'names']) as $tag) {
            printf("<meta property=\"article:tag\" content=\"%s\">\n", esc_attr($tag));
        }
    }
}
add_action('wp_head', 'kepoli_social_meta', 3);

function kepoli_code_seed_version(): string
{
    static $version = null;

    if ($version !== null) {
        return $version;
    }

    if (!function_exists('kepoli_seed_target_version') && file_exists('/seed/version.php')) {
        require_once '/seed/version.php';
    }

    $version = function_exists('kepoli_seed_target_version')
        ? (string) kepoli_seed_target_version()
        : '';

    return $version;
}

function kepoli_deploy_fingerprint_meta(): void
{
    if (!kepoli_env_bool('KEPOLI_DEPLOY_FINGERPRINT')) {
        return;
    }

    $target_version = kepoli_code_seed_version();
    $current_version = (string) get_option('kepoli_seed_version', '');

    if ($target_version !== '') {
        printf("<meta name=\"kepoli-seed-target\" content=\"%s\">\n", esc_attr($target_version));
    }

    if ($current_version !== '') {
        printf("<meta name=\"kepoli-seed-current\" content=\"%s\">\n", esc_attr($current_version));
    }
}
add_action('wp_head', 'kepoli_deploy_fingerprint_meta', 4);

function kepoli_adsense_head(): void
{
    $client = kepoli_env('ADSENSE_CLIENT_ID');
    if ($client === '' || !kepoli_ads_enabled()) {
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
    if ($measurement_id === '' || !kepoli_ga_enabled()) {
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

function kepoli_newsletter_cta_id(): string
{
    return kepoli_env('RRM_NEWSLETTER_CTA_ID', 'e67fddb0-cd37-468c-b8ae-f11f3fb7d446');
}

function kepoli_newsletter_cta(string $class = ''): string
{
    $cta_id = kepoli_newsletter_cta_id();
    if ($cta_id === '') {
        return '';
    }

    $classes = trim('newsletter-cta ' . $class);

    return sprintf(
        '<section class="%1$s" aria-label="%2$s"><div class="newsletter-cta__embed" rrm-inline-cta="%3$s"></div></section>',
        esc_attr($classes),
        esc_attr__('Newsletter Kepoli', 'kepoli'),
        esc_attr($cta_id)
    );
}

function kepoli_ad_slot(string $slot, string $class = ''): string
{
    $client = kepoli_env('ADSENSE_CLIENT_ID');
    $slot_key = 'ADSENSE_SLOT_' . strtoupper($slot);
    $slot_id = kepoli_env($slot_key);
    $classes = trim('ad-slot ad-slot--' . sanitize_html_class(str_replace('_', '-', $slot)) . ' ' . $class);

    if (!kepoli_ads_enabled() || $client === '' || $slot_id === '') {
        return '';
    }

    return sprintf(
        '<div class="%1$s"><ins class="adsbygoogle" style="display:block" data-ad-client="%2$s" data-ad-slot="%3$s" data-ad-format="auto" data-full-width-responsive="true"></ins><script>(adsbygoogle = window.adsbygoogle || []).push({});</script></div>',
        esc_attr($classes . ' ad-slot--live'),
        esc_attr($client),
        esc_attr($slot_id)
    );
}

function kepoli_ad_shortcode(array $atts): string
{
    $atts = shortcode_atts(['slot' => 'mid_content'], $atts, 'kepoli_ad');
    return kepoli_ad_slot((string) $atts['slot']);
}
add_shortcode('kepoli_ad', 'kepoli_ad_shortcode');

function kepoli_admin_adsense_notice(): void
{
    if (!is_admin() || !current_user_can('manage_options')) {
        return;
    }

    if (kepoli_env('ADSENSE_CLIENT_ID') === '' || kepoli_ads_enabled()) {
        return;
    }

    $consent_url = get_page_by_path('publicitate-si-consimtamant', OBJECT, 'page');
    $consent_link = $consent_url ? get_permalink($consent_url) : home_url('/publicitate-si-consimtamant/');

    echo '<div class="notice notice-warning"><p>';
    echo esc_html__('AdSense este configurat, dar codul de reclame ramane oprit pana cand finalizezi consimtamantul pentru vizitatorii din Romania/EEA.', 'kepoli');
    echo ' ';
    echo wp_kses_post(sprintf(
        __('Configureaza Google Privacy & Messaging sau un CMP certificat, apoi seteaza <code>ADSENSE_ENABLE=1</code> in Coolify. Vezi si pagina <a href="%s">Publicitate si consimtamant</a>.', 'kepoli'),
        esc_url($consent_link)
    ));
    echo '</p></div>';
}
add_action('admin_notices', 'kepoli_admin_adsense_notice');

function kepoli_read_time(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $words = str_word_count(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
    $minutes = max(1, (int) ceil($words / 220));
    return sprintf(_n('%d min de citit', '%d min de citit', $minutes, 'kepoli'), $minutes);
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

function kepoli_format_iso_duration(string $duration): string
{
    $duration = strtoupper(trim($duration));
    if ($duration === '') {
        return '';
    }

    if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/', $duration, $matches)) {
        return $duration;
    }

    $hours = isset($matches[1]) ? (int) $matches[1] : 0;
    $minutes = isset($matches[2]) ? (int) $matches[2] : 0;
    $parts = [];

    if ($hours > 0) {
        $parts[] = sprintf('%d h', $hours);
    }

    if ($minutes > 0 || !$parts) {
        $parts[] = sprintf('%d min', $minutes);
    }

    return implode(' ', $parts);
}

function kepoli_recipe_snapshot_items(int $post_id = 0): array
{
    $data = kepoli_recipe_data($post_id);
    if (!$data) {
        return [];
    }

    $items = [];
    $candidates = [
        [
            'label' => __('Pregatire', 'kepoli'),
            'value' => trim((string) ($data['prep'] ?? kepoli_format_iso_duration((string) ($data['prep_iso'] ?? '')))),
            'icon' => 'prep',
        ],
        [
            'label' => __('Gatire', 'kepoli'),
            'value' => trim((string) ($data['cook'] ?? kepoli_format_iso_duration((string) ($data['cook_iso'] ?? '')))),
            'icon' => 'clock',
        ],
        [
            'label' => __('Total', 'kepoli'),
            'value' => trim((string) ($data['total_label'] ?? kepoli_format_iso_duration((string) ($data['total_iso'] ?? '')))),
            'icon' => 'refresh',
        ],
        [
            'label' => __('Portii', 'kepoli'),
            'value' => trim((string) ($data['servings'] ?? '')),
            'icon' => 'ingredients',
        ],
    ];

    foreach ($candidates as $candidate) {
        if ($candidate['value'] === '') {
            continue;
        }

        $items[] = $candidate;
    }

    return $items;
}

function kepoli_recipe_step_anchor(int $position): string
{
    return 'mod-de-preparare-step-' . max(1, $position);
}

function kepoli_recipe_step_name(string $step): string
{
    $text = trim(wp_strip_all_tags($step));
    if ($text === '') {
        return __('Pas de preparare', 'kepoli');
    }

    $name = wp_trim_words($text, 8, '');
    return rtrim($name, " \t\n\r\0\x0B.,;:") ?: __('Pas de preparare', 'kepoli');
}

function kepoli_recipe_keywords(int $post_id = 0): string
{
    $post_id = $post_id ?: get_the_ID();
    $keywords = wp_get_post_tags($post_id, ['fields' => 'names']);

    if (!is_array($keywords)) {
        return '';
    }

    $keywords = array_values(array_unique(array_filter(array_map(static function ($keyword): string {
        return trim((string) $keyword);
    }, $keywords))));

    return implode(', ', $keywords);
}

function kepoli_article_snapshot_data(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();
    $json = (string) get_post_meta($post_id, '_kepoli_article_snapshot', true);
    $data = json_decode($json, true);

    if (is_array($data)) {
        return $data;
    }

    $headings = array_column(kepoli_article_heading_index($post_id), 'label');

    return [
        'takeaways' => [],
        'section_headings' => $headings,
        'section_count' => count($headings),
        'faq_count' => 0,
        'related_recipe_count' => count(kepoli_related_posts_by_kind($post_id, 'recipe')),
    ];
}

function kepoli_article_snapshot_items(int $post_id = 0): array
{
    $data = kepoli_article_snapshot_data($post_id);
    if (!$data) {
        return [];
    }

    $takeaways = array_values(array_filter(array_map('trim', $data['takeaways'] ?? [])));
    $headings = array_values(array_filter(array_map('trim', $data['section_headings'] ?? [])));
    $section_count = (int) ($data['section_count'] ?? count($headings));
    $faq_count = (int) ($data['faq_count'] ?? 0);
    $related_recipe_count = (int) ($data['related_recipe_count'] ?? 0);
    $items = [];

    if (!empty($takeaways[0])) {
        $items[] = [
            'label' => __('Ideea cheie', 'kepoli'),
            'value' => $takeaways[0],
            'icon' => 'tips',
        ];
    }

    if (!empty($headings[0])) {
        $items[] = [
            'label' => __('Pornesti cu', 'kepoli'),
            'value' => $headings[0],
            'icon' => 'steps',
        ];
    } elseif (!empty($takeaways[1])) {
        $items[] = [
            'label' => __('Urmaresti', 'kepoli'),
            'value' => $takeaways[1],
            'icon' => 'steps',
        ];
    }

    $structure = [];
    if ($section_count > 0) {
        $structure[] = sprintf(_n('%d sectiune practica', '%d sectiuni practice', $section_count, 'kepoli'), $section_count);
    }
    if ($faq_count > 0) {
        $structure[] = sprintf(_n('%d raspuns rapid', '%d raspunsuri rapide', $faq_count, 'kepoli'), $faq_count);
    }
    if ($structure !== []) {
        $items[] = [
            'label' => __('Include', 'kepoli'),
            'value' => implode(' si ', $structure),
            'icon' => 'question',
        ];
    }

    if ($related_recipe_count > 0) {
        $items[] = [
            'label' => __('Aplici cu', 'kepoli'),
            'value' => sprintf(_n('%d reteta legata', '%d retete legate', $related_recipe_count, 'kepoli'), $related_recipe_count),
            'icon' => 'arrow-right',
        ];
    }

    return $items;
}

function kepoli_post_card_meta_items(int $post_id = 0): array
{
    $post_id = $post_id ?: get_the_ID();

    if (kepoli_post_kind($post_id) === 'recipe') {
        $data = kepoli_recipe_data($post_id);
        $items = [];
        $total = trim((string) ($data['total_label'] ?? kepoli_format_iso_duration((string) ($data['total_iso'] ?? ''))));
        $servings = trim((string) ($data['servings'] ?? ''));

        if ($total !== '') {
            $items[] = sprintf(__('Total %s', 'kepoli'), $total);
        } elseif (!empty($data['prep_iso'])) {
            $prep = kepoli_format_iso_duration((string) $data['prep_iso']);
            if ($prep !== '') {
                $items[] = sprintf(__('Pregatire %s', 'kepoli'), $prep);
            }
        }

        if ($servings !== '') {
            $items[] = $servings;
        }

        if ($items !== []) {
            return $items;
        }
    }

    if (kepoli_post_kind($post_id) === 'article') {
        return [
            kepoli_article_freshness_label($post_id),
            kepoli_read_time($post_id),
        ];
    }

    return [
        get_the_date('j M Y', $post_id),
        kepoli_read_time($post_id),
    ];
}

function kepoli_render_post_card_meta(int $post_id = 0, string $class = 'post-card__meta', string $item_class = ''): string
{
    $items = array_values(array_filter(kepoli_post_card_meta_items($post_id), static function ($item) {
        return trim((string) $item) !== '';
    }));

    if ($items === []) {
        return '';
    }

    $html = '<div class="' . esc_attr(trim($class)) . '">';
    $item_attr = $item_class !== '' ? ' class="' . esc_attr($item_class) . '"' : '';

    foreach ($items as $item) {
        $html .= '<span' . $item_attr . '>' . esc_html($item) . '</span>';
    }

    $html .= '</div>';

    return $html;
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

    $author_id = (int) get_post_field('post_author', get_the_ID());
    $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : get_bloginfo('name');
    $recipe_image = kepoli_social_image_schema_object();

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Recipe',
        'name' => get_the_title(),
        'description' => wp_strip_all_tags(get_the_excerpt()),
        'image' => [$recipe_image],
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => get_permalink(),
        ],
        'inLanguage' => get_bloginfo('language') ?: 'ro-RO',
        'author' => [
            '@type' => 'Person',
            'name' => $author_name ?: 'Isalune Merovik',
            'url' => kepoli_author_page_url(),
        ],
        'publisher' => kepoli_schema_publisher(),
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'recipeCategory' => $data['category'] ?? '',
        'recipeCuisine' => 'Romanian',
        'recipeYield' => $data['servings'] ?? '',
        'prepTime' => $data['prep_iso'] ?? '',
        'cookTime' => $data['cook_iso'] ?? '',
        'totalTime' => $data['total_iso'] ?? '',
        'recipeIngredient' => $data['ingredients'] ?? [],
        'recipeInstructions' => array_map(static function ($step, $index) use ($recipe_image) {
            $position = (int) $index + 1;
            return [
                '@type' => 'HowToStep',
                'name' => kepoli_recipe_step_name((string) $step),
                'text' => $step,
                'url' => get_permalink() . '#' . kepoli_recipe_step_anchor($position),
                'image' => $recipe_image,
            ];
        }, $data['steps'] ?? [], array_keys($data['steps'] ?? [])),
    ];

    $keywords = kepoli_recipe_keywords();
    if ($keywords !== '') {
        $schema['keywords'] = $keywords;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_recipe_json_ld', 20);

function kepoli_article_json_ld(): void
{
    if (!is_singular('post') || kepoli_post_kind() === 'recipe') {
        return;
    }

    $author_id = (int) get_post_field('post_author', get_the_ID());
    $author_name = $author_id ? get_the_author_meta('display_name', $author_id) : 'Isalune Merovik';

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => get_the_title(),
        'description' => kepoli_current_description(),
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => get_permalink(),
        ],
        'image' => [kepoli_social_image_schema_object()],
        'inLanguage' => get_bloginfo('language') ?: 'ro-RO',
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => [
            '@type' => 'Person',
            'name' => $author_name,
            'url' => kepoli_author_page_url(),
        ],
        'publisher' => kepoli_schema_publisher(),
    ];

    $keywords = kepoli_recipe_keywords();
    if ($keywords !== '') {
        $schema['keywords'] = $keywords;
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_article_json_ld', 21);

function kepoli_site_json_ld(): void
{
    if (is_admin()) {
        return;
    }

    $graph = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => home_url('/#organization'),
                'name' => 'Kepoli',
                'url' => home_url('/'),
                'email' => kepoli_env('SITE_EMAIL', 'contact@kepoli.com'),
                'description' => kepoli_brand_description(),
                'logo' => kepoli_schema_asset_image_object('kepoli-wordmark', 'svg', 'Kepoli'),
            ],
            [
                '@type' => 'WebSite',
                '@id' => home_url('/#website'),
                'url' => home_url('/'),
                'name' => 'Kepoli',
                'alternateName' => 'kepoli.com',
                'description' => kepoli_brand_description(),
                'inLanguage' => get_bloginfo('language') ?: 'ro-RO',
                'publisher' => ['@id' => home_url('/#organization')],
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => home_url('/?s={search_term_string}'),
                    'query-input' => 'required name=search_term_string',
                ],
            ],
            [
                '@type' => 'Person',
                '@id' => kepoli_author_page_url() . '#person',
                'name' => 'Isalune Merovik',
                'url' => kepoli_author_page_url(),
                'email' => kepoli_env('WRITER_EMAIL', 'isalunemerovik@gmail.com'),
                'image' => kepoli_schema_asset_image_object('writer-photo', 'jpg', 'Isalune Merovik'),
                'worksFor' => ['@id' => home_url('/#organization')],
                'jobTitle' => 'Autor culinar',
                'description' => 'Autoare Kepoli. Scrie retete romanesti, articole culinare si ghiduri practice pentru gatit acasa.',
            ],
        ],
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_site_json_ld', 19);

function kepoli_collection_schema_posts(): array
{
    if (is_page('retete')) {
        return get_posts([
            'post_type' => 'post',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'recipe',
        ]);
    }

    if (is_page('articole')) {
        return get_posts([
            'post_type' => 'post',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'article',
        ]);
    }

    if (is_category()) {
        $term = get_queried_object();
        if (!$term instanceof WP_Term) {
            return [];
        }

        return get_posts([
            'post_type' => 'post',
            'posts_per_page' => 24,
            'ignore_sticky_posts' => true,
            'cat' => (int) $term->term_id,
        ]);
    }

    return [];
}

function kepoli_collection_json_ld(): void
{
    if (is_admin() || is_singular()) {
        return;
    }

    $posts = kepoli_collection_schema_posts();
    if (!$posts) {
        return;
    }

    $items = [];
    foreach (array_values($posts) as $index => $post) {
        $items[] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => get_permalink($post),
            'name' => get_the_title($post),
        ];
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => is_category() ? single_cat_title('', false) : (is_page() ? get_the_title() : get_bloginfo('name')),
        'description' => kepoli_current_description(),
        'url' => kepoli_current_url(),
        'inLanguage' => get_bloginfo('language') ?: 'ro-RO',
        'isPartOf' => ['@id' => home_url('/#website')],
        'mainEntity' => [
            '@type' => 'ItemList',
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($items),
            'itemListElement' => $items,
        ],
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_collection_json_ld', 22);

function kepoli_recipe_content_anchors(string $content): string
{
    if (!is_singular('post') || kepoli_post_kind() !== 'recipe') {
        return $content;
    }

    $content = strtr($content, [
        '<h2>Pe scurt</h2>' => '<h2 id="pe-scurt">Pe scurt</h2>',
        '<h2>Ingrediente</h2>' => '<h2 id="ingrediente">Ingrediente</h2>',
        '<h2>Mod de preparare</h2>' => '<h2 id="mod-de-preparare">Mod de preparare</h2>',
        '<h2>Sfaturi pentru reusita</h2>' => '<h2 id="sfaturi-pentru-reusita">Sfaturi pentru reusita</h2>',
        '<section class="related-posts"><h2>Legaturi utile</h2>' => '<section class="related-posts" id="legaturi-utile"><h2>Legaturi utile</h2>',
    ]);

    $anchored_content = preg_replace_callback(
        '/(<h2 id="mod-de-preparare">Mod de preparare<\/h2>\s*<ol>)(.*?)(<\/ol>)/is',
        static function (array $matches): string {
            $position = 0;
            $steps = (string) preg_replace_callback(
                '/<li(?![^>]*\sid=)([^>]*)>/i',
                static function (array $li_matches) use (&$position): string {
                    $position++;
                    return '<li id="' . esc_attr(kepoli_recipe_step_anchor($position)) . '"' . $li_matches[1] . '>';
                },
                $matches[2]
            );

            return $matches[1] . $steps . $matches[3];
        },
        $content,
        1
    );

    return is_string($anchored_content) ? $anchored_content : $content;
}
add_filter('the_content', 'kepoli_recipe_content_anchors', 5);

function kepoli_article_content_anchors(string $content): string
{
    if (!is_singular('post') || kepoli_post_kind() !== 'article') {
        return $content;
    }

    $headings = kepoli_article_heading_index(get_the_ID());
    if (!$headings) {
        return $content;
    }

    $index = 0;

    return (string) preg_replace_callback(
        '/<h2(?![^>]*\sid=)([^>]*)>(.*?)<\/h2>/i',
        static function (array $matches) use (&$index, $headings): string {
            if (!isset($headings[$index])) {
                return $matches[0];
            }

            $attrs = trim($matches[1]);
            $attrs = $attrs !== '' ? ' ' . $attrs : '';
            $id = $headings[$index]['id'];
            $index++;

            return '<h2 id="' . esc_attr($id) . '"' . $attrs . '>' . $matches[2] . '</h2>';
        },
        $content
    );
}
add_filter('the_content', 'kepoli_article_content_anchors', 6);

function kepoli_breadcrumbs(): void
{
    $items = kepoli_breadcrumb_items();

    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Fir de navigare', 'kepoli') . '">';
    foreach ($items as $index => $item) {
        if ($index > 0) {
            echo ' / ';
        }

        if (!empty($item['url'])) {
            echo '<a href="' . esc_url($item['url']) . '">' . esc_html($item['name']) . '</a>';
        } else {
            echo '<span>' . esc_html($item['name']) . '</span>';
        }
    }
    echo '</nav>';
}

function kepoli_breadcrumb_items(): array
{
    $items = [
        [
            'name' => __('Acasa', 'kepoli'),
            'url' => home_url('/'),
        ],
    ];

    if (is_singular('post')) {
        $category = get_the_category();
        if ($category) {
            $items[] = [
                'name' => $category[0]->name,
                'url' => get_category_link($category[0]),
            ];
        }
        $items[] = [
            'name' => get_the_title(),
            'url' => '',
        ];
    } elseif (is_category()) {
        $items[] = [
            'name' => single_cat_title('', false),
            'url' => '',
        ];
    } elseif (is_page()) {
        $items[] = [
            'name' => get_the_title(),
            'url' => '',
        ];
    } elseif (is_search()) {
        $items[] = [
            'name' => sprintf(__('Rezultate pentru "%s"', 'kepoli'), get_search_query()),
            'url' => '',
        ];
    }

    return $items;
}

function kepoli_breadcrumb_json_ld(): void
{
    if (is_admin()) {
        return;
    }

    $items = kepoli_breadcrumb_items();
    if (count($items) < 2) {
        return;
    }

    $list = [];
    foreach ($items as $index => $item) {
        $entry = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $item['name'],
        ];

        if (!empty($item['url'])) {
            $entry['item'] = $item['url'];
        }

        $list[] = $entry;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'kepoli_breadcrumb_json_ld', 23);

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
