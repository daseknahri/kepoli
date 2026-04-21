<?php
/**
 * Single post template.
 */
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('content-layout'); ?>>
        <div class="entry">
            <header class="entry-header">
                <?php kepoli_breadcrumbs(); ?>
                <div class="entry-meta">
                    <?php echo esc_html(get_the_date()); ?> / <?php echo esc_html(kepoli_read_time()); ?> / <?php echo esc_html(get_the_author()); ?>
                </div>
                <h1 class="entry-title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="entry-excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php echo kepoli_ad_slot('below_content'); ?>
            <?php
            $related_slugs = get_post_meta(get_the_ID(), '_kepoli_related_slugs', true);
            $related_slugs = is_array($related_slugs) ? $related_slugs : [];
            $related_posts = kepoli_get_posts_by_slugs($related_slugs);
            if ($related_posts) :
                ?>
                <section class="related-posts">
                    <h2><?php esc_html_e('Mai multe de gatit', 'kepoli'); ?></h2>
                    <ul>
                        <?php foreach ($related_posts as $related) : ?>
                            <li><a href="<?php echo esc_url(get_permalink($related)); ?>"><?php echo esc_html(get_the_title($related)); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endif; ?>
        </div>
        <aside class="sidebar" aria-label="<?php esc_attr_e('Context articol', 'kepoli'); ?>">
            <?php get_template_part('template-parts-sidebar'); ?>
        </aside>
    </article>
<?php endwhile; ?>
<?php
get_footer();
