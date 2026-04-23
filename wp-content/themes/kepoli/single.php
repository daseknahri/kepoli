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
    $recipe_snapshot = $is_recipe ? kepoli_recipe_snapshot_items() : [];
    $share_icons = ['facebook' => 'facebook', 'whatsapp' => 'whatsapp', 'email' => 'email', 'copy' => 'link', 'print' => 'print'];
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
                <div class="entry-summary">
                    <div class="entry-meta-row" aria-label="<?php esc_attr_e('Informatii articol', 'kepoli'); ?>">
                        <span class="entry-meta-row__item"><?php echo kepoli_icon('calendar'); ?><strong><?php echo esc_html(get_the_date()); ?></strong></span>
                        <span class="entry-meta-row__item"><?php echo kepoli_icon('clock'); ?><strong><?php echo esc_html(kepoli_read_time()); ?></strong></span>
                        <span class="entry-meta-row__item entry-meta-row__item--author"><?php echo kepoli_icon('user'); ?><strong><a href="<?php echo esc_url(kepoli_author_page_url()); ?>" rel="author"><?php echo esc_html(get_the_author()); ?></a></strong></span>
                        <?php if ($updated_label !== '') : ?>
                            <span class="entry-meta-row__item entry-meta-row__item--updated"><?php echo kepoli_icon('refresh'); ?><strong><?php echo esc_html($updated_label); ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <div class="share-tools share-tools--minimal" aria-label="<?php esc_attr_e('Actiuni articol', 'kepoli'); ?>">
                        <?php foreach ($share_links as $share) : ?>
                            <?php $icon = $share_icons[$share['type']] ?? 'link'; ?>
                            <?php if ($share['type'] === 'copy') : ?>
                                <button class="share-tools__button share-tools__button--icon" type="button" title="<?php echo esc_attr($share['label']); ?>" aria-label="<?php echo esc_attr($share['label']); ?>" data-copy-url="<?php echo esc_attr($share['url']); ?>" data-copy-default="<?php echo esc_attr($share['label']); ?>" data-copy-success="<?php esc_attr_e('Link copiat', 'kepoli'); ?>">
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
                    <div class="entry-recipe-snapshot" aria-label="<?php esc_attr_e('Detalii rapide reteta', 'kepoli'); ?>">
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
                <?php endif; ?>
                <?php kepoli_render_reader_trust_links(); ?>
                <?php if ($is_recipe) : ?>
                    <nav class="entry-jumpnav" aria-label="<?php esc_attr_e('Navigatie reteta', 'kepoli'); ?>">
                        <a href="#ce-merita-sa-stii"><?php echo kepoli_icon('tips'); ?><span><?php esc_html_e('Repere', 'kepoli'); ?></span></a>
                        <a href="#ingrediente"><?php echo kepoli_icon('ingredients'); ?><span><?php esc_html_e('Ingrediente', 'kepoli'); ?></span></a>
                        <a href="#mod-de-preparare"><?php echo kepoli_icon('steps'); ?><span><?php esc_html_e('Preparare', 'kepoli'); ?></span></a>
                        <a href="#inainte-sa-incepi"><?php echo kepoli_icon('prep'); ?><span><?php esc_html_e('Inainte', 'kepoli'); ?></span></a>
                        <a href="#sfaturi-pentru-reusita"><?php echo kepoli_icon('tips'); ?><span><?php esc_html_e('Sfaturi', 'kepoli'); ?></span></a>
                        <a href="#cum-pastrezi"><?php echo kepoli_icon('storage'); ?><span><?php esc_html_e('Pastrare', 'kepoli'); ?></span></a>
                        <a href="#intrebari-frecvente"><?php echo kepoli_icon('question'); ?><span><?php esc_html_e('FAQ', 'kepoli'); ?></span></a>
                        <a href="#legaturi-utile"><?php echo kepoli_icon('arrow-right'); ?><span><?php esc_html_e('Mai departe', 'kepoli'); ?></span></a>
                    </nav>
                <?php elseif (count($article_headings) > 1) : ?>
                    <nav class="entry-outline" aria-label="<?php esc_attr_e('In acest articol', 'kepoli'); ?>">
                        <div class="entry-outline__header">
                            <p class="eyebrow"><?php esc_html_e('In articol', 'kepoli'); ?></p>
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
            <?php
            $featured_image = kepoli_post_featured_image_markup(get_the_ID(), 'large', ['class' => 'entry-featured-media__image']);
            if ($featured_image !== '') :
                ?>
                <figure class="entry-featured-media">
                    <?php echo $featured_image; ?>
                    <?php
                    $featured_caption = kepoli_post_featured_image_caption(get_the_ID());
                    if ($featured_caption) :
                        ?>
                        <figcaption><?php echo esc_html($featured_caption); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endif; ?>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
            <?php
            wp_link_pages([
                'before' => '<nav class="post-page-links" aria-label="' . esc_attr__('Navigatie pagini articol', 'kepoli') . '"><span class="post-page-links__label">' . esc_html__('Partile articolului', 'kepoli') . '</span><div class="post-page-links__items">',
                'after' => '</div></nav>',
                'link_before' => '<span>',
                'link_after' => '</span>',
            ]);
            ?>
            <?php echo kepoli_ad_slot('below_content'); ?>
            <?php
            $related_posts = kepoli_related_posts_by_kind(get_the_ID(), $is_recipe ? 'article' : 'recipe');
            if ($related_posts) :
                ?>
                <section class="related-posts related-posts--cards">
                    <div class="related-posts__heading">
                        <p class="eyebrow"><?php echo $is_recipe ? esc_html__('Articole recomandate', 'kepoli') : esc_html__('Retete recomandate', 'kepoli'); ?></p>
                        <h2><?php echo $is_recipe ? esc_html__('Mai departe', 'kepoli') : esc_html__('Continua lectura', 'kepoli'); ?></h2>
                        <p><?php echo $is_recipe ? esc_html__('Ghiduri utile care completeaza reteta de mai sus.', 'kepoli') : esc_html__('Retete potrivite pentru a transforma articolul in ceva concret de pus pe masa.', 'kepoli'); ?></p>
                    </div>
                    <div class="related-grid">
                        <?php foreach ($related_posts as $related) : ?>
                            <?php
                            $related_category = kepoli_primary_category($related->ID);
                            $show_related_category = $related_category && $related_category->slug !== 'articole';
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
                                    <div class="post-card__meta">
                                        <span><?php echo esc_html(get_the_date('j M Y', $related)); ?></span>
                                        <span><?php echo esc_html(kepoli_read_time($related->ID)); ?></span>
                                    </div>
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
                <nav class="post-navigation-simple" aria-label="<?php esc_attr_e('Navigatie intre articole', 'kepoli'); ?>">
                    <?php if ($previous_post) : ?>
                        <a class="post-navigation-simple__item post-navigation-simple__item--prev" href="<?php echo esc_url(get_permalink($previous_post)); ?>">
                            <span class="post-navigation-simple__eyebrow"><?php esc_html_e('Anterior', 'kepoli'); ?></span>
                            <div class="post-navigation-simple__body">
                                <strong><?php echo esc_html(get_the_title($previous_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($previous_post), 14, '...')); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                    <?php if ($next_post) : ?>
                        <a class="post-navigation-simple__item post-navigation-simple__item--next" href="<?php echo esc_url(get_permalink($next_post)); ?>">
                            <span class="post-navigation-simple__eyebrow"><?php esc_html_e('Urmator', 'kepoli'); ?></span>
                            <div class="post-navigation-simple__body">
                                <strong><?php echo esc_html(get_the_title($next_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($next_post), 14, '...')); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
        <aside class="sidebar" aria-label="<?php esc_attr_e('Context articol', 'kepoli'); ?>">
            <?php get_template_part('template-parts-sidebar'); ?>
        </aside>
    </article>
<?php endwhile; ?>
<?php
get_footer();
