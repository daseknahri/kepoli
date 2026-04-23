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
    foreach (['png', 'jpg', 'jpeg', 'webp', 'svg'] as $extension) {
        $path = "/assets/img/{$basename}.{$extension}";
        if (file_exists($dir . $path)) {
            return $uri . $path;
        }
    }
    return $uri . "/assets/img/{$basename}.{$fallback_extension}";
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

    $social_cover = get_template_directory() . '/assets/img/kepoli-social-cover.png';
    if (file_exists($social_cover)) {
        return kepoli_asset_uri('kepoli-social-cover', 'png');
    }

    return kepoli_asset_uri('writer-photo', 'svg');
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

    $classes = isset($attr['class']) ? ' class="' . esc_attr((string) $attr['class']) . '"' : '';
    return sprintf(
        '<img src="%1$s" alt="%2$s"%3$s>',
        esc_url($url),
        esc_attr(kepoli_post_featured_image_alt($post_id)),
        $classes
    );
}

function kepoli_post_card_media_markup(int $post_id = 0, string $context = 'card'): string
{
    $post_id = $post_id ?: get_the_ID();
    $size = match ($context) {
        'related' => 'large',
        'sidebar' => 'thumbnail',
        default => 'medium_large',
    };
    $featured_image = kepoli_post_featured_image_markup($post_id, $size, ['class' => 'post-media__image']);

    if ($featured_image !== '') {
        return sprintf(
            '%1$s<span class="post-media__shade"></span><img class="post-media__mark" src="%2$s" alt="">',
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
    $image = kepoli_post_media_url($post_id, $context === 'related' ? 'large' : 'medium_large');
    $image_alt = $mode === 'photo' && kepoli_post_featured_image_id($post_id) ? kepoli_post_featured_image_alt($post_id) : '';

    if ($mode === 'photo') {
        return sprintf(
            '<div class="%1$s"><img class="post-media__image" src="%2$s" alt="%3$s"><span class="post-media__shade"></span><img class="post-media__mark" src="%4$s" alt=""></div>',
            esc_attr($media_class),
            esc_url($image),
            esc_attr($image_alt),
            esc_url(kepoli_asset_uri('kepoli-icon'))
        );
    }

    return sprintf(
        '<div class="%1$s"><span class="post-media__fill"></span><img class="post-media__icon" src="%2$s" alt=""></div>',
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

    $script = get_template_directory() . '/assets/js/site.js';
    if (file_exists($script)) {
        wp_enqueue_script(
            'kepoli-site',
            get_template_directory_uri() . '/assets/js/site.js',
            [],
            (string) filemtime($script),
            true
        );
    }
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
    $description = kepoli_current_description();
    if ($description !== '') {
        printf("<meta name=\"description\" content=\"%s\">\n", esc_attr(wp_trim_words($description, 28, '')));
    }

    printf("<meta name=\"robots\" content=\"max-image-preview:large\">\n");
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

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Recipe',
        'name' => get_the_title(),
        'description' => wp_strip_all_tags(get_the_excerpt()),
        'image' => [kepoli_social_image_url()],
        'mainEntityOfPage' => get_permalink(),
        'author' => [
            '@type' => 'Person',
            'name' => $author_name ?: 'Isalune Merovik',
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Kepoli',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => kepoli_asset_uri('kepoli-icon'),
            ],
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
        'mainEntityOfPage' => get_permalink(),
        'image' => [kepoli_social_image_url()],
        'datePublished' => get_the_date('c'),
        'dateModified' => get_the_modified_date('c'),
        'author' => [
            '@type' => 'Person',
            'name' => $author_name,
            'url' => kepoli_author_page_url(),
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Kepoli',
            'logo' => [
                '@type' => 'ImageObject',
                'url' => kepoli_asset_uri('kepoli-icon'),
            ],
        ],
    ];

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
            'logo' => [
                '@type' => 'ImageObject',
                'url' => kepoli_asset_uri('kepoli-wordmark'),
            ],
            ],
            [
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url('/'),
            'name' => 'Kepoli',
            'alternateName' => 'kepoli.com',
            'description' => kepoli_brand_description(),
            'inLanguage' => 'ro-RO',
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
            'image' => kepoli_social_image_url(),
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

    return strtr($content, [
        '<h2>Pe scurt</h2>' => '<h2 id="pe-scurt">Pe scurt</h2>',
        '<h2>Ingrediente</h2>' => '<h2 id="ingrediente">Ingrediente</h2>',
        '<h2>Mod de preparare</h2>' => '<h2 id="mod-de-preparare">Mod de preparare</h2>',
        '<h2>Sfaturi pentru reusita</h2>' => '<h2 id="sfaturi-pentru-reusita">Sfaturi pentru reusita</h2>',
        '<section class="related-posts"><h2>Legaturi utile</h2>' => '<section class="related-posts" id="legaturi-utile"><h2>Legaturi utile</h2>',
    ]);
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

    echo '<nav class="breadcrumbs" aria-label="' . esc_attr__('Breadcrumbs', 'kepoli') . '">';
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
