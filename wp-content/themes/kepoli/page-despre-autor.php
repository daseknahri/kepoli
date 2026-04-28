<?php
/**
 * Author page.
 */
get_header();
$writer_name = kepoli_writer_name();
$writer_email = kepoli_writer_email();
$site_name = kepoli_site_name();
?>
<section class="section">
    <div class="author-strip">
        <div class="author-strip__photo">
            <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'jpg')); ?>" alt="<?php echo esc_attr(sprintf(kepoli_ui_text('%1$s, autoarea %2$s', '%1$s, writer for %2$s'), $writer_name, $site_name)); ?>"<?php echo kepoli_asset_dimension_attributes('writer-photo'); ?> loading="eager" fetchpriority="high" decoding="async">
        </div>
        <div class="author-strip__copy">
            <?php kepoli_breadcrumbs(); ?>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Autoare', 'Writer')); ?></p>
            <h1 class="entry-title"><?php echo esc_html($writer_name); ?></h1>
            <p><?php echo esc_html(kepoli_writer_description()); ?></p>
            <?php if ($writer_email !== '') : ?>
                <p><a href="mailto:<?php echo esc_attr($writer_email); ?>"><?php echo esc_html($writer_email); ?></a></p>
            <?php endif; ?>
        </div>
    </div>
</section>
<section class="section section--tight">
    <div class="entry-content entry-content--page">
        <?php
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
        ?>
    </div>
</section>
<section class="section section--tight">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Publicat recent', 'Recently published')); ?></p>
            <h2><?php echo esc_html(sprintf(kepoli_ui_text('Retete si ghiduri de la %s', 'Recipes and guides from %s'), $writer_name)); ?></h2>
        </div>
        <p><?php echo esc_html(sprintf(kepoli_ui_text('Cele mai noi materiale %s, cu accent pe retete, organizare si gatit clar pentru acasa.', 'The newest pieces on %s, focused on recipes, kitchen guidance, and practical home cooking.'), $site_name)); ?></p>
    </div>
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
