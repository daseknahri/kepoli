<?php
/**
 * Plugin Name: Kepoli Social Profiles
 * Description: Kepoli brand social profiles in ONE place: the theme footer brand
 *   row (via viral-reader's vr_social_profiles filter, 1.9.20+), the Organization
 *   `sameAs` (kepoli-schema.php reads kepoli_social_urls()), and a one-time seed
 *   of the author contact-method meta so the theme's Person `sameAs`/author-box
 *   links carry them too. Kepoli-only brand identity → lives here, not the engine.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Canonical list of kepoli brand profile URLs (network => url).
 * Single source consumed by the footer row + Organization sameAs.
 */
function kepoli_social_urls(): array
{
    return [
        'facebook'  => 'https://www.facebook.com/profile.php?id=61594166378812',
        'instagram' => 'https://www.instagram.com/kepolirecipe/',
        'pinterest' => 'https://www.pinterest.com/kepolirecipe/',
    ];
}

/**
 * Feed the theme's reusable footer brand-social row (viral-reader 1.9.20+).
 * Shape: array of ['network' => ..., 'url' => ...].
 */
add_filter('vr_social_profiles', function ($profiles) {
    $out = is_array($profiles) ? $profiles : [];
    foreach (kepoli_social_urls() as $network => $url) {
        $out[] = ['network' => $network, 'url' => $url];
    }
    return $out;
});

/**
 * One-time seed of author contact-method meta so the theme's author box +
 * Person `sameAs` include the brand profiles. Fills only empty fields.
 */
add_action('init', function () {
    if (get_option('kepoli_social_seed_v1')) {
        return;
    }
    $map = [
        'vr_social_facebook'  => 'https://www.facebook.com/profile.php?id=61594166378812',
        'vr_social_instagram' => 'https://www.instagram.com/kepolirecipe/',
        'vr_social_pinterest' => 'https://www.pinterest.com/kepolirecipe/',
    ];
    $users = get_users(['role__in' => ['administrator', 'editor', 'author'], 'fields' => 'ID']);
    foreach ($users as $uid) {
        foreach ($map as $key => $url) {
            if ('' === trim((string) get_the_author_meta($key, (int) $uid))) {
                update_user_meta((int) $uid, $key, $url);
            }
        }
    }
    update_option('kepoli_social_seed_v1', time());
});
