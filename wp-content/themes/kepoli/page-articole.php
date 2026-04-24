<?php
/**
 * Articles landing page.
 */
get_header();

$featured_article = kepoli_latest_post_by_kind('article');
$editorial_paths = kepoli_editorial_paths();
$article_meta_items = kepoli_article_collection_meta_items(kepoli_post_count_by_kind('article'));
$recently_touched_articles = kepoli_recently_touched_posts_by_kind('article', 3, $featured_article ? [$featured_article->ID] : []);
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Articole', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Ghiduri de bucatarie', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Organizare, ingrediente, tehnici si idei pentru mese romanesti bine asezate.', 'kepoli'); ?></p>
    <?php if ($article_meta_items) : ?>
        <div class="meta-strip">
            <?php foreach ($article_meta_items as $item) : ?>
                <span class="meta-strip__item"><?php echo esc_html($item); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php kepoli_render_reader_trust_links(); ?>
</header>
<?php if ($featured_article) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact">
            <div>
                <p class="eyebrow"><?php esc_html_e('In prim-plan', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Un bun punct de pornire', 'kepoli'); ?></h2>
            </div>
        </div>
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
    </section>
<?php endif; ?>
<?php if ($recently_touched_articles) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact section__header--simple">
            <div>
                <p class="eyebrow"><?php esc_html_e('Urmarite de aproape', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Ghiduri publicate sau revizuite recent', 'kepoli'); ?></h2>
            </div>
            <p><?php esc_html_e('Aici apar ghidurile la care ne-am intors recent, fie pentru ca sunt noi, fie pentru ca au primit clarificari utile.', 'kepoli'); ?></p>
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
    </section>
<?php endif; ?>
<?php if ($editorial_paths) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact section__header--simple">
            <div>
                <p class="eyebrow"><?php esc_html_e('Zone editoriale', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege dupa tipul de ajutor de care ai nevoie', 'kepoli'); ?></h2>
            </div>
            <p><?php esc_html_e('Ghidurile sunt grupate simplu, ca sa ajungi mai repede la ingredientele, tehnicile sau ideile de planificare care conteaza pentru tine.', 'kepoli'); ?></p>
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
    </section>
<?php endif; ?>
<section class="section section--tight">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php esc_html_e('Toate ghidurile', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Citeste dupa nevoie', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Ghiduri practice, aranjate simplu pentru citire rapida.', 'kepoli'); ?></p>
    </div>
    <div class="post-grid">
        <?php
        $articles = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 12,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'article',
        ]);
        while ($articles->have_posts()) :
            $articles->the_post();
            get_template_part('template-parts-card');
        endwhile;
        wp_reset_postdata();
        ?>
    </div>
</section>
<?php
get_footer();
