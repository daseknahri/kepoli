<?php
/**
 * Plugin Name: Kepoli End-of-Article Share
 * Description: The theme renders its share row only once, right after the hero image at the TOP of the post.
 *   On a ~99%-mobile site where articles run several screens tall, the moment a reader decides to share is
 *   when they FINISH — by then the top share row is thousands of pixels back up. This appends a second share
 *   row at the true end of the content (before tags/related) by reusing the theme's own vr_share_links() +
 *   vr_icon() output, so the existing .share-tools CSS and the document-delegated copy/print handlers in
 *   site.js apply automatically — no theme edit, no new JS. Posts only. Reversible.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('the_content', 'kepoli_share_at_end', 20);
function kepoli_share_at_end($content)
{
    if (!is_singular('post') || !in_the_loop() || !is_main_query()
        || !function_exists('vr_share_links') || !function_exists('vr_icon')) {
        return $content;
    }
    $icons = ['facebook' => 'facebook', 'whatsapp' => 'whatsapp', 'email' => 'email', 'copy' => 'link', 'print' => 'print'];
    $out = '<div class="share-tools share-tools--end" role="group" aria-label="' . esc_attr__('Share this story', 'viral-reader') . '">';
    foreach (vr_share_links() as $s) {
        $ic = $icons[$s['type']] ?? 'link';
        if ('copy' === $s['type']) {
            $out .= '<button class="share-tools__button" type="button" data-copy-url="' . esc_attr($s['url'])
                . '" aria-label="' . esc_attr($s['label']) . '" title="' . esc_attr($s['label']) . '">' . vr_icon($ic) . '</button>';
        } elseif ('print' === $s['type']) {
            $out .= '<button class="share-tools__button" type="button" data-print aria-label="' . esc_attr($s['label'])
                . '" title="' . esc_attr($s['label']) . '">' . vr_icon($ic) . '</button>';
        } else {
            $out .= '<a class="share-tools__button" href="' . esc_url($s['url']) . '" target="_blank" rel="noopener nofollow" aria-label="'
                . esc_attr($s['label']) . '" title="' . esc_attr($s['label']) . '">' . vr_icon($ic) . '</a>';
        }
    }
    $out .= '</div>';
    return $content . $out;
}

add_action('wp_head', static function (): void {
    if (is_admin() || !is_singular('post')) {
        return;
    }
    echo "\n<style id=\"kepoli-share-end-css\">.share-tools--end{margin-top:2.2em;padding-top:1.4em;border-top:1px solid var(--line,#e7e2d8)}</style>\n";
}, 99);
