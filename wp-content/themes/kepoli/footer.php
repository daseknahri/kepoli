<?php
/**
 * Site footer.
 */
?>
</main>
<footer class="site-footer">
    <div class="site-footer__inner">
        <div>
            <img src="<?php echo esc_url(kepoli_asset_uri('kepoli-wordmark')); ?>" alt="Kepoli">
            <p><?php esc_html_e('Retete romanesti, ghiduri de bucatarie si idei de sezon scrise pentru gatit linistit acasa.', 'kepoli'); ?></p>
            <p><a href="mailto:<?php echo esc_attr(kepoli_env('SITE_EMAIL', 'contact@kepoli.com')); ?>"><?php echo esc_html(kepoli_env('SITE_EMAIL', 'contact@kepoli.com')); ?></a></p>
            <div class="site-footer__identity">
                <a href="<?php echo esc_url(home_url('/despre-kepoli/')); ?>"><?php esc_html_e('Despre Kepoli', 'kepoli'); ?></a>
                <a href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></a>
                <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact editorial', 'kepoli'); ?></a>
            </div>
        </div>
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
    <div class="footer-bottom">
        &copy; <?php echo esc_html(gmdate('Y')); ?> Kepoli. <?php esc_html_e('Continut culinar informativ; adaptati retetele la ingredientele si nevoile proprii.', 'kepoli'); ?>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
