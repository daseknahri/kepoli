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
    <p><?php esc_html_e('Alege o categorie sau porneste de la cele mai noi retete Kepoli, fiecare structurata cu pasi, sfaturi, pastrare si raspunsuri utile.', 'kepoli'); ?></p>
    <div class="meta-strip" aria-label="<?php esc_attr_e('Rezumat retete', 'kepoli'); ?>">
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d reteta publicata', '%d retete publicate', $recipe_count, 'kepoli'), $recipe_count)); ?></span>
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d categorie', '%d categorii', count($recipe_categories), 'kepoli'), count($recipe_categories))); ?></span>
        <span class="meta-strip__item"><?php esc_html_e('Gatire de zi cu zi, explicata simplu', 'kepoli'); ?></span>
    </div>
</header>
<section class="category-band">
    <div class="section">
        <div class="section__header section__header--compact">
            <div>
                <p class="eyebrow"><?php esc_html_e('Navigare rapida', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege categoria potrivita', 'kepoli'); ?></h2>
            </div>
            <p><?php esc_html_e('De la ciorbe si feluri principale la deserturi si conserve, fiecare categorie reuneste retete scrise pentru gatit clar, practic si usor de repetat acasa.', 'kepoli'); ?></p>
        </div>
        <div class="category-list">
            <?php foreach ($recipe_categories as $category) : ?>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span><?php echo esc_html(sprintf(_n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)); ?></span>
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
        <p><?php esc_html_e('O colectie curata de retete romanesti, gandite pentru mese de familie, seri obisnuite si weekenduri in care vrei ceva bun pe masa.', 'kepoli'); ?></p>
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
