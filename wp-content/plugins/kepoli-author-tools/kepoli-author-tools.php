<?php
/**
 * Plugin Name: Kepoli Author Tools
 * Description: Simplifies the post editor for Kepoli authors and adds toolbar tools for splitting long posts into two or three pages.
 * Version: 1.0.0
 * Author: Kepoli
 * Text Domain: kepoli-author-tools
 */

if (!defined('ABSPATH')) {
    exit;
}

final class Kepoli_Author_Tools
{
    private const VERSION = '1.0.0';

    public static function init(): void
    {
        add_filter('use_block_editor_for_post_type', [self::class, 'use_classic_editor_for_posts'], 10, 2);
        add_filter('mce_external_plugins', [self::class, 'register_tinymce_plugin']);
        add_filter('mce_buttons', [self::class, 'register_tinymce_buttons']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_admin_assets']);
        add_action('add_meta_boxes_post', [self::class, 'add_writer_guide_box']);
    }

    public static function use_classic_editor_for_posts(bool $use_block_editor, string $post_type): bool
    {
        return $post_type === 'post' ? false : $use_block_editor;
    }

    public static function register_tinymce_plugin(array $plugins): array
    {
        if (self::is_post_editor_screen()) {
            $plugins['kepoli_author_tools'] = plugins_url('assets/editor-tools.js', __FILE__);
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
        </div>
        <?php
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
}

Kepoli_Author_Tools::init();
