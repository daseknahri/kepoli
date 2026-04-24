<?php
/**
 * Site footer.
 */
?>
</main>
<footer class="site-footer">
    <div class="site-footer__inner">
        <div class="site-footer__brand">
            <img src="<?php echo esc_url(kepoli_asset_uri('kepoli-wordmark')); ?>" alt="Kepoli"<?php echo kepoli_asset_dimension_attributes('kepoli-wordmark'); ?> loading="lazy" decoding="async">
            <p><?php esc_html_e('Retete romanesti, ghiduri de bucatarie si idei de sezon scrise pentru gatit linistit acasa.', 'kepoli'); ?></p>
            <p><a href="mailto:<?php echo esc_attr(kepoli_env('SITE_EMAIL', 'contact@kepoli.com')); ?>"><?php echo esc_html(kepoli_env('SITE_EMAIL', 'contact@kepoli.com')); ?></a></p>
            <div class="site-footer__identity">
                <a href="<?php echo esc_url(home_url('/despre-kepoli/')); ?>"><?php esc_html_e('Despre Kepoli', 'kepoli'); ?></a>
                <a href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact editorial', 'kepoli'); ?></a>
            </div>
        </div>
        <div class="site-footer__column">
            <p class="eyebrow"><?php esc_html_e('Exploreaza', 'kepoli'); ?></p>
            <ul class="footer-links-list">
                <li><a href="<?php echo esc_url(home_url('/retete/')); ?>"><?php esc_html_e('Retete', 'kepoli'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/articole/')); ?>"><?php esc_html_e('Articole', 'kepoli'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/category/ciorbe-si-supe/')); ?>"><?php esc_html_e('Ciorbe si supe', 'kepoli'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/category/feluri-principale/')); ?>"><?php esc_html_e('Feluri principale', 'kepoli'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/category/patiserie-si-deserturi/')); ?>"><?php esc_html_e('Deserturi', 'kepoli'); ?></a></li>
            </ul>
        </div>
        <div class="site-footer__column">
            <p class="eyebrow"><?php esc_html_e('Informatii', 'kepoli'); ?></p>
            <nav class="footer-menu" aria-label="<?php esc_attr_e('Navigatie footer', 'kepoli'); ?>">
                <?php
                wp_nav_menu([
                    'theme_location' => 'footer',
                    'container' => false,
                    'fallback_cb' => false,
                ]);
                ?>
            </nav>
        </div>
    </div>
    <div class="footer-bottom">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Kepoli. <?php esc_html_e('Continut culinar informativ; adaptati retetele la ingredientele si nevoile proprii.', 'kepoli'); ?>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
