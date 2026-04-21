<?php
/**
 * Archive template.
 */
get_header();

$found_posts = isset($GLOBALS['wp_query']) ? (int) $GLOBALS['wp_query']->found_posts : 0;
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Arhiva', 'kepoli'); ?></p>
    <h1><?php the_archive_title(); ?></h1>
    <?php the_archive_description('<p>', '</p>'); ?>
    <div class="meta-strip" aria-label="<?php esc_attr_e('Rezumat arhiva', 'kepoli'); ?>">
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d articol gasit', '%d articole gasite', $found_posts, 'kepoli'), $found_posts)); ?></span>
        <span class="meta-strip__item"><?php esc_html_e('Navigare simpla intre retete si articole', 'kepoli'); ?></span>
    </div>
    <?php kepoli_render_browse_links(); ?>
</header>
<section class="section section--tight">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php esc_html_e('Continut publicat', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Rasfoieste arhiva', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Materialele sunt ordonate pentru citire rapida, cu accent pe retete practice, ghiduri de bucatarie si legaturi clare intre subiecte apropiate.', 'kepoli'); ?></p>
    </div>
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
