<?php
/**
 * Plugin Name: Kepoli Author Tools
 * Description: Simplifies the post editor for Kepoli authors and adds toolbar tools for splitting long posts into two or three pages.
 * Version: 1.1.0
 * Author: Kepoli
 * Text Domain: kepoli-author-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Kepoli_Author_Tools
{
    private const VERSION = '1.1.0';

    public static function init(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'use_classic_editor_for_posts'], 10, 2);
        add_filter('mce_external_plugins', [self::class, 'register_tinymce_plugin']);
        add_filter('mce_buttons', [self::class, 'register_tinymce_buttons']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('add_meta_boxes_post', [self::class, 'add_writer_guide_box']);
        add_action('add_meta_boxes_post', [self::class, 'add_post_setup_box']);
        add_action('save_post_post', [self::class, 'save_post_setup'], 10, 3);
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
        if (!in_array($hook, ['post.php', 'post-new.php'], true) || self::current_post_type() !== 'post') {
            return;
        }

        wp_enqueue_style(
            'kepoli-author-tools-admin',
            plugins_url('assets/admin.css', __FILE__),
            [],
            self::VERSION
        );

        wp_enqueue_script(
            'kepoli-author-tools-admin',
            plugins_url('assets/admin.js', __FILE__),
            ['quicktags'],
            self::VERSION,
            true
        );
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
        $meta_description = (string) get_post_meta($post->ID, '_kepoli_meta_description', true);
        $related_recipes = self::array_meta_to_text($post->ID, '_kepoli_related_recipe_slugs');
        $related_articles = self::array_meta_to_text($post->ID, '_kepoli_related_article_slugs');
        $recipe = self::recipe_data($post->ID);

        wp_nonce_field('kepoli_author_tools_save', 'kepoli_author_tools_nonce');
        ?>
        <div class="kepoli-post-setup">
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

        self::save_text_meta($post_id, '_kepoli_seo_title', 'kepoli_seo_title', 70);
        self::save_textarea_meta($post_id, '_kepoli_meta_description', 'kepoli_meta_description', 180);

        $related_recipes = self::posted_slugs('kepoli_related_recipe_slugs');
        $related_articles = self::posted_slugs('kepoli_related_article_slugs');
        update_post_meta($post_id, '_kepoli_related_recipe_slugs', $related_recipes);
        update_post_meta($post_id, '_kepoli_related_article_slugs', $related_articles);
        update_post_meta($post_id, '_kepoli_related_slugs', array_values(array_unique(array_merge($related_recipes, $related_articles))));

        if ($kind === 'recipe') {
            self::save_recipe_json($post_id);
            return;
        }

        delete_post_meta($post_id, '_kepoli_recipe_json');
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

    private static function save_textarea_meta(int $post_id, string $meta_key, string $field, int $max_length): void
    {
        $value = isset($_POST[$field]) ? sanitize_textarea_field(wp_unslash((string) $_POST[$field])) : '';
        $value = self::limit_text($value, $max_length);

        if ($value === '') {
            delete_post_meta($post_id, $meta_key);
            return;
        }

        update_post_meta($post_id, $meta_key, $value);
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
