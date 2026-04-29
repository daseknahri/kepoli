<?php
/**
 * Front page.
 */
get_header();

$hero_image = kepoli_asset_uri('hero-homepage', 'jpg');
$hero_srcset = kepoli_home_hero_srcset();
$hero_sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 92vw, 1536px';
$categories = get_categories([
    'hide_empty' => true,
    'exclude' => [1],
    'orderby' => 'name',
]);
$featured_recipe = kepoli_latest_post_by_kind('recipe');
$featured_article = kepoli_latest_post_by_kind('article');
$recently_touched_articles = kepoli_recently_touched_posts_by_kind('article', 3, $featured_article ? [$featured_article->ID] : []);
$writer_name = kepoli_writer_name();
$site_name = kepoli_site_name();
$hero_alt = sprintf(kepoli_ui_text('Masa de acasa cu retete si ghiduri culinare de la %s', 'Home cooking table with recipes and kitchen guides from %s'), $site_name);
$front_page_content = '';
$front_page_id = get_queried_object_id();
if ($front_page_id) {
    $front_page_content = trim((string) apply_filters('the_content', (string) get_post_field('post_content', $front_page_id)));
}

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
<section class="home-hero">
    <img class="home-hero__image" src="<?php echo esc_url($hero_image); ?>" alt="<?php echo esc_attr($hero_alt); ?>"<?php echo kepoli_asset_dimension_attributes('hero-homepage'); ?><?php echo $hero_srcset !== '' ? ' srcset="' . esc_attr($hero_srcset) . '" sizes="' . esc_attr($hero_sizes) . '"' : ''; ?> fetchpriority="high" loading="eager" decoding="async">
    <div class="home-hero__inner">
        <p class="eyebrow"><?php echo esc_html($site_name); ?></p>
        <h1><?php echo esc_html((string) kepoli_profile_value(['brand', 'tagline'], kepoli_ui_text('Retete pentru acasa si ghiduri practice.', 'Recipes and guides for better home cooking.'))); ?></h1>
        <p><?php echo esc_html(kepoli_brand_description()); ?></p>
        <div class="button-row">
            <a class="button" href="<?php echo esc_url(kepoli_recipes_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Vezi retetele', 'View recipes')); ?></a>
        </div>
    </div>
</section>

<?php if ($front_page_content !== '') : ?>
    <section class="section section--tight home-copy defer-section">
        <div class="entry-content entry-content--page">
            <?php echo $front_page_content; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section section--tight home-proof defer-section">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Transparenta', 'Transparency')); ?></p>
            <h2><?php echo esc_html(kepoli_ui_text('Cine scrie, cum lucram, unde ne poti verifica', 'Who writes, how we work, and how to check us')); ?></h2>
        </div>
        <p><?php echo esc_html(sprintf(kepoli_ui_text('%s este construit pentru cititorii care intra direct intr-o pagina si vor sa vada repede autorul, regulile editoriale si cum pot cere clarificari.', '%s is built for readers who land directly on a page and want to quickly see the author, editorial rules, and how to ask for clarification.'), $site_name)); ?></p>
    </div>
    <?php kepoli_render_reader_trust_links('browse-links browse-links--trust home-proof__links'); ?>
</section>

<section class="section defer-section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Retete publicate', 'Published recipes')); ?></p>
            <h2><?php echo esc_html(kepoli_ui_text('De gatit saptamana aceasta', 'Cook this week')); ?></h2>
        </div>
        <p><?php echo esc_html(kepoli_ui_text('Retete clare, usor de scanat si simple de pus in practica.', 'Clear recipes that are easy to scan and simple to put into practice.')); ?></p>
    </div>
    <div class="home-cluster">
        <?php if ($featured_recipe) : ?>
            <article class="lead-story <?php echo esc_attr(kepoli_post_tone_class($featured_recipe->ID)); ?>">
                <a class="lead-story__media" href="<?php echo esc_url(get_permalink($featured_recipe)); ?>">
                    <?php echo kepoli_post_media_markup($featured_recipe->ID, 'related', true); ?>
                </a>
                <div class="lead-story__body">
                    <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Reteta recomandata', 'Recommended recipe')); ?></p>
                    <h3><a href="<?php echo esc_url(get_permalink($featured_recipe)); ?>"><?php echo esc_html(get_the_title($featured_recipe)); ?></a></h3>
                    <p><?php echo esc_html(get_the_excerpt($featured_recipe)); ?></p>
                    <?php echo kepoli_render_post_card_meta($featured_recipe->ID, 'meta-strip meta-strip--inline', 'meta-strip__item'); ?>
                </div>
            </article>
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Mai multe retete', 'More recipes')); ?></p>
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

<section class="category-band defer-section">
    <div class="section">
        <div class="section__header section__header--simple">
            <div>
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Categorii', 'Categories')); ?></p>
                <h2><?php echo esc_html(kepoli_ui_text('Alege dupa pofta', 'Choose by appetite')); ?></h2>
            </div>
        </div>
        <div class="category-list category-list--showcase">
            <?php foreach ($categories as $category) : ?>
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
                            <img src="<?php echo esc_url($category_image['url']); ?>" alt="<?php echo esc_attr(($category_image['alt'] ?? '') !== '' ? $category_image['alt'] : sprintf(kepoli_ui_text('Imagine pentru categoria %s', 'Image for the %s category'), $category->name)); ?>"<?php echo kepoli_dimension_attributes($category_image); ?> loading="lazy" decoding="async">
                        </span>
                    <?php endif; ?>
                    <span class="category-card__top">
                        <span class="category-card__icon" aria-hidden="true"><?php echo esc_html($category_meta['icon']); ?></span>
                        <span class="category-card__count">
                            <?php
                            echo esc_html(
                                kepoli_is_editorial_category_slug($category->slug)
                                    ? sprintf(kepoli_is_english() ? _n('%d guide', '%d guides', $category->count, 'kepoli') : _n('%d articol', '%d articole', $category->count, 'kepoli'), $category->count)
                                    : sprintf(kepoli_is_english() ? _n('%d recipe', '%d recipes', $category->count, 'kepoli') : _n('%d reteta', '%d retete', $category->count, 'kepoli'), $category->count)
                            );
                            ?>
                        </span>
                    </span>
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span class="category-card__description"><?php echo esc_html($category_description); ?></span>
                    <?php if (!empty($category_image['gallery'])) : ?>
                        <span class="category-card__gallery" aria-hidden="true">
                            <?php foreach ($category_image['gallery'] as $gallery_item) : ?>
                                <span class="category-card__thumb">
                                    <img src="<?php echo esc_url($gallery_item['url']); ?>" alt="<?php echo esc_attr(($gallery_item['alt'] ?? '') !== '' ? $gallery_item['alt'] : sprintf(kepoli_ui_text('Imagine pentru categoria %s', 'Image for the %s category'), $category->name)); ?>"<?php echo kepoli_dimension_attributes($gallery_item); ?> loading="lazy" decoding="async">
                                </span>
                            <?php endforeach; ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($category_image['sample'])) : ?>
                        <span class="category-card__sample"><?php echo esc_html(sprintf(kepoli_ui_text('De inceput: %s', 'Start with: %s'), $category_image['sample'])); ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section defer-section">
    <?php $editorial_paths = kepoli_editorial_paths(); ?>
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Articole', 'Guides')); ?></p>
            <h2><?php echo esc_html(kepoli_ui_text('Ghiduri pentru bucatarie', 'Kitchen guides')); ?></h2>
        </div>
        <p><?php echo esc_html(kepoli_ui_text('Context scurt si util pentru ingrediente, tehnici si planificare, cu ghidurile importante revizuite periodic.', 'Short, useful context for ingredients, techniques, and planning, with important guides reviewed over time.')); ?></p>
    </div>
    <div class="home-cluster home-cluster--reverse">
        <?php if ($featured_article) : ?>
            <article class="lead-story <?php echo esc_attr(kepoli_post_tone_class($featured_article->ID)); ?>">
                <a class="lead-story__media" href="<?php echo esc_url(get_permalink($featured_article)); ?>">
                    <?php echo kepoli_post_media_markup($featured_article->ID, 'related'); ?>
                </a>
                <div class="lead-story__body">
                    <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Articol recomandat', 'Recommended guide')); ?></p>
                    <h3><a href="<?php echo esc_url(get_permalink($featured_article)); ?>"><?php echo esc_html(get_the_title($featured_article)); ?></a></h3>
                    <p><?php echo esc_html(get_the_excerpt($featured_article)); ?></p>
                    <?php echo kepoli_render_post_card_meta($featured_article->ID, 'meta-strip meta-strip--inline', 'meta-strip__item'); ?>
                </div>
            </article>
        <?php endif; ?>
        <div class="compact-post-list">
            <div class="compact-post-list__heading">
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Ghiduri recente', 'Recent guides')); ?></p>
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
    <?php if ($recently_touched_articles) : ?>
        <div class="review-lane">
            <div class="section__header section__header--compact section__header--simple">
                <div>
                    <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Urmarite de aproape', 'Recently touched')); ?></p>
                    <h2><?php echo esc_html(kepoli_ui_text('Ghiduri publicate sau revizuite recent', 'Recently published or reviewed guides')); ?></h2>
                </div>
                <p><?php echo esc_html(kepoli_ui_text('Un semnal simplu ca partea editoriala nu sta pe loc: unele ghiduri sunt noi, altele sunt revazute cand apar clarificari utile.', 'A simple signal that the editorial side keeps moving: some guides are new, others are reviewed when useful clarifications appear.')); ?></p>
            </div>
            <div class="review-grid">
                <?php foreach ($recently_touched_articles as $article) : ?>
                    <a class="page-panel review-card tone-guides" href="<?php echo esc_url(get_permalink($article)); ?>">
                        <p class="eyebrow"><?php echo esc_html(kepoli_article_freshness_label($article->ID)); ?></p>
                        <h3><?php echo esc_html(get_the_title($article)); ?></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt($article), 18, '...')); ?></p>
                        <span class="review-card__meta"><?php echo esc_html(kepoli_read_time($article->ID)); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    <?php if ($editorial_paths) : ?>
        <div class="guide-paths">
            <div class="section__header section__header--compact section__header--simple">
                <div>
                    <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Zone editoriale', 'Editorial paths')); ?></p>
                    <h2><?php echo esc_html(kepoli_ui_text('Intra direct in tipul de ghid care te ajuta acum', 'Go straight to the guide type that helps now')); ?></h2>
                </div>
                <p><?php echo esc_html(kepoli_ui_text('Ingrediente, tehnici, sezon si planificare, grupate ca sa ajungi mai repede la articolul potrivit.', 'Ingredients, techniques, seasonality, and planning grouped so readers can reach the right article faster.')); ?></p>
            </div>
            <div class="guide-path-grid">
                <?php foreach ($editorial_paths as $path) : ?>
                    <section class="guide-path <?php echo esc_attr($path['class']); ?>">
                        <p class="eyebrow"><?php echo esc_html($path['eyebrow']); ?></p>
                        <h3><?php echo esc_html($path['title']); ?></h3>
                        <p><?php echo esc_html($path['summary']); ?></p>
                        <ul class="guide-path__list">
                            <?php foreach ($path['articles'] as $article) : ?>
                                <li><a href="<?php echo esc_url($article['url']); ?>"><?php echo esc_html($article['title']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<section class="section section--tight defer-section">
    <div class="author-strip">
        <div class="author-strip__photo">
            <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'jpg')); ?>" alt="<?php echo esc_attr(sprintf(kepoli_ui_text('%1$s, autoarea %2$s', '%1$s, writer for %2$s'), $writer_name, $site_name)); ?>"<?php echo kepoli_asset_dimension_attributes('writer-photo'); ?> loading="lazy" decoding="async">
        </div>
        <div class="author-strip__copy">
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Autoare', 'Writer')); ?></p>
            <h2><?php echo esc_html($writer_name); ?></h2>
            <p><?php echo esc_html(kepoli_writer_description()); ?></p>
            <?php echo kepoli_newsletter_cta('newsletter-cta--compact newsletter-cta--homepage'); ?>
            <a class="button" href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Citeste povestea', 'Read the story')); ?></a>
        </div>
    </div>
</section>
<?php
get_footer();
