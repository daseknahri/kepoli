<?php
/**
 * Plugin Name: Food Blog Author Tools
 * Description: Simplifies the post editor with split tools, excerpt and SEO helpers, internal-link suggestions, and featured-image metadata.
 * Version: 1.8.25
 * Author: Site tools
 * Text Domain: kepoli-author-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Kepoli_Author_Tools
{
    private const VERSION = '1.8.25';
    private const AUTO_INTERNAL_LINKS_START = '<!-- kepoli-auto-internal-links:start -->';
    private const AUTO_INTERNAL_LINKS_END = '<!-- kepoli-auto-internal-links:end -->';
    private const AUTO_FAQ_START = '<!-- kepoli-auto-faq:start -->';
    private const AUTO_FAQ_END = '<!-- kepoli-auto-faq:end -->';
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
        'Adauga linkuri interne catre retete sau ghiduri apropiate.',
        'Write 2-3 sentences about the result, occasion, and texture.',
        'Add ingredients in a list, one per line.',
        'Add the steps in order, with time, temperature, and visual signs where useful.',
        'Note mistakes to avoid, adjustments, and useful variations.',
        'Explain storage, reheating, and safe consumption.',
        'Answer practically, with realistic time ranges.',
        'Introduce the topic and tell the reader what they will learn.',
        'Explain the important points in short paragraphs with concrete examples.',
        'Connect the advice to recipes, ingredients, or home cooking habits.',
        'Add internal links to nearby recipes or guides.',
    ];
    private const TEMPLATE_OUTLINE_LABELS = [
        'Pe scurt',
        'Detalii despre reteta',
        'Ingrediente',
        'Mod de preparare',
        'Cum se serveste',
        'Sfaturi pentru o reteta reusita',
        'Sfaturi pentru reusita',
        'Variatii ale retetei',
        'Cum se pastreaza',
        'Cum pastrezi',
        'Intrebari frecvente',
        'Concluzie',
        'Pot pregati reteta in avans?',
        'Ideea principala',
        'Ce merita retinut',
        'Cum aplici in bucatarie',
        'Legaturi utile',
        'What to know first',
        'Recipe details',
        'Ingredients',
        'Method',
        'How to serve it',
        'Success notes',
        'Variations',
        'Storage',
        'Frequently asked questions',
        'Conclusion',
        'Can I prepare this recipe ahead?',
        'Main idea',
        'What to remember',
        'How to use it in the kitchen',
        'Useful links',
    ];
    private static $is_updating_post = false;

    private static function site_profile(): array
    {
        static $profile = null;

        if ($profile !== null) {
            return $profile;
        }

        $public_locale = (string) get_option('WPLANG');
        if ($public_locale === '') {
            $public_locale = 'ro_RO';
        }

        $default = [
            'brand' => [
                'name' => get_bloginfo('name') ?: 'Food Blog',
                'site_email' => get_option('admin_email') ?: 'contact@example.com',
            ],
            'locales' => [
                'public' => $public_locale,
                'admin' => 'en_US',
                'force_admin' => true,
            ],
            'writer' => [
                'name' => '',
                'email' => '',
                'bio' => '',
            ],
            'slugs' => [],
        ];

        $stored = get_option('kepoli_site_profile');
        $profile = array_replace_recursive($default, is_array($stored) ? $stored : []);
        $profile['locales']['admin'] = 'en_US';
        $profile['locales']['force_admin'] = true;

        return $profile;
    }

    private static function profile_value(array $path, $default = '')
    {
        $value = self::site_profile();
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }

            $value = $value[$key];
        }

        return $value;
    }

    private static function public_locale(): string
    {
        $locale = trim((string) self::profile_value(['locales', 'public'], get_option('WPLANG') ?: 'ro_RO'));
        return $locale !== '' ? $locale : 'ro_RO';
    }

    private static function admin_locale(): string
    {
        $locale = trim((string) self::profile_value(['locales', 'admin'], 'en_US'));
        return $locale !== '' ? $locale : 'en_US';
    }

    private static function locale_is_english(string $locale): bool
    {
        return str_starts_with(strtolower($locale), 'en');
    }

    private static function public_is_english(): bool
    {
        return self::locale_is_english(self::public_locale());
    }

    private static function admin_is_english(): bool
    {
        return self::locale_is_english(self::admin_locale());
    }

    private static function is_english(): bool
    {
        return self::public_is_english();
    }

    private static function admin_ui_text(string $ro, string $en): string
    {
        return self::admin_is_english() ? $en : $ro;
    }

    private static function public_content_text(string $ro, string $en): string
    {
        return self::public_is_english() ? $en : $ro;
    }

    private static function ui_text(string $ro, string $en): string
    {
        return self::admin_ui_text($ro, $en);
    }

    private static function content_text(string $ro, string $en): string
    {
        return self::public_content_text($ro, $en);
    }

    private static function content_text_for_locale(bool $is_english, string $ro, string $en): string
    {
        return $is_english ? $en : $ro;
    }

    private static function site_name(): string
    {
        $name = trim((string) self::profile_value(['brand', 'name'], ''));
        return $name !== '' ? $name : (get_bloginfo('name') ?: 'Food Blog');
    }

    private static function guides_slug(): string
    {
        $slug = sanitize_title((string) self::profile_value(['slugs', 'guides'], ''));
        if ($slug !== '') {
            return $slug;
        }

        return self::public_is_english() ? 'guides' : 'articole';
    }

    private static function article_category_slugs(): array
    {
        return array_values(array_unique(array_filter([
            self::guides_slug(),
            'articole',
            'guides',
            'articles',
        ])));
    }

    public static function init(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'use_classic_editor_for_posts'], 10, 2);
        add_filter('mce_external_plugins', [self::class, 'register_tinymce_plugin']);
        add_filter('mce_buttons', [self::class, 'register_tinymce_buttons']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('add_meta_boxes_post', [self::class, 'add_publish_companion_box']);
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
                'siteName' => self::site_name(),
                'isEnglish' => self::public_is_english(),
                'adminIsEnglish' => self::admin_is_english(),
                'publicIsEnglish' => self::public_is_english(),
                'guidesSlug' => self::guides_slug(),
                'relatedPosts' => self::related_posts_payload(self::current_post_id()),
                'categories' => self::category_payload(),
                'strings' => [
                    'checkReady' => self::ui_text('Setup aproape complet. Mai verifica naturaletea textului inainte de publicare.', 'Setup is almost complete. Review the text once before publishing.'),
                    'checkMissingPrefix' => self::ui_text('De completat inainte de publicare:', 'Complete before publishing:'),
                    'publishConfirmPrefix' => self::ui_text('Postarea mai are campuri lipsa:', 'The post still has missing fields:'),
                    'publishConfirmSuffix' => self::ui_text('Continui totusi publicarea?', 'Publish anyway?'),
                    'companionReady' => self::ui_text('Postarea arata bine pentru urmatorul pas. Fa doar o ultima lectura inainte de publicare.', 'The post looks ready for the next step. Give it one final read before publishing.'),
                    'companionReview' => self::ui_text('Mai sunt cateva lucruri de verificat inainte sa publici.', 'A few things still need review before publishing.'),
                    'companionStatusReady' => self::ui_text('Gata pentru o ultima lectura.', 'Ready for a final read.'),
                    'companionStatusSingle' => self::ui_text('Mai lipseste 1 lucru important.', '1 important item is still missing.'),
                    'companionStatusMultiple' => self::ui_text('Mai lipsesc %d lucruri importante.', '%d important items are still missing.'),
                    'companionNoCategory' => self::ui_text('Nicio sugestie clara inca', 'No clear suggestion yet'),
                    'companionNoTags' => self::ui_text('Fara taguri sugerate inca', 'No suggested tags yet'),
                    'defaultSlugHint' => self::ui_text('Slugul se va curata automat la salvare.', 'The slug will be cleaned automatically on save.'),
                ],
            ]);
        }
    }

    public static function add_publish_companion_box(): void
    {
        add_meta_box(
            'kepoli-publish-companion',
            self::ui_text('Asistent publicare', 'Publish helper'),
            [self::class, 'render_publish_companion_box'],
            'post',
            'side',
            'high'
        );
    }

    public static function render_publish_companion_box(): void
    {
        ?>
        <div class="kepoli-publish-companion" data-kepoli-publish-companion>
            <p class="kepoli-publish-companion__intro"><?php echo esc_html(self::ui_text('Cand esti aproape gata, foloseste acest buton pentru completarea automata finala.', 'When the post is almost ready, use this button for the final automatic setup.')); ?></p>
            <div class="kepoli-publish-companion__actions">
                <button type="button" class="button button-primary" data-kepoli-companion-complete><?php echo esc_html(self::ui_text('Pregateste pentru publicare', 'Prepare for publishing')); ?></button>
                <p class="kepoli-publish-companion__status" data-kepoli-companion-status></p>
            </div>
            <p class="kepoli-publish-companion__summary" data-kepoli-companion-summary></p>
            <details class="kepoli-publish-companion__details">
                <summary><?php echo esc_html(self::ui_text('Vezi detalii', 'View details')); ?></summary>
                <div class="kepoli-publish-companion__block">
                    <span class="kepoli-publish-companion__label"><?php echo esc_html(self::ui_text('Categoria sugerata', 'Suggested category')); ?></span>
                    <strong data-kepoli-companion-category><?php echo esc_html(self::ui_text('Se calculeaza...', 'Calculating...')); ?></strong>
                </div>
                <div class="kepoli-publish-companion__block">
                    <span class="kepoli-publish-companion__label"><?php echo esc_html(self::ui_text('Taguri sugerate', 'Suggested tags')); ?></span>
                    <p data-kepoli-companion-tags><?php echo esc_html(self::ui_text('Se calculeaza...', 'Calculating...')); ?></p>
                </div>
                <div class="kepoli-publish-companion__block">
                    <span class="kepoli-publish-companion__label"><?php echo esc_html(self::ui_text('Mai verifica', 'Review')); ?></span>
                    <ul class="kepoli-publish-companion__checks" data-kepoli-companion-checks></ul>
                </div>
            </details>
        </div>
        <?php
    }

    public static function add_writer_guide_box(): void
    {
        add_meta_box(
            'kepoli-author-guide',
            self::ui_text('Unelte de scriere', 'Writing tools'),
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
            <p class="kepoli-author-guide__intro"><strong><?php echo esc_html(self::ui_text('Porneste rapid cu o structura gata facuta.', 'Start quickly with a ready structure.')); ?></strong></p>
            <div class="kepoli-template-actions">
                <button type="button" class="button" data-kepoli-template="recipe"><?php echo esc_html(self::ui_text('Structura reteta', 'Recipe structure')); ?></button>
                <button type="button" class="button" data-kepoli-template="article"><?php echo esc_html(self::ui_text('Structura articol', 'Article structure')); ?></button>
            </div>
            <p class="kepoli-author-guide__note"><?php echo esc_html(self::ui_text('Pentru articole lungi, foloseste `Pauza`, `2 parti` sau `3 parti` din toolbar.', 'For long posts, use `Break`, `2 parts`, or `3 parts` in the toolbar.')); ?></p>
        </div>
        <?php
    }

    public static function add_post_setup_box(): void
    {
        add_meta_box(
            'kepoli-post-setup',
            self::ui_text('Setup postare', 'Post setup'),
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
        $auto_split_parts = (int) get_post_meta($post->ID, '_kepoli_auto_split_parts', true);
        $auto_split_parts = in_array($auto_split_parts, [2, 3], true) ? $auto_split_parts : 0;
        $recipe = self::recipe_data($post->ID);
        $image_meta = self::featured_image_meta($post->ID);
        $has_image_meta = array_filter($image_meta, static function ($value): bool {
            return trim((string) $value) !== '';
        });
        $has_seo_details = trim($seo_title) !== '' || trim($meta_description) !== '' || trim($related_recipes) !== '' || trim($related_articles) !== '';

        wp_nonce_field('kepoli_author_tools_save', 'kepoli_author_tools_nonce');
        ?>
        <div class="kepoli-post-setup">
            <div class="kepoli-automation-actions kepoli-automation-actions--primary">
                <button type="button" class="button button-primary" data-kepoli-complete-setup><?php echo esc_html(self::ui_text('Completeaza automat', 'Auto fill')); ?></button>
                <span class="kepoli-automation-actions__status" data-kepoli-automation-status></span>
            </div>
            <p class="kepoli-automation-actions__note"><?php echo esc_html(self::ui_text('Acesta este butonul principal pentru lucru rapid. Site-ul incearca sa completeze campurile goale si iti lasa doar verificarea finala.', 'This is the main quick-work button. It fills empty fields where possible and leaves only the final review.')); ?></p>
            <details class="kepoli-automation-more">
                <summary><?php echo esc_html(self::ui_text('Mai multe unelte', 'More tools')); ?></summary>
                <div class="kepoli-automation-actions kepoli-automation-actions--secondary">
                    <button type="button" class="button" data-kepoli-suggest-category><?php echo esc_html(self::ui_text('Sugereaza categorie', 'Suggest category')); ?></button>
                    <button type="button" class="button" data-kepoli-suggest-tags><?php echo esc_html(self::ui_text('Sugereaza taguri', 'Suggest tags')); ?></button>
                    <button type="button" class="button" data-kepoli-extract-recipe><?php echo esc_html(self::ui_text('Extrage schema reteta', 'Extract recipe schema')); ?></button>
                    <button type="button" class="button" data-kepoli-generate-excerpt><?php echo esc_html(self::ui_text('Genereaza excerpt', 'Generate excerpt')); ?></button>
                    <button type="button" class="button" data-kepoli-generate-meta><?php echo esc_html(self::ui_text('Genereaza meta description', 'Generate meta description')); ?></button>
                    <button type="button" class="button" data-kepoli-suggest-related><?php echo esc_html(self::ui_text('Sugereaza linkuri interne', 'Suggest internal links')); ?></button>
                    <button type="button" class="button" data-kepoli-generate-image-meta><?php echo esc_html(self::ui_text('Genereaza meta imagine', 'Generate image metadata')); ?></button>
                </div>
                <p class="kepoli-automation-actions__note"><?php echo esc_html(self::ui_text('Pentru retete, Extrage schema reteta citeste ingredientele si pasii din continut daca folosesti structura pregatita.', 'For recipes, Extract recipe schema reads ingredients and steps from the post if you use the prepared structure.')); ?></p>
            </details>

            <fieldset class="kepoli-post-setup__group">
                <legend><?php echo esc_html(self::ui_text('Tip continut', 'Content type')); ?></legend>
                <label class="kepoli-choice">
                    <input type="radio" name="kepoli_post_kind" value="recipe" <?php checked($kind, 'recipe'); ?>>
                    <span><?php echo esc_html(self::ui_text('Reteta', 'Recipe')); ?></span>
                </label>
                <label class="kepoli-choice">
                    <input type="radio" name="kepoli_post_kind" value="article" <?php checked($kind, 'article'); ?>>
                    <span><?php echo esc_html(self::ui_text('Articol', 'Article')); ?></span>
                </label>
            </fieldset>

            <div class="kepoli-post-setup__grid kepoli-post-setup__grid--single">
                <label>
                    <span><?php esc_html_e('Excerpt', 'kepoli-author-tools'); ?></span>
                    <textarea name="kepoli_post_excerpt" rows="3" maxlength="260" placeholder="<?php echo esc_attr(self::ui_text('Rezumat scurt pentru carduri, arhive si intro.', 'Short summary for cards, archives, and the post intro.')); ?>"><?php echo esc_textarea($excerpt); ?></textarea>
                </label>
            </div>

            <div class="kepoli-post-setup__grid kepoli-post-setup__grid--single">
                <label>
                    <span><?php echo esc_html(self::ui_text('Impartire automata', 'Automatic split')); ?></span>
                    <select name="kepoli_auto_split_parts">
                        <option value="0" <?php selected($auto_split_parts, 0); ?>><?php echo esc_html(self::ui_text('Fara impartire automata', 'No automatic split')); ?></option>
                        <option value="2" <?php selected($auto_split_parts, 2); ?>><?php echo esc_html(self::ui_text('2 parti la salvare', '2 parts on save')); ?></option>
                        <option value="3" <?php selected($auto_split_parts, 3); ?>><?php echo esc_html(self::ui_text('3 parti la salvare', '3 parts on save')); ?></option>
                    </select>
                    <small><?php echo esc_html(self::ui_text('Pauzele manuale din editor raman prioritare. Impartirea automata se aplica doar daca postarea nu are deja nextpage.', 'Manual page breaks stay in control. Automatic split only runs if the post does not already have nextpage markers.')); ?></small>
                </label>
            </div>

            <details class="kepoli-setup-section kepoli-seo-fields" <?php echo $has_seo_details ? ' open' : ''; ?>>
                <summary><?php echo esc_html(self::ui_text('Detalii SEO si legaturi', 'SEO and links')); ?></summary>
                <p><?php echo esc_html(self::ui_text('Aceste campuri sunt optionale pentru lucru manual. Daca le lasi goale, site-ul incearca sa le completeze automat.', 'These fields are optional for manual work. If you leave them empty, the site will try to fill them automatically.')); ?></p>
                <div class="kepoli-post-setup__grid kepoli-post-setup__grid--single">
                    <label>
                        <span><?php echo esc_html(self::ui_text('SEO title optional', 'Optional SEO title')); ?></span>
                        <input type="text" name="kepoli_seo_title" value="<?php echo esc_attr($seo_title); ?>" placeholder="<?php echo esc_attr(self::ui_text('Daca ramane gol, se foloseste titlul postarii.', 'If empty, the post title will be used.')); ?>">
                    </label>
                </div>
                <div class="kepoli-post-setup__grid kepoli-post-setup__grid--single">
                    <label>
                        <span><?php esc_html_e('Meta description', 'kepoli-author-tools'); ?></span>
                        <textarea name="kepoli_meta_description" rows="3" maxlength="180" placeholder="<?php echo esc_attr(self::ui_text('Rezumat scurt pentru Google si distribuire sociala.', 'Short summary for Google and social sharing.')); ?>"><?php echo esc_textarea($meta_description); ?></textarea>
                    </label>
                </div>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php echo esc_html(self::ui_text('Sluguri retete recomandate', 'Related recipe slugs')); ?></span>
                        <textarea name="kepoli_related_recipe_slugs" rows="3" placeholder="sarmale-in-foi-de-varza, ciorba-radauteana"><?php echo esc_textarea($related_recipes); ?></textarea>
                    </label>
                    <label>
                        <span><?php echo esc_html(self::ui_text('Sluguri articole recomandate', 'Related article slugs')); ?></span>
                        <textarea name="kepoli_related_article_slugs" rows="3" placeholder="ghidul-camarii-romanesti"><?php echo esc_textarea($related_articles); ?></textarea>
                    </label>
                </div>
            </details>

            <details class="kepoli-setup-section kepoli-image-fields" <?php echo $has_image_meta ? ' open' : ''; ?>>
                <summary><?php echo esc_html(self::ui_text('Detalii imagine', 'Image details')); ?></summary>
                <p><?php echo esc_html(self::ui_text('Completeaza aceste campuri pentru imaginea reprezentativa. La salvare, site-ul le aplica pe featured image daca exista una selectata.', 'Fill these fields for the featured image. On save, the site applies them to the selected featured image.')); ?></p>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php esc_html_e('Alt text', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_alt" value="<?php echo esc_attr($image_meta['alt']); ?>" placeholder="<?php echo esc_attr(self::ui_text('Descriere scurta si precisa a imaginii.', 'Short, accurate description of the image.')); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Image title', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_title" value="<?php echo esc_attr($image_meta['title']); ?>" placeholder="<?php echo esc_attr(self::ui_text('Titlu imagine in Media Library.', 'Image title in the Media Library.')); ?>">
                    </label>
                </div>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php esc_html_e('Caption', 'kepoli-author-tools'); ?></span>
                        <input type="text" name="kepoli_image_caption" value="<?php echo esc_attr($image_meta['caption']); ?>" placeholder="<?php echo esc_attr(self::ui_text('Text optional afisat/subtitrare imagine.', 'Optional text shown as the image caption.')); ?>">
                    </label>
                    <label>
                        <span><?php esc_html_e('Description', 'kepoli-author-tools'); ?></span>
                        <textarea name="kepoli_image_description" rows="2" placeholder="<?php echo esc_attr(self::ui_text('Descriere interna pentru Media Library.', 'Internal description for the Media Library.')); ?>"><?php echo esc_textarea($image_meta['description']); ?></textarea>
                    </label>
                </div>
            </details>

            <details class="kepoli-setup-section kepoli-recipe-fields" data-kepoli-recipe-fields <?php echo $kind === 'recipe' ? ' open' : ''; ?>>
                <summary><?php echo esc_html(self::ui_text('Date reteta', 'Recipe data')); ?></summary>
                <p><?php echo esc_html(self::ui_text('Completeaza aceste campuri pentru retete noi. Ele alimenteaza schema Recipe folosita de Google.', 'Fill these fields for new recipes. They power the Recipe schema used by Google.')); ?></p>
                <div class="kepoli-post-setup__grid kepoli-post-setup__grid--thirds">
                    <label>
                        <span><?php echo esc_html(self::ui_text('Portii', 'Servings')); ?></span>
                        <input type="text" name="kepoli_recipe_servings" value="<?php echo esc_attr($recipe['servings']); ?>" placeholder="<?php echo esc_attr(self::ui_text('4 portii', '4 servings')); ?>">
                    </label>
                    <label>
                        <span><?php echo esc_html(self::ui_text('Pregatire minute', 'Prep minutes')); ?></span>
                        <input type="number" min="0" step="1" name="kepoli_recipe_prep_minutes" value="<?php echo esc_attr($recipe['prep_minutes']); ?>">
                    </label>
                    <label>
                        <span><?php echo esc_html(self::ui_text('Gatire minute', 'Cook minutes')); ?></span>
                        <input type="number" min="0" step="1" name="kepoli_recipe_cook_minutes" value="<?php echo esc_attr($recipe['cook_minutes']); ?>">
                    </label>
                    <label>
                        <span><?php echo esc_html(self::ui_text('Total minute', 'Total minutes')); ?></span>
                        <input type="number" min="0" step="1" name="kepoli_recipe_total_minutes" value="<?php echo esc_attr($recipe['total_minutes']); ?>">
                    </label>
                </div>
                <div class="kepoli-post-setup__grid">
                    <label>
                        <span><?php echo esc_html(self::ui_text('Ingrediente, cate unul pe linie', 'Ingredients, one per line')); ?></span>
                        <textarea name="kepoli_recipe_ingredients" rows="6"><?php echo esc_textarea(implode("\n", $recipe['ingredients'])); ?></textarea>
                    </label>
                    <label>
                        <span><?php echo esc_html(self::ui_text('Pasi, cate unul pe linie', 'Steps, one per line')); ?></span>
                        <textarea name="kepoli_recipe_steps" rows="6"><?php echo esc_textarea(implode("\n", $recipe['steps'])); ?></textarea>
                    </label>
                </div>
            </details>

            <details class="kepoli-editor-checklist" data-kepoli-editor-checklist>
                <summary class="kepoli-editor-checklist__toggle">
                    <span class="kepoli-editor-checklist__title"><?php echo esc_html(self::ui_text('Checklist editorial', 'Editorial checklist')); ?></span>
                    <span class="kepoli-editor-checklist__summary" data-kepoli-checklist-summary></span>
                </summary>
                <p class="kepoli-editor-checklist__intro"><?php echo esc_html(self::ui_text('Deschide lista doar daca vrei sa vezi exact ce mai lipseste.', 'Open the list only when you want to see exactly what is still missing.')); ?></p>
                <ul class="kepoli-editor-checklist__items">
                    <li data-kepoli-check="title"><?php echo esc_html(self::ui_text('Titlu clar', 'Clear title')); ?></li>
                    <li data-kepoli-check="content"><?php echo esc_html(self::ui_text('Continut suficient', 'Enough content')); ?></li>
                    <li data-kepoli-check="excerpt"><?php echo esc_html(self::ui_text('Excerpt completat', 'Excerpt filled')); ?></li>
                    <li data-kepoli-check="meta"><?php echo esc_html(self::ui_text('Meta description completata', 'Meta description filled')); ?></li>
                    <li data-kepoli-check="language"><?php echo esc_html(self::ui_text('Limba coerenta', 'Consistent language')); ?></li>
                    <li data-kepoli-check="slug"><?php echo esc_html(self::ui_text('Slug curat', 'Clean slug')); ?></li>
                    <li data-kepoli-check="featuredImage"><?php echo esc_html(self::ui_text('Imagine reprezentativa setata', 'Featured image set')); ?></li>
                    <li data-kepoli-check="imageAlt"><?php echo esc_html(self::ui_text('Alt text pentru imagine', 'Image alt text')); ?></li>
                    <li data-kepoli-check="related"><?php echo esc_html(self::ui_text('Linkuri interne pregatite', 'Internal links ready')); ?></li>
                    <li data-kepoli-check="recipe"><?php echo esc_html(self::ui_text('Schema reteta completata', 'Recipe schema filled')); ?></li>
                </ul>
            </details>
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
        $auto_split_parts = isset($_POST['kepoli_auto_split_parts']) ? (int) wp_unslash((string) $_POST['kepoli_auto_split_parts']) : 0;
        $auto_split_parts = in_array($auto_split_parts, [2, 3], true) ? $auto_split_parts : 0;
        update_post_meta($post_id, '_kepoli_auto_split_parts', $auto_split_parts);
        self::maybe_clean_post_slug($post_id, $post);
        self::maybe_normalize_content_structure($post_id, $post, $kind);

        self::save_post_excerpt($post_id, $post);
        self::save_seo_title($post_id, $post);
        self::save_meta_description($post_id, $post);
        self::maybe_apply_suggested_category($post_id, $kind, $post);
        self::maybe_apply_suggested_tags($post_id, $kind, $post);

        $related = self::resolve_related_slugs($post_id, $kind, $post);
        $related_recipes = $related['recipes'];
        $related_articles = $related['articles'];

        update_post_meta($post_id, '_kepoli_related_recipe_slugs', $related_recipes);
        update_post_meta($post_id, '_kepoli_related_article_slugs', $related_articles);
        update_post_meta($post_id, '_kepoli_related_slugs', array_values(array_unique(array_merge($related_recipes, $related_articles))));
        self::store_auto_text_flag($post_id, '_kepoli_auto_related_slugs', !empty($related['is_auto']));

        if ($kind === 'recipe') {
            self::save_recipe_json($post_id, $post);
            self::maybe_add_recipe_faq($post_id, $post);
        } else {
            delete_post_meta($post_id, '_kepoli_recipe_json');
            delete_post_meta($post_id, '_kepoli_auto_recipe_json');
            self::maybe_remove_recipe_faq($post_id, $post);
        }

        self::maybe_add_internal_links_to_content($post_id, $post, $kind, $related_recipes, $related_articles);
        self::maybe_apply_auto_split($post_id, $post, $auto_split_parts);
        self::save_featured_image_meta($post_id);
    }

    public static function add_post_list_columns(array $columns): array
    {
        $updated = [];

        foreach ($columns as $key => $label) {
            $updated[$key] = $label;

            if ($key === 'title') {
                $updated['kepoli_kind'] = self::ui_text('Tip continut', 'Content type');
                $updated['kepoli_readiness'] = __('Setup', 'kepoli-author-tools');
            }
        }

        return $updated;
    }

    public static function render_post_list_column(string $column, int $post_id): void
    {
        if ($column === 'kepoli_kind') {
            $kind = self::post_kind($post_id);
            $label = $kind === 'article' ? self::ui_text('Articol', 'Article') : self::ui_text('Reteta', 'Recipe');

            echo '<span class="kepoli-status-pill kepoli-status-pill--' . esc_attr($kind) . '">' . esc_html($label) . '</span>';
            return;
        }

        if ($column === 'kepoli_readiness') {
            $missing = self::post_missing_items($post_id);

            if (!$missing) {
                echo '<span class="kepoli-status-pill kepoli-status-pill--ready">' . esc_html(self::ui_text('Complet', 'Complete')) . '</span>';
                return;
            }

            echo '<span class="kepoli-status-pill kepoli-status-pill--needs">' . esc_html(self::ui_text('De completat', 'Needs work')) . '</span>';
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
        <label class="screen-reader-text" for="kepoli-post-kind-filter"><?php echo esc_html(self::ui_text('Filtreaza dupa tip continut', 'Filter by content type')); ?></label>
        <select id="kepoli-post-kind-filter" name="kepoli_post_kind_filter">
            <option value=""><?php echo esc_html(self::ui_text('Toate tipurile', 'All types')); ?></option>
            <option value="recipe" <?php selected($selected, 'recipe'); ?>><?php echo esc_html(self::ui_text('Retete', 'Recipes')); ?></option>
            <option value="article" <?php selected($selected, 'article'); ?>><?php echo esc_html(self::ui_text('Articole', 'Articles')); ?></option>
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
        $usage_counts = self::related_slug_usage_counts($current_post_id);
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
                'linkUsage' => (int) ($usage_counts[(string) get_post_field('post_name', $post_id)] ?? 0),
            ];
        }

        return $items;
    }

    private static function related_slug_usage_counts(int $exclude_post_id = 0): array
    {
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => 250,
            'post__not_in' => $exclude_post_id ? [$exclude_post_id] : [],
            'fields' => 'ids',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $counts = [];

        foreach ($query->posts as $post_id) {
            $post_id = (int) $post_id;
            $slugs = get_post_meta($post_id, '_kepoli_related_slugs', true);
            $slugs = is_array($slugs) ? $slugs : [];

            foreach ($slugs as $slug) {
                $slug = sanitize_title((string) $slug);
                if ($slug === '') {
                    continue;
                }

                $counts[$slug] = (int) ($counts[$slug] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private static function category_payload(): array
    {
        $terms = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
        ]);

        if (!is_array($terms)) {
            return [];
        }

        $items = [];
        foreach ($terms as $term) {
            if (!$term instanceof WP_Term || (int) $term->term_id === 1) {
                continue;
            }

            $items[] = [
                'id' => (int) $term->term_id,
                'slug' => (string) $term->slug,
                'name' => (string) $term->name,
                'description' => (string) $term->description,
            ];
        }

        return $items;
    }

    private static function featured_image_meta(int $post_id): array
    {
        $planned = self::planned_image_meta($post_id);
        $pending = self::pending_image_meta($post_id);
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            return [
                'alt' => $pending['alt'] !== '' ? $pending['alt'] : $planned['alt'],
                'title' => $pending['title'] !== '' ? $pending['title'] : $planned['title'],
                'caption' => $pending['caption'] !== '' ? $pending['caption'] : $planned['caption'],
                'description' => $pending['description'] !== '' ? $pending['description'] : $planned['description'],
            ];
        }

        $attachment = get_post($thumbnail_id);

        return [
            'alt' => (string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) ?: ($pending['alt'] !== '' ? $pending['alt'] : $planned['alt']),
            'title' => ($attachment ? $attachment->post_title : '') ?: ($pending['title'] !== '' ? $pending['title'] : $planned['title']),
            'caption' => ($attachment ? $attachment->post_excerpt : '') ?: ($pending['caption'] !== '' ? $pending['caption'] : $planned['caption']),
            'description' => ($attachment ? $attachment->post_content : '') ?: ($pending['description'] !== '' ? $pending['description'] : $planned['description']),
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

    private static function pending_image_meta(int $post_id): array
    {
        return [
            'alt' => (string) get_post_meta($post_id, '_kepoli_image_alt', true),
            'title' => (string) get_post_meta($post_id, '_kepoli_image_title', true),
            'caption' => (string) get_post_meta($post_id, '_kepoli_image_caption', true),
            'description' => (string) get_post_meta($post_id, '_kepoli_image_description', true),
        ];
    }

    private static function normalized_image_meta_values(array $meta): array
    {
        return [
            'alt' => self::limit_text(sanitize_text_field(trim((string) ($meta['alt'] ?? ''))), 160),
            'title' => self::limit_text(sanitize_text_field(trim((string) ($meta['title'] ?? ''))), 90),
            'caption' => self::limit_text(sanitize_text_field(trim((string) ($meta['caption'] ?? ''))), 180),
            'description' => self::limit_text(sanitize_textarea_field(trim((string) ($meta['description'] ?? ''))), 320),
        ];
    }

    private static function image_meta_is_empty(array $meta): bool
    {
        $meta = self::normalized_image_meta_values($meta);
        return $meta['alt'] === '' && $meta['title'] === '' && $meta['caption'] === '' && $meta['description'] === '';
    }

    private static function image_meta_matches(array $left, array $right): bool
    {
        return self::normalized_image_meta_values($left) === self::normalized_image_meta_values($right);
    }

    private static function store_post_image_meta(int $post_id, array $meta): void
    {
        $limits = [
            'alt' => 160,
            'title' => 90,
            'caption' => 180,
            'description' => 320,
        ];

        foreach ($limits as $key => $limit) {
            $value = isset($meta[$key]) ? trim((string) $meta[$key]) : '';
            $meta_key = '_kepoli_image_' . $key;

            if ($value === '') {
                delete_post_meta($post_id, $meta_key);
                continue;
            }

            $clean = $key === 'description'
                ? sanitize_textarea_field($value)
                : sanitize_text_field($value);

            update_post_meta($post_id, $meta_key, self::limit_text($clean, $limit));
        }
    }

    private static function save_featured_image_meta(int $post_id): void
    {
        $pending = self::pending_image_meta($post_id);
        $planned = self::planned_image_meta($post_id);
        $generated = self::generated_image_meta($post_id);
        $posted_meta = [
            'alt' => isset($_POST['kepoli_image_alt']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_alt'])) : '',
            'title' => isset($_POST['kepoli_image_title']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_title'])) : '',
            'caption' => isset($_POST['kepoli_image_caption']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_image_caption'])) : '',
            'description' => isset($_POST['kepoli_image_description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_image_description'])) : '',
        ];
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_image_meta');
        $legacy_english = self::image_meta_uses_legacy_english_copy($post_id, $posted_meta, $pending, $planned);
        $should_refresh = ($auto_generated && self::image_meta_matches($posted_meta, $pending))
            || (!$auto_generated && self::image_meta_is_empty($posted_meta) && self::image_meta_is_empty($pending) && self::image_meta_is_empty($planned))
            || $legacy_english;

        if ($should_refresh) {
            $resolved_meta = $generated;
            $is_auto = true;
        } else {
            $resolved_meta = [
                'alt' => $posted_meta['alt'] !== '' ? $posted_meta['alt'] : ($pending['alt'] !== '' ? $pending['alt'] : ($planned['alt'] !== '' ? $planned['alt'] : $generated['alt'])),
                'title' => $posted_meta['title'] !== '' ? $posted_meta['title'] : ($pending['title'] !== '' ? $pending['title'] : ($planned['title'] !== '' ? $planned['title'] : $generated['title'])),
                'caption' => $posted_meta['caption'] !== '' ? $posted_meta['caption'] : ($pending['caption'] !== '' ? $pending['caption'] : ($planned['caption'] !== '' ? $planned['caption'] : $generated['caption'])),
                'description' => $posted_meta['description'] !== '' ? $posted_meta['description'] : ($pending['description'] !== '' ? $pending['description'] : ($planned['description'] !== '' ? $planned['description'] : $generated['description'])),
            ];
            $is_auto = !self::image_meta_is_empty($posted_meta) && self::image_meta_matches($posted_meta, $generated);
        }

        self::store_post_image_meta($post_id, $resolved_meta);
        self::store_auto_text_flag($post_id, '_kepoli_auto_image_meta', $is_auto);

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id && isset($_POST['_thumbnail_id'])) {
            $posted_thumbnail_id = absint(wp_unslash((string) $_POST['_thumbnail_id']));
            $thumbnail_id = $posted_thumbnail_id > 0 ? $posted_thumbnail_id : 0;
        }

        if (!$thumbnail_id) {
            return;
        }

        $existing = self::attachment_image_meta($thumbnail_id);
        if ($should_refresh) {
            $alt = $resolved_meta['alt'];
            $title = $resolved_meta['title'];
            $caption = $resolved_meta['caption'];
            $description = $resolved_meta['description'];
        } else {
            $alt = $posted_meta['alt'] !== '' ? $posted_meta['alt'] : ($existing['alt'] !== '' ? $existing['alt'] : $resolved_meta['alt']);
            $title = $posted_meta['title'] !== '' ? $posted_meta['title'] : ($existing['title'] !== '' ? $existing['title'] : $resolved_meta['title']);
            $caption = $posted_meta['caption'] !== '' ? $posted_meta['caption'] : ($existing['caption'] !== '' ? $existing['caption'] : $resolved_meta['caption']);
            $description = $posted_meta['description'] !== '' ? $posted_meta['description'] : ($existing['description'] !== '' ? $existing['description'] : $resolved_meta['description']);
        }

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

    private static function generated_image_meta(int $post_id, ?bool $is_english = null): array
    {
        $is_english = $is_english ?? self::public_is_english();
        $post = get_post($post_id);
        $title = $post ? trim((string) $post->post_title) : '';
        $title = $title !== '' ? $title : sprintf(self::content_text_for_locale($is_english, 'Reteta %s', '%s recipe'), self::site_name());
        $kind = self::post_kind($post_id);
        $prefix = $kind === 'article'
            ? self::content_text_for_locale($is_english, 'Imagine editoriala pentru', 'Editorial image for')
            : self::content_text_for_locale($is_english, 'Fotografie culinara pentru', 'Food photo for');
        $published_on = sprintf(self::content_text_for_locale($is_english, 'publicata pe %s.', 'published on %s.'), self::site_name());

        return [
            'alt' => self::sentence_limit($prefix . ' ' . $title . ', ' . $published_on, 150),
            'title' => self::limit_text($title, 90),
            'caption' => self::sentence_limit(sprintf(self::content_text_for_locale($is_english, '%1$s pe %2$s.', '%1$s on %2$s.'), $title, self::site_name()), 120),
            'description' => self::sentence_limit(sprintf(self::content_text_for_locale($is_english, 'Imagine reprezentativa pentru %1$s, folosita in articolul culinar %2$s.', 'Representative image for %1$s, used in a %2$s food article.'), $title, self::site_name()), 220),
        ];
    }

    private static function image_meta_uses_legacy_english_copy(int $post_id, array ...$metas): bool
    {
        if (self::public_is_english()) {
            return false;
        }

        $english_generated = self::generated_image_meta($post_id, true);
        foreach ($metas as $meta) {
            if (self::image_meta_matches($meta, $english_generated)) {
                return true;
            }
        }

        return false;
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
        $post = get_post($post_id);
        $has_internal_links = $post instanceof WP_Post
            ? self::content_has_internal_links((string) $post->post_content, $post_id)
            : false;
        $language_consistent = $post instanceof WP_Post
            ? self::is_post_language_consistent($post_id, $post)
            : true;

        if ((string) get_post_meta($post_id, '_kepoli_meta_description', true) === '') {
            $missing[] = 'meta';
        }

        if (!has_excerpt($post_id)) {
            $missing[] = 'excerpt';
        }

        if (!$language_consistent) {
            $missing[] = 'language';
        }

        $thumbnail_id = get_post_thumbnail_id($post_id);
        if (!$thumbnail_id) {
            $missing[] = 'image';
        } elseif ((string) get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true) === '') {
            $missing[] = 'image meta';
        }

        if (!$has_internal_links && $related_count === 0) {
            $missing[] = 'internal links';
        }

        if ($kind === 'recipe') {
            $recipe = self::recipe_data($post_id);
            if (!self::recipe_data_complete($recipe)) {
                $missing[] = 'recipe schema';
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
            'servings' => self::recipe_servings_has_value(isset($data['servings']) ? (string) $data['servings'] : '') ? (string) $data['servings'] : '',
            'prep_minutes' => self::iso_to_minutes((string) ($data['prep_iso'] ?? '')),
            'cook_minutes' => self::iso_to_minutes((string) ($data['cook_iso'] ?? '')),
            'total_minutes' => self::iso_to_minutes((string) ($data['total_iso'] ?? '')),
            'ingredients' => isset($data['ingredients']) && is_array($data['ingredients']) ? $data['ingredients'] : [],
            'steps' => isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : [],
        ];
    }

    private static function recipe_data_complete(array $recipe): bool
    {
        return !empty($recipe['ingredients'])
            && !empty($recipe['steps'])
            && self::recipe_servings_has_value((string) ($recipe['servings'] ?? ''))
            && (int) ($recipe['prep_minutes'] ?? 0) > 0
            && (int) ($recipe['cook_minutes'] ?? 0) > 0;
    }

    private static function recipe_servings_has_value(string $servings): bool
    {
        $servings = trim(sanitize_text_field($servings));
        if ($servings === '') {
            return false;
        }

        if (!preg_match('/\d+/', $servings, $matches)) {
            return false;
        }

        return isset($matches[0]) && (int) $matches[0] > 0;
    }

    private static function normalized_heading(string $text): string
    {
        $text = self::plain_text($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = remove_accents($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);

        return trim((string) preg_replace('/\s+/', ' ', (string) $text));
    }

    private static function normalized_recipe_text(string $text): string
    {
        $text = self::plain_text($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = remove_accents($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim((string) $text);
    }

    private static function recipe_section_targets(string $section): array
    {
        if ($section === 'ingredients') {
            return ['ingrediente', 'ingredients', 'ingredient list'];
        }

        return ['mod de preparare', 'preparare', 'pasi', 'pasii', 'method', 'instructions', 'directions', 'preparation', 'steps'];
    }

    private static function outline_heading_targets(): array
    {
        return [
            'Pe scurt',
            'Detalii despre reteta',
            'Ingrediente',
            'Mod de preparare',
            'Cum se serveste',
            'Sfaturi pentru o reteta reusita',
            'Sfaturi pentru reusita',
            'Variatii ale retetei',
            'Cum se pastreaza',
            'Cum pastrezi',
            'Intrebari frecvente',
            'Concluzie',
            'Ideea principala',
            'Ce merita retinut',
            'Cum aplici in bucatarie',
            'Legaturi utile',
            'What to know first',
            'Recipe details',
            'Ingredients',
            'Method',
            'How to serve it',
            'Success notes',
            'Variations',
            'Storage',
            'Frequently asked questions',
            'Conclusion',
            'Main idea',
            'What to remember',
            'How to use it in the kitchen',
            'Useful links',
        ];
    }

    private static function summary_heading_targets(): array
    {
        return [
            'Pe scurt',
            'Ideea principala',
            'What to know first',
            'Main idea',
        ];
    }

    private static function faq_heading_targets(): array
    {
        return [
            'Intrebari frecvente',
            'Frequently asked questions',
            'FAQs',
            'FAQ',
        ];
    }

    private static function is_outline_heading(string $text): bool
    {
        $normalized = self::normalized_heading($text);
        if ($normalized === '') {
            return false;
        }

        foreach (self::outline_heading_targets() as $candidate) {
            if ($normalized === self::normalized_heading($candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function is_summary_heading(string $text): bool
    {
        $normalized = self::normalized_heading($text);
        if ($normalized === '') {
            return false;
        }

        foreach (self::summary_heading_targets() as $candidate) {
            if ($normalized === self::normalized_heading($candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function is_faq_heading(string $text): bool
    {
        $normalized = self::normalized_heading($text);
        if ($normalized === '') {
            return false;
        }

        foreach (self::faq_heading_targets() as $candidate) {
            if ($normalized === self::normalized_heading($candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function looks_like_faq_question(string $text): bool
    {
        $plain = trim(self::plain_text($text));
        if ($plain === '') {
            return false;
        }

        if (!preg_match('/\?\s*$/u', $plain)) {
            return false;
        }

        return self::word_count($plain) <= 18;
    }

    private static function recipe_html_lines(string $html): array
    {
        if ($html === '') {
            return [];
        }

        $html = (string) preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = (string) preg_replace('/<\/(?:p|div|li)>/i', "\n", $html);
        $text = strip_shortcodes($html);
        $text = wp_strip_all_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'));
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $value = trim((string) preg_replace('/\s+/', ' ', (string) $line));
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return $clean;
    }

    private static function clean_recipe_item_line(string $text, string $section): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', html_entity_decode($text, ENT_QUOTES, get_bloginfo('charset'))));
        if ($value === '') {
            return '';
        }

        $value = (string) preg_replace('/^(?:[-*\x{2022}]+)\s*/u', '', $value);
        $value = (string) preg_replace('/^\d+\s*(?:[)-]\s*|\.\s+)/u', '', $value);

        if ($section === 'steps') {
            $value = (string) preg_replace('/^(?:pasul|step)\s*\d+\s*[:.)-]?\s*/iu', '', $value);
        }

        return trim($value);
    }

    private static function content_has_markup(string $content): bool
    {
        return (bool) preg_match('/<[^>]+>/', $content);
    }

    private static function strip_auto_generated_blocks(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $content = self::strip_auto_internal_links_block($content);
        $content = self::strip_auto_faq_block($content);

        return trim($content);
    }

    private static function recipe_source_lines(string $content, bool $keep_empty = false): array
    {
        if (self::content_has_markup($content)) {
            $content = (string) preg_replace('/<br\s*\/?>/i', "\n", $content);
            $content = (string) preg_replace('/<\/(?:p|div|li|h[1-6]|ul|ol)>/i', "\n", $content);
            $content = strip_shortcodes($content);
            $content = wp_strip_all_tags($content);
        }

        $content = html_entity_decode($content, ENT_QUOTES, get_bloginfo('charset'));
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $clean = [];

        foreach ($lines as $line) {
            $value = trim((string) preg_replace('/\s+/', ' ', (string) $line));
            if ($value === '') {
                if ($keep_empty) {
                    $clean[] = '';
                }
                continue;
            }

            $clean[] = $value;
        }

        return $clean;
    }

    private static function parse_recipe_outline_sections(string $content): array
    {
        $lines = self::recipe_source_lines($content, true);
        $sections = [];
        $current_key = '';

        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                if ($current_key !== '' && isset($sections[$current_key])) {
                    $sections[$current_key]['lines'][] = '';
                }
                continue;
            }

            if (self::is_outline_heading($value)) {
                $current_key = self::normalized_heading($value);
                if (!isset($sections[$current_key])) {
                    $sections[$current_key] = [
                        'heading' => $value,
                        'lines' => [],
                    ];
                }
                continue;
            }

            if ($current_key === '') {
                $current_key = '_intro';
                if (!isset($sections[$current_key])) {
                    $sections[$current_key] = [
                        'heading' => '',
                        'lines' => [],
                    ];
                }
            }

            $sections[$current_key]['lines'][] = $value;
        }

        return $sections;
    }

    private static function recipe_section_items_from_lines(string $content, string $section): array
    {
        $sections = self::parse_recipe_outline_sections($content);
        $targets = array_map([self::class, 'normalized_heading'], self::recipe_section_targets($section));
        $items = [];

        foreach ($sections as $key => $section_data) {
            if ($key === '_intro' || !in_array((string) $key, $targets, true)) {
                continue;
            }

            foreach ((array) ($section_data['lines'] ?? []) as $line) {
                $value = self::clean_recipe_item_line((string) $line, $section);
                if ($value !== '') {
                    $items[] = $value;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $items))));
    }

    private static function html_paragraphs_from_lines(array $lines): string
    {
        $paragraphs = [];
        $buffer = [];

        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                if ($buffer !== []) {
                    $paragraphs[] = '<p>' . esc_html(implode(' ', $buffer)) . '</p>';
                    $buffer = [];
                }
                continue;
            }

            $buffer[] = $value;
        }

        if ($buffer !== []) {
            $paragraphs[] = '<p>' . esc_html(implode(' ', $buffer)) . '</p>';
        }

        return implode("\n", $paragraphs);
    }

    private static function render_recipe_section_html(string $heading, array $lines): string
    {
        $normalized = self::normalized_heading($heading);
        $html = ['<h2>' . esc_html($heading) . '</h2>'];
        $ingredient_headings = [
            self::normalized_heading('Ingrediente'),
            self::normalized_heading('Ingredients'),
        ];
        $step_headings = [
            self::normalized_heading('Mod de preparare'),
            self::normalized_heading('Method'),
        ];
        $faq_headings = [
            self::normalized_heading('Intrebari frecvente'),
            self::normalized_heading('Frequently asked questions'),
        ];

        if (in_array($normalized, $ingredient_headings, true)) {
            $items = [];
            foreach ($lines as $line) {
                $value = self::clean_recipe_item_line((string) $line, 'ingredients');
                if ($value !== '') {
                    $items[] = '<li>' . esc_html($value) . '</li>';
                }
            }

            if ($items !== []) {
                $html[] = "<ul>\n" . implode("\n", $items) . "\n</ul>";
            }

            return implode("\n", $html);
        }

        if (in_array($normalized, $step_headings, true)) {
            $items = [];
            foreach ($lines as $line) {
                $value = self::clean_recipe_item_line((string) $line, 'steps');
                if ($value !== '') {
                    $items[] = '<li>' . esc_html($value) . '</li>';
                }
            }

            if ($items !== []) {
                $html[] = "<ol>\n" . implode("\n", $items) . "\n</ol>";
            }

            return implode("\n", $html);
        }

        if (in_array($normalized, $faq_headings, true)) {
            $faq_lines = [];
            $answer_buffer = [];
            $current_question = '';

            $flush_answer = static function () use (&$faq_lines, &$answer_buffer, &$current_question): void {
                if ($current_question === '') {
                    $answer_buffer = [];
                    return;
                }

                if ($answer_buffer !== []) {
                    $faq_lines[] = '<p>' . esc_html(implode(' ', $answer_buffer)) . '</p>';
                    $answer_buffer = [];
                }
            };

            foreach ($lines as $line) {
                $value = trim((string) $line);
                if ($value === '') {
                    $flush_answer();
                    continue;
                }

                if (self::looks_like_faq_question($value)) {
                    $flush_answer();
                    $current_question = $value;
                    $faq_lines[] = '<h3>' . esc_html($value) . '</h3>';
                    continue;
                }

                $answer_buffer[] = $value;
            }

            $flush_answer();

            if ($faq_lines !== []) {
                $html[] = implode("\n", $faq_lines);
            }

            return implode("\n", $html);
        }

        $paragraphs = self::html_paragraphs_from_lines($lines);
        if ($paragraphs !== '') {
            $html[] = $paragraphs;
        }

        return implode("\n", $html);
    }

    private static function should_rebuild_simple_recipe_markup(string $content): bool
    {
        $content = self::strip_auto_generated_blocks($content);
        $plain = self::plain_text($content);
        if ($plain === '') {
            return false;
        }

        if (!self::content_has_markup($content)) {
            return true;
        }

        if (preg_match('/<(?:a|img|figure|table|blockquote|pre|code|iframe)\b/i', $content)) {
            return false;
        }

        if (str_contains($content, '<!--')) {
            return false;
        }

        $has_semantic_structure = (bool) preg_match('/<(?:h[1-6]|ul|ol|li)\b/i', $content);
        $has_simple_lines = (bool) preg_match('/<br\s*\/?>/i', $content) || (bool) preg_match('/<p[^>]*>\s*(?:Pe scurt|Detalii despre reteta|Ingrediente|Mod de preparare|Cum se serveste|Intrebari frecvente|Concluzie|What to know first|Recipe details|Ingredients|Method|How to serve it|Frequently asked questions|Conclusion)\s*<\/p>/iu', $content);

        return !$has_semantic_structure || $has_simple_lines;
    }

    private static function rebuild_simple_recipe_markup(string $content): string
    {
        $content = self::strip_auto_generated_blocks($content);
        $sections = self::parse_recipe_outline_sections($content);
        if ($sections === []) {
            return trim($content);
        }

        $output = [];

        foreach ($sections as $key => $section_data) {
            if ($key === '_intro') {
                $intro = self::html_paragraphs_from_lines((array) ($section_data['lines'] ?? []));
                if ($intro !== '') {
                    $output[] = $intro;
                }
                continue;
            }

            $heading = trim((string) ($section_data['heading'] ?? ''));
            if ($heading === '') {
                continue;
            }

            $output[] = self::render_recipe_section_html($heading, (array) ($section_data['lines'] ?? []));
        }

        return trim(implode("\n\n", array_filter($output)));
    }

    private static function extract_summary_source_from_content(string $content): string
    {
        if ($content === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            return self::plain_text($content);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="kepoli-summary-root">' . $content . '</div>';

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $document->getElementById('kepoli-summary-root');
        if (!$root) {
            return self::plain_text($content);
        }

        $paragraphs = [];
        $capture_summary = false;
        $before_first_heading = true;

        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower((string) $node->nodeName);
            $plain = self::plain_text($document->saveHTML($node));
            if ($plain === '') {
                continue;
            }

            $heading_like = preg_match('/^h[1-6]$/', $tag) || ($tag === 'p' && self::is_outline_heading($plain));
            if ($heading_like) {
                if (self::is_summary_heading($plain)) {
                    $capture_summary = true;
                    $before_first_heading = false;
                    continue;
                }

                if ($capture_summary) {
                    break;
                }

                $before_first_heading = false;
                continue;
            }

            if ($capture_summary || $before_first_heading) {
                $paragraphs[] = $plain;
                if (count($paragraphs) >= 2) {
                    break;
                }
            }
        }

        if ($paragraphs === []) {
            return self::plain_text($content);
        }

        return trim(implode(' ', $paragraphs));
    }

    private static function replace_dom_tag(DOMDocument $document, DOMElement $element, string $tag_name): DOMElement
    {
        if (strtolower($element->tagName) === strtolower($tag_name)) {
            return $element;
        }

        $replacement = $document->createElement($tag_name);
        if ($element->hasAttributes()) {
            foreach ($element->attributes as $attribute) {
                $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue ?? '');
            }
        }

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        if ($element->parentNode) {
            $element->parentNode->replaceChild($replacement, $element);
        }

        return $replacement;
    }

    private static function recipe_section_items_from_content(string $content, string $section): array
    {
        $targets = array_map([self::class, 'normalized_heading'], self::recipe_section_targets($section));
        if (!$targets) {
            return [];
        }

        $line_items = self::recipe_section_items_from_lines($content, $section);
        if ($line_items !== []) {
            return $line_items;
        }

        if (!class_exists('DOMDocument')) {
            return [];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="kepoli-recipe-root">' . $content . '</div>';

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $document->getElementById('kepoli-recipe-root');
        if (!$root) {
            return [];
        }

        $items = [];
        $active = false;

        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE && trim((string) $node->textContent) === '') {
                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $tag = strtolower((string) $node->nodeName);
            $text = self::plain_text($document->saveHTML($node));
            $heading_like = preg_match('/^h[1-6]$/', $tag) || ($tag === 'p' && (self::is_outline_heading($text) || in_array(self::normalized_heading($text), $targets, true)));
            if ($heading_like) {
                $heading = self::normalized_heading((string) $node->textContent);
                $active = in_array($heading, $targets, true);
                continue;
            }

            if (!$active) {
                continue;
            }

            if ($tag === 'ul' || $tag === 'ol') {
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType !== XML_ELEMENT_NODE || strtolower((string) $child->nodeName) !== 'li') {
                        continue;
                    }

                    $text = self::clean_recipe_item_line(self::plain_text($document->saveHTML($child)), $section);
                    if ($text !== '') {
                        $items[] = $text;
                    }
                }

                continue;
            }

            foreach (self::recipe_html_lines($document->saveHTML($node)) as $line) {
                $line = self::clean_recipe_item_line($line, $section);
                if ($line !== '') {
                    $items[] = $line;
                }
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $items))));
    }

    private static function recipe_minutes_from_text(string $text, array $labels): int
    {
        if ($text === '' || !$labels) {
            return 0;
        }

        $quoted = implode('|', array_map(static fn (string $label): string => preg_quote($label, '/'), $labels));
        if (!preg_match('/(?:^|[\s(\[-])(?:' . $quoted . ')(?![a-z])[^0-9]{0,32}((?:\d{1,2}\s*(?:h|hr|hrs|ora|ore|hour|hours)(?:\s*\d{1,3}\s*(?:m|min|mins|minute|minutes))?)|(?:\d{1,3}\s*(?:m|min|mins|minute|minutes))|(?:\d{1,3}))/i', $text, $matches)) {
            return 0;
        }

        return isset($matches[1]) ? self::recipe_duration_value_to_minutes((string) $matches[1]) : 0;
    }

    private static function recipe_minutes_from_lines(array $lines, array $labels): int
    {
        if (!$lines || !$labels) {
            return 0;
        }

        $normalized_labels = array_values(array_unique(array_filter(array_map([self::class, 'normalized_heading'], $labels))));
        foreach ($lines as $line) {
            $normalized_line = self::normalized_heading((string) $line);
            if ($normalized_line === '') {
                continue;
            }

            foreach ($normalized_labels as $label) {
                if ($normalized_line === $label || !str_starts_with($normalized_line, $label . ' ')) {
                    continue;
                }

                $value = trim(substr($normalized_line, strlen($label)));
                if ($value !== '') {
                    return self::recipe_duration_value_to_minutes($value);
                }
            }
        }

        return 0;
    }

    private static function recipe_duration_value_to_minutes(string $value): int
    {
        if ($value === '') {
            return 0;
        }

        $value = self::normalized_recipe_text($value);
        $hours = 0;
        $minutes = 0;

        if (preg_match('/(\d{1,2})\s*(?:h|hr|hrs|ora|ore|hour|hours)\b/i', $value, $matches)) {
            $hours = max(0, (int) ($matches[1] ?? 0));
        }

        if (preg_match('/(\d{1,3})\s*(?:m|min|mins|minute|minutes)\b/i', $value, $matches)) {
            $minutes = max(0, (int) ($matches[1] ?? 0));
        }

        if ($hours === 0 && $minutes === 0 && preg_match('/(\d{1,3})/', $value, $matches)) {
            return max(0, (int) ($matches[1] ?? 0));
        }

        return ($hours * 60) + $minutes;
    }

    private static function recipe_servings_from_text(string $text): string
    {
        if ($text === '') {
            return '';
        }

        if (!preg_match('/(?:portii|portie|persoane|servings?|serves|yield|pentru|aproximativ|cam)[^0-9]{0,24}(\d{1,2}(?:\s*(?:portii|persoane|servings?|people|persons))?)/i', $text, $matches)) {
            return '';
        }

        return trim((string) ($matches[1] ?? ''));
    }

    private static function extract_recipe_data_from_content(string $content): array
    {
        $normalized = self::normalized_recipe_text($content);
        $source_lines = self::recipe_source_lines($content);
        $prep_minutes = self::recipe_minutes_from_lines($source_lines, ['timp de pregatire', 'timp pregatire', 'prep time', 'preparation time', 'prep']);
        if ($prep_minutes === 0) {
            $prep_minutes = self::recipe_minutes_from_text($normalized, ['timp de pregatire', 'timp pregatire', 'prep time', 'preparation time', 'prep']);
        }

        $cook_minutes = self::recipe_minutes_from_lines($source_lines, ['timp de gatire', 'timp gatire', 'cook time', 'cooking time', 'bake time', 'timp de coacere', 'timp de fierbere']);
        if ($cook_minutes === 0) {
            $cook_minutes = self::recipe_minutes_from_text($normalized, ['timp de gatire', 'timp gatire', 'cook time', 'cooking time', 'bake time', 'boil time', 'simmer time', 'timp de coacere', 'timp de fierbere']);
        }

        $total_minutes = self::recipe_minutes_from_lines($source_lines, ['timp total', 'total time', 'total']);
        if ($total_minutes === 0) {
            $total_minutes = self::recipe_minutes_from_text($normalized, ['timp total', 'total time', 'total']);
        }

        if ($prep_minutes > 0 && $cook_minutes === 0 && $total_minutes > $prep_minutes) {
            $cook_minutes = max(0, $total_minutes - $prep_minutes);
        } elseif ($cook_minutes > 0 && $prep_minutes === 0 && $total_minutes > $cook_minutes) {
            $prep_minutes = max(0, $total_minutes - $cook_minutes);
        }

        return [
            'servings' => self::recipe_servings_from_text($normalized),
            'prep_minutes' => $prep_minutes,
            'cook_minutes' => $cook_minutes,
            'total_minutes' => $total_minutes,
            'ingredients' => self::recipe_section_items_from_content($content, 'ingredients'),
            'steps' => self::recipe_section_items_from_content($content, 'steps'),
        ];
    }

    private static function save_meta_description(int $post_id, WP_Post $post): void
    {
        $existing = (string) get_post_meta($post_id, '_kepoli_meta_description', true);
        $posted = isset($_POST['kepoli_meta_description']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_meta_description'])) : '';
        $posted = self::remove_template_prompt_text($posted);
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_meta_description');
        $generated = self::limit_text(self::plain_text(self::generate_meta_description($post)), 180);

        if (self::should_refresh_auto_text($posted, $existing, $auto_generated)) {
            $value = $generated;
            $is_auto = true;
        } else {
            $value = $posted !== '' ? $posted : $existing;
            $is_auto = self::plain_text($value) !== '' && self::plain_text($value) === self::plain_text($generated);
        }

        if (self::summary_starts_with_outline_heading($value)) {
            $value = $generated;
            $is_auto = true;
        }

        $value = self::limit_text(self::plain_text($value), 180);
        if (self::word_count($value) < 8) {
            $value = $generated;
            $is_auto = true;
        }

        if ($value === '') {
            delete_post_meta($post_id, '_kepoli_meta_description');
            self::store_auto_text_flag($post_id, '_kepoli_auto_meta_description', false);
            return;
        }

        update_post_meta($post_id, '_kepoli_meta_description', $value);
        self::store_auto_text_flag($post_id, '_kepoli_auto_meta_description', $is_auto);
    }

    private static function save_post_excerpt(int $post_id, WP_Post $post): void
    {
        $existing = trim((string) $post->post_excerpt);
        $posted = isset($_POST['kepoli_post_excerpt']) ? sanitize_textarea_field(wp_unslash((string) $_POST['kepoli_post_excerpt'])) : '';
        $posted = self::remove_template_prompt_text($posted);
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_excerpt');
        $generated = self::limit_text(self::plain_text(self::generate_post_excerpt($post)), 260);

        if (self::should_refresh_auto_text($posted, $existing, $auto_generated)) {
            $value = $generated;
            $is_auto = true;
        } else {
            $value = $posted !== '' ? $posted : $existing;
            $is_auto = self::plain_text($value) !== '' && self::plain_text($value) === self::plain_text($generated);
        }

        if (self::summary_starts_with_outline_heading($value)) {
            $value = $generated;
            $is_auto = true;
        }

        $value = self::limit_text(self::plain_text($value), 260);
        if (self::word_count($value) < 8) {
            $value = $generated;
            $is_auto = true;
        }

        if ($value === '' || $value === (string) $post->post_excerpt) {
            self::store_auto_text_flag($post_id, '_kepoli_auto_excerpt', $is_auto && $value !== '');
            return;
        }

        self::$is_updating_post = true;
        wp_update_post([
            'ID' => $post_id,
            'post_excerpt' => $value,
        ]);
        self::$is_updating_post = false;
        $post->post_excerpt = $value;
        self::store_auto_text_flag($post_id, '_kepoli_auto_excerpt', $is_auto);
    }

    private static function maybe_clean_post_slug(int $post_id, WP_Post $post): void
    {
        $title = trim((string) $post->post_title);
        if ($title === '') {
            return;
        }

        $current_slug = (string) $post->post_name;
        $default_slug = sanitize_title($title);
        $clean_slug = self::clean_slug_from_title($title);

        if ($clean_slug === '' || $clean_slug === $current_slug) {
            return;
        }

        if ($current_slug !== '' && $current_slug !== $default_slug) {
            return;
        }

        self::$is_updating_post = true;
        wp_update_post([
            'ID' => $post_id,
            'post_name' => $clean_slug,
        ]);
        self::$is_updating_post = false;
        $post->post_name = $clean_slug;
    }

    private static function maybe_normalize_content_structure(int $post_id, WP_Post $post, string $kind): void
    {
        $content = (string) $post->post_content;
        if ($content === '') {
            return;
        }

        $normalized = self::normalize_content_structure($content, $kind);
        if ($normalized === '' || $normalized === $content) {
            return;
        }

        self::$is_updating_post = true;
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $normalized,
        ]);
        self::$is_updating_post = false;
        $post->post_content = $normalized;
    }

    private static function maybe_add_internal_links_to_content(int $post_id, WP_Post $post, string $kind, array $related_recipes, array $related_articles): void
    {
        $content = (string) $post->post_content;
        $clean_content = self::strip_auto_internal_links_block($content);
        $has_existing_links = self::content_has_internal_links($clean_content, $post_id);

        if ($has_existing_links) {
            if ($clean_content !== $content) {
                self::update_post_content($post_id, $clean_content);
            }
            return;
        }

        $suggested_posts = self::auto_internal_link_posts($kind, $related_recipes, $related_articles);
        if (!$suggested_posts) {
            if ($clean_content !== $content) {
                self::update_post_content($post_id, $clean_content);
            }
            return;
        }

        $updated_content = self::place_auto_internal_links_in_content($clean_content, $suggested_posts);

        if ($updated_content !== $content) {
            self::update_post_content($post_id, $updated_content);
        }
    }

    private static function maybe_add_recipe_faq(int $post_id, WP_Post $post): void
    {
        $content = (string) $post->post_content;
        $clean_content = self::strip_auto_faq_block($content);

        if (self::content_has_faq_section($clean_content)) {
            if ($clean_content !== $content) {
                self::update_post_content($post_id, $clean_content);
            }
            return;
        }

        $faq_block = self::build_recipe_faq_block($post_id, $clean_content);
        if ($faq_block === '') {
            if ($clean_content !== $content) {
                self::update_post_content($post_id, $clean_content);
            }
            return;
        }

        $updated_content = rtrim($clean_content) . "\n\n" . $faq_block;
        if ($updated_content !== $content) {
            self::update_post_content($post_id, $updated_content);
        }
    }

    private static function maybe_remove_recipe_faq(int $post_id, WP_Post $post): void
    {
        $content = (string) $post->post_content;
        $clean_content = self::strip_auto_faq_block($content);

        if ($clean_content !== $content) {
            self::update_post_content($post_id, $clean_content);
            $post->post_content = $clean_content;
        }
    }

    private static function maybe_apply_auto_split(int $post_id, WP_Post $post, int $parts): void
    {
        if (!in_array($parts, [2, 3], true)) {
            return;
        }

        $content = (string) $post->post_content;
        if ($content === '' || stripos($content, '<!--nextpage-->') !== false) {
            return;
        }

        $split = self::split_content_into_parts($content, $parts);
        if ($split === '' || $split === $content) {
            return;
        }

        self::update_post_content($post_id, $split);
        $post->post_content = $split;
    }

    private static function generate_post_excerpt(WP_Post $post): string
    {
        $source = self::remove_template_prompt_text(trim((string) $post->post_excerpt));

        if ($source === '') {
            $source = self::remove_template_prompt_text(self::extract_summary_source_from_content((string) $post->post_content));
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
            $source = self::remove_template_prompt_text(self::extract_summary_source_from_content((string) $post->post_content));
        }

        if ($source === '') {
            $source = trim((string) $post->post_title);
        }

        return self::sentence_limit($source, 155);
    }

    private static function generate_seo_title(WP_Post $post): string
    {
        $title = trim((string) $post->post_title);
        if ($title === '') {
            return '';
        }

        $title = self::remove_template_prompt_text($title);
        return rtrim(self::sentence_limit($title, 65, 30), '. ');
    }

    private static function text_was_auto_generated(int $post_id, string $meta_key): bool
    {
        return get_post_meta($post_id, $meta_key, true) === '1';
    }

    private static function store_auto_text_flag(int $post_id, string $meta_key, bool $auto_generated): void
    {
        if ($auto_generated) {
            update_post_meta($post_id, $meta_key, '1');
            return;
        }

        delete_post_meta($post_id, $meta_key);
    }

    private static function should_refresh_auto_text(string $posted_value, string $existing_value, bool $auto_generated): bool
    {
        if ($posted_value === '') {
            return true;
        }

        if (!$auto_generated) {
            return false;
        }

        return self::plain_text($posted_value) === self::plain_text($existing_value);
    }

    private static function summary_starts_with_outline_heading(string $text): bool
    {
        $normalized_text = self::normalized_heading($text);
        if ($normalized_text === '') {
            return false;
        }

        foreach (self::outline_heading_targets() as $heading) {
            $normalized_heading = self::normalized_heading($heading);
            if ($normalized_heading !== '' && str_starts_with($normalized_text, $normalized_heading) && $normalized_text !== $normalized_heading) {
                return true;
            }
        }

        return false;
    }

    private static function field_was_posted(string $field): bool
    {
        return is_array($_POST) && array_key_exists($field, $_POST);
    }

    private static function normalized_slug_values(array $values): array
    {
        $slugs = [];

        foreach ($values as $value) {
            $slug = sanitize_title((string) $value);
            if ($slug !== '' && !in_array($slug, $slugs, true)) {
                $slugs[] = $slug;
            }
        }

        return $slugs;
    }

    private static function stored_slug_meta(int $post_id, string $meta_key): array
    {
        $values = get_post_meta($post_id, $meta_key, true);
        return is_array($values) ? self::normalized_slug_values($values) : [];
    }

    private static function slug_lists_match(array $left, array $right): bool
    {
        return self::normalized_slug_values($left) === self::normalized_slug_values($right);
    }

    private static function resolve_related_slugs(int $post_id, string $kind, WP_Post $post): array
    {
        $posted_recipes = self::posted_slugs('kepoli_related_recipe_slugs');
        $posted_articles = self::posted_slugs('kepoli_related_article_slugs');
        $existing_recipes = self::stored_slug_meta($post_id, '_kepoli_related_recipe_slugs');
        $existing_articles = self::stored_slug_meta($post_id, '_kepoli_related_article_slugs');
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_related_slugs');
        $should_refresh = ($existing_recipes === [] && $existing_articles === [] && $posted_recipes === [] && $posted_articles === [])
            || ($auto_generated
                && self::slug_lists_match($posted_recipes, $existing_recipes)
                && self::slug_lists_match($posted_articles, $existing_articles));

        $suggested = self::suggest_related_slugs($post_id, $kind, $post);
        $suggested_recipes = self::normalized_slug_values($suggested['recipes'] ?? []);
        $suggested_articles = self::normalized_slug_values($suggested['articles'] ?? []);

        if ($should_refresh) {
            return [
                'recipes' => $suggested_recipes,
                'articles' => $suggested_articles,
                'is_auto' => true,
            ];
        }

        $recipes = self::field_was_posted('kepoli_related_recipe_slugs') ? $posted_recipes : $existing_recipes;
        $articles = self::field_was_posted('kepoli_related_article_slugs') ? $posted_articles : $existing_articles;

        return [
            'recipes' => $recipes,
            'articles' => $articles,
            'is_auto' => self::slug_lists_match($recipes, $suggested_recipes) && self::slug_lists_match($articles, $suggested_articles),
        ];
    }

    private static function save_seo_title(int $post_id, WP_Post $post): void
    {
        $existing = (string) get_post_meta($post_id, '_kepoli_seo_title', true);
        $posted = isset($_POST['kepoli_seo_title']) ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_seo_title'])) : '';
        $posted = self::limit_text(self::remove_template_prompt_text($posted), 70);
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_seo_title');
        $generated = self::limit_text(self::generate_seo_title($post), 70);

        if (self::should_refresh_auto_text($posted, $existing, $auto_generated)) {
            $value = $generated;
            $is_auto = true;
        } else {
            $value = $posted !== '' ? $posted : $existing;
            $value = self::limit_text($value, 70);
            $is_auto = $value !== '' && self::plain_text($value) === self::plain_text($generated);
        }

        if ($value === '') {
            delete_post_meta($post_id, '_kepoli_seo_title');
            self::store_auto_text_flag($post_id, '_kepoli_auto_seo_title', false);
            return;
        }

        update_post_meta($post_id, '_kepoli_seo_title', $value);
        self::store_auto_text_flag($post_id, '_kepoli_auto_seo_title', $is_auto);
    }

    private static function posted_category_ids(): array
    {
        $raw = isset($_POST['post_category']) ? (array) wp_unslash($_POST['post_category']) : [];
        $ids = [];

        foreach ($raw as $value) {
            $id = absint((string) $value);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private static function posted_tags(): array
    {
        if (!isset($_POST['tax_input']) || !is_array($_POST['tax_input']) || !isset($_POST['tax_input']['post_tag'])) {
            return [];
        }

        $raw = wp_unslash($_POST['tax_input']['post_tag']);
        $parts = is_array($raw) ? $raw : preg_split('/,/', (string) $raw);
        $tags = [];

        foreach ((array) $parts as $part) {
            $tag = trim(sanitize_text_field((string) $part));
            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    private static function has_non_default_category(array $category_ids): bool
    {
        $default_category = (int) get_option('default_category');

        foreach ($category_ids as $category_id) {
            if ((int) $category_id > 0 && (int) $category_id !== $default_category) {
                return true;
            }
        }

        return false;
    }

    private static function normalized_category_ids(array $category_ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn ($value): int => absint((string) $value), $category_ids))));
        sort($ids);
        return $ids;
    }

    private static function normalized_tags(array $tags): array
    {
        $normalized = [];

        foreach ($tags as $tag) {
            $clean = trim(sanitize_text_field((string) $tag));
            if ($clean !== '') {
                $normalized[] = strtolower($clean);
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        return $normalized;
    }

    private static function tags_look_stale_for_post(array $tags, WP_Post $post): bool
    {
        $tags = self::normalized_tags($tags);
        if ($tags === [] || count($tags) > 5) {
            return false;
        }

        $source_words = self::keywords_from_text(implode(' ', [
            $post->post_title,
            $post->post_excerpt,
            $post->post_content,
        ]));
        if ($source_words === []) {
            return false;
        }

        foreach ($tags as $tag) {
            if (array_intersect(self::keywords_from_text($tag), $source_words) !== []) {
                return false;
            }
        }

        return true;
    }

    private static function is_article_category_term(WP_Term $category): bool
    {
        $label = self::normalized_recipe_text(implode(' ', [
            (string) $category->slug,
            (string) $category->name,
            (string) $category->description,
        ]));

        return in_array((string) $category->slug, self::article_category_slugs(), true)
            || str_contains($label, 'article')
            || str_contains($label, 'guide');
    }

    private static function article_category_id(): int
    {
        $categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
        ]);

        if (!is_array($categories)) {
            return 0;
        }

        foreach ($categories as $category) {
            if ($category instanceof WP_Term && self::is_article_category_term($category)) {
                return (int) $category->term_id;
            }
        }

        return 0;
    }

    private static function recipe_category_ids(): array
    {
        $categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
        ]);

        if (!is_array($categories)) {
            return [];
        }

        $ids = [];
        foreach ($categories as $category) {
            if (!$category instanceof WP_Term || (int) $category->term_id === 1 || self::is_article_category_term($category)) {
                continue;
            }

            $ids[] = (int) $category->term_id;
        }

        return self::normalized_category_ids($ids);
    }

    private static function category_ids_intersect(array $left, array $right): bool
    {
        $lookup = array_flip(self::normalized_category_ids($right));

        foreach (self::normalized_category_ids($left) as $id) {
            if (isset($lookup[$id])) {
                return true;
            }
        }

        return false;
    }

    private static function intersect_category_ids_in_order(array $source, array $allowed): array
    {
        $allowed_lookup = array_flip(self::normalized_category_ids($allowed));
        $result = [];

        foreach ($source as $value) {
            $id = absint((string) $value);
            if ($id > 0 && isset($allowed_lookup[$id]) && !in_array($id, $result, true)) {
                $result[] = $id;
            }
        }

        return $result;
    }

    private static function maybe_apply_suggested_category(int $post_id, string $kind, WP_Post $post): void
    {
        $article_category_id = self::article_category_id();
        $recipe_category_ids = self::recipe_category_ids();
        $posted_categories = self::posted_category_ids();

        if ($kind === 'article') {
            if ($article_category_id > 0) {
                wp_set_post_categories($post_id, [$article_category_id], false);
                update_post_meta($post_id, '_kepoli_auto_category_id', $article_category_id);
            }
            return;
        }

        $posted_recipe_categories = self::intersect_category_ids_in_order($posted_categories, $recipe_category_ids);
        $suggested_category_id = self::suggested_category_id($kind, $post);
        if ($posted_recipe_categories !== []) {
            wp_set_post_categories($post_id, [$posted_recipe_categories[0]], false);
            if ($suggested_category_id > 0 && $posted_recipe_categories[0] === $suggested_category_id) {
                update_post_meta($post_id, '_kepoli_auto_category_id', $suggested_category_id);
            } else {
                delete_post_meta($post_id, '_kepoli_auto_category_id');
            }
            return;
        }

        $current_categories = wp_get_post_categories($post_id);
        $current_categories = is_array($current_categories) ? $current_categories : [];
        $current_recipe_categories = self::intersect_category_ids_in_order($current_categories, $recipe_category_ids);
        $auto_category_id = (int) get_post_meta($post_id, '_kepoli_auto_category_id', true);
        $is_current_auto = $auto_category_id > 0 && self::normalized_category_ids($current_categories) === self::normalized_category_ids([$auto_category_id]);

        if ($current_recipe_categories !== [] && !$is_current_auto) {
            wp_set_post_categories($post_id, [$current_recipe_categories[0]], false);
            return;
        }

        if ($suggested_category_id > 0) {
            wp_set_post_categories($post_id, [$suggested_category_id], false);
            update_post_meta($post_id, '_kepoli_auto_category_id', $suggested_category_id);
        }
    }

    private static function maybe_apply_suggested_tags(int $post_id, string $kind, WP_Post $post): void
    {
        $posted_tags = self::posted_tags();
        $suggested_tags = self::suggested_tags($post_id, $kind, $post);
        if ($posted_tags) {
            if (self::normalized_tags($posted_tags) === self::normalized_tags($suggested_tags)) {
                wp_set_post_terms($post_id, $suggested_tags, 'post_tag', false);
                update_post_meta($post_id, '_kepoli_auto_tags', array_values($suggested_tags));
            } else {
                delete_post_meta($post_id, '_kepoli_auto_tags');
            }
            return;
        }

        $current_tags = wp_get_post_tags($post_id, ['fields' => 'names']);
        $current_tags = is_array($current_tags) ? $current_tags : [];
        $auto_tags = get_post_meta($post_id, '_kepoli_auto_tags', true);
        $auto_tags = is_array($auto_tags) ? $auto_tags : [];
        $has_manual_tags = $current_tags !== [] && self::normalized_tags($current_tags) !== self::normalized_tags($auto_tags);
        $has_stale_tags = $has_manual_tags && self::tags_look_stale_for_post($current_tags, $post);

        if ($has_manual_tags && !$has_stale_tags) {
            return;
        }

        if ($suggested_tags !== []) {
            wp_set_post_terms($post_id, $suggested_tags, 'post_tag', false);
            update_post_meta($post_id, '_kepoli_auto_tags', array_values($suggested_tags));
        }
    }

    private static function category_keyword_map(): array
    {
        return [
            'ciorbe-si-supe' => ['ciorba', 'bors', 'supa', 'supa crema', 'zeama', 'galuste', 'radauteana', 'soup', 'soups', 'stew', 'broth', 'cream soup'],
            'feluri-principale' => ['sarmale', 'tochitura', 'tocanita', 'friptura', 'mamaliga', 'ostropel', 'snitel', 'varza', 'pilaf', 'chiftele', 'paste', 'pasta', 'spaghetti', 'penne', 'fusilli', 'rigatoni', 'tagliatelle', 'lasagna', 'risotto', 'burger', 'burgeri', 'sandvis', 'sandwich', 'wrap', 'pui', 'chicken', 'dinner', 'lunch', 'main dish', 'family meal'],
            'patiserie-si-deserturi' => ['desert', 'prajitura', 'cozonac', 'placinta', 'clatite', 'papanasi', 'chec', 'cornulete', 'aluat', 'foi', 'dessert', 'cake', 'chocolate', 'sweet', 'cookies', 'pie', 'pastry', 'baking'],
            'conserve-si-garnituri' => ['zacusca', 'muraturi', 'salata', 'garnitura', 'borcan', 'compot', 'bulion', 'gem', 'dulceata', 'piure', 'side dish', 'salad', 'preserves', 'pickle', 'jam', 'sauce', 'vegetables'],
            self::guides_slug() => ['ghid', 'cum', 'calendar', 'meniuri', 'tehnici', 'organizare', 'ingrediente', 'bucatarie', 'pastrare', 'explica', 'guide', 'how', 'tips', 'history', 'explained', 'storage', 'pantry', 'ingredients', 'technique'],
        ];
    }

    private static function title_category_keyword_map(): array
    {
        return [
            'ciorbe-si-supe' => ['ciorba', 'bors', 'supa', 'supa crema', 'zeama'],
            'feluri-principale' => ['paste', 'pasta', 'spaghetti', 'penne', 'fusilli', 'rigatoni', 'lasagna', 'risotto', 'pilaf', 'tocanita', 'friptura', 'snitel', 'burger', 'burgeri', 'sandvis', 'sandwich', 'wrap'],
            'patiserie-si-deserturi' => ['desert', 'prajitura', 'cozonac', 'placinta', 'clatite', 'papanasi', 'chec', 'tort', 'cookies', 'cake', 'pie'],
            'conserve-si-garnituri' => ['zacusca', 'muraturi', 'garnitura', 'salata', 'compot', 'gem', 'dulceata', 'bulion', 'piure'],
        ];
    }

    private static function text_contains_keyword(string $source, string $keyword): bool
    {
        $needle = self::normalized_recipe_text($keyword);
        return $needle !== '' && str_contains($source, $needle);
    }

    private static function suggested_category_id(string $kind, WP_Post $post): int
    {
        $categories = get_terms([
            'taxonomy' => 'category',
            'hide_empty' => false,
        ]);

        if (!is_array($categories)) {
            return 0;
        }

        if ($kind === 'article') {
            return self::article_category_id();
        }

        $source_text = self::normalized_recipe_text(implode(' ', [
            $post->post_title,
            $post->post_excerpt,
            $post->post_content,
        ]));
        $title_text = self::normalized_recipe_text((string) $post->post_title);
        $source_words = self::keywords_from_text($source_text);
        $keyword_map = self::category_keyword_map();
        $title_keyword_map = self::title_category_keyword_map();
        $best_id = 0;
        $best_score = PHP_INT_MIN;

        foreach ($categories as $category) {
            if (
                !$category instanceof WP_Term
                || (int) $category->term_id === 1
                || self::is_article_category_term($category)
            ) {
                continue;
            }

            $label = self::normalized_recipe_text(implode(' ', [
                (string) $category->slug,
                (string) $category->name,
                (string) $category->description,
            ]));
            $haystack = self::keywords_from_text($category->name . ' ' . $category->description);
            $score = 0;

            foreach ($source_words as $word) {
                if (in_array($word, $haystack, true)) {
                    $score += 2;
                }
            }

            foreach ($keyword_map[(string) $category->slug] ?? [] as $keyword) {
                if (self::text_contains_keyword($source_text, $keyword)) {
                    $score += 6;
                }
            }

            foreach ($title_keyword_map[(string) $category->slug] ?? [] as $keyword) {
                if (self::text_contains_keyword($title_text, $keyword)) {
                    $score += 12;
                }
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best_id = (int) $category->term_id;
            }
        }

        return $best_score > 0 ? $best_id : 0;
    }

    private static function title_keyword_tag_map(): array
    {
        return [
            'ciorba' => ['ciorba'],
            'supa' => ['supa'],
            'soup' => ['soup'],
            'stew' => ['stew', 'comfort food'],
            'chicken' => ['chicken', 'dinner'],
            'burger' => ['burger', 'cina rapida'],
            'burgeri' => ['burger', 'cina rapida'],
            'sandvis' => ['sandvis', 'pranz'],
            'sandwich' => ['sandwich', 'lunch'],
            'paste' => ['paste', 'cina rapida'],
            'pasta' => ['pasta', 'dinner'],
            'rice' => ['rice', 'side dish'],
            'rosii' => ['rosii'],
            'tomate' => ['rosii'],
            'tomato' => ['tomato'],
            'mozzarella' => ['mozzarella'],
            'busuioc' => ['busuioc'],
            'basil' => ['basil'],
            'papanasi' => ['papanasi', 'desert'],
            'placinta' => ['placinta', 'desert'],
            'pie' => ['pie', 'dessert'],
            'cake' => ['cake', 'dessert'],
            'chocolate' => ['chocolate', 'dessert'],
            'cookies' => ['cookies', 'dessert'],
            'bread' => ['bread', 'baking'],
            'cozonac' => ['cozonac', 'aluat'],
            'zacusca' => ['zacusca', 'conserve'],
            'muraturi' => ['muraturi', 'conserve'],
            'ghid' => ['ingrediente'],
            'guide' => ['ingredients', 'kitchen tips'],
            'meniu' => ['meniu', 'familie'],
            'menu' => ['menu', 'family meals'],
            'aluat' => ['aluat', 'patiserie'],
            'dough' => ['dough', 'baking'],
            'sezon' => ['sezon'],
            'season' => ['seasonal'],
            'pastrare' => ['pastrare', 'organizare'],
            'storage' => ['storage', 'kitchen tips'],
        ];
    }

    private static function suggested_tags(int $post_id, string $kind, WP_Post $post): array
    {
        $source_words = self::keywords_from_text(implode(' ', [
            $post->post_title,
            $post->post_excerpt,
            $post->post_content,
        ]));
        $usage_counts = self::related_slug_usage_counts($post_id);
        $source_category_slug = self::primary_category_slug($post_id);
        $query = new WP_Query([
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'posts_per_page' => 120,
            'post__not_in' => $post_id ? [$post_id] : [],
            'orderby' => 'date',
            'order' => 'DESC',
            'fields' => 'ids',
        ]);

        $matched_posts = [];
        foreach ($query->posts as $candidate_id) {
            $candidate_id = (int) $candidate_id;
            $slug = (string) get_post_field('post_name', $candidate_id);
            $score = self::score_related_candidate(
                $candidate_id,
                $source_words,
                $source_category_slug,
                (int) ($usage_counts[$slug] ?? 0)
            );

            if ($score > 0) {
                $matched_posts[] = [
                    'id' => $candidate_id,
                    'score' => $score,
                    'tags' => wp_get_post_tags($candidate_id, ['fields' => 'names']),
                ];
            }
        }

        usort($matched_posts, static function (array $a, array $b): int {
            return $b['score'] <=> $a['score'];
        });

        $matched_posts = array_slice($matched_posts, 0, 8);
        $tag_scores = [];
        $seed_tags = $kind === 'article'
            ? ['ingrediente', 'organizare', 'tehnici']
            : ['retete romanesti'];
        $locked_tags = array_fill_keys($seed_tags, true);

        foreach ($seed_tags as $tag) {
            $tag_scores[$tag] = (int) ($tag_scores[$tag] ?? 0) + 1;
        }

        foreach ($matched_posts as $matched) {
            foreach ((array) ($matched['tags'] ?? []) as $tag) {
                $tag = trim((string) $tag);
                if ($tag === '') {
                    continue;
                }

                $tag_scores[$tag] = (int) ($tag_scores[$tag] ?? 0) + max(1, (int) $matched['score']);

                foreach ($source_words as $word) {
                    foreach (self::keywords_from_text($tag) as $normalized_tag_word) {
                        if ($normalized_tag_word === $word) {
                            $tag_scores[$tag] += 2;
                            break;
                        }
                    }
                }
            }
        }

        $title_text = self::normalized_recipe_text($post->post_title);
        foreach (self::title_keyword_tag_map() as $keyword => $tags) {
            if (!self::text_contains_keyword($title_text, $keyword)) {
                continue;
            }

            foreach ($tags as $tag) {
                $tag_scores[$tag] = (int) ($tag_scores[$tag] ?? 0) + 5;
                $locked_tags[$tag] = true;
            }
        }

        foreach (array_keys($tag_scores) as $tag) {
            if (isset($locked_tags[$tag])) {
                continue;
            }

            $tag_words = self::keywords_from_text($tag);
            if ($tag_words === [] || array_intersect($tag_words, $source_words) === []) {
                unset($tag_scores[$tag]);
            }
        }

        if ($tag_scores === []) {
            return [];
        }

        uksort($tag_scores, static function (string $left, string $right) use ($tag_scores): int {
            $score_compare = $tag_scores[$right] <=> $tag_scores[$left];
            return $score_compare !== 0 ? $score_compare : strcasecmp($left, $right);
        });

        return array_slice(array_keys($tag_scores), 0, 5);
    }

    private static function clean_slug_from_title(string $title): string
    {
        $parts = preg_split('/\s+/', self::plain_text($title)) ?: [];
        $stopwords = array_flip([
            'si', 'sau', 'din', 'de', 'la', 'cu', 'pentru', 'despre', 'care', 'este', 'sunt',
            'the', 'and', 'with', 'from', 'into', 'your', 'this', 'that', 'history', 'fascinating',
            'what', 'when', 'where', 'how', 'why', 'guide', 'tips', 'best', 'more',
        ]);
        $kept = [];

        foreach ($parts as $part) {
            $normalized = remove_accents(function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part));
            $normalized = preg_replace('/[^a-z0-9-]/', '', (string) $normalized);
            if ($normalized === '' || isset($stopwords[$normalized])) {
                continue;
            }

            $kept[] = $normalized;
            if (count($kept) >= 8) {
                break;
            }
        }

        $slug = sanitize_title(implode(' ', $kept));
        if ($slug === '') {
            $slug = sanitize_title($title);
        }

        return $slug;
    }

    private static function normalize_content_structure(string $content, string $kind = 'article'): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        if ($kind === 'recipe' && self::should_rebuild_simple_recipe_markup($content)) {
            $rebuilt = self::rebuild_simple_recipe_markup($content);
            if ($rebuilt !== '') {
                return $rebuilt;
            }
        }

        if (!class_exists('DOMDocument')) {
            $heading_index = 0;

            $content = (string) preg_replace_callback(
                '/<h([1-6])([^>]*)>(.*?)<\/h\1>/is',
                static function (array $matches) use (&$heading_index): string {
                    $attributes = isset($matches[2]) ? (string) $matches[2] : '';
                    $inner_html = isset($matches[3]) ? trim((string) $matches[3]) : '';
                    $plain = trim(wp_strip_all_tags($inner_html));

                    if ($plain === '') {
                        return '';
                    }

                    $target_level = $heading_index === 0 ? 2 : (((int) ($matches[1] ?? 2)) <= 2 ? 2 : 3);
                    $heading_index++;

                    return sprintf('<h%1$d%2$s>%3$s</h%1$d>', $target_level, $attributes, $inner_html);
                },
                $content
            );

            $content = (string) preg_replace('/<p>\s*<\/p>/i', '', $content);
            return trim($content);
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="kepoli-normalize-root">' . $content . '</div>';

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $document->getElementById('kepoli-normalize-root');
        if (!$root) {
            return $content;
        }

        $nodes = [];
        foreach ($root->childNodes as $child) {
            $nodes[] = $child;
        }

        $heading_index = 0;
        $in_faq = false;

        foreach ($nodes as $node) {
            if ($node->nodeType === XML_TEXT_NODE && trim((string) $node->textContent) === '') {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
                continue;
            }

            if ($node->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            /** @var DOMElement $node */
            $tag = strtolower((string) $node->nodeName);
            $has_comment_child = false;
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $has_comment_child = true;
                    break;
                }
            }

            $plain = trim(wp_strip_all_tags($document->saveHTML($node)));
            if ($plain === '' && !$has_comment_child) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
                continue;
            }

            if (preg_match('/^h[1-6]$/', $tag)) {
                $target_tag = $heading_index === 0 ? 'h2' : (((int) substr($tag, 1)) <= 2 ? 'h2' : 'h3');
                $node = self::replace_dom_tag($document, $node, $target_tag);
                $heading_index++;
                $in_faq = self::is_faq_heading($plain);
                continue;
            }

            if ($tag === 'p' && self::is_outline_heading($plain)) {
                $node = self::replace_dom_tag($document, $node, 'h2');
                $heading_index++;
                $in_faq = self::is_faq_heading($plain);
                continue;
            }

            if ($tag === 'p' && $in_faq && self::looks_like_faq_question($plain)) {
                self::replace_dom_tag($document, $node, 'h3');
            }
        }

        $output = [];
        foreach ($root->childNodes as $child) {
            $html = trim((string) $document->saveHTML($child));
            if ($html !== '') {
                $output[] = $html;
            }
        }

        return trim(implode("\n", $output));
    }

    private static function split_content_into_parts(string $content, int $parts): string
    {
        $blocks = self::content_blocks($content);
        if (count($blocks) <= $parts) {
            return $content;
        }

        $preferred = self::preferred_block_break_indexes($blocks);
        $breaks = self::compute_split_breaks(count($blocks), $parts, $preferred);
        if (!$breaks) {
            return $content;
        }

        $output = [];
        foreach ($blocks as $index => $block) {
            if (in_array($index, $breaks, true)) {
                $output[] = '<!--nextpage-->';
            }
            $output[] = $block;
        }

        return trim(implode("\n\n", $output));
    }

    private static function content_blocks(string $content): array
    {
        if (!class_exists('DOMDocument')) {
            $blocks = preg_split('/\n{2,}/', trim($content)) ?: [];
            return array_values(array_filter(array_map('trim', $blocks)));
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<div id="kepoli-split-root">' . str_replace('<!--nextpage-->', '', $content) . '</div>';

        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $document->getElementById('kepoli-split-root');
        if (!$root) {
            $blocks = preg_split('/\n{2,}/', trim($content)) ?: [];
            return array_values(array_filter(array_map('trim', $blocks)));
        }

        $blocks = [];
        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_COMMENT_NODE) {
                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE && trim((string) $node->textContent) === '') {
                continue;
            }

            $blocks[] = trim($document->saveHTML($node));
        }

        return array_values(array_filter($blocks));
    }

    private static function preferred_block_break_indexes(array $blocks): array
    {
        $indexes = [];

        foreach ($blocks as $index => $block) {
            if ($index === 0) {
                continue;
            }

            if (preg_match('/^<h[23]\b/i', $block)) {
                $indexes[] = $index;
            }
        }

        return $indexes;
    }

    private static function compute_split_breaks(int $total, int $parts, array $preferred): array
    {
        $breaks = [];
        $used = [];
        $tolerance = max(1, (int) floor($total / ($parts * 2)));

        for ($index = 1; $index < $parts; $index++) {
            $target = max(1, (int) round(($total * $index) / $parts));
            $chosen = $target;

            foreach ($preferred as $candidate) {
                if (isset($used[$candidate]) || $candidate <= 0 || $candidate >= $total) {
                    continue;
                }

                if (abs($candidate - $target) <= $tolerance && abs($candidate - $target) < abs($chosen - $target)) {
                    $chosen = $candidate;
                }
            }

            while (isset($used[$chosen]) && $chosen < ($total - 1)) {
                $chosen++;
            }

            $chosen = max(1, min($total - 1, $chosen));
            $used[$chosen] = true;
            $breaks[] = $chosen;
        }

        return $breaks;
    }

    private static function build_recipe_faq_block(int $post_id, string $content): string
    {
        $recipe = self::recipe_data($post_id);
        $items = [];

        if ($recipe['servings'] !== '') {
            $items[] = [
                'question' => self::content_text('Cate portii ies din reteta?', 'How many servings does this recipe make?'),
                'answer' => sprintf(
                    self::content_text('Reteta este gandita pentru %s.', 'The recipe is designed for %s.'),
                    $recipe['servings']
                ),
            ];
        }

        $time_answer = self::recipe_time_faq_answer($recipe);
        if ($time_answer !== '') {
            $items[] = [
                'question' => self::content_text('Cat dureaza pregatirea?', 'How long does it take?'),
                'answer' => $time_answer,
            ];
        }

        $storage_answer = self::extract_storage_answer($content);
        if ($storage_answer !== '') {
            $items[] = [
                'question' => self::content_text('Cum se pastreaza?', 'How should it be stored?'),
                'answer' => $storage_answer,
            ];
        }

        if (count($items) < 2) {
            return '';
        }

        $html = [self::AUTO_FAQ_START, '<h2>' . esc_html(self::content_text('Intrebari frecvente', 'Frequently asked questions')) . '</h2>'];

        foreach (array_slice($items, 0, 3) as $item) {
            $html[] = '<h3>' . esc_html($item['question']) . '</h3>';
            $html[] = '<p>' . esc_html($item['answer']) . '</p>';
        }

        $html[] = self::AUTO_FAQ_END;

        return implode("\n", $html);
    }

    private static function recipe_time_faq_answer(array $recipe): string
    {
        $prep = (int) ($recipe['prep_minutes'] ?? 0);
        $cook = (int) ($recipe['cook_minutes'] ?? 0);
        $total = $prep + $cook;

        if ($prep > 0 && $cook > 0) {
            return sprintf(
                self::content_text('Ai nevoie de aproximativ %1$d minute pentru pregatire, %2$d minute pentru gatire si cam %3$d minute in total.', 'You need about %1$d minutes for prep, %2$d minutes for cooking, and about %3$d minutes in total.'),
                $prep,
                $cook,
                $total
            );
        }

        if ($total > 0) {
            return sprintf(
                self::content_text('Reteta cere aproximativ %d minute in total.', 'The recipe takes about %d minutes in total.'),
                $total
            );
        }

        return '';
    }

    private static function auto_internal_link_posts(string $kind, array $related_recipes, array $related_articles): array
    {
        $recipe_queue = array_values(array_unique(array_map('sanitize_title', $related_recipes)));
        $article_queue = array_values(array_unique(array_map('sanitize_title', $related_articles)));
        $recipe_posts = self::posts_from_slug_queue($recipe_queue, 2);
        $article_posts = self::posts_from_slug_queue($article_queue, 2);

        if ($kind === 'article' && $recipe_posts && $article_posts) {
            return [$recipe_posts[0], $article_posts[0]];
        }

        if ($kind === 'recipe' && $article_posts) {
            $posts = [$article_posts[0]];
            if ($recipe_posts) {
                $posts[] = $recipe_posts[0];
            } elseif (isset($article_posts[1])) {
                $posts[] = $article_posts[1];
            }

            return array_slice($posts, 0, 2);
        }

        return array_slice(array_merge($recipe_posts, $article_posts), 0, 2);
    }

    private static function suggest_related_slugs(int $post_id, string $kind, WP_Post $post): array
    {
        $source_category_slug = self::primary_category_slug($post_id);
        $usage_counts = self::related_slug_usage_counts($post_id);
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
                'score' => self::score_related_candidate(
                    $candidate_id,
                    $source_words,
                    $source_category_slug,
                    (int) ($usage_counts[$slug] ?? 0)
                ),
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

    private static function score_related_candidate(int $post_id, array $source_words, string $source_category_slug = '', int $usage_count = 0): int
    {
        if (!$source_words) {
            return 0;
        }

        $article_category_slugs = self::article_category_slugs();
        $candidate_words = self::keywords_from_text(self::related_candidate_text($post_id));
        $candidate_lookup = array_flip($candidate_words);
        $candidate_category_slug = self::primary_category_slug($post_id);
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

        if ($source_category_slug !== '') {
            if ($candidate_category_slug === $source_category_slug) {
                $score += 12;
            } elseif (
                !in_array($source_category_slug, $article_category_slugs, true)
                && $candidate_category_slug !== ''
                && !in_array($candidate_category_slug, $article_category_slugs, true)
            ) {
                $score -= 2;
            }
        }

        if ($usage_count > 0) {
            $score -= min(9, $usage_count * 2);
        }

        return $score;
    }

    private static function is_post_language_consistent(int $post_id, WP_Post $post): bool
    {
        $content_language = self::detect_language(implode(' ', [
            $post->post_title,
            $post->post_excerpt,
            $post->post_content,
        ]));

        if ($content_language === 'unknown') {
            return true;
        }

        $meta_language = self::detect_language((string) get_post_meta($post_id, '_kepoli_meta_description', true));
        $slug_language = self::detect_language(str_replace('-', ' ', (string) $post->post_name));

        if ($meta_language !== 'unknown' && $meta_language !== $content_language) {
            return false;
        }

        if ($slug_language !== 'unknown' && $slug_language !== $content_language) {
            return false;
        }

        return true;
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

    private static function strip_auto_faq_block(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $pattern = '/' . preg_quote(self::AUTO_FAQ_START, '/') . '.*?' . preg_quote(self::AUTO_FAQ_END, '/') . '\s*/is';
        $content = (string) preg_replace($pattern, '', $content);
        return rtrim($content);
    }

    private static function content_has_faq_section(string $content): bool
    {
        $content = self::strip_auto_faq_block($content);
        if ($content === '') {
            return false;
        }

        return (bool) preg_match('/<h[23][^>]*>\s*(?:Intrebari frecvente|Frequently asked questions|FAQs?)\s*<\/h[23]>/iu', $content);
    }

    private static function extract_storage_answer(string $content): string
    {
        if (!preg_match('/<h[23][^>]*>\s*(?:Cum pastrezi|Cum se pastreaza|Storage)\s*<\/h[23]>\s*(<p\b[^>]*>.*?<\/p>)/isu', $content, $matches)) {
            return '';
        }

        $text = self::sentence_limit((string) ($matches[1] ?? ''), 220, 60);
        return $text;
    }

    private static function strip_auto_internal_links_block(string $content): string
    {
        if ($content === '') {
            return '';
        }

        $pattern = '/' . preg_quote(self::AUTO_INTERNAL_LINKS_START, '/') . '.*?' . preg_quote(self::AUTO_INTERNAL_LINKS_END, '/') . '\s*/is';
        $content = (string) preg_replace($pattern, '', $content);
        return rtrim($content);
    }

    private static function content_has_internal_links(string $content, int $post_id = 0): bool
    {
        $content = self::strip_auto_internal_links_block($content);
        if ($content === '') {
            return false;
        }

        $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
        $current_permalink = $post_id ? untrailingslashit((string) get_permalink($post_id)) : '';

        if (!preg_match_all('/<a\b[^>]*href=("|\')([^"\']+)\\1/i', $content, $matches, PREG_SET_ORDER)) {
            return false;
        }

        foreach ($matches as $match) {
            $href = html_entity_decode((string) ($match[2] ?? ''), ENT_QUOTES, get_bloginfo('charset'));
            $href = trim($href);

            if ($href === '' || strpos($href, '#') === 0 || stripos($href, 'mailto:') === 0 || stripos($href, 'tel:') === 0) {
                continue;
            }

            if (strpos($href, '/') === 0) {
                return true;
            }

            $href_host = (string) wp_parse_url($href, PHP_URL_HOST);
            if ($href_host === '' || !$host || !hash_equals(strtolower($host), strtolower($href_host))) {
                continue;
            }

            if ($current_permalink !== '' && untrailingslashit($href) === $current_permalink) {
                continue;
            }

            return true;
        }

        return false;
    }

    private static function build_auto_internal_links_block(array $posts): string
    {
        $anchors = [];
        $kinds = [];

        foreach ($posts as $post) {
            if (!$post instanceof WP_Post) {
                continue;
            }

            $kinds[] = self::post_kind($post->ID);
            $anchors[] = sprintf(
                '<a href="%1$s">%2$s</a>',
                esc_url(get_permalink($post)),
                esc_html(get_the_title($post))
            );
        }

        if (!$anchors) {
            return '';
        }

        $separator = self::content_text(' si ', ' and ');
        $links_text = count($anchors) === 1
            ? $anchors[0]
            : implode($separator, $anchors);
        $lead = self::auto_internal_links_lead($kinds, count($anchors));

        return self::AUTO_INTERNAL_LINKS_START
            . "\n"
            . '<p><strong>' . esc_html($lead) . '</strong> ' . $links_text . '.</p>'
            . "\n"
            . self::AUTO_INTERNAL_LINKS_END;
    }

    private static function auto_internal_links_lead(array $kinds, int $count): string
    {
        $kinds = array_values(array_unique(array_filter($kinds)));

        if ($count <= 0) {
            return self::content_text('Citeste si:', 'Read also:');
        }

        if ($kinds === ['recipe']) {
            return $count === 1
                ? self::content_text('Ca sa pui ideea in practica, vezi:', 'To put the idea into practice, see:')
                : self::content_text('Ca sa pui ideile in practica, vezi:', 'To put the ideas into practice, see:');
        }

        if ($kinds === ['article']) {
            return $count === 1
                ? self::content_text('Pentru context suplimentar, vezi:', 'For more context, see:')
                : self::content_text('Pentru context suplimentar, vezi si:', 'For more context, also see:');
        }

        return $count === 1
            ? self::content_text('Ca sa mergi mai departe, vezi:', 'To continue, see:')
            : self::content_text('Ca sa mergi mai departe, vezi si:', 'To continue, also see:');
    }

    private static function place_auto_internal_links_in_content(string $content, array $posts): string
    {
        $block = self::build_auto_internal_links_block($posts);
        if ($block === '') {
            return $content;
        }

        if (!preg_match_all('/<p\b[^>]*>.*?<\/p>/is', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $trimmed = rtrim($content);
            return $trimmed . ($trimmed === '' ? '' : "\n\n") . $block;
        }

        $best_index = -1;
        $best_score = 0;
        $post_keywords = [];

        foreach ($posts as $post) {
            if ($post instanceof WP_Post) {
                $post_keywords[] = self::keywords_from_text(self::related_candidate_text($post->ID));
            }
        }

        foreach ($matches[0] as $index => $match) {
            $paragraph_html = (string) $match[0];
            $paragraph_text = self::plain_text($paragraph_html);
            $paragraph_keywords = self::keywords_from_text($paragraph_text);
            $score = 0;

            foreach ($post_keywords as $keywords) {
                $score += self::keyword_overlap_score($paragraph_keywords, $keywords);
            }

            if (self::word_count($paragraph_text) >= 12) {
                $score += 1;
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best_index = $index;
            }
        }

        if ($best_index < 0) {
            $best_index = max(0, count($matches[0]) - 1);
        }

        $selected = $matches[0][$best_index];
        $paragraph_html = (string) $selected[0];
        $offset = (int) $selected[1];
        $insert_at = $offset + strlen($paragraph_html);

        return substr($content, 0, $insert_at) . "\n\n" . $block . substr($content, $insert_at);
    }

    private static function keyword_overlap_score(array $left_words, array $right_words): int
    {
        if (!$left_words || !$right_words) {
            return 0;
        }

        $lookup = array_flip($right_words);
        $score = 0;

        foreach ($left_words as $word) {
            if (isset($lookup[$word])) {
                $score += 3;
                continue;
            }

            foreach ($right_words as $candidate) {
                if (strpos($candidate, $word) !== false || strpos($word, $candidate) !== false) {
                    $score += 1;
                    break;
                }
            }
        }

        return $score;
    }

    private static function detect_language(string $text): string
    {
        $plain = self::plain_text($text);
        if ($plain === '') {
            return 'unknown';
        }

        $normalized = function_exists('mb_strtolower') ? mb_strtolower($plain, 'UTF-8') : strtolower($plain);
        $romanian_markers = [' si ', ' din ', ' pentru ', ' cu ', ' este ', ' sunt ', ' reteta ', ' articol ', ' gatit ', ' ciocolata ', ' desert '];
        $english_markers = [' the ', ' and ', ' with ', ' from ', ' history ', ' guide ', ' recipe ', ' article ', ' chocolate ', ' sweet '];

        $romanian_score = preg_match('/[ăâîșşțţ]/u', $normalized) ? 4 : 0;
        $english_score = 0;

        foreach ($romanian_markers as $marker) {
            if (strpos(' ' . $normalized . ' ', $marker) !== false) {
                $romanian_score += 2;
            }
        }

        foreach ($english_markers as $marker) {
            if (strpos(' ' . $normalized . ' ', $marker) !== false) {
                $english_score += 2;
            }
        }

        if ($romanian_score === 0 && $english_score === 0) {
            return 'unknown';
        }

        if ($romanian_score >= $english_score + 2) {
            return 'ro';
        }

        if ($english_score >= $romanian_score + 2) {
            return 'en';
        }

        return 'unknown';
    }

    private static function posts_from_slug_queue(array $slugs, int $limit = 2): array
    {
        $posts = [];

        foreach ($slugs as $slug) {
            if ($slug === '') {
                continue;
            }

            $candidate = get_page_by_path($slug, OBJECT, 'post');
            if (!$candidate instanceof WP_Post || $candidate->post_status !== 'publish') {
                continue;
            }

            $posts[] = $candidate;
            if (count($posts) >= $limit) {
                break;
            }
        }

        return $posts;
    }

    private static function update_post_content(int $post_id, string $content): void
    {
        self::$is_updating_post = true;
        wp_update_post([
            'ID' => $post_id,
            'post_content' => $content,
        ]);
        self::$is_updating_post = false;
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

        $trimmed = rtrim(self::limit_text($slice, $end), " \t\n\r\0\x0B,;:.!?");
        if ($end === $sentence_end + 1 && $trimmed !== '') {
            return $trimmed;
        }

        return $trimmed . '...';
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

    private static function normalized_text_lines(array $lines): array
    {
        $clean = [];

        foreach ($lines as $line) {
            $value = trim(sanitize_text_field((string) $line));
            if ($value !== '') {
                $clean[] = $value;
            }
        }

        return $clean;
    }

    private static function stored_recipe_data(int $post_id): array
    {
        $json = (string) get_post_meta($post_id, '_kepoli_recipe_json', true);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            return [
                'servings' => '',
                'prep_minutes' => 0,
                'cook_minutes' => 0,
                'total_minutes' => 0,
                'ingredients' => [],
                'steps' => [],
            ];
        }

        return [
            'servings' => self::recipe_servings_has_value((string) ($data['servings'] ?? '')) ? sanitize_text_field((string) ($data['servings'] ?? '')) : '',
            'prep_minutes' => isset($data['prep_iso']) ? self::iso_to_minutes((string) $data['prep_iso']) : 0,
            'cook_minutes' => isset($data['cook_iso']) ? self::iso_to_minutes((string) $data['cook_iso']) : 0,
            'total_minutes' => isset($data['total_iso']) ? self::iso_to_minutes((string) $data['total_iso']) : 0,
            'ingredients' => self::normalized_text_lines(isset($data['ingredients']) && is_array($data['ingredients']) ? $data['ingredients'] : []),
            'steps' => self::normalized_text_lines(isset($data['steps']) && is_array($data['steps']) ? $data['steps'] : []),
        ];
    }

    private static function recipe_data_is_empty(array $data): bool
    {
        return ($data['servings'] ?? '') === ''
            && (int) ($data['prep_minutes'] ?? 0) === 0
            && (int) ($data['cook_minutes'] ?? 0) === 0
            && (int) ($data['total_minutes'] ?? 0) === 0
            && ($data['ingredients'] ?? []) === []
            && ($data['steps'] ?? []) === [];
    }

    private static function recipe_data_matches(array $left, array $right): bool
    {
        return [
            'servings' => self::recipe_servings_has_value((string) ($left['servings'] ?? '')) ? sanitize_text_field((string) ($left['servings'] ?? '')) : '',
            'prep_minutes' => max(0, (int) ($left['prep_minutes'] ?? 0)),
            'cook_minutes' => max(0, (int) ($left['cook_minutes'] ?? 0)),
            'total_minutes' => max(0, (int) ($left['total_minutes'] ?? 0)),
            'ingredients' => self::normalized_text_lines($left['ingredients'] ?? []),
            'steps' => self::normalized_text_lines($left['steps'] ?? []),
        ] === [
            'servings' => self::recipe_servings_has_value((string) ($right['servings'] ?? '')) ? sanitize_text_field((string) ($right['servings'] ?? '')) : '',
            'prep_minutes' => max(0, (int) ($right['prep_minutes'] ?? 0)),
            'cook_minutes' => max(0, (int) ($right['cook_minutes'] ?? 0)),
            'total_minutes' => max(0, (int) ($right['total_minutes'] ?? 0)),
            'ingredients' => self::normalized_text_lines($right['ingredients'] ?? []),
            'steps' => self::normalized_text_lines($right['steps'] ?? []),
        ];
    }

    private static function save_recipe_json(int $post_id, WP_Post $post): void
    {
        $posted = [
            'ingredients' => self::posted_lines('kepoli_recipe_ingredients'),
            'steps' => self::posted_lines('kepoli_recipe_steps'),
            'servings' => self::recipe_servings_has_value(isset($_POST['kepoli_recipe_servings']) ? (string) wp_unslash((string) $_POST['kepoli_recipe_servings']) : '')
                ? sanitize_text_field(wp_unslash((string) $_POST['kepoli_recipe_servings']))
                : '',
            'prep_minutes' => isset($_POST['kepoli_recipe_prep_minutes']) ? absint(wp_unslash((string) $_POST['kepoli_recipe_prep_minutes'])) : 0,
            'cook_minutes' => isset($_POST['kepoli_recipe_cook_minutes']) ? absint(wp_unslash((string) $_POST['kepoli_recipe_cook_minutes'])) : 0,
            'total_minutes' => isset($_POST['kepoli_recipe_total_minutes']) ? absint(wp_unslash((string) $_POST['kepoli_recipe_total_minutes'])) : 0,
        ];
        $existing = self::stored_recipe_data($post_id);
        $auto_generated = self::text_was_auto_generated($post_id, '_kepoli_auto_recipe_json');
        $should_refresh = (self::recipe_data_is_empty($existing) && self::recipe_data_is_empty($posted))
            || ($auto_generated && self::recipe_data_matches($posted, $existing));
        $extracted = self::extract_recipe_data_from_content((string) $post->post_content);
        $extracted_data = [
            'ingredients' => !empty($extracted['ingredients']) && is_array($extracted['ingredients']) ? self::normalized_text_lines($extracted['ingredients']) : [],
            'steps' => !empty($extracted['steps']) && is_array($extracted['steps']) ? self::normalized_text_lines($extracted['steps']) : [],
            'servings' => self::recipe_servings_has_value((string) ($extracted['servings'] ?? '')) ? sanitize_text_field((string) $extracted['servings']) : '',
            'prep_minutes' => !empty($extracted['prep_minutes']) ? max(0, (int) $extracted['prep_minutes']) : 0,
            'cook_minutes' => isset($extracted['cook_minutes']) ? max(0, (int) $extracted['cook_minutes']) : 0,
            'total_minutes' => !empty($extracted['total_minutes']) ? max(0, (int) $extracted['total_minutes']) : 0,
        ];

        if ($should_refresh) {
            $resolved_data = [
                'ingredients' => $extracted_data['ingredients'] !== [] ? $extracted_data['ingredients'] : $existing['ingredients'],
                'steps' => $extracted_data['steps'] !== [] ? $extracted_data['steps'] : $existing['steps'],
                'servings' => $extracted_data['servings'] !== '' ? $extracted_data['servings'] : $existing['servings'],
                'prep_minutes' => $extracted_data['prep_minutes'] > 0 ? $extracted_data['prep_minutes'] : $existing['prep_minutes'],
                'cook_minutes' => $extracted_data['cook_minutes'] > 0 ? $extracted_data['cook_minutes'] : $existing['cook_minutes'],
                'total_minutes' => $extracted_data['total_minutes'] > 0 ? $extracted_data['total_minutes'] : $existing['total_minutes'],
            ];
            $is_auto = true;
        } else {
            $resolved_data = $posted;

            if ($resolved_data['ingredients'] === [] && $extracted_data['ingredients'] !== []) {
                $resolved_data['ingredients'] = $extracted_data['ingredients'];
            }

            if ($resolved_data['steps'] === [] && $extracted_data['steps'] !== []) {
                $resolved_data['steps'] = $extracted_data['steps'];
            }

            if ($resolved_data['servings'] === '' && $extracted_data['servings'] !== '') {
                $resolved_data['servings'] = $extracted_data['servings'];
            }

            if ((int) $resolved_data['prep_minutes'] === 0 && $extracted_data['prep_minutes'] > 0) {
                $resolved_data['prep_minutes'] = $extracted_data['prep_minutes'];
            }

            if ((int) $resolved_data['cook_minutes'] === 0 && $extracted_data['cook_minutes'] > 0) {
                $resolved_data['cook_minutes'] = $extracted_data['cook_minutes'];
            }

            if ((int) $resolved_data['total_minutes'] === 0 && $extracted_data['total_minutes'] > 0) {
                $resolved_data['total_minutes'] = $extracted_data['total_minutes'];
            }

            $is_auto = !self::recipe_data_is_empty($extracted_data) && self::recipe_data_matches($resolved_data, $extracted_data);
        }

        $resolved_data = [
            'ingredients' => self::normalized_text_lines($resolved_data['ingredients'] ?? []),
            'steps' => self::normalized_text_lines($resolved_data['steps'] ?? []),
            'servings' => self::recipe_servings_has_value((string) ($resolved_data['servings'] ?? '')) ? sanitize_text_field((string) ($resolved_data['servings'] ?? '')) : '',
            'prep_minutes' => max(0, (int) ($resolved_data['prep_minutes'] ?? 0)),
            'cook_minutes' => max(0, (int) ($resolved_data['cook_minutes'] ?? 0)),
            'total_minutes' => max(0, (int) ($resolved_data['total_minutes'] ?? 0)),
        ];

        if ($resolved_data['total_minutes'] <= 0 && ($resolved_data['prep_minutes'] > 0 || $resolved_data['cook_minutes'] > 0)) {
            $resolved_data['total_minutes'] = $resolved_data['prep_minutes'] + $resolved_data['cook_minutes'];
        }

        if (self::recipe_data_is_empty($resolved_data)) {
            delete_post_meta($post_id, '_kepoli_recipe_json');
            self::store_auto_text_flag($post_id, '_kepoli_auto_recipe_json', false);
            return;
        }

        update_post_meta($post_id, '_kepoli_recipe_json', wp_json_encode([
            'category' => self::primary_category_name($post_id),
            'servings' => $resolved_data['servings'],
            'prep_iso' => self::minutes_to_iso($resolved_data['prep_minutes']),
            'cook_iso' => self::minutes_to_iso($resolved_data['cook_minutes']),
            'total_iso' => self::minutes_to_iso($resolved_data['total_minutes']),
            'ingredients' => $resolved_data['ingredients'],
            'steps' => $resolved_data['steps'],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        self::store_auto_text_flag($post_id, '_kepoli_auto_recipe_json', $is_auto);
    }

    private static function primary_category_name(int $post_id): string
    {
        $categories = get_the_category($post_id);
        return !empty($categories) ? $categories[0]->name : 'Retete romanesti';
    }

    private static function primary_category_slug(int $post_id): string
    {
        $categories = get_the_category($post_id);
        return !empty($categories) && isset($categories[0]->slug) ? (string) $categories[0]->slug : '';
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
