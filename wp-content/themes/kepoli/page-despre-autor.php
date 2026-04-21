<?php
/**
 * Author page.
 */
get_header();
?>
<section class="section">
    <div class="author-strip">
        <div class="author-strip__photo" style="--author-image: url('<?php echo esc_url(kepoli_asset_uri('writer-photo', 'svg')); ?>');"></div>
        <div class="author-strip__copy">
            <?php kepoli_breadcrumbs(); ?>
            <p class="eyebrow"><?php esc_html_e('Autoare', 'kepoli'); ?></p>
            <h1 class="entry-title"><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h1>
            <p><?php esc_html_e('Gatesc si scriu pentru oameni care vor retete romanesti clare, cu pasi simpli, gust echilibrat si ingrediente care se gasesc usor. Kepoli este locul unde strang retete de familie, idei de sezon si ghiduri practice pentru bucataria de acasa.', 'kepoli'); ?></p>
            <p><a href="mailto:isalunemerovik@gmail.com">isalunemerovik@gmail.com</a></p>
        </div>
    </div>
</section>
<section class="section section--tight">
    <div class="post-grid">
        <?php
        $author_posts = new WP_Query(['post_type' => 'post', 'posts_per_page' => 6]);
        while ($author_posts->have_posts()) :
            $author_posts->the_post();
            get_template_part('template-parts-card');
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
</section>
<?php
get_footer();
