<?php
/**
 * Search results.
 */
get_header();
?>
<header class="search-header">
    <p class="eyebrow"><?php esc_html_e('Cautare', 'kepoli'); ?></p>
    <h1><?php echo esc_html(sprintf(__('Rezultate pentru "%s"', 'kepoli'), get_search_query())); ?></h1>
    <?php get_search_form(); ?>
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
            echo '<p>' . esc_html__('Nu am gasit rezultate. Incearca un ingredient sau numele unei retete.', 'kepoli') . '</p>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php esc_attr_e('Paginare', 'kepoli'); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
