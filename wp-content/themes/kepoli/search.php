<?php
/**
 * Search results.
 */
get_header();
?>
<header class="search-header">
    <p class="eyebrow"><?php esc_html_e('Cautare', 'kepoli'); ?></p>
    <h1><?php echo esc_html(sprintf(__('Rezultate pentru "%s"', 'kepoli'), get_search_query())); ?></h1>
    <p><?php esc_html_e('Cauta dupa ingredient, tip de preparat sau numele unei retete. Daca nu gasesti exact ce vrei, foloseste legaturile rapide de mai jos.', 'kepoli'); ?></p>
    <?php get_search_form(); ?>
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
            echo '<div class="search-empty"><p>' . esc_html__('Nu am gasit rezultate. Incearca un ingredient, un preparat sau una dintre categoriile populare.', 'kepoli') . '</p></div>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php esc_attr_e('Paginare', 'kepoli'); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
