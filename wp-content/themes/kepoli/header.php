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
            <div class="site-nav-panel__inner" data-nav-panel-inner>
                <nav class="site-nav" aria-label="<?php esc_attr_e('Navigatie principala', 'kepoli'); ?>">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container' => false,
                        'fallback_cb' => false,
                    ]);
                    ?>
                </nav>
                <div class="site-utility-links" aria-label="<?php esc_attr_e('Legaturi editoriale', 'kepoli'); ?>">
                    <a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php esc_html_e('Autor', 'kepoli'); ?></a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'kepoli'); ?></a>
                </div>
            </div>
        </div>
        <div class="site-header__cluster">
            <a class="search-link search-link--header" href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="<?php esc_attr_e('Cauta', 'kepoli'); ?>">
                <?php echo kepoli_icon('search'); ?>
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
<?php if (!is_front_page()) : ?>
    <?php echo kepoli_ad_slot('header'); ?>
<?php endif; ?>
<main id="content" class="site-main">
