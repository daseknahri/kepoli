<?php
/**
 * Default page template.
 */
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <article class="content-layout">
        <div class="entry">
            <header class="entry-header">
                <?php kepoli_breadcrumbs(); ?>
                <h1 class="entry-title"><?php the_title(); ?></h1>
                <?php kepoli_render_reader_trust_links(); ?>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </div>
        <aside class="sidebar" aria-label="<?php esc_attr_e('Informatii Kepoli', 'kepoli'); ?>">
            <?php get_template_part('template-parts-sidebar'); ?>
        </aside>
    </article>
<?php endwhile; ?>
<?php
get_footer();
