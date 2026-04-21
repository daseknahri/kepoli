<?php
/**
 * Articles landing page.
 */
get_header();
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Articole', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Ghiduri de bucatarie', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Organizare, ingrediente, tehnici si idei pentru mese romanesti bine asezate.', 'kepoli'); ?></p>
</header>
<section class="section section--tight">
    <div class="post-grid">
        <?php
        $articles = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 12,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'article',
        ]);
        while ($articles->have_posts()) :
            $articles->the_post();
            get_template_part('template-parts-card');
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
</section>
<?php
get_footer();
