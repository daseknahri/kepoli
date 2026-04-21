<?php
/**
 * Articles landing page.
 */
get_header();

$article_count = kepoli_published_kind_count('article');
$featured_article = kepoli_latest_post_by_kind('article');
?>
<header class="archive-header">
    <?php kepoli_breadcrumbs(); ?>
    <p class="eyebrow"><?php esc_html_e('Articole', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Ghiduri de bucatarie', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Organizare, ingrediente, tehnici si idei pentru mese romanesti bine asezate.', 'kepoli'); ?></p>
    <div class="meta-strip" aria-label="<?php esc_attr_e('Rezumat articole', 'kepoli'); ?>">
        <span class="meta-strip__item"><?php echo esc_html(sprintf(_n('%d articol publicat', '%d articole publicate', $article_count, 'kepoli'), $article_count)); ?></span>
        <span class="meta-strip__item"><?php esc_html_e('Context pentru retete, ingrediente si planificare', 'kepoli'); ?></span>
    </div>
    <?php kepoli_render_browse_links(); ?>
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
                <div class="meta-strip meta-strip--inline">
                    <span class="meta-strip__item"><?php echo esc_html(get_the_date('j M Y', $featured_article)); ?></span>
                    <span class="meta-strip__item"><?php echo esc_html(kepoli_read_time($featured_article->ID)); ?></span>
                </div>
                <a class="button" href="<?php echo esc_url(get_permalink($featured_article)); ?>"><?php esc_html_e('Citeste articolul', 'kepoli'); ?></a>
            </div>
        </article>
    </section>
<?php endif; ?>
<section class="section section--tight">
    <div class="section__header section__header--compact">
        <div>
            <p class="eyebrow"><?php esc_html_e('Toate ghidurile', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Citeste dupa nevoie', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Articole scurte si utile despre organizare, produse, tehnici si obiceiuri care fac gatitul de acasa mai usor si mai sigur.', 'kepoli'); ?></p>
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
