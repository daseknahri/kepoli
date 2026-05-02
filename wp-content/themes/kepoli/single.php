<?php
/**
 * Single post template.
 */
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <?php
    $is_recipe = kepoli_post_kind() === 'recipe';
    $category = kepoli_primary_category();
    $updated_label = kepoli_post_updated_label();
    $share_links = kepoli_share_links();
    $article_headings = !$is_recipe ? kepoli_article_heading_index() : [];
    $article_snapshot = [];
    $recipe_snapshot = $is_recipe ? kepoli_recipe_snapshot_items() : [];
    $post_next_steps = $is_recipe ? kepoli_post_next_steps() : ['items' => []];
    $share_icons = ['facebook' => 'facebook', 'whatsapp' => 'whatsapp', 'email' => 'email', 'copy' => 'link', 'print' => 'print'];
    $featured_image = kepoli_post_featured_image_markup(get_the_ID(), 'large', [
        'class' => 'entry-featured-media__image',
        'loading' => 'eager',
        'fetchpriority' => 'high',
        'decoding' => 'async',
        'sizes' => '(max-width: 760px) 100vw, 760px',
    ]);
    $featured_caption = $featured_image !== '' ? kepoli_post_featured_image_caption(get_the_ID()) : '';
    ?>
    <article <?php post_class('content-layout content-layout--single-post ' . ($is_recipe ? 'content-layout--recipe' : 'content-layout--article')); ?> data-reading-progress-source>
        <div class="entry">
            <header class="entry-header">
                <?php kepoli_breadcrumbs(); ?>
                <div class="entry-toolbar">
                    <span class="entry-toolbar__pill <?php echo esc_attr(kepoli_post_tone_class()); ?>"><?php echo esc_html(kepoli_post_kind_label()); ?></span>
                    <?php if ($category) : ?>
                        <a class="entry-toolbar__pill entry-toolbar__pill--link" href="<?php echo esc_url(get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
                    <?php endif; ?>
                </div>
                <h1 class="entry-title"><?php the_title(); ?></h1>
                <?php if (has_excerpt()) : ?>
                    <p class="entry-excerpt"><?php echo esc_html(get_the_excerpt()); ?></p>
                <?php endif; ?>
                <?php if ($featured_image !== '') : ?>
                    <figure class="entry-featured-media entry-featured-media--header">
                        <?php echo $featured_image; ?>
                        <?php if ($featured_caption) : ?>
                            <figcaption><?php echo esc_html($featured_caption); ?></figcaption>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>
                <div class="entry-summary">
                    <div class="entry-meta-row" aria-label="<?php echo esc_attr(kepoli_ui_text('Informatii articol', 'Article information')); ?>">
                        <span class="entry-meta-row__item"><?php echo kepoli_icon('calendar'); ?><strong><?php echo esc_html(get_the_date()); ?></strong></span>
                        <span class="entry-meta-row__item"><?php echo kepoli_icon('clock'); ?><strong><?php echo esc_html(kepoli_read_time()); ?></strong></span>
                        <span class="entry-meta-row__item entry-meta-row__item--author"><?php echo kepoli_icon('user'); ?><strong><a href="<?php echo esc_url(kepoli_author_page_url()); ?>" rel="author"><?php echo esc_html(get_the_author()); ?></a></strong></span>
                        <?php if ($updated_label !== '') : ?>
                            <span class="entry-meta-row__item entry-meta-row__item--updated"><?php echo kepoli_icon('refresh'); ?><strong><?php echo esc_html($updated_label); ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <div class="share-tools share-tools--minimal" aria-label="<?php echo esc_attr(kepoli_ui_text('Actiuni articol', 'Article actions')); ?>">
                        <?php foreach ($share_links as $share) : ?>
                            <?php $icon = $share_icons[$share['type']] ?? 'link'; ?>
                            <?php if ($share['type'] === 'copy') : ?>
                                <button class="share-tools__button share-tools__button--icon" type="button" title="<?php echo esc_attr($share['label']); ?>" aria-label="<?php echo esc_attr($share['label']); ?>" data-copy-url="<?php echo esc_attr($share['url']); ?>" data-copy-default="<?php echo esc_attr($share['label']); ?>" data-copy-success="<?php echo esc_attr(kepoli_ui_text('Link copiat', 'Link copied')); ?>">
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span class="screen-reader-text"><?php echo esc_html($share['label']); ?></span>
                                </button>
                            <?php elseif ($share['type'] === 'print') : ?>
                                <button class="share-tools__button share-tools__button--icon" type="button" title="<?php echo esc_attr($share['label']); ?>" aria-label="<?php echo esc_attr($share['label']); ?>" data-print-page>
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span class="screen-reader-text"><?php echo esc_html($share['label']); ?></span>
                                </button>
                            <?php else : ?>
                                <a class="share-tools__button share-tools__button--icon" href="<?php echo esc_url($share['url']); ?>" target="_blank" rel="noopener nofollow" title="<?php echo esc_attr($share['label']); ?>" aria-label="<?php echo esc_attr($share['label']); ?>">
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span class="screen-reader-text"><?php echo esc_html($share['label']); ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($recipe_snapshot) : ?>
                    <div class="entry-recipe-snapshot" aria-label="<?php echo esc_attr(kepoli_ui_text('Detalii rapide reteta', 'Quick recipe details')); ?>">
                        <?php foreach ($recipe_snapshot as $item) : ?>
                            <div class="entry-recipe-snapshot__item">
                                <span class="entry-recipe-snapshot__label">
                                    <?php echo kepoli_icon($item['icon']); ?>
                                    <span><?php echo esc_html($item['label']); ?></span>
                                </span>
                                <strong><?php echo esc_html($item['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($article_snapshot) : ?>
                    <div class="entry-article-snapshot" aria-label="<?php echo esc_attr(kepoli_ui_text('Repere rapide ghid', 'Quick guide highlights')); ?>">
                        <?php foreach ($article_snapshot as $item) : ?>
                            <div class="entry-article-snapshot__item">
                                <span class="entry-article-snapshot__label">
                                    <?php echo kepoli_icon($item['icon']); ?>
                                    <span><?php echo esc_html($item['label']); ?></span>
                                </span>
                                <strong><?php echo esc_html($item['value']); ?></strong>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php kepoli_render_reader_trust_links(); ?>
                <?php if (!$is_recipe && count($article_headings) > 1) : ?>
                    <nav class="entry-outline" aria-label="<?php echo esc_attr(kepoli_ui_text('In acest articol', 'In this article')); ?>">
                        <div class="entry-outline__header">
                            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('In articol', 'In this article')); ?></p>
                        </div>
                        <ol class="entry-outline__list">
                            <?php foreach ($article_headings as $index => $heading) : ?>
                                <li>
                                    <a href="#<?php echo esc_attr($heading['id']); ?>">
                                        <span class="entry-outline__number"><?php echo esc_html((string) ($index + 1)); ?></span>
                                        <span><?php echo esc_html($heading['label']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                <?php endif; ?>
            </header>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php
            wp_link_pages([
                'before' => '<nav class="post-page-links" aria-label="' . esc_attr(kepoli_ui_text('Navigatie pagini articol', 'Article page navigation')) . '"><span class="post-page-links__label">' . esc_html(kepoli_ui_text('Partile articolului', 'Article parts')) . '</span><div class="post-page-links__items">',
                'after' => '</div></nav>',
                'link_before' => '<span>',
                'link_after' => '</span>',
            ]);
            ?>
            <?php if (!empty($post_next_steps['items'])) : ?>
                <section class="entry-next-steps">
                    <div class="entry-next-steps__header">
                        <p class="eyebrow"><?php echo esc_html($post_next_steps['eyebrow']); ?></p>
                        <h2><?php echo esc_html($post_next_steps['title']); ?></h2>
                        <p><?php echo esc_html($post_next_steps['description']); ?></p>
                    </div>
                    <div class="page-grid entry-next-grid">
                        <?php foreach ($post_next_steps['items'] as $item) : ?>
                            <a class="page-panel entry-next-card <?php echo esc_attr($item['class']); ?>" href="<?php echo esc_url($item['url']); ?>">
                                <span class="eyebrow"><?php echo esc_html($item['eyebrow']); ?></span>
                                <strong><?php echo esc_html($item['label']); ?></strong>
                                <span><?php echo esc_html($item['meta']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php echo kepoli_ad_slot('below_content'); ?>
            <?php
            $related_posts = kepoli_related_posts_by_kind(get_the_ID(), $is_recipe ? 'article' : 'recipe');
            if ($related_posts) :
                ?>
                <section class="related-posts related-posts--cards">
                    <div class="related-posts__heading">
                        <p class="eyebrow"><?php echo esc_html($is_recipe ? kepoli_ui_text('Articole recomandate', 'Recommended guides') : kepoli_ui_text('Retete recomandate', 'Recommended recipes')); ?></p>
                        <h2><?php echo esc_html($is_recipe ? kepoli_ui_text('Mai departe', 'Next reads') : kepoli_ui_text('Continua lectura', 'Keep reading')); ?></h2>
                        <p><?php echo esc_html($is_recipe ? kepoli_ui_text('Ghiduri utile care completeaza reteta de mai sus.', 'Useful guides that support the recipe above.') : kepoli_ui_text('Retete potrivite pentru a transforma articolul in ceva concret de pus pe masa.', 'Recipes that turn the article into something concrete for the table.')); ?></p>
                    </div>
                    <div class="related-grid">
                        <?php foreach ($related_posts as $related) : ?>
                            <?php
                            $related_category = kepoli_primary_category($related->ID);
                            $show_related_category = $related_category && !kepoli_is_editorial_category_slug($related_category->slug);
                            ?>
                            <article <?php post_class('related-card ' . kepoli_post_tone_class($related->ID), $related->ID); ?>>
                                <a class="related-card__media" href="<?php echo esc_url(get_permalink($related)); ?>">
                                    <?php echo kepoli_post_card_media_markup($related->ID, 'related'); ?>
                                </a>
                                <div class="related-card__body">
                                    <div class="related-card__eyebrow content-chip-row">
                                        <span class="content-chip content-chip--muted"><?php echo esc_html(kepoli_post_kind_label($related->ID)); ?></span>
                                        <?php if ($show_related_category) : ?>
                                            <a class="content-chip content-chip--category" href="<?php echo esc_url(get_category_link($related_category)); ?>"><?php echo esc_html($related_category->name); ?></a>
                                        <?php endif; ?>
                                    </div>
                                    <?php echo kepoli_render_post_card_meta($related->ID); ?>
                                    <?php $related_reason = kepoli_related_card_reason(get_the_ID(), $related->ID); ?>
                                    <?php if ($related_reason !== '') : ?>
                                        <p class="related-card__reason"><?php echo esc_html($related_reason); ?></p>
                                    <?php endif; ?>
                                    <h3><a href="<?php echo esc_url(get_permalink($related)); ?>"><?php echo esc_html(get_the_title($related)); ?></a></h3>
                                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt($related), 24, '...')); ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php
            $previous_post = get_previous_post();
            $next_post = get_next_post();
            if ($previous_post || $next_post) :
                ?>
                <nav class="post-navigation-simple" aria-label="<?php echo esc_attr(kepoli_ui_text('Navigatie intre articole', 'Post navigation')); ?>">
                    <?php if ($previous_post) : ?>
                        <a class="post-navigation-simple__item post-navigation-simple__item--prev" href="<?php echo esc_url(get_permalink($previous_post)); ?>">
                            <span class="post-navigation-simple__eyebrow"><?php echo esc_html(kepoli_ui_text('Anterior', 'Previous')); ?></span>
                            <div class="post-navigation-simple__body">
                                <strong><?php echo esc_html(get_the_title($previous_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($previous_post), 14, '...')); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                    <?php if ($next_post) : ?>
                        <a class="post-navigation-simple__item post-navigation-simple__item--next" href="<?php echo esc_url(get_permalink($next_post)); ?>">
                            <span class="post-navigation-simple__eyebrow"><?php echo esc_html(kepoli_ui_text('Urmator', 'Next')); ?></span>
                            <div class="post-navigation-simple__body">
                                <strong><?php echo esc_html(get_the_title($next_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($next_post), 14, '...')); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
        <aside class="sidebar" aria-label="<?php echo esc_attr(kepoli_ui_text('Context articol', 'Article context')); ?>">
            <?php get_template_part('template-parts-sidebar'); ?>
        </aside>
    </article>
<?php endwhile; ?>
<?php
get_footer();
