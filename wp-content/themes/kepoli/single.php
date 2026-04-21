<?php
/**
 * Single post template.
 */
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('content-layout'); ?> data-reading-progress-source>
        <div class="entry">
            <header class="entry-header">
                <?php kepoli_breadcrumbs(); ?>
                <div class="entry-meta">
                    <?php echo esc_html(get_the_date()); ?> / <?php echo esc_html(kepoli_read_time()); ?> / <?php echo esc_html(get_the_author()); ?>
                </div>
                <div class="entry-toolbar">
                    <span class="entry-toolbar__pill <?php echo esc_attr(kepoli_post_tone_class()); ?>"><?php echo esc_html(kepoli_post_kind_label()); ?></span>
                    <?php $category = kepoli_primary_category(); ?>
                    <?php if ($category) : ?>
                        <a class="entry-toolbar__pill entry-toolbar__pill--link" href="<?php echo esc_url(get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
                    <?php endif; ?>
                </div>
                <h1 class="entry-title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="entry-excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
                <?php if (kepoli_post_kind() === 'recipe') : ?>
                    <nav class="entry-jumpnav" aria-label="<?php esc_attr_e('Navigatie reteta', 'kepoli'); ?>">
                        <a href="#ingrediente"><?php esc_html_e('Ingrediente', 'kepoli'); ?></a>
                        <a href="#mod-de-preparare"><?php esc_html_e('Preparare', 'kepoli'); ?></a>
                        <a href="#sfaturi-pentru-reusita"><?php esc_html_e('Sfaturi', 'kepoli'); ?></a>
                        <a href="#legaturi-utile"><?php esc_html_e('Mai departe', 'kepoli'); ?></a>
                    </nav>
                <?php endif; ?>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <section class="author-box author-box--expanded">
                <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'svg')); ?>" alt="<?php esc_attr_e('Isalune Merovik', 'kepoli'); ?>">
                <div>
                    <p class="eyebrow"><?php esc_html_e('Autoare', 'kepoli'); ?></p>
                    <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
                    <p><?php esc_html_e('Scrie retete romanesti, ghiduri pentru bucatarie si explicatii practice pentru gatit acasa, cu accent pe claritate, gust si ingrediente accesibile.', 'kepoli'); ?></p>
                    <div class="author-box__links">
                        <a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a>
                        <a href="mailto:isalunemerovik@gmail.com">isalunemerovik@gmail.com</a>
                    </div>
                </div>
            </section>
            <?php echo kepoli_ad_slot('below_content'); ?>
            <?php
            $is_recipe = kepoli_post_kind() === 'recipe';
            $related_posts = kepoli_related_posts_by_kind(get_the_ID(), $is_recipe ? 'article' : 'recipe');
            if ($related_posts) :
                ?>
                <section class="related-posts related-posts--cards">
                    <div class="related-posts__heading">
                        <p class="eyebrow"><?php echo $is_recipe ? esc_html__('Articole recomandate', 'kepoli') : esc_html__('Retete recomandate', 'kepoli'); ?></p>
                        <h2><?php echo $is_recipe ? esc_html__('Citeste mai departe', 'kepoli') : esc_html__('Ce poti gati dupa acest articol', 'kepoli'); ?></h2>
                        <p><?php echo $is_recipe ? esc_html__('Ghiduri si articole care completeaza reteta de mai sus si te ajuta sa alegi, organizezi sau servesti mai bine preparatul.', 'kepoli') : esc_html__('Retete legate de subiectul articolului, alese pentru a continua lectura cu ceva concret de pus pe masa.', 'kepoli'); ?></p>
                    </div>
                    <div class="related-grid">
                        <?php foreach ($related_posts as $related) : ?>
                            <article <?php post_class('related-card ' . kepoli_post_tone_class($related->ID), $related->ID); ?>>
                                <a class="related-card__media" href="<?php echo esc_url(get_permalink($related)); ?>">
                                    <?php echo kepoli_post_media_markup($related->ID, 'related'); ?>
                                </a>
                                <div class="related-card__body">
                                    <div class="post-card__meta">
                                        <?php echo esc_html(get_the_date('j M Y', $related)); ?> / <?php echo esc_html(kepoli_read_time($related->ID)); ?>
                                    </div>
                                    <h3><a href="<?php echo esc_url(get_permalink($related)); ?>"><?php echo esc_html(get_the_title($related)); ?></a></h3>
                                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt($related), 24, '...')); ?></p>
                                    <a class="post-card__link" href="<?php echo esc_url(get_permalink($related)); ?>">
                                        <?php echo get_post_meta($related->ID, '_kepoli_post_kind', true) === 'article' ? esc_html__('Citeste articolul', 'kepoli') : esc_html__('Citeste reteta', 'kepoli'); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
        <aside class="sidebar" aria-label="<?php esc_attr_e('Context articol', 'kepoli'); ?>">
            <?php get_template_part('template-parts-sidebar'); ?>
        </aside>
    </article>
<?php endwhile; ?>
<?php
get_footer();
