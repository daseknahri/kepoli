<?php
/**
 * Recipes landing page.
 */
get_header();

$recipe_categories = array_values(array_filter(get_categories([
    'hide_empty' => true,
    'exclude' => [1],
    'taxonomy' => 'category',
]), static function (WP_Term $category): bool {
    return $category->slug !== 'articole';
}));
$featured_recipe = kepoli_latest_post_by_kind('recipe');
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Retete', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Retete romanesti', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Alege o categorie sau porneste de la cele mai noi retete Kepoli.', 'kepoli'); ?></p>
    <?php kepoli_render_reader_trust_links(); ?>
</header>
<section class="category-band">
    <div class="section">
        <div class="section__header section__header--compact section__header--simple">
            <div>
                <p class="eyebrow"><?php esc_html_e('Navigare rapida', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege categoria potrivita', 'kepoli'); ?></h2>
            </div>
        </div>
        <div class="category-list category-list--showcase">
            <?php foreach ($recipe_categories as $category) : ?>
                <?php
                $category_meta = kepoli_category_card_meta($category);
                $category_image = kepoli_category_card_image_data($category);
                $category_description = trim(wp_strip_all_tags((string) $category->description));
                if ($category_description === '') {
                    $category_description = $category_meta['description'];
                } else {
                    $category_description = wp_trim_words($category_description, 18, '...');
                }
                ?>
                <a class="category-card <?php echo esc_attr(kepoli_tone_class($category->slug) . (!empty($category_image['url']) ? ' category-card--with-image' : '')); ?>" href="<?php echo esc_url(get_category_link($category)); ?>">
                    <?php if (!empty($category_image['url'])) : ?>
                        <span class="category-card__visual" aria-hidden="true">
                            <img src="<?php echo esc_url($category_image['url']); ?>" alt="<?php echo esc_attr($category_image['alt'] ?? ''); ?>"<?php echo kepoli_dimension_attributes($category_image); ?> loading="lazy" decoding="async">
                        </span>
                    <?php endif; ?>
                    <span class="category-card__top">
                        <span class="category-card__icon" aria-hidden="true"><?php echo esc_html($category_meta['icon']); ?></span>
                        <span class="category-card__count"><?php echo esc_html(sprintf(_n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)); ?></span>
                    </span>
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span class="category-card__description"><?php echo esc_html($category_description); ?></span>
                    <?php if (!empty($category_image['gallery'])) : ?>
                        <span class="category-card__gallery" aria-hidden="true">
                            <?php foreach ($category_image['gallery'] as $gallery_item) : ?>
                                <span class="category-card__thumb">
                                    <img src="<?php echo esc_url($gallery_item['url']); ?>" alt="<?php echo esc_attr($gallery_item['alt'] ?? ''); ?>"<?php echo kepoli_dimension_attributes($gallery_item); ?> loading="lazy" decoding="async">
                                </span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($category_image['sample'])) : ?>
                        <span class="category-card__sample"><?php echo esc_html(sprintf(__('De inceput: %s', 'kepoli'), $category_image['sample'])); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php if ($featured_recipe) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact">
            <div>
                <p class="eyebrow"><?php esc_html_e('Din prim-plan', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Incepe cu reteta aceasta', 'kepoli'); ?></h2>
            </div>
        </div>
        <article class="lead-story <?php echo esc_attr(kepoli_post_tone_class($featured_recipe->ID)); ?>">
            <a class="lead-story__media" href="<?php echo esc_url(get_permalink($featured_recipe)); ?>">
                <?php echo kepoli_post_media_markup($featured_recipe->ID, 'related'); ?>
            </a>
            <div class="lead-story__body">
                <p class="eyebrow"><?php esc_html_e('Reteta recomandata', 'kepoli'); ?></p>
                <h3><a href="<?php echo esc_url(get_permalink($featured_recipe)); ?>"><?php echo esc_html(get_the_title($featured_recipe)); ?></a></h3>
                <p><?php echo esc_html(get_the_excerpt($featured_recipe)); ?></p>
                <?php echo kepoli_render_post_card_meta($featured_recipe->ID, 'meta-strip meta-strip--inline', 'meta-strip__item'); ?>
            </div>
        </article>
    </section>
<?php endif; ?>
<section class="section">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php esc_html_e('Toate retetele', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Biblioteca Kepoli', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Toate retetele intr-o lista simpla, usor de rasfoit.', 'kepoli'); ?></p>
    </div>
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
