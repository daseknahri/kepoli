<?php
/**
 * Plugin Name: Kepoli Author Tools
 * Description: Simplifies the Kepoli post editor with split tools, excerpt and SEO helpers, internal-link suggestions, and featured-image metadata.
 * Version: 1.4.3
 * Author: Kepoli
 * Text Domain: kepoli-author-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Kepoli_Author_Tools
{
    private const VERSION = '1.4.3';
    private const TEMPLATE_PROMPTS = [
        'Scrie aici de ce merita pregatita reteta, cand se potriveste si ce rezultat trebuie sa obtina cititorul.',
        'Ingredient 1',
        'Ingredient 2',
        'Ingredient 3',
        'Descrie primul pas clar, cu temperatura, timp sau semne vizuale daca este nevoie.',
        'Continua cu pasii in ordinea fireasca.',
        'Incheie cu momentul in care preparatul este gata.',
        'Adauga ajustari, greseli de evitat si variante utile pentru ingrediente.',
        'Explica pastrarea la frigider, reincalzirea sau consumul in siguranta.',
        'Raspunde practic, cu intervale realiste.',
        'Prezinta subiectul si spune cititorului ce va invata din articol.',
        'Explica punctele importante in paragrafe scurte, cu exemple concrete.',
        'Leaga sfaturile de retete, ingrediente sau obiceiuri de gatit acasa.',
        'Adauga linkuri interne catre retete sau ghiduri Kepoli apropiate.',
    ];
    private const TEMPLATE_OUTLINE_LABELS = [
        'Pe scurt',
        'Ingrediente',
        'Mod de preparare',
        'Sfaturi pentru reusita',
        'Cum pastrezi',
        'Intrebari frecvente',
        'Pot pregati reteta in avans?',
        'Ideea principala',
        'Ce merita retinut',
        'Cum aplici in bucatarie',
        'Legaturi utile',
    ];
    private static $is_updating_post = false;

    public static function init(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'use_classic_editor_for_posts'], 10, 2);
        add_filter('mce_external_plugins', [self::class, 'register_tinymce_plugin']);
        add_filter('mce_buttons', [self::class, 'register_tinymce_buttons']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('add_meta_boxes_post', [self::class, 'add_writer_guide_box']);
        add_action('add_meta_boxes_post', [self::class, 'add_post_setup_box']);
        add_action('save_post_post', [self::class, 'save_post_setup'], 10, 3);
        add_filter('manage_post_posts_columns', [self::class, 'add_post_list_columns']);
        add_action('manage_post_posts_custom_column', [self::class, 'render_post_list_column'], 10, 2);
        add_action('restrict_manage_posts', [self::class, 'render_post_kind_filter']);
        add_action('pre_get_posts', [self::class, 'filter_posts_by_kind']);
        add_filter('the_content', [self::class, 'remove_template_prompts_from_content'], 4);
        add_filter('get_the_excerpt', [self::class, 'remove_template_prompts_from_excerpt'], 12, 2);
    }

    public static function use_classic_editor_for_posts(bool $use_block_editor, string $post_type): bool
    {
        return $post_type === 'post' ? false : $use_block_editor;
    }

    public static function register_tinymce_plugin(array $plugins): array
    {
        if (self::is_post_editor_screen()) {
            $plugins['kepoli_author_tools'] = add_query_arg(
                'ver',
                self::VERSION,
                plugins_url('assets/editor-tools.js', __FILE__)
            );
        }

        return $plugins;
    }

    public static function register_tinymce_buttons(array $buttons): array
    {
        if (self::is_post_editor_screen()) {
            $buttons[] = 'separator';
            $buttons[] = 'kepoli_page_break';
            $buttons[] = 'kepoli_split_two';
            $buttons[] = 'kepoli_split_three';
        }

        return $buttons;
    }

    public static function enqueue_admin_assets(string $hook): void
    {
        if (!in_array($hook, ['post.php', 'post-new.php', 'edit.php'], true) || self::current_post_type() !== 'post') {
            return;
        }

        wp_enqueue_style(
            'kepoli-author-tools-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            self::VERSION
        );

        if (in_array($hook, ['post.php', 'post-new.php'], true)) {
            wp_enqueue_script(
                'kepoli-author-tools-admin',
                plugins_url('assets/admin.js', __FILE__),
                ['quicktags'],
                self::VERSION,
                true
            );

            wp_localize_script('kepoli-author-tools-admin', 'kepoliAuthorTools', [
                'currentPostId' => self::current_post_id(),
                'relatedPosts' => self::related_posts_payload(self::current_post_id()),
            ]);
        }
    }

    public static function add_writer_guide_box(): void
    {
        add_meta_box(
            'kepoli-author-guide',
            __('Kepoli writing tools', 'kepoli-author-tools'),
            [self::class, 'render_writer_guide_box'],
            'post',
            'side',
            'high'
        );
    }

    public static function render_writer_guide_box(): void
    {
        ?>
        <div class="kepoli-author-guide">
            <p><strong><?php esc_html_e('Flux simplu pentru autor', 'kepoli-author-tools'); ?></strong></p>
            <ol>
                <li><?php esc_html_e('Scrie titlul in campul de sus.', 'kepoli-author-tools'); ?></li>
                <li><?php esc_html_e('Scrie articolul sau reteta in editorul mare de continut.', 'kepoli-author-tools'); ?></li>
                <li><?php esc_html_e('Foloseste butoanele Pauza, 2 parti sau 3 parti din toolbar pentru articole lungi.', 'kepoli-author-tools'); ?></li>
            </ol>
            <p><?php esc_html_e('Impartirea foloseste pagini WordPress native, astfel incat cititorii primesc navigare clara intre parti.', 'kepoli-author-tools'); ?></p>
            <div class="kepoli-template-actions">
                <button type="button" class="button" data-kepoli-template="recipe"><?php esc_html_e('Structura reteta', 'kepoli-author-tools'); ?></button>
                <button type="button" class="button" data-kepoli-template="article"><?php esc_html_e('Structura articol', 'kepoli-author-tools'); ?></button>
            </div>
        </div>
        <?php
    }

    public static function add_post_setup_box(): void
    {
        add_meta_box(
            'kepoli-post-setup',
            __('Kepoli post setup', 'kepoli-author-tools'),
            [self::class, 'render_post_setup_box'],
            'post',
            'normal',
            'high'
        );
    }

    public static function render_post_setup_box(WP_Post $post): void
    {
        $kind = get_post_meta($post->ID, '_kepoli_post_kind', true);
        $kind = in_array($kind, ['recipe', 'article'], true) ? $kind : 'recipe';
        $seo_title = (string) get_post_meta($post->ID, '_kepoli_seo_title', true);
        $excerpt = (string) $post->post_excerpt;
        $meta_description = (string) get_post_meta($post->ID, '_kepoli_meta_description', true);
        $related_recipes = self::array_meta_to_text($post->ID, '_kepoli_related_recipe_slugs');
        $related_articles = self::array_meta_to_text($post->ID, '_kepoli_related_article_slugs');
        $recipe = self::recipe_data($post->ID);
        $image_meta = self::featured_image_meta($post->ID);
        $image_prompt = (string) get_post_meta($post->ID, '_kepoli_image_plan_prompt', true);

        wp_nonce_field('kepoli_author_tools_save', 'kepoli_author_tools_nonce');
        ?>
        <div class="kepoli-post-setup">
            <div class="kepoli-automation-actions">
                <button type="button" class="button" data-kepoli-generate-excerpt><?php esc_html_e('Genereaza excerpt', 'kepoli-author-tools'); ?></button>
                <button type="button" class="button" data-kepoli-generate-meta><?php esc_html_e('Genereaza meta description', 'kepoli-author-tools'); ?></button>
                <button type="button" class="button" data-kepoli-suggest-related><?php esc_html_e('Sugereaza linkuri interne', 'kepoli-author-tools'); ?></button>
                <button type="button" class="button" data-kepoli-generate-image-meta><?php esc_html_e('Genereaza meta imagine', 'kepoli-author-tools'); ?></button>
                <span class="kepoli-automation-actions__status" data-kepoli-automation-status></span>
            </div>

            <fieldset class="kepoli-post-setup__group">
                <legend><?php esc_html_e('Tip continut', 'kepoli-author-tools'); ?></legend>
                <label class="kepoli-choice">
                    <input type="radio" name="kepoli_post_kind" value="recipe" <?php checked($kind, 'recipe'); ?>>
                    <span><?php esc_html_e('Reteta', 'kepoli-author-tools'); ?></span>
                </label>
                <label class="kepoli-choice">
                    <input type="radio" name="kepoli_post_kind" value="article" <?php checked($kind, 'article'); ?>>
                    <span><?php esc_html_e('Articol', 'kepoli-author-tools'); ?></span>
                </label>
            </fieldset>

            <div class="kepoli-post-setup__grid">
                <label>
                    <span><?php esc_html_e('SEO title optional', 'kepoli-author-tools'); ?></span>
                    <input type="text" name="kepoli_seo_title" value="<?php echo esc_attr($seo_title); ?>" placeholder="<?php esc_attr_e('Daca ramane gol, se foloseste titlul postarii.', 'kepoli-author-tools'); ?>">
                </label>
                <label>
                    <span><?php esc_html_e('Excerpt', 'kepoli-author-tools'); ?></span>
                    <textarea name="kepoli_post_excerpt" rows="3" maxlength="260" placeholder="<?php esc_attr_e('Rezumat scurt pentru carduri, arhive si intro.', 'kepoli-author-tools'); ?>"><?php echo esc_textarea($excerpt); ?></textarea>
                </label>
            </div>

            <div class="kepoli-post-setup__grid kepoli-post-setup__grid--single">
                <label>
                    <span><?php esc_html_e('Meta description', 'kepoli-author-tools'); ?></span>
                    <textarea name="kepoli_meta_description" rows="3" maxlength="180" placeholder="<?php esc_attr_e('Rezumat scurt pentru Google si distribuire sociala.', 'kepoli-author-tools'); ?>"><?php echo esc_textarea($meta_description); ?></textarea>
                </label>
            </div>

            <div class="kepoli-post-setup__grid">
                <label>
                    <span><?php esc_html_e('Related recipe slugs', 'kepoli-author-tools'); ?></span>
                    <textarea name="kepoli_related_recipe_slugs" rows="3" placeholder="sarmale-in-foi-de-varza, ciorba-radauteana"><?php echo esc_textarea($related_recipes); ?></textarea>
                </label>
                <label>
                    <span><?php esc_html_e('Related article slugs', 'kepoli-author-tools'); ?></span>
                    <textarea name="kepoli_related_article_slugs" rows="3" placeholder="ghidul-camarii-romanesti"><?php echo esc_textarea($related_articles); ?></textarea>
                </label>
            </div>

            <div class="kepoli-image-fields">
                <h4><?php esc_html_e('Featured image metadata', 'kepoli-author-tools'); ?></h4>
                <p><?php esc_html_e('Completeaza aceste campuri pentru imaginea reprezentativa. La salvare, Kepoli le aplica pe featured image daca exista una selectata.', 'kepoli-author-tools'); ?></p>
                <?php if ($image_prompt !== '') : ?>
                    <label class="kepoli-post-setup__prompt">
                        <span><?php esc_html_e('Prompt imagine AI', 'kepoli-author-tools'); ?></span>
                        <textarea rows="4" readonly><?php echo esc_textarea($image_prompt); ?></textarea>
                    </label>
                <?php endif; ?>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php esc_html_e('Alt text', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_alt" value="<?php echo esc_attr($image_meta['alt']); ?>" placeholder="<?php esc_attr_e('Descriere scurta si precisa a imaginii.', 'kepoli-author-tools'); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Image title', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_title" value="<?php echo esc_attr($image_meta['title']); ?>" placeholder="<?php esc_attr_e('Titlu imagine in Media Library.', 'kepoli-author-tools'); ?>">
                    </label>
                </div>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php esc_html_e('Caption', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_caption" value="<?php echo esc_attr($image_meta['caption']); ?>" placeholder="<?php esc_attr_e('Text optional afisat/subtitrare imagine.', 'kepoli-author-tools'); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Description', 'kepoli-author-tools'); ?></span>
                        <textarea name="kepoli_image_description" rows="2" placeholder="<?php esc_attr_e('Descriere interna pentru Media Library.', 'kepoli-author-tools'); ?>"><?php echo esc_textarea($image_meta['description']); ?></textarea>
                    </label>
                </div>
            </div>

            <div class="kepoli-recipe-fields" data-kepoli-recipe-fields>
                <h4><?php esc_html_e('Recipe structured data', 'kepoli-author-tools'); ?></h4>
                <p><?php esc_html_e('Completeaza aceste campuri pentru retete noi. Ele alimenteaza schema Recipe folosita de Google.', 'kepoli-author-tools'); ?></p>
                <div class="kepoli-post-setup__grid kepoli-post-setup__grid--thirds">
                    <label>
                        <span><?php esc_html_e('Portii', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_recipe_servings" value="<?php echo esc_attr($recipe['servings']); ?>" placeholder="4 portii">
                    </label>
                    <label>
                        <span><?php esc_html_e('Pregatire minute', 'kepoli-author-tools'); ?></span>
                        <input type="number" min="0" step="1" name="kepoli_recipe_prep_minutes" value="<?php echo esc_attr($recipe['prep_minutes']); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Gatire minute', 'kepoli-author-tools'); ?></span>
                        <input type="number" min="0" step="1" name="kepoli_recipe_cook_minutes" value="<?php echo esc_attr($recipe['cook_minutes']); ?>">
                    </label>
                </div>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php esc_html_e('Ingrediente, cate unul pe linie', 'kepoli-author-tools'); ?></span>
                        <textarea name="kepoli_recipe_ingredients" rows="6"><?php echo esc_textarea(implode("\n", $recipe['ingredients'])); ?></textarea>
                    </label>
                    <label>
                        <span><?php esc_html_e('Pasi, cate unul pe linie', 'kepoli-author-tools'); ?></span>
                        <textarea name="kepoli_recipe_steps" rows="6"><?php echo esc_textarea(implode("\n", $recipe['steps'])); ?></textarea>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }

    public static function save_post_setup(int $post_id, WP_Post $post, bool $update): void
    {
        unset($update);

        if (self::$is_updating_post) {
            return;
        }

        if (!isset($_POST['kepoli_author_tools_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash((string) $_POST['kepoli_author_tools_nonce'])), 'kepoli_author_tools_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type !== 'post' || !current_user_can('edit_post', $post_id)) {
            return;
        }

        $kind = isset($_POST['kepoli_post_kind']) ? sanitize_key(wp_unslash((string) $_POST['kepoli_post_kind'])) : 'recipe';
        $kind = in_array($kind, ['recipe', 'article'], true) ? $kind : 'recipe';
        update_post_meta($post_id, '_kepoli_post_kind', $kind);

        self::save_post_excerpt($post_id, $post);
        self::save_text_meta($post_id, '_kepoli_seo_title', 'kepoli_seo_title', 70);
        self::save_meta_description($post_id, $post);

        $related_recipes = self::posted_slugs('kepoli_related_recipe_slugs');
        $related_articles = self::posted_slugs('kepoli_related_article_slugs');

        if (!$related_recipes && !$related_articles) {
            $suggested_related = self::suggest_related_slugs($post_id, $kind, $post);
            $related_recipes = $suggested_related['recipes'];
            $related_articles = $suggested_related['articles'];
        }

        update_post_meta($post_id, '_kepoli_related_recipe_slugs', $related_recipes);
        update_post_meta($post_id, '_kepoli_related_article_slugs', $related_articles);
        update_post_meta($post_id, '_kepoli_related_slugs', array_values(array_unique(array_merge($related_recipes, $related_articles))));

        if ($kind === 'recipe') {
            self::save_recipe_json($post_id);
        } else {
            delete_post_meta($post_id, '_kepoli_recipe_json');
        }

        self::save_featured_image_meta($post_id);
    }

    public static function add_post_list_columns(array $columns): array
    {
        $updated = [];

        foreach ($columns as $key => $label) {
            $updated[$key] = $label;

            if ($key === 'title') {
                $updated['kepoli_kind'] = __('Tip Kepoli', 'kepoli-author-tools');
                $updated['kepoli_readiness'] = __('Setup', 'kepoli-author-tools');
            }
        }

        return $updated;
    }

    public static function render_post_list_column(string $column, int $post_id): void
    {
        if ($column === 'kepoli_kind') {
            $kind = self::post_kind($post_id);
            $label = $kind === 'article' ? __('Articol', 'kepoli-author-tools') : __('Reteta', 'kepoli-author-tools');

            echo '<span class="kepoli-status-pill kepoli-status-pill--' . esc_attr($kind) . '">' . esc_html($label) . '</span>';
            return;
        }

        if ($column === 'kepoli_readiness') {
            $missing = self::post_missing_items($post_id);

            if (!$missing) {
                echo '<span class="kepoli-status-pill kepoli-status-pill--ready">' . esc_html__('Complet', 'kepoli-author-tools') . '</span>';
                return;
            }

            echo '<span class="kepoli-status-pill kepoli-status-pill--needs">' . esc_html__('De completat', 'kepoli-author-tools') . '</span>';
            echo '<span class="kepoli-admin-note">' . esc_html(implode(', ', $missing)) . '</span>';
        }
    }

    public static function render_post_kind_filter(string $post_type): void
    {
        if ($post_type !== 'post') {
            return;
        }

        $selected = isset($_GET['kepoli_post_kind_filter']) ? sanitize_key(wp_unslash((string) $_GET['kepoli_post_kind_filter'])) : '';
        ?>
        <label class="screen-reader-text" for="kepoli-post-kind-filter"><?php esc_html_e('Filtreaza dupa tip Kepoli', 'kepoli-author-tools'); ?></label>
        <select id="kepoli-post-kind-filter" name="kepoli_post_kind_filter">
            <option value=""><?php esc_html_e('Toate tipurile Kepoli', 'kepoli-author-tools'); ?></option>
            <option value="recipe" <?php selected($selected, 'recipe'); ?>><?php esc_html_e('Retete', 'kepoli-author-tools'); ?></option>
            <option value="article" <?php selected($selected, 'article'); ?>><?php esc_html_e('Articole', 'kepoli-author-tools'); ?></option>
        </select>
        <?php
    }

    public static function filter_posts_by_kind(WP_Query $query): void
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if ($post_type !== 'post' && $post_type !== '') {
            return;
        }

        $selected = isset($_GET['kepoli_post_kind_filter']) ? sanitize_key(wp_unslash((string) $_GET['kepoli_post_kind_filter'])) : '';
        if (!in_array($selected, ['recipe', 'article'], true)) {
            return;
        }

        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = [
            'key' => '_kepoli_post_kind',
            'value' => $selected,
            'compare' => '=',
        ];

        $query->set('meta_query', $meta_query);
    }

    public static function remove_template_prompts_from_content(string $content): string
    {
        if (is_admin() || $content === '') {
            return $content;
        }

        foreach (self::TEMPLATE_PROMPTS as $prompt) {
            $quoted = preg_quote($prompt, '/');
            $content = (string) preg_replace('/<p>\s*' . $quoted . '\s*<\/p>/iu', '', $content);
            $content = (string) preg_replace('/<li>\s*' . $quoted . '\s*<\/li>/iu', '', $content);
        }

        $content = (string) preg_replace('/<ul>\s*<\/ul>/i', '', $content);
        $content = (string) preg_replace('/<ol>\s*<\/ol>/i', '', $content);

        return $content;
    }

    public static function remove_template_prompts_from_excerpt(string $excerpt, ?WP_Post $post = null): string
    {
        if ($excerpt === '') {
            return $excerpt;
        }

        $clean = self::remove_template_prompt_text($excerpt);

        if (($clean === '' || self::word_count($clean) < 8) && $post instanceof WP_Post) {
            $clean = self::sentence_limit((string) $post->post_content, 220, 95);
        }

        return self::word_count($clean) < 8 ? '' : $clean;
    }

    private static function is_post_editor_screen(): bool
    {
        if (!is_admin()) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        if ($screen && $screen->post_type) {
            return $screen->post_type === 'post';
        }

        return self::current_post_type() === 'post';
    }

    private static function current_post_type(): string
    {
        $post_type = '';

        if (isset($_GET['post_type'])) {
            $post_type = sanitize_key(wp_unslash((string) $_GET['post_type']));
        }

        if ($post_type === '' && isset($_GET['post'])) {
            $post = get_post((int) $_GET['post']);
            $post_type = $post ? $post->post_type : '';
        }

        if ($post_type === '' && isset($GLOBALS['typenow'])) {
            $post_type = sanitize_key((string) $GLOBALS['typenow']);
        }

        return $post_type !== '' ? $post_type : 'post';
    }

    private static function current_post_id(): int
    {
        if (isset($_GET['post'])) {
            return absint(wp_unslash((string) $_GET['post']));
        }

        return 0;
    }

    private static function related_posts_payload(int $current_post_id): array
    {
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => 120,
            'post__not_in' => $current_post_id ? [$current_post_id] : [],
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        $items = [];
        foreach ($query->posts as $post_id) {
            $post_id = (int) $post_id;
            $categories = wp_get_post_categories($post_id, ['fields' => 'names']);
            $tags = wp_get_post_tags($post_id, ['fields' => 'names']);

            $items[] = [
                'id' => $post_id,
                'slug' => get_post_field('post_name', $post_id),
                'title' => get_the_title($post_id),
                'kind' => self::post_kind($post_id),
                'excerpt' => wp_strip_all_tags(get_the_excerpt($post_id)),
                'categories' => is_array($categories) ? array_values($categories) : [],
                'tags' => is_array($tags) ? array_values($tags) : [],
            ];
        }

        return $items;
    }

    private static function featured_image_meta(int $post_id): array
    {
        $planned = self::planned_image_meta($post_id);
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            return $planned;
        }

        $attachment = get_post($thumbnail_id);

        return [
            'alt' => (string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: $planned['alt'],
            'title' => ($attachment ? $attachment->post_title : '') ?: $planned['title'],
            'caption' => ($attachment ? $attachment->post_excerpt : '') ?: $planned['caption'],
            'description' => ($attachment ? $attachment->post_content : '') ?: $planned['description'],
        ];
    }

    private static function planned_image_meta(int $post_id): array
    {
        return [
            'alt' => (string) get_post_meta($post_id, '_kepoli_image_plan_alt', true),
            'title' => (string) get_post_meta($post_id, '_kepoli_image_plan_title', true),
            'caption' => (string) get_post_meta($post_id, '_kepoli_image_plan_caption', true),
            'description' => (string) get_post_meta($post_id, '_kepoli_image_plan_description', true),
        ];
    }

    private static function save_featured_image_meta(int $post_id): void
    {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id && isset($_POST['_thumbnail_id'])) {
            $posted_thumbnail_id = absint(wp_unslash((string) $_POST['_thumbnail_id']));
            $thumbnail_id = $posted_thumbnail_id > 0 ? $posted_thumbnail_id : 0;
        }

        if (!$thumbnail_id) {
            return;
        }

        $existing = self::attachment_image_meta($thumbnail_id);
        $generated = self::generated_image_meta($post_id);
        $alt = isset($_POST['kepoli_image_alt']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_alt'])) : '';
        $title = isset($_POST['kepoli_image_title']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_title'])) : '';
        $caption = isset($_POST['kepoli_image_caption']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_caption'])) : '';
        $description = isset($_POST['kepoli_image_description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_image_description'])) : '';

        $alt = $alt !== '' ? $alt : ($existing['alt'] !== '' ? $existing['alt'] : $generated['alt']);
        $title = $title !== '' ? $title : ($existing['title'] !== '' ? $existing['title'] : $generated['title']);
        $caption = $caption !== '' ? $caption : ($existing['caption'] !== '' ? $existing['caption'] : $generated['caption']);
        $description = $description !== '' ? $description : ($existing['description'] !== '' ? $existing['description'] : $generated['description']);

        if ($alt !== '') {
            update_post_meta($thumbnail_id, '_wp_attachment_image_alt', self::limit_text($alt, 160));
        }

        $attachment_update = ['ID' => $thumbnail_id];
        if ($title !== '') {
            $attachment_update['post_title'] = self::limit_text($title, 90);
        }
        if ($caption !== '') {
            $attachment_update['post_excerpt'] = self::limit_text($caption, 180);
        }
        if ($description !== '') {
            $attachment_update['post_content'] = self::limit_text($description, 320);
        }

        if (count($attachment_update) > 1) {
            wp_update_post(wp_slash($attachment_update), true);
        }
    }

    private static function attachment_image_meta(int $attachment_id): array
    {
        $attachment = get_post($attachment_id);

        return [
            'alt' => (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'title' => $attachment ? $attachment->post_title : '',
            'caption' => $attachment ? $attachment->post_excerpt : '',
            'description' => $attachment ? $attachment->post_content : '',
        ];
    }

    private static function generated_image_meta(int $post_id): array
    {
        $post = get_post($post_id);
        $title = $post ? trim((string) $post->post_title) : '';
        $title = $title !== '' ? $title : __('Reteta Kepoli', 'kepoli-author-tools');
        $kind = self::post_kind($post_id);
        $prefix = $kind === 'article' ? __('Imagine editoriala pentru', 'kepoli-author-tools') : __('Fotografie culinara pentru', 'kepoli-author-tools');

        return [
            'alt' => self::sentence_limit($prefix . ' ' . $title . ', publicata pe blogul romanesc Kepoli.', 150),
            'title' => self::limit_text($title, 90),
            'caption' => self::sentence_limit($title . ' pe Kepoli.', 120),
            'description' => self::sentence_limit('Imagine reprezentativa pentru ' . $title . ', folosita in articolul culinar Kepoli.', 220),
        ];
    }

    private static function post_kind(int $post_id): string
    {
        $kind = (string) get_post_meta($post_id, '_kepoli_post_kind', true);
        return in_array($kind, ['recipe', 'article'], true) ? $kind : 'recipe';
    }

    private static function post_missing_items(int $post_id): array
    {
        $missing = [];
        $kind = self::post_kind($post_id);
        $related_recipes = get_post_meta($post_id, '_kepoli_related_recipe_slugs', true);
        $related_articles = get_post_meta($post_id, '_kepoli_related_article_slugs', true);
        $related_count = (is_array($related_recipes) ? count($related_recipes) : 0) + (is_array($related_articles) ? count($related_articles) : 0);

        if ((string) get_post_meta($post_id, '_kepoli_meta_description', true) === '') {
            $missing[] = __('meta', 'kepoli-author-tools');
        }

        if (!has_excerpt($post_id)) {
            $missing[] = __('excerpt', 'kepoli-author-tools');
        }

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            $missing[] = __('imagine', 'kepoli-author-tools');
        } elseif ((string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) === '') {
            $missing[] = __('image meta', 'kepoli-author-tools');
        }

        if ($related_count === 0) {
            $missing[] = __('linkuri', 'kepoli-author-tools');
        }

        if ($kind === 'recipe') {
            $recipe = self::recipe_data($post_id);
            if (!$recipe['ingredients'] || !$recipe['steps'] || $recipe['servings'] === '') {
                $missing[] = __('schema reteta', 'kepoli-author-tools');
            }
        }

        return $missing;
    }

    private static function array_meta_to_text(int $post_id, string $key): string
    {
        $value = get_post_meta($post_id, $key, true);
        return is_array($value) ? implode(', ', array_map('sanitize_title', $value)) : '';
    }

    private static function recipe_data(int $post_id): array
    {
        $json = (string) get_post_meta($post_id, '_kepoli_recipe_json', true);
        $data = json_decode($json, true);
        $data = is_array($data) ? $data : [];

        return [
            'servings' => isset($data['servings']) ? (string) $data['servings'] : '',
            'prep_minutes' => self::iso_to_minutes((string) ($data['prep_iso'] ?? '')),
            'cook_minutes' => self::iso_to_minutes((string) ($data['cook_iso'] ?? '')),
            'ingredients' => isset($data['ingredients']) && is_array($data['ingredients']) ? $data['ingredients'] : [],
            'steps' => isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : [],
        ];
    }

    private static function save_meta_description(int $post_id, WP_Post $post): void
    {
        $value = isset($_POST['kepoli_meta_description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_meta_description'])) : '';
        $value = self::remove_template_prompt_text($value);
        $value = $value !== '' ? $value : self::generate_meta_description($post);
        $value = self::limit_text(self::plain_text($value), 180);
        if (self::word_count($value) < 8) {
            $value = self::limit_text(self::plain_text(self::generate_meta_description($post)), 180);
        }

        if ($value === '') {
            delete_post_meta($post_id, '_kepoli_meta_description');
            return;
        }

        update_post_meta($post_id, '_kepoli_meta_description', $value);
    }

    private static function save_post_excerpt(int $post_id, WP_Post $post): void
    {
        $value = isset($_POST['kepoli_post_excerpt']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_post_excerpt'])) : '';
        $value = self::remove_template_prompt_text($value);
        $value = $value !== '' ? $value : trim((string) $post->post_excerpt);
        $value = self::remove_template_prompt_text($value);
        $value = $value !== '' ? $value : self::generate_post_excerpt($post);
        $value = self::limit_text(self::plain_text($value), 260);
        if (self::word_count($value) < 8) {
            $value = self::limit_text(self::plain_text(self::generate_post_excerpt($post)), 260);
        }

        if ($value === '' || $value === (string) $post->post_excerpt) {
            return;
        }

        self::$is_updating_post = true;
        wp_update_post([
            'ID' => $post_id,
            'post_excerpt' => $value,
        ]);
        self::$is_updating_post = false;
        $post->post_excerpt = $value;
    }

    private static function generate_post_excerpt(WP_Post $post): string
    {
        $source = self::remove_template_prompt_text(trim((string) $post->post_excerpt));

        if ($source === '') {
            $source = self::remove_template_prompt_text(trim((string) $post->post_content));
        }

        if ($source === '') {
            $source = trim((string) $post->post_title);
        }

        return self::sentence_limit($source, 220, 95);
    }

    private static function generate_meta_description(WP_Post $post): string
    {
        $source = self::remove_template_prompt_text(trim((string) $post->post_excerpt));

        if ($source === '') {
            $source = self::remove_template_prompt_text(trim((string) $post->post_content));
        }

        if ($source === '') {
            $source = trim((string) $post->post_title);
        }

        return self::sentence_limit($source, 155);
    }

    private static function suggest_related_slugs(int $post_id, string $kind, WP_Post $post): array
    {
        $source_words = self::keywords_from_text(implode(' ', [
            $post->post_title,
            $post->post_excerpt,
            $post->post_content,
            self::primary_category_name($post_id),
        ]));

        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => 120,
            'post__not_in' => $post_id ? [$post_id] : [],
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        $candidates = [];
        foreach ($query->posts as $index => $candidate_id) {
            $candidate_id = (int) $candidate_id;
            $slug = (string) get_post_field('post_name', $candidate_id);

            if ($slug === '') {
                continue;
            }

            $candidates[] = [
                'index' => $index,
                'kind' => self::post_kind($candidate_id),
                'slug' => $slug,
                'score' => self::score_related_candidate($candidate_id, $source_words),
                'title' => (string) get_the_title($candidate_id),
            ];
        }

        usort($candidates, static function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            $title_compare = strcasecmp($a['title'], $b['title']);
            return $title_compare !== 0 ? $title_compare : ($a['index'] <=> $b['index']);
        });

        $recipe_limit = $kind === 'article' ? 5 : 3;
        $article_limit = $kind === 'article' ? 2 : 1;
        $recipes = [];
        $articles = [];

        foreach ($candidates as $candidate) {
            if ($candidate['kind'] === 'article') {
                if (count($articles) < $article_limit) {
                    $articles[] = $candidate['slug'];
                }
            } elseif (count($recipes) < $recipe_limit) {
                $recipes[] = $candidate['slug'];
            }

            if (count($recipes) >= $recipe_limit && count($articles) >= $article_limit) {
                break;
            }
        }

        return [
            'recipes' => $recipes,
            'articles' => $articles,
        ];
    }

    private static function score_related_candidate(int $post_id, array $source_words): int
    {
        if (!$source_words) {
            return 0;
        }

        $candidate_words = self::keywords_from_text(self::related_candidate_text($post_id));
        $candidate_lookup = array_flip($candidate_words);
        $score = 0;

        foreach ($source_words as $word) {
            if (isset($candidate_lookup[$word])) {
                $score += 3;
                continue;
            }

            foreach ($candidate_words as $candidate_word) {
                if (strpos($candidate_word, $word) !== false || strpos($word, $candidate_word) !== false) {
                    $score += 1;
                    break;
                }
            }
        }

        return $score;
    }

    private static function related_candidate_text(int $post_id): string
    {
        $categories = wp_get_post_categories($post_id, ['fields' => 'names']);
        $tags = wp_get_post_tags($post_id, ['fields' => 'names']);

        return implode(' ', [
            get_the_title($post_id),
            get_post_field('post_excerpt', $post_id),
            get_post_field('post_content', $post_id),
            is_array($categories) ? implode(' ', $categories) : '',
            is_array($tags) ? implode(' ', $tags) : '',
        ]);
    }

    private static function save_text_meta(int $post_id, string $meta_key, string $field, int $max_length): void
    {
        $value = isset($_POST[$field]) ? sanitize_text_field(wp_unslash((string) $_POST[$field])) : '';
        $value = self::limit_text($value, $max_length);

        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        update_post_meta($post_id, $meta_key, $value);
    }

    private static function keywords_from_text(string $text): array
    {
        $stopwords = [
            'aceasta', 'aceste', 'acest', 'acolo', 'acasa', 'adauga', 'aici', 'ale', 'are', 'asta',
            'care', 'cand', 'cele', 'celor', 'chiar', 'cum', 'daca', 'deja', 'despre', 'din', 'dupa',
            'este', 'fara', 'fiecare', 'foarte', 'intr', 'intre', 'mai', 'mult', 'pentru', 'peste',
            'poate', 'prin', 'reteta', 'retete', 'romanesc', 'romaneasca', 'romanesti', 'sau', 'sunt',
            'toate', 'unui', 'unei', 'unde', 'kepoli', 'the', 'and', 'with', 'from',
        ];
        $stopword_lookup = array_flip($stopwords);
        $plain = self::plain_text($text);
        $plain = function_exists('mb_strtolower') ? mb_strtolower($plain, 'UTF-8') : strtolower($plain);
        $plain = remove_accents($plain);
        $plain = preg_replace('/[^a-z0-9\s-]/', ' ', $plain);
        $parts = preg_split('/\s+/', (string) $plain) ?: [];
        $words = [];

        foreach ($parts as $part) {
            $word = trim($part, "- \t\n\r\0\x0B");
            if (strlen($word) > 3 && !isset($stopword_lookup[$word])) {
                $words[] = $word;
            }
        }

        return array_values(array_unique($words));
    }

    private static function plain_text(string $text): string
    {
        $text = self::remove_template_prompt_text($text);
        $text = strip_shortcodes($text);
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }

    private static function remove_template_prompt_text(string $text): string
    {
        if ($text === '') {
            return '';
        }

        foreach (self::TEMPLATE_PROMPTS as $prompt) {
            $text = str_replace($prompt, '', $text);
        }

        foreach (self::TEMPLATE_OUTLINE_LABELS as $label) {
            $quoted = preg_quote($label, '/');
            $text = (string) preg_replace('/<h[23][^>]*>\s*' . $quoted . '\s*<\/h[23]>/iu', ' ', $text);
        }

        $labels = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), self::TEMPLATE_OUTLINE_LABELS));
        $text = (string) preg_replace('/\b(?:' . $labels . ')\b(?=(?:\s+(?:' . $labels . ')\b)|\s*$)/iu', ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', $text));
    }

    private static function word_count(string $text): int
    {
        $plain = self::plain_text($text);
        if ($plain === '') {
            return 0;
        }

        $words = preg_split('/\s+/', $plain) ?: [];
        return count(array_filter($words));
    }

    private static function posted_slugs(string $field): array
    {
        $value = isset($_POST[$field]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$field])) : '';
        $parts = preg_split('/[\s,]+/', $value) ?: [];
        $slugs = [];

        foreach ($parts as $part) {
            $slug = sanitize_title($part);
            if ($slug !== '') {
                $slugs[] = $slug;
            }
        }

        return array_values(array_unique($slugs));
    }

    private static function limit_text(string $value, int $max_length): string
    {
        return function_exists('mb_substr') ? mb_substr($value, 0, $max_length) : substr($value, 0, $max_length);
    }

    private static function sentence_limit(string $value, int $max_length, int $min_length = 80): string
    {
        $value = self::plain_text($value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

        if ($length <= $max_length) {
            return $value;
        }

        $slice = self::limit_text($value, $max_length + 1);
        $sentence_end = -1;

        foreach (['.', '!', '?'] as $mark) {
            $position = function_exists('mb_strrpos') ? mb_strrpos($slice, $mark, 0, 'UTF-8') : strrpos($slice, $mark);
            if ($position !== false) {
                $sentence_end = max($sentence_end, $position);
            }
        }

        $word_end = function_exists('mb_strrpos') ? mb_strrpos($slice, ' ', 0, 'UTF-8') : strrpos($slice, ' ');
        if ($sentence_end > $min_length) {
            $end = $sentence_end + 1;
        } elseif ($word_end !== false && $word_end > $min_length) {
            $end = $word_end;
        } else {
            $end = $max_length;
        }

        return rtrim(self::limit_text($slice, $end), " \t\n\r\0\x0B,;:") . '...';
    }

    private static function posted_lines(string $field): array
    {
        $value = isset($_POST[$field]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$field])) : '';
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $clean[] = $line;
            }
        }

        return $clean;
    }

    private static function save_recipe_json(int $post_id): void
    {
        $ingredients = self::posted_lines('kepoli_recipe_ingredients');
        $steps = self::posted_lines('kepoli_recipe_steps');
        $servings = isset($_POST['kepoli_recipe_servings']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_recipe_servings'])) : '';
        $prep_minutes = isset($_POST['kepoli_recipe_prep_minutes']) ? absint(wp_unslash((string) $_POST['kepoli_recipe_prep_minutes'])) : 0;
        $cook_minutes = isset($_POST['kepoli_recipe_cook_minutes']) ? absint(wp_unslash((string) $_POST['kepoli_recipe_cook_minutes'])) : 0;

        if (!$ingredients && !$steps && $servings === '') {
            delete_post_meta($post_id, '_kepoli_recipe_json');
            return;
        }

        update_post_meta($post_id, '_kepoli_recipe_json', wp_json_encode([
            'category' => self::primary_category_name($post_id),
            'servings' => $servings,
            'prep_iso' => self::minutes_to_iso($prep_minutes),
            'cook_iso' => self::minutes_to_iso($cook_minutes),
            'total_iso' => self::minutes_to_iso($prep_minutes + $cook_minutes),
            'ingredients' => $ingredients,
            'steps' => $steps,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private static function primary_category_name(int $post_id): string
    {
        $categories = get_the_category($post_id);
        return !empty($categories) ? $categories[0]->name : 'Retete romanesti';
    }

    private static function minutes_to_iso(int $minutes): string
    {
        $minutes = max(0, $minutes);
        if ($minutes === 0) {
            return 'PT0M';
        }

        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        $duration = 'PT';

        if ($hours > 0) {
            $duration .= $hours . 'H';
        }

        if ($remaining > 0) {
            $duration .= $remaining . 'M';
        }

        return $duration;
    }

    private static function iso_to_minutes(string $duration): int
    {
        if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/', $duration, $matches)) {
            return 0;
        }

        $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
        $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;

        return ($hours * 60) + $minutes;
    }
}

Kepoli_Author_Tools::init();
