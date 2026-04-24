<?php
/**
 * Archive template.
 */
get_header();
$archive_term = is_category() ? get_queried_object() : null;
$archive_guidance = $archive_term instanceof WP_Term ? kepoli_archive_guidance_items() : [];
$article_archive_meta = ($archive_term instanceof WP_Term && $archive_term->slug === 'articole')
    ? kepoli_article_collection_meta_items((int) $archive_term->count)
    : [];
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow">
        <?php
        if ($archive_term instanceof WP_Term) {
            echo esc_html($archive_term->slug === 'articole' ? __('Ghiduri Kepoli', 'kepoli') : __('Retete Kepoli', 'kepoli'));
        } else {
            esc_html_e('Arhiva', 'kepoli');
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
                <span class="meta-strip__item"><?php esc_html_e('Pagini cu imagini, timpi si recomandari utile mai departe', 'kepoli'); ?></span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php kepoli_render_reader_trust_links(); ?>
    <?php if ($archive_guidance) : ?>
        <div class="archive-guide" aria-label="<?php esc_attr_e('Cum folosesti aceasta categorie', 'kepoli'); ?>">
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
