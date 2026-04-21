<?php
/**
 * Archive template.
 */
get_header();
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Arhiva', 'kepoli'); ?></p>
    <h1><?php the_archive_title(); ?></h1>
    <?php the_archive_description('<p>', '</p>'); ?>
    <?php kepoli_render_browse_links(); ?>
</header>
<section class="section section--tight">
    <div class="post-grid">
        <?php
        if (have_posts()) :
            while (have_posts()) :
                the_post();
                get_template_part('template-parts-card');
            endwhile;
        else :
            echo '<p>' . esc_html__('Nu am gasit continut in aceasta arhiva.', 'kepoli') . '</p>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php esc_attr_e('Paginare', 'kepoli'); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
