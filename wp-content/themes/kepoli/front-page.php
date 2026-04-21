<?php
/**
 * Front page.
 */
get_header();

$hero_image = kepoli_asset_uri('hero-homepage', 'png');
$wordmark = kepoli_asset_uri('kepoli-wordmark');
$recipe_count = kepoli_published_kind_count('recipe');
$article_count = kepoli_published_kind_count('article');
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
        <img class="home-hero__brand" src="<?php echo esc_url($wordmark); ?>" alt="Kepoli">
        <h1><?php esc_html_e('Retete romanesti cu gust de acasa.', 'kepoli'); ?></h1>
        <p><?php esc_html_e('Ciorbe, feluri principale, deserturi si ghiduri simple pentru bucataria de fiecare zi.', 'kepoli'); ?></p>
        <div class="button-row">
            <a class="button" href="<?php echo esc_url(home_url('/retete/')); ?>"><?php esc_html_e('Vezi retetele', 'kepoli'); ?></a>
            <a class="button button--ghost" href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a>
        </div>
        <div class="home-hero__meta" aria-label="<?php esc_attr_e('Rezumat continut Kepoli', 'kepoli'); ?>">
            <span><?php echo esc_html(sprintf(_n('%d reteta', '%d retete', $recipe_count, 'kepoli'), $recipe_count)); ?></span>
            <span><?php echo esc_html(sprintf(_n('%d articol', '%d articole', $article_count, 'kepoli'), $article_count)); ?></span>
            <span><?php echo esc_html(sprintf(_n('%d categorie', '%d categorii', count($categories), 'kepoli'), count($categories))); ?></span>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php esc_html_e('Retete publicate', 'kepoli'); ?></p>
            <h2><?php esc_html_e('De gatit saptamana aceasta', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Retete romanesti asezate clar, cu ingrediente la indemana si pasi usor de urmat.', 'kepoli'); ?></p>
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
                    <div class="meta-strip meta-strip--inline">
                        <span class="meta-strip__item"><?php echo esc_html(get_the_date('j M Y', $featured_recipe)); ?></span>
                        <span class="meta-strip__item"><?php echo esc_html(kepoli_read_time($featured_recipe->ID)); ?></span>
                    </div>
                    <a class="button" href="<?php echo esc_url(get_permalink($featured_recipe)); ?>"><?php esc_html_e('Deschide reteta', 'kepoli'); ?></a>
                </div>
            </article>
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php esc_html_e('Mai multe retete', 'kepoli'); ?></p>
                <a class="section-link" href="<?php echo esc_url(home_url('/retete/')); ?>"><?php esc_html_e('Vezi tot', 'kepoli'); ?></a>
            </div>
            <?php while ($recipe_list->have_posts()) : $recipe_list->the_post(); ?>
                <article <?php post_class('compact-post ' . kepoli_post_tone_class()); ?>>
                    <a class="compact-post__media" href="<?php the_permalink(); ?>">
                        <?php echo kepoli_post_media_markup(get_the_ID(), 'related'); ?>
                    </a>
                    <div class="compact-post__body">
                        <div class="post-card__meta">
                            <?php echo esc_html(get_the_date('j M Y')); ?> / <?php echo esc_html(kepoli_read_time()); ?>
                        </div>
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
        <div class="section__header">
            <div>
                <p class="eyebrow"><?php esc_html_e('Categorii', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege dupa pofta', 'kepoli'); ?></h2>
            </div>
            <p><?php esc_html_e('Intrari rapide catre categoriile cele mai cautate, pentru cand stii deja ce fel de masa vrei sa pregatesti.', 'kepoli'); ?></p>
        </div>
        <div class="category-list">
            <?php foreach ($categories as $category) : ?>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span><?php echo esc_html(sprintf(_n('%d articol', '%d articole', $category->count, 'kepoli'), $category->count)); ?></span>
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
        <p><?php esc_html_e('Idei de organizare, ingrediente, tehnici si meniuri care sustin retetele de zi cu zi.', 'kepoli'); ?></p>
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
                    <div class="meta-strip meta-strip--inline">
                        <span class="meta-strip__item"><?php echo esc_html(get_the_date('j M Y', $featured_article)); ?></span>
                        <span class="meta-strip__item"><?php echo esc_html(kepoli_read_time($featured_article->ID)); ?></span>
                    </div>
                    <a class="button" href="<?php echo esc_url(get_permalink($featured_article)); ?>"><?php esc_html_e('Citeste articolul', 'kepoli'); ?></a>
                </div>
            </article>
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php esc_html_e('Ghiduri recente', 'kepoli'); ?></p>
                <a class="section-link" href="<?php echo esc_url(home_url('/articole/')); ?>"><?php esc_html_e('Vezi tot', 'kepoli'); ?></a>
            </div>
            <?php while ($article_list->have_posts()) : $article_list->the_post(); ?>
                <article <?php post_class('compact-post ' . kepoli_post_tone_class()); ?>>
                    <a class="compact-post__media" href="<?php the_permalink(); ?>">
                        <?php echo kepoli_post_media_markup(get_the_ID(), 'related'); ?>
                    </a>
                    <div class="compact-post__body">
                        <div class="post-card__meta">
                            <?php echo esc_html(get_the_date('j M Y')); ?> / <?php echo esc_html(kepoli_read_time()); ?>
                        </div>
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
        <div class="author-strip__photo" style="--author-image: url('<?php echo esc_url($hero_image); ?>');"></div>
        <div class="author-strip__copy">
            <p class="eyebrow"><?php esc_html_e('Autoare', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
            <p><?php esc_html_e('Scriu retete romanesti intr-un stil calm si practic: ce cumperi, cum pregatesti, cum ajustezi gustul si cum pastrezi mancarea fara risipa.', 'kepoli'); ?></p>
            <a class="button" href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Citeste povestea', 'kepoli'); ?></a>
        </div>
    </div>
</section>
<?php
get_footer();
