<?php
/**
 * Archive template.
 */
get_header();
$archive_term = is_category() ? get_queried_object() : null;
$archive_guidance = $archive_term instanceof WP_Term ? kepoli_archive_guidance_items() : [];
$article_archive_meta = ($archive_term instanceof WP_Term && kepoli_is_editorial_category_slug($archive_term->slug))
    ? kepoli_article_collection_meta_items((int) $archive_term->count)
    : [];
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow">
        <?php
        if ($archive_term instanceof WP_Term) {
            echo esc_html(kepoli_is_editorial_category_slug($archive_term->slug) ? sprintf(kepoli_ui_text('Ghiduri %s', '%s guides'), kepoli_site_name()) : sprintf(kepoli_ui_text('Retete %s', '%s recipes'), kepoli_site_name()));
        } else {
            echo esc_html(kepoli_ui_text('Arhiva', 'Archive'));
        }
        ?>
    </p>
    <h1><?php echo esc_html($archive_term instanceof WP_Term ? $archive_term->name : get_the_archive_title()); ?></h1>
    <?php the_archive_description('<p>', '</p>'); ?>
    <?php if ($archive_term instanceof WP_Term) : ?>
        <div class="meta-strip">
            <?php if ($article_archive_meta) : ?>
                <?php foreach ($article_archive_meta as $item) : ?>
                    <span class="meta-strip__item"><?php echo esc_html($item); ?></span>
                <?php endforeach; ?>
            <?php else : ?>
                <span class="meta-strip__item"><?php echo esc_html(kepoli_archive_count_label($archive_term)); ?></span>
                <span class="meta-strip__item"><?php echo esc_html(kepoli_ui_text('Pagini cu imagini, timpi si recomandari utile mai departe', 'Pages with images, timings, and useful next steps')); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php kepoli_render_reader_trust_links(); ?>
    <?php if ($archive_guidance) : ?>
        <div class="archive-guide" aria-label="<?php echo esc_attr(kepoli_ui_text('Cum folosesti aceasta categorie', 'How to use this category')); ?>">
            <?php foreach ($archive_guidance as $item) : ?>
                <section class="archive-guide__item">
                    <h2><?php echo esc_html($item['title']); ?></h2>
                    <p><?php echo esc_html($item['body']); ?></p>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
            echo '<p>' . esc_html(kepoli_ui_text('Nu am gasit continut in aceasta arhiva.', 'No content was found in this archive.')) . '</p>';
        endif;
        ?>
    </div>
    <nav class="pagination" aria-label="<?php echo esc_attr(kepoli_ui_text('Paginare', 'Pagination')); ?>">
        <?php the_posts_pagination(['mid_size' => 1]); ?>
    </nav>
</section>
<?php
get_footer();
