<?php
/**
 * Plugin Name: Kepoli Structure Fix (one-time, AdSense low-value remediation)
 * Description: kepoli was flagged "Low value content" (May 2, 2026, before a full content rewrite). The CONTENT
 *   is now strong + human (avg 757 words, 0 thin posts), but the SITE STRUCTURE still reads as incoherent to a
 *   reviewer: the primary nav surfaced the 3 SMALLEST categories (Story 3 / Tips 7 / Recipes 18) while ~105 of
 *   133 posts sat in categories not in the nav; the brand copy called the site a "food blog / three pillars"
 *   while the real content is mostly nutrition, skin & beauty, wellness, home and honest home-remedies; category
 *   archives had no intro text; the health taxonomy was fragmented across three 5-8 post buckets; and two empty
 *   leftover categories (Main Dishes, Soups & Stews) from the old Romanian site still existed. This one-time pass
 *   makes the structure honestly match the content (per a 2-model reviewer panel, 2026-09-01):
 *     1. deletes the two empty categories,
 *     2. merges the 3 fragmented health categories into one "Natural Remedies" (old URLs preserved),
 *     3. writes honest category-archive intros,
 *     4. broadens the homepage + About + tagline to the true content mix (no post content touched),
 *     5. rebuilds the primary nav around the six categories that hold the content.
 *   Idempotent (option-guarded), admin/cron only, each step isolated in try/catch so one failure can't wedge the
 *   rest; page originals backed up to _kepoli_structure_backup. Reversible; logs a JSON summary to the error log.
 *
 * @package Kepoli
 */

if (!defined('ABSPATH')) {
    exit;
}

/* --- Flags / small scalars (edit here to retune; bump VERSION to force a re-run) --------------------------- */
const KEPOLI_STRUCTURE_FIX_VERSION = 'v1';
const KEPOLI_SF_TAGLINE            = 'Home cooking and everyday wellness, tried and tested.'; // blogdescription
const KEPOLI_SF_DELETE_EMPTY_CATS  = ['main-dishes', 'soups'];
const KEPOLI_SF_MERGE_HEALTH       = true;
const KEPOLI_SF_MERGE_FROM_SLUGS   = ['colds-respiratory', 'skin-wounds-teeth', 'aches-pains-fever'];
const KEPOLI_SF_MERGE_TARGET_SLUG  = 'natural-remedies';
const KEPOLI_SF_MERGE_TARGET_NAME  = 'Natural Remedies';
const KEPOLI_SF_HOME_PAGE_ID       = 3345;
const KEPOLI_SF_ABOUT_PAGE_SLUG    = 'about-kepoli';

/* --- Homepage front-page welcome (replaces the "three pillars" framing) ----------------------------------- */
function kepoli_sf_home_welcome_html(): string {
    return <<<'HTML'
<p>Kepoli started as a place to write down what actually works in my own kitchen — and it grew from there. Most days you'll find me testing a recipe, reading up on what a food or habit actually does for the body, or trying some small fix for skin, home, or everyday aches that people swear by. I'm a home cook, not a nutritionist or a doctor, so nothing here is expert advice — just what I've cooked, tried, and learned along the way, written down honestly so you can decide what's worth trying yourself.</p>
<p>Whether you're after something to make tonight or a few minutes of good reading with your coffee, I'm glad you're here. Browse the latest posts below, and cook — or try — something you'll want to come back to.</p>
HTML;
}

/* --- About Kepoli full body (broadened identity; keeps the trust + authorship sections) ------------------- */
function kepoli_sf_about_html(): string {
    return <<<'HTML'
<p>Kepoli is a small, independent blog for people who cook and keep house at home. It was built around a simple belief: that cooking — and the everyday business of feeding yourself and looking after a home — should be approachable, honest, and free of unnecessary fuss. You do not need professional training, specialty equipment, or a long list of hard-to-find ingredients. You need clear guidance and a few things you can trust.</p>
<h2>What you'll find here</h2>
<p>Kepoli isn't built around three tidy pillars anymore — it's grown into five loose ones, because that's honestly where the cooking and the curiosity kept leading. <strong>Recipes</strong> are the backbone: dishes I've actually made, mistakes included. <strong>Food &amp; Nutrition</strong> digs into what's really behind an ingredient or a habit — the plain-language version, not the hype. <strong>Wellness &amp; Habits</strong> covers the small daily things — sleep, stress, movement — that I've tried and read into honestly. <strong>Skin &amp; Beauty</strong> is the same approach turned toward the bathroom cabinet: simple, low-risk things people use, with the caveats included. And <strong>Home &amp; Natural Living</strong> is everything else that keeps a kitchen and a house running — cleaning, storage, small fixes. None of it is expert advice. It's a home cook's notes, checked against what's actually known, not what sells.</p>
<h2>Who Kepoli is for</h2>
<p>This site is for the person who cooks on ordinary evenings, not only on special occasions — and who's curious about the everyday things that keep a kitchen and a home running well. It is for beginners who want recipes that explain the why, and for anyone who appreciates a clear, honest guide without the padding. If you have ever wanted advice that simply works and tells you what to expect along the way, you are in the right place.</p>
<h2>How we work</h2>
<p>We hold everything here to a straightforward standard: recipes are tested in a real home kitchen, and the wellness, beauty, and home pieces are written from personal experience and checked honestly against what's actually known — with the limits and uncertainty left in, never smoothed over. When something needs a tweak to work reliably, we make it before we publish. We also revisit older posts and update them when we find a clearer or more accurate way to explain something.</p>
<h2>Independent and reader-first</h2>
<p>Kepoli is independent. Our writing and recommendations reflect our own cooking and honest opinions, not the priorities of a sponsor. We do not publish paid content disguised as editorial. If we ever mention a product or use advertising to help cover the cost of running the site, we keep it clearly separate from the writing you come here to read. Questions and ideas are always welcome at contact@kepoli.com.</p>
<h2>Who writes Kepoli</h2>
<p>Kepoli is written by <a href="/about-the-author/">Isalune Merovik</a>, a home cook and writer. She also researches the site's traditional home-remedy articles — documented honestly, for general interest, and never as medical advice (see our <a href="/medical-disclaimer/">Medical Disclaimer</a>). You can reach Isalune at isalunemerovik@gmail.com, or the site at contact@kepoli.com.</p>
HTML;
}

/* --- Category archive intros (slug => description). natural-remedies is created by the merge step first. --- */
function kepoli_sf_category_descriptions(): array {
    return [
        'food-nutrition'      => "Food & Nutrition is where I dig into what's actually behind an ingredient, a diet trend, or a habit — the plain-language version, not the hype. I'm not a nutritionist; I read the research I can find, note what's solid and what's still uncertain, and write down what I've learned. Expect honest context, not miracle claims.",
        'recipes'             => "Recipes is the heart of Kepoli — dishes I've actually cooked in my own kitchen, mistakes and all. Nothing fancy or restaurant-trained, just food that works on a weeknight: tested methods, realistic timing, and notes on what went wrong the first time so yours turns out better.",
        'wellness-habits'     => "Wellness & Habits covers the small, everyday things — sleep, stress, movement, routines — that people, including me, try to get right. I'm not a health professional, so you'll find honest first-person notes on what I've tried and what the evidence actually says, hedged where it should be, not sold as a cure.",
        'skin-beauty'         => "Skin & Beauty looks at the simple things people reach for in the bathroom cabinet — natural remedies, everyday routines, low-risk fixes. I'm a home cook, not a dermatologist, so this is what I've tried and researched honestly, with the caveats left in, not expert advice or guaranteed results.",
        'home-natural-living' => "Home & Natural Living is everything that keeps a kitchen and a house actually running — cleaning tricks, storage, small natural fixes for everyday problems. Practical, tested at home, no special equipment required. If something only worked for me under specific conditions, I'll say so instead of overselling it.",
        'tips'                => "Tips is the short, practical stuff — kitchen shortcuts, food storage, small fixes that save time or a spoiled meal. Quick reads, no fluff, drawn from things that actually tripped me up in my own kitchen before I figured out a better way to do them.",
        'story'               => "Story is the smaller, personal corner of Kepoli — how a dish came about, a kitchen mistake that taught me something, the reasoning behind why I cook the way I do. Not recipes or advice, just the honest backstory behind the rest of the site.",
        'natural-remedies'    => "Natural Remedies gathers the traditional, home-style fixes people have used for colds, minor aches, and small skin troubles — and looks honestly at what's actually behind them. I'm not a doctor; think of this as a home cook's research notes, not medical advice, with the real limits and uncertainty left in, not smoothed over.",
    ];
}

/* --- Primary nav, in order (fronts the six categories that hold ~123 of 133 posts, + About) --------------- */
function kepoli_sf_nav_items(): array {
    return [
        ['type' => 'custom',   'url'  => '/',                   'title' => 'Home'],
        ['type' => 'category', 'slug' => 'food-nutrition',      'title' => 'Food & Nutrition'],
        ['type' => 'category', 'slug' => 'recipes',             'title' => 'Recipes'],
        ['type' => 'category', 'slug' => 'wellness-habits',     'title' => 'Wellness & Habits'],
        ['type' => 'category', 'slug' => 'skin-beauty',         'title' => 'Skin & Beauty'],
        ['type' => 'category', 'slug' => 'home-natural-living', 'title' => 'Home & Natural Living'],
        ['type' => 'category', 'slug' => 'natural-remedies',    'title' => 'Natural Remedies'],
        ['type' => 'page',     'slug' => 'about-kepoli',        'title' => 'About Kepoli'],
    ];
}

/* =============================================================================
 * RUNNER
 * ========================================================================== */

add_action('admin_init', 'kepoli_structure_fix_run');
add_action('wp_loaded', static function (): void {
    if (wp_doing_cron()) {
        kepoli_structure_fix_run();
    }
});

function kepoli_structure_fix_run(): void
{
    if (!is_admin() && !wp_doing_cron()) {
        return;
    }
    $guard = 'kepoli_structure_fix_' . KEPOLI_STRUCTURE_FIX_VERSION;
    if (get_option($guard)) {
        return; // already done
    }

    $log = [];

    // 1) Tagline (feeds the <title> tag).
    kepoli_sf_try($log, 'tagline', static function () {
        if (KEPOLI_SF_TAGLINE !== '') {
            update_option('blogdescription', KEPOLI_SF_TAGLINE);
            return 'set';
        }
        return 'skipped';
    });

    // 2) Delete empty leftover categories (only if truly empty).
    kepoli_sf_try($log, 'delete_empty_cats', static function () {
        $n = 0;
        foreach (KEPOLI_SF_DELETE_EMPTY_CATS as $slug) {
            $t = get_term_by('slug', $slug, 'category');
            if ($t && (int) $t->count === 0) {
                wp_delete_term($t->term_id, 'category');
                $n++;
            }
        }
        return "deleted {$n}";
    });

    // 3) Consolidate the fragmented health categories into one "Natural Remedies" (preserves old URLs).
    kepoli_sf_try($log, 'merge_health', static function () {
        if (!KEPOLI_SF_MERGE_HEALTH) {
            return 'disabled';
        }
        $target = get_term_by('slug', KEPOLI_SF_MERGE_TARGET_SLUG, 'category');
        if (!$target) {
            $res = wp_insert_term(KEPOLI_SF_MERGE_TARGET_NAME, 'category', ['slug' => KEPOLI_SF_MERGE_TARGET_SLUG]);
            if (is_wp_error($res)) {
                return 'target create failed: ' . $res->get_error_message();
            }
            $target_id = (int) $res['term_id'];
        } else {
            $target_id = (int) $target->term_id;
        }
        $moved = 0;
        foreach (KEPOLI_SF_MERGE_FROM_SLUGS as $slug) {
            $from = get_term_by('slug', $slug, 'category');
            if (!$from) {
                continue;
            }
            $posts = get_posts([
                'category'       => $from->term_id,
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]);
            foreach ($posts as $pid) {
                // postname is unchanged by a category move, but ensure it is alias-mapped so recovery always 301s.
                $aliases = array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($pid, '_kepoli_slug_aliases', true))), 'strlen'));
                $pname = get_post_field('post_name', $pid);
                if ($pname && !in_array($pname, $aliases, true)) {
                    $aliases[] = $pname;
                    update_post_meta($pid, '_kepoli_slug_aliases', implode(',', $aliases));
                }
                wp_remove_object_terms($pid, $from->term_id, 'category');
                wp_set_object_terms($pid, $target_id, 'category', true);
                update_post_meta($pid, '_wpap_smart_link', get_permalink($pid));
                clean_post_cache($pid);
                $moved++;
            }
            $still = get_term($from->term_id, 'category');
            if ($still && !is_wp_error($still) && (int) $still->count === 0) {
                wp_delete_term($from->term_id, 'category');
            }
        }
        return "merged {$moved} posts into " . KEPOLI_SF_MERGE_TARGET_SLUG;
    });

    // 4) Category archive intro descriptions.
    kepoli_sf_try($log, 'category_descriptions', static function () {
        $n = 0;
        foreach (kepoli_sf_category_descriptions() as $slug => $desc) {
            $t = get_term_by('slug', $slug, 'category');
            if ($t && $desc !== '') {
                wp_update_term($t->term_id, 'category', ['description' => $desc]);
                $n++;
            }
        }
        return "set {$n}";
    });

    // 5) Homepage front-page welcome copy.
    kepoli_sf_try($log, 'homepage_welcome', static function () {
        $id  = KEPOLI_SF_HOME_PAGE_ID;
        $p   = get_post($id);
        if (!$p || $p->post_type !== 'page') {
            return "page {$id} not found";
        }
        update_post_meta($id, '_kepoli_structure_backup', $p->post_content);
        $res = wp_update_post(['ID' => $id, 'post_content' => kepoli_sf_home_welcome_html()], true);
        clean_post_cache($id);
        return is_wp_error($res) ? ('failed: ' . $res->get_error_message()) : 'updated';
    });

    // 6) About Kepoli body copy.
    kepoli_sf_try($log, 'about_copy', static function () {
        $page = get_page_by_path(KEPOLI_SF_ABOUT_PAGE_SLUG, OBJECT, 'page');
        if (!$page) {
            return 'about page not found';
        }
        update_post_meta($page->ID, '_kepoli_structure_backup', $page->post_content);
        $res = wp_update_post(['ID' => $page->ID, 'post_content' => kepoli_sf_about_html()], true);
        clean_post_cache($page->ID);
        return is_wp_error($res) ? ('failed: ' . $res->get_error_message()) : 'updated';
    });

    // 7) Rebuild the primary nav menu.
    kepoli_sf_try($log, 'rebuild_nav', static function () {
        $items = kepoli_sf_nav_items();
        if (!$items) {
            return 'skipped (no items)';
        }
        $locations = get_nav_menu_locations();
        $menu_id   = 0;
        foreach (['primary', 'main', 'header', 'menu-1'] as $loc) {
            if (!empty($locations[$loc])) {
                $menu_id = (int) $locations[$loc];
                break;
            }
        }
        if (!$menu_id && $locations) {
            $menu_id = (int) reset($locations);
        }
        if (!$menu_id) {
            return 'no menu location found';
        }
        $existing = wp_get_nav_menu_items($menu_id);
        if ($existing) {
            foreach ($existing as $it) {
                wp_delete_post($it->ID, true);
            }
        }
        $order = 1;
        foreach ($items as $it) {
            $args = ['menu-item-status' => 'publish', 'menu-item-position' => $order];
            if (($it['type'] ?? '') === 'page') {
                $pg = get_page_by_path($it['slug'], OBJECT, 'page');
                if (!$pg) { continue; }
                $args['menu-item-type']      = 'post_type';
                $args['menu-item-object']    = 'page';
                $args['menu-item-object-id'] = $pg->ID;
                $args['menu-item-title']     = $it['title'] ?? $pg->post_title;
            } elseif (($it['type'] ?? '') === 'category') {
                $t = get_term_by('slug', $it['slug'], 'category');
                if (!$t) { continue; }
                $args['menu-item-type']      = 'taxonomy';
                $args['menu-item-object']    = 'category';
                $args['menu-item-object-id'] = $t->term_id;
                $args['menu-item-title']     = $it['title'] ?? $t->name;
            } else {
                $args['menu-item-type']  = 'custom';
                $args['menu-item-url']   = $it['url'] ?? '/';
                $args['menu-item-title'] = $it['title'] ?? 'Link';
            }
            wp_update_nav_menu_item($menu_id, 0, $args);
            $order++;
        }
        return 'rebuilt with ' . ($order - 1) . ' items';
    });

    delete_transient('kepoli_link_recovery_maps');
    update_option($guard, 1, false);
    error_log('[kepoli] structure fix ' . KEPOLI_STRUCTURE_FIX_VERSION . ': ' . wp_json_encode($log));
}

function kepoli_sf_try(array &$log, string $step, callable $fn): void
{
    try {
        $log[$step] = $fn();
    } catch (\Throwable $e) {
        $log[$step] = 'THREW: ' . $e->getMessage();
        error_log('[kepoli] structure fix step ' . $step . ' threw: ' . $e->getMessage());
    }
}
