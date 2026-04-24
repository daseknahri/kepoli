<?php
/**
 * Plugin Name: Kepoli Newsletter Signups
 * Description: Stores lightweight newsletter signups inside WordPress admin.
 */

if (!defined('ABSPATH')) {
    exit;
}

function kepoli_newsletter_post_type(): string
{
    return 'kepoli_newsletter';
}

function kepoli_newsletter_normalize_email(string $email): string
{
    $email = sanitize_email(wp_unslash($email));
    return strtolower(trim($email));
}

function kepoli_newsletter_signup_exists(string $email): bool
{
    $entries = get_posts([
        'post_type' => kepoli_newsletter_post_type(),
        'post_status' => 'private',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_kepoli_newsletter_email',
        'meta_value' => $email,
    ]);

    return !empty($entries);
}

function kepoli_newsletter_can_manage(): bool
{
    return current_user_can('edit_posts');
}

function kepoli_newsletter_redirect(string $redirect_to, string $status): void
{
    $redirect_to = wp_validate_redirect($redirect_to, home_url('/'));
    $redirect_to = remove_query_arg('newsletter', $redirect_to);
    wp_safe_redirect(add_query_arg('newsletter', $status, $redirect_to), 303, 'Kepoli');
    exit;
}

add_action('init', static function (): void {
    register_post_type(kepoli_newsletter_post_type(), [
        'labels' => [
            'name' => __('Abonari newsletter', 'kepoli'),
            'singular_name' => __('Abonare newsletter', 'kepoli'),
            'menu_name' => __('Newsletter', 'kepoli'),
            'name_admin_bar' => __('Abonare newsletter', 'kepoli'),
            'all_items' => __('Toate abonarile', 'kepoli'),
            'search_items' => __('Cauta emailuri', 'kepoli'),
            'not_found' => __('Nu exista abonari inca.', 'kepoli'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_admin_bar' => false,
        'show_in_rest' => false,
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'rewrite' => false,
        'query_var' => false,
        'menu_icon' => 'dashicons-email-alt',
        'menu_position' => 24,
        'supports' => ['title'],
        'map_meta_cap' => true,
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => 'do_not_allow',
        ],
    ]);
});

add_filter('use_block_editor_for_post_type', static function (bool $use_block_editor, string $post_type): bool {
    if ($post_type === kepoli_newsletter_post_type()) {
        return false;
    }

    return $use_block_editor;
}, 10, 2);

add_filter('enter_title_here', static function (string $title, WP_Post $post): string {
    if ($post->post_type === kepoli_newsletter_post_type()) {
        return __('Adresa de email', 'kepoli');
    }

    return $title;
}, 10, 2);

add_filter('manage_edit_kepoli_newsletter_columns', static function (array $columns): array {
    return [
        'cb' => $columns['cb'] ?? '',
        'title' => __('Email', 'kepoli'),
        'newsletter_source' => __('Sursa', 'kepoli'),
        'date' => __('Data', 'kepoli'),
    ];
});

add_action('manage_kepoli_newsletter_posts_custom_column', static function (string $column, int $post_id): void {
    if ($column !== 'newsletter_source') {
        return;
    }

    $source_label = trim((string) get_post_meta($post_id, '_kepoli_newsletter_source_label', true));
    $source_url = trim((string) get_post_meta($post_id, '_kepoli_newsletter_source_url', true));

    if ($source_label === '') {
        $source_label = __('Site Kepoli', 'kepoli');
    }

    if ($source_url !== '') {
        printf(
            '<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
            esc_url($source_url),
            esc_html($source_label)
        );
        return;
    }

    echo esc_html($source_label);
}, 10, 2);

add_filter('post_row_actions', static function (array $actions, WP_Post $post): array {
    if ($post->post_type !== kepoli_newsletter_post_type()) {
        return $actions;
    }

    unset($actions['inline hide-if-no-js'], $actions['view']);
    return $actions;
}, 10, 2);

add_action('pre_get_posts', static function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }

    if ($query->get('post_type') !== kepoli_newsletter_post_type()) {
        return;
    }

    if (!$query->get('orderby')) {
        $query->set('orderby', 'date');
    }

    if (!$query->get('order')) {
        $query->set('order', 'DESC');
    }
});

add_action('restrict_manage_posts', static function (): void {
    global $typenow;

    if ($typenow !== kepoli_newsletter_post_type() || !kepoli_newsletter_can_manage()) {
        return;
    }

    $url = wp_nonce_url(
        admin_url('admin.php?action=kepoli_export_newsletter'),
        'kepoli_export_newsletter'
    );

    printf(
        '<a class="button" href="%1$s">%2$s</a>',
        esc_url($url),
        esc_html__('Export CSV', 'kepoli')
    );
});

function kepoli_export_newsletter_csv(): void
{
    if (!kepoli_newsletter_can_manage()) {
        wp_die(esc_html__('Nu ai permisiunea pentru acest export.', 'kepoli'));
    }

    check_admin_referer('kepoli_export_newsletter');

    $entries = get_posts([
        'post_type' => kepoli_newsletter_post_type(),
        'post_status' => 'private',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="kepoli-newsletter-' . gmdate('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    if ($output === false) {
        exit;
    }

    fputcsv($output, ['email', 'source', 'source_url', 'subscribed_at']);

    foreach ($entries as $entry) {
        fputcsv($output, [
            (string) get_post_meta($entry->ID, '_kepoli_newsletter_email', true),
            (string) get_post_meta($entry->ID, '_kepoli_newsletter_source_label', true),
            (string) get_post_meta($entry->ID, '_kepoli_newsletter_source_url', true),
            get_post_time('c', true, $entry),
        ]);
    }

    fclose($output);
    exit;
}

add_action('admin_action_kepoli_export_newsletter', 'kepoli_export_newsletter_csv');

function kepoli_handle_newsletter_signup(): void
{
    $redirect_to = isset($_POST['redirect_to']) ? (string) wp_unslash($_POST['redirect_to']) : home_url('/');

    if (!isset($_POST['kepoli_newsletter_nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['kepoli_newsletter_nonce']), 'kepoli_newsletter_signup')) {
        kepoli_newsletter_redirect($redirect_to, 'error');
    }

    $honeypot = isset($_POST['website']) ? trim((string) wp_unslash($_POST['website'])) : '';
    if ($honeypot !== '') {
        kepoli_newsletter_redirect($redirect_to, 'success');
    }

    $email = kepoli_newsletter_normalize_email((string) ($_POST['newsletter_email'] ?? ''));
    if ($email === '' || !is_email($email)) {
        kepoli_newsletter_redirect($redirect_to, 'invalid');
    }

    if (kepoli_newsletter_signup_exists($email)) {
        kepoli_newsletter_redirect($redirect_to, 'duplicate');
    }

    $source_label = sanitize_text_field(wp_unslash((string) ($_POST['source_label'] ?? '')));
    $source_url = '';
    if (isset($_POST['source_url'])) {
        $source_url = esc_url_raw(wp_validate_redirect((string) wp_unslash($_POST['source_url']), home_url('/')));
    }

    $signup_id = wp_insert_post([
        'post_type' => kepoli_newsletter_post_type(),
        'post_status' => 'private',
        'post_title' => $email,
    ], true);

    if (is_wp_error($signup_id) || !$signup_id) {
        kepoli_newsletter_redirect($redirect_to, 'error');
    }

    update_post_meta($signup_id, '_kepoli_newsletter_email', $email);
    update_post_meta($signup_id, '_kepoli_newsletter_source_label', $source_label);
    update_post_meta($signup_id, '_kepoli_newsletter_source_url', $source_url);

    kepoli_newsletter_redirect($redirect_to, 'success');
}

add_action('admin_post_nopriv_kepoli_newsletter_signup', 'kepoli_handle_newsletter_signup');
add_action('admin_post_kepoli_newsletter_signup', 'kepoli_handle_newsletter_signup');
