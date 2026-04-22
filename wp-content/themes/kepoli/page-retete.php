<?php
/**
 * Recipes landing page.
 */
get_header();

$recipe_count = kepoli_published_kind_count('recipe');
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
    <div class="meta-strip" aria-label="<?php esc_attr_e('Rezumat retete', 'kepoli'); ?>">
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d reteta publicata', '%d retete publicate', $recipe_count, 'kepoli'), $recipe_count)); ?></span>
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d categorie', '%d categorii', count($recipe_categories), 'kepoli'), count($recipe_categories))); ?></span>
    </div>
</header>
<section class="category-band">
    <div class="section">
        <div class="section__header section__header--compact section__header--simple">
            <div>
                <p class="eyebrow"><?php esc_html_e('Navigare rapida', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege categoria potrivita', 'kepoli'); ?></h2>
            </div>
        </div>
        <div class="category-list category-list--compact">
            <?php foreach ($recipe_categories as $category) : ?>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <span><?php echo esc_html($category->name); ?></span>
                    <strong><?php echo esc_html(sprintf(_n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)); ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php if ($featured_recipe) : ?>
    <?php $featured_recipe_category = kepoli_primary_category($featured_recipe->ID); ?>
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
                <?php if ($featured_recipe_category && $featured_recipe_category->slug !== 'articole') : ?>
                    <div class="lead-story__chips content-chip-row">
                        <a class="content-chip content-chip--category" href="<?php echo esc_url(get_category_link($featured_recipe_category)); ?>"><?php echo esc_html($featured_recipe_category->name); ?></a>
                    </div>
                <?php endif; ?>
                <h3><a href="<?php echo esc_url(get_permalink($featured_recipe)); ?>"><?php echo esc_html(get_the_title($featured_recipe)); ?></a></h3>
                <p><?php echo esc_html(get_the_excerpt($featured_recipe)); ?></p>
                <div class="meta-strip meta-strip--inline">
                    <span class="meta-strip__item"><?php echo esc_html(get_the_date('j M Y', $featured_recipe)); ?></span>
                    <span class="meta-strip__item"><?php echo esc_html(kepoli_read_time($featured_recipe->ID)); ?></span>
                </div>
                <a class="button" href="<?php echo esc_url(get_permalink($featured_recipe)); ?>"><?php esc_html_e('Deschide reteta', 'kepoli'); ?></a>
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
