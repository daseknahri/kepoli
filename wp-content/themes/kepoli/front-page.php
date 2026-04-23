<?php
/**
 * Front page.
 */
get_header();

$hero_image = kepoli_asset_uri('hero-homepage', 'png');
$categories = get_categories([
    'hide_empty' => true,
    'exclude' => [1],
    'orderby' => 'name',
]);
$featured_recipe = kepoli_latest_post_by_kind('recipe');
$featured_article = kepoli_latest_post_by_kind('article');

$recipe_list = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 4,
    'post__not_in' => $featured_recipe ? [$featured_recipe->ID] : [],
    'meta_key' => '_kepoli_post_kind',
    'meta_value' => 'recipe',
]);

$article_list = new WP_Query([
    'post_type' => 'post',
    'posts_per_page' => 3,
    'post__not_in' => $featured_article ? [$featured_article->ID] : [],
    'meta_key' => '_kepoli_post_kind',
    'meta_value' => 'article',
]);
?>
<section class="home-hero" style="--hero-image: url('<?php echo esc_url($hero_image); ?>');">
    <div class="home-hero__inner">
        <p class="eyebrow"><?php esc_html_e('Kepoli', 'kepoli'); ?></p>
        <h1><?php esc_html_e('Retete romanesti si ghiduri pentru gatit acasa.', 'kepoli'); ?></h1>
        <p><?php esc_html_e('Pagini clare, imagini utile si explicatii practice pentru cititorii care ajung direct din cautare, recomandari sau social.', 'kepoli'); ?></p>
        <div class="button-row">
            <a class="button" href="<?php echo esc_url(home_url('/retete/')); ?>"><?php esc_html_e('Vezi retetele', 'kepoli'); ?></a>
        </div>
    </div>
</section>

<section class="section section--tight home-proof">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php esc_html_e('Transparenta', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Cine scrie, cum lucram, unde ne poti verifica', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Kepoli este construit pentru cititorii care intra direct intr-o pagina si vor sa vada repede autorul, regulile editoriale si cum pot cere clarificari.', 'kepoli'); ?></p>
    </div>
    <?php kepoli_render_reader_trust_links('browse-links browse-links--trust home-proof__links'); ?>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php esc_html_e('Retete publicate', 'kepoli'); ?></p>
            <h2><?php esc_html_e('De gatit saptamana aceasta', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Retete clare, usor de scanat si simple de pus in practica.', 'kepoli'); ?></p>
    </div>
    <div class="home-cluster">
        <?php if ($featured_recipe) : ?>
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
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php esc_html_e('Mai multe retete', 'kepoli'); ?></p>
            </div>
            <?php while ($recipe_list->have_posts()) : $recipe_list->the_post(); ?>
                <article <?php post_class('compact-post ' . kepoli_post_tone_class()); ?>>
                    <a class="compact-post__media" href="<?php the_permalink(); ?>">
                        <?php echo kepoli_post_media_markup(get_the_ID(), 'related'); ?>
                    </a>
                    <div class="compact-post__body">
                        <?php echo kepoli_render_post_card_meta(); ?>
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 12, '...')); ?></p>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>

<section class="category-band">
    <div class="section">
        <div class="section__header section__header--simple">
            <div>
                <p class="eyebrow"><?php esc_html_e('Categorii', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege dupa pofta', 'kepoli'); ?></h2>
            </div>
        </div>
        <div class="category-list category-list--showcase">
            <?php foreach ($categories as $category) : ?>
                <?php
                $category_meta = kepoli_category_card_meta($category);
                $category_description = trim(wp_strip_all_tags((string) $category->description));
                if ($category_description === '') {
                    $category_description = $category_meta['description'];
                } else {
                    $category_description = wp_trim_words($category_description, 18, '...');
                }
                ?>
                <a class="category-card <?php echo esc_attr(kepoli_tone_class($category->slug)); ?>" href="<?php echo esc_url(get_category_link($category)); ?>">
                    <span class="category-card__top">
                        <span class="category-card__icon" aria-hidden="true"><?php echo esc_html($category_meta['icon']); ?></span>
                        <span class="category-card__count">
                            <?php
                            echo esc_html(
                                $category->slug === 'articole'
                                    ? sprintf(_n('%d articol', '%d articole', $category->count, 'kepoli'), $category->count)
                                    : sprintf(_n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)
                            );
                            ?>
                        </span>
                    </span>
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span class="category-card__description"><?php echo esc_html($category_description); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php esc_html_e('Articole', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Ghiduri pentru bucatarie', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Context scurt si util pentru ingrediente, tehnici si planificare.', 'kepoli'); ?></p>
    </div>
    <div class="home-cluster home-cluster--reverse">
        <?php if ($featured_article) : ?>
            <article class="lead-story <?php echo esc_attr(kepoli_post_tone_class($featured_article->ID)); ?>">
                <a class="lead-story__media" href="<?php echo esc_url(get_permalink($featured_article)); ?>">
                    <?php echo kepoli_post_media_markup($featured_article->ID, 'related'); ?>
                </a>
                <div class="lead-story__body">
                    <p class="eyebrow"><?php esc_html_e('Articol recomandat', 'kepoli'); ?></p>
                    <h3><a href="<?php echo esc_url(get_permalink($featured_article)); ?>"><?php echo esc_html(get_the_title($featured_article)); ?></a></h3>
                    <p><?php echo esc_html(get_the_excerpt($featured_article)); ?></p>
                    <?php echo kepoli_render_post_card_meta($featured_article->ID, 'meta-strip meta-strip--inline', 'meta-strip__item'); ?>
                </div>
            </article>
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php esc_html_e('Ghiduri recente', 'kepoli'); ?></p>
            </div>
            <?php while ($article_list->have_posts()) : $article_list->the_post(); ?>
                <article <?php post_class('compact-post ' . kepoli_post_tone_class()); ?>>
                    <a class="compact-post__media" href="<?php the_permalink(); ?>">
                        <?php echo kepoli_post_media_markup(get_the_ID(), 'related'); ?>
                    </a>
                    <div class="compact-post__body">
                        <?php echo kepoli_render_post_card_meta(); ?>
                        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 14, '...')); ?></p>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="author-strip">
        <div class="author-strip__photo" style="--author-image: url('<?php echo esc_url(kepoli_asset_uri('writer-photo', 'svg')); ?>');"></div>
        <div class="author-strip__copy">
            <p class="eyebrow"><?php esc_html_e('Autoare', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
            <p><?php esc_html_e('Scriu retete romanesti si ghiduri practice pentru gatit calm, clar si usor de urmat acasa.', 'kepoli'); ?></p>
            <a class="button" href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Citeste povestea', 'kepoli'); ?></a>
        </div>
    </div>
</section>
<?php
get_footer();
