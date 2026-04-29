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
<a class="skip-link" href="#content"><?php echo esc_html(kepoli_ui_text('Sari la continut', 'Skip to content')); ?></a>
<header class="site-header">
    <div class="site-header__inner">
        <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" aria-label="<?php echo esc_attr(sprintf(kepoli_ui_text('Acasa %s', '%s home'), kepoli_site_name())); ?>">
            <img src="<?php echo esc_url(kepoli_asset_uri(kepoli_wordmark_asset())); ?>" alt="<?php echo esc_attr(kepoli_site_name()); ?>"<?php echo kepoli_asset_dimension_attributes(kepoli_wordmark_asset()); ?> decoding="async">
        </a>
        <div class="site-nav-panel" id="site-nav-panel" data-nav-panel>
            <div class="site-nav-panel__inner" data-nav-panel-inner>
                <nav class="site-nav" aria-label="<?php echo esc_attr(kepoli_ui_text('Navigatie principala', 'Primary navigation')); ?>">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container' => false,
                        'fallback_cb' => 'kepoli_primary_menu_fallback',
                    ]);
                    ?>
                </nav>
                <div class="site-utility-links" aria-label="<?php echo esc_attr(kepoli_ui_text('Legaturi editoriale', 'Editorial links')); ?>">
                    <a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Autor', 'Author')); ?></a>
                    <a href="<?php echo esc_url(kepoli_contact_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Contact', 'Contact')); ?></a>
                </div>
            </div>
        </div>
        <div class="site-header__cluster">
            <a class="search-link search-link--header" href="<?php echo esc_url(home_url('/?s=')); ?>" aria-label="<?php echo esc_attr(kepoli_ui_text('Cauta', 'Search')); ?>">
                <?php echo kepoli_icon('search'); ?>
            </a>
            <button class="site-nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav-panel" data-nav-toggle>
                <span></span>
                <span></span>
                <span></span>
                <span class="screen-reader-text"><?php echo esc_html(kepoli_ui_text('Deschide meniul', 'Open menu')); ?></span>
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
