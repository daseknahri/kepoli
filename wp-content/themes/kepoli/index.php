<?php
/**
 * Main index fallback.
 */
get_header();
?>
<header class="archive-header">
    <p class="eyebrow"><?php esc_html_e('Kepoli', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Retete si articole', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Toate materialele Kepoli, de la ciorbe si mancaruri calde la deserturi si ghiduri de bucatarie.', 'kepoli'); ?></p>
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
            echo '<p>' . esc_html__('Nu am gasit articole.', 'kepoli') . '</p>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php esc_attr_e('Paginare', 'kepoli'); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
