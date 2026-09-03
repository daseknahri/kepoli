<?php
/**
 * Plugin Name: Kepoli FAQ Schema (FAQPage JSON-LD from existing on-page Q&A)
 * Description: The writer pipeline reliably ends most posts with an "<h2>Frequently asked questions</h2>" (or
 *   "<h2>… FAQ</h2>") section of <h3>Question?</h3><p>Answer</p> pairs, but nothing marks it up as structured
 *   data. This emits FAQPage JSON-LD built ONLY from that already-visible on-page content — purely additive, no
 *   new copy, no fabrication. It strengthens topical/semantic clarity and feeds AI/assistant surfaces; note
 *   Google restricted the expandable FAQ rich-result to authoritative gov/health sites (Aug 2023), so this is
 *   not sold as a guaranteed SERP snippet. Emits nothing when a post has no FAQ section or no valid Q&A pairs,
 *   and never on non-posts. Sits alongside the existing connected @graph (Recipe/Article/Breadcrumb/Person).
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_head', 'kepoli_faq_jsonld', 23); // after recipe(20)/breadcrumb(21)/author(22)
function kepoli_faq_jsonld(): void
{
    if (!is_singular('post') || is_feed()) {
        return;
    }
    $id      = get_queried_object_id();
    $content = (string) get_post_field('post_content', $id); // raw, pre-the_content

    // Isolate the FAQ section: an <h2> whose text mentions FAQ / Frequently Asked Questions / Questions,
    // up to the next <h2> or end of content.
    if (!preg_match('/<h2\b[^>]*>[^<]*(?:FAQ|Frequently\s+Asked\s+Questions|Questions)[^<]*<\/h2>(.*?)(?=<h2\b|\z)/is', $content, $sec)) {
        return;
    }

    // Each Q/A: an <h3> question, then everything up to the next <h3>/<h2> as the answer.
    if (!preg_match_all('/<h3\b[^>]*>(.*?)<\/h3>\s*((?:(?!<h3\b|<h2\b).)*)/is', $sec[1], $pairs, PREG_SET_ORDER)) {
        return;
    }

    $entities = [];
    foreach ($pairs as $p) {
        $question = kepoli_faq_text($p[1]);
        $answer   = kepoli_faq_text($p[2] ?? '');
        if ($question === '' || $answer === '') {
            continue;
        }
        $entities[] = [
            '@type'          => 'Question',
            'name'           => $question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
        ];
    }
    if (empty($entities)) {
        return; // never emit an empty/invalid FAQPage
    }

    $data = [
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        'mainEntity' => $entities,
    ];
    echo "\n" . '<script type="application/ld+json">'
        . wp_json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>' . "\n";
}

/**
 * Flatten an HTML fragment to clean plain text for schema: replace every tag with a space (so block
 * boundaries don't merge adjacent words, e.g. "</p><p>"), decode entities, collapse whitespace.
 */
function kepoli_faq_text(string $html): string
{
    $text = preg_replace('/<[^>]+>/', ' ', $html);
    $text = html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text);
    return trim((string) $text);
}
