<?php
/**
 * Site footer.
 */
?>
</main>
<?php
$footer_categories = array_values(array_filter(get_categories([
    'hide_empty' => true,
    'exclude' => [1],
    'taxonomy' => 'category',
]), static function (WP_Term $category): bool {
    return !kepoli_is_editorial_category_slug($category->slug);
}));
$footer_categories = array_slice($footer_categories, 0, 3);
$author_page = kepoli_find_page_by_candidates(array_unique(array_filter([kepoli_profile_slug('author', ''), 'despre-autor', 'about-author'])));
$author_label = $author_page instanceof WP_Post ? get_the_title($author_page) : kepoli_ui_text('Autor', 'Author');
$site_name = kepoli_site_name();
$site_email = trim((string) kepoli_profile_value(['brand', 'site_email'], kepoli_env('SITE_EMAIL', 'contact@example.com')));
?>
<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <img src="<?php echo esc_url(kepoli_asset_uri(kepoli_wordmark_asset())); ?>" alt="<?php echo esc_attr($site_name); ?>"<?php echo kepoli_asset_dimension_attributes(kepoli_wordmark_asset()); ?> loading="lazy" decoding="async">
            <p><?php echo esc_html(kepoli_brand_description()); ?></p>
            <?php if ($site_email !== '') : ?>
                <p><a href="mailto:<?php echo esc_attr($site_email); ?>"><?php echo esc_html($site_email); ?></a></p>
            <?php endif; ?>
            <div class="site-footer__identity">
                <a href="<?php echo esc_url(kepoli_about_page_url()); ?>"><?php echo esc_html(sprintf(kepoli_ui_text('Despre %s', 'About %s'), $site_name)); ?></a>
                <a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html($author_label); ?></a>
                <a href="<?php echo esc_url(kepoli_contact_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Contact editorial', 'Editorial contact')); ?></a>
            </div>
        </div>
        <div class="site-footer__column">
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Exploreaza', 'Explore')); ?></p>
            <ul class="footer-links-list">
                <li><a href="<?php echo esc_url(kepoli_recipes_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Retete', 'Recipes')); ?></a></li>
                <li><a href="<?php echo esc_url(kepoli_guides_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Articole', 'Guides')); ?></a></li>
                <?php foreach ($footer_categories as $category) : ?>
                    <li><a href="<?php echo esc_url(get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="site-footer__column">
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Informatii', 'Information')); ?></p>
            <nav class="footer-menu" aria-label="<?php echo esc_attr(kepoli_ui_text('Navigatie footer', 'Footer navigation')); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container' => false,
                    'fallback_cb' => 'kepoli_footer_menu_fallback',
                ]);
                ?>
            </nav>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo esc_html(gmdate('Y')); ?> <?php echo esc_html($site_name); ?>. <?php echo esc_html(kepoli_ui_text('Continut culinar informativ; adaptati retetele la ingredientele si nevoile proprii.', 'Informational food content; adapt recipes to your own ingredients and needs.')); ?>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
