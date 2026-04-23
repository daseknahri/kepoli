<?php
/**
 * Search results.
 */
get_header();

$found_posts = isset($GLOBALS['wp_query']) ? (int) $GLOBALS['wp_query']->found_posts : 0;
?>
<header class="search-header">
    <p class="eyebrow"><?php esc_html_e('Cautare', 'kepoli'); ?></p>
    <h1><?php echo esc_html(sprintf(__('Rezultate pentru "%s"', 'kepoli'), get_search_query())); ?></h1>
    <p><?php esc_html_e('Cauta dupa ingredient, tip de preparat sau numele unei retete.', 'kepoli'); ?></p>
    <div class="meta-strip" aria-label="<?php esc_attr_e('Rezumat cautare', 'kepoli'); ?>">
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d rezultat', '%d rezultate', $found_posts, 'kepoli'), $found_posts)); ?></span>
    </div>
    <?php kepoli_render_reader_trust_links(); ?>
    <?php get_search_form(); ?>
</header>
<section class="section section--tight">
    <?php if (have_posts()) : ?>
        <div class="section__header section__header--compact">
            <div>
                <p class="eyebrow"><?php esc_html_e('Rezultate disponibile', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege ce vrei sa citesti', 'kepoli'); ?></h2>
            </div>
        </div>
    <?php endif; ?>
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
