<?php
/**
 * Main index fallback.
 */
get_header();
?>
<header class="archive-header">
    <p class="eyebrow"><?php echo esc_html(kepoli_site_name()); ?></p>
    <h1><?php echo esc_html(kepoli_ui_text('Retete si articole', 'Recipes and articles')); ?></h1>
    <p><?php echo esc_html(sprintf(kepoli_ui_text('Toate materialele %s, de la ciorbe si mancaruri calde la deserturi si ghiduri de bucatarie.', 'All %s posts, from recipes and desserts to practical kitchen guides.'), kepoli_site_name())); ?></p>
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
            echo '<p>' . esc_html(kepoli_ui_text('Nu am gasit articole.', 'No posts were found.')) . '</p>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php echo esc_attr(kepoli_ui_text('Paginare', 'Pagination')); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
