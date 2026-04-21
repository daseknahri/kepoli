<?php
/**
 * Recipes landing page.
 */
get_header();
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Retete', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Retete romanesti', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Alege o categorie sau porneste de la cele mai noi retete Kepoli.', 'kepoli'); ?></p>
</header>
<section class="category-band">
    <div class="section">
        <div class="category-list">
            <?php foreach (get_categories(['hide_empty' => true, 'exclude' => [1]]) as $category) : ?>
                <?php if ($category->slug === 'articole') { continue; } ?>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span><?php echo esc_html(sprintf(_n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<section class="section">
    <div class="post-grid">
        <?php
        $recipes = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 24,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'recipe',
        ]);
        while ($recipes->have_posts()) :
            $recipes->the_post();
            get_template_part('template-parts-card');
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
</section>
<?php
get_footer();
