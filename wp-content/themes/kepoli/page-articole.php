<?php
/**
 * Articles landing page.
 */
get_header();

$featured_article = kepoli_latest_post_by_kind('article');
$editorial_paths = kepoli_editorial_paths();
$article_meta_items = kepoli_article_collection_meta_items(kepoli_post_count_by_kind('article'));
$recently_touched_articles = kepoli_recently_touched_posts_by_kind('article', 3, $featured_article ? [$featured_article->ID] : []);
$page_id = get_queried_object_id();
$page_title = $page_id ? get_the_title($page_id) : '';
$page_content = $page_id ? trim((string) apply_filters('the_content', (string) get_post_field('post_content', $page_id))) : '';
$page_intro = $page_content !== '' ? wp_trim_words(wp_strip_all_tags($page_content), 28, '') : '';
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Articole', 'Guides')); ?></p>
    <h1><?php echo esc_html($page_title !== '' ? $page_title : kepoli_ui_text('Ghiduri de bucatarie', 'Kitchen guides')); ?></h1>
    <p><?php echo esc_html($page_intro !== '' ? $page_intro : kepoli_ui_text('Organizare, ingrediente, tehnici si idei practice pentru gatit mai clar acasa.', 'Organization, ingredients, techniques, and practical ideas for better home cooking.')); ?></p>
    <?php if ($article_meta_items) : ?>
        <div class="meta-strip">
            <?php foreach ($article_meta_items as $item) : ?>
                <span class="meta-strip__item"><?php echo esc_html($item); ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php kepoli_render_reader_trust_links(); ?>
</header>
<?php if ($page_content !== '') : ?>
    <section class="section section--tight">
        <div class="entry-content entry-content--page">
            <?php echo $page_content; ?>
        </div>
    </section>
<?php endif; ?>
<?php if ($featured_article) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact">
            <div>
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('In prim-plan', 'Featured')); ?></p>
                <h2><?php echo esc_html(kepoli_ui_text('Un bun punct de pornire', 'A good place to start')); ?></h2>
            </div>
        </div>
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
    </section>
<?php endif; ?>
<?php if ($recently_touched_articles) : ?>
    <section class="section section--tight">
        <div class="section__header section__header--compact section__header--simple">
            <div>
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Urmarite de aproape', 'Recently touched')); ?></p>
                <h2><?php echo esc_html(kepoli_ui_text('Ghiduri publicate sau revizuite recent', 'Recently published or reviewed guides')); ?></h2>
            </div>
            <p><?php echo esc_html(kepoli_ui_text('Aici apar ghidurile la care ne-am intors recent, fie pentru ca sunt noi, fie pentru ca au primit clarificari utile.', 'These are the guides we have returned to recently, either because they are new or because they received useful clarifications.')); ?></p>
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
                <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Zone editoriale', 'Editorial paths')); ?></p>
                <h2><?php echo esc_html(kepoli_ui_text('Alege dupa tipul de ajutor de care ai nevoie', 'Choose by the kind of help you need')); ?></h2>
            </div>
            <p><?php echo esc_html(kepoli_ui_text('Ghidurile sunt grupate simplu, ca sa ajungi mai repede la ingredientele, tehnicile sau ideile de planificare care conteaza pentru tine.', 'Guides are grouped simply so readers can reach the ingredients, techniques, or planning ideas they need faster.')); ?></p>
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
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Toate ghidurile', 'All guides')); ?></p>
            <h2><?php echo esc_html(kepoli_ui_text('Citeste dupa nevoie', 'Read by need')); ?></h2>
        </div>
        <p><?php echo esc_html(kepoli_ui_text('Ghiduri practice, aranjate simplu pentru citire rapida.', 'Practical guides, arranged simply for quick reading.')); ?></p>
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
