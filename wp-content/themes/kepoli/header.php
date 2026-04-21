<?php
/**
 * Site header.
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e('Sari la continut', 'kepoli'); ?></a>
<header class="site-header">
    <div class="site-header__inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php esc_attr_e('Kepoli home', 'kepoli'); ?>">
            <img src="<?php echo esc_url(kepoli_asset_uri('kepoli-wordmark')); ?>" alt="Kepoli">
        </a>
        <div class="site-nav-panel" id="site-nav-panel" data-nav-panel>
            <nav class="site-nav" aria-label="<?php esc_attr_e('Navigatie principala', 'kepoli'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'primary',
                    'container' => false,
                    'fallback_cb' => false,
                ]);
                ?>
            </nav>
            <a class="search-link search-link--panel" href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="<?php esc_attr_e('Cauta', 'kepoli'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M10.7 4a6.7 6.7 0 0 1 5.28 10.82l3.6 3.6-1.42 1.42-3.6-3.6A6.7 6.7 0 1 1 10.7 4m0 2a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4"/></svg>
                <span><?php esc_html_e('Cauta', 'kepoli'); ?></span>
            </a>
        </div>
        <div class="site-header__cluster">
            <a class="search-link search-link--desktop" href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="<?php esc_attr_e('Cauta', 'kepoli'); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M10.7 4a6.7 6.7 0 0 1 5.28 10.82l3.6 3.6-1.42 1.42-3.6-3.6A6.7 6.7 0 1 1 10.7 4m0 2a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4"/></svg>
            </a>
            <button class="site-nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav-panel" data-nav-toggle>
                <span></span>
                <span></span>
                <span></span>
                <span class="screen-reader-text"><?php esc_html_e('Deschide meniul', 'kepoli'); ?></span>
            </button>
        </div>
    </div>
    <div class="reading-progress" data-reading-progress hidden>
        <span class="reading-progress__bar" data-reading-progress-bar></span>
    </div>
</header>
<?php kepoli_render_topic_rail(); ?>
<?php if (!is_front_page()) : ?>
    <?php echo kepoli_ad_slot('header'); ?>
<?php endif; ?>
<main id="content" class="site-main">
