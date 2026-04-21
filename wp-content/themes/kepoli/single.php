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
                <div class="entry-summary-board">
                    <div class="entry-meta-grid" aria-label="<?php esc_attr_e('Informatii articol', 'kepoli'); ?>">
                        <div class="entry-meta-grid__item">
                            <span class="entry-meta-grid__icon"><?php echo kepoli_icon('calendar'); ?></span>
                            <span><?php esc_html_e('Publicat', 'kepoli'); ?></span>
                            <strong><?php echo esc_html(get_the_date()); ?></strong>
                        </div>
                        <div class="entry-meta-grid__item">
                            <span class="entry-meta-grid__icon"><?php echo kepoli_icon('clock'); ?></span>
                            <span><?php esc_html_e('Lectura', 'kepoli'); ?></span>
                            <strong><?php echo esc_html(kepoli_read_time()); ?></strong>
                        </div>
                        <div class="entry-meta-grid__item">
                            <span class="entry-meta-grid__icon"><?php echo kepoli_icon('user'); ?></span>
                            <span><?php esc_html_e('Autor', 'kepoli'); ?></span>
                            <strong><?php echo esc_html(get_the_author()); ?></strong>
                        </div>
                        <?php if ($updated_label !== '') : ?>
                            <div class="entry-meta-grid__item">
                                <span class="entry-meta-grid__icon"><?php echo kepoli_icon('refresh'); ?></span>
                                <span><?php esc_html_e('Actualizat', 'kepoli'); ?></span>
                                <strong><?php echo esc_html($updated_label); ?></strong>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="share-tools" aria-label="<?php esc_attr_e('Actiuni articol', 'kepoli'); ?>">
                        <?php foreach ($share_links as $share) : ?>
                            <?php $icon = $share_icons[$share['type']] ?? 'link'; ?>
                            <?php if ($share['type'] === 'copy') : ?>
                                <button class="share-tools__button" type="button" data-copy-url="<?php echo esc_attr($share['url']); ?>" data-copy-default="<?php echo esc_attr($share['label']); ?>" data-copy-success="<?php esc_attr_e('Link copiat', 'kepoli'); ?>">
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span><?php echo esc_html($share['label']); ?></span>
                                </button>
                            <?php elseif ($share['type'] === 'print') : ?>
                                <button class="share-tools__button" type="button" data-print-page>
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span><?php echo esc_html($share['label']); ?></span>
                                </button>
                            <?php else : ?>
                                <a class="share-tools__button" href="<?php echo esc_url($share['url']); ?>" target="_blank" rel="noopener nofollow">
                                    <span class="share-tools__icon"><?php echo kepoli_icon($icon); ?></span>
                                    <span><?php echo esc_html($share['label']); ?></span>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php if ($is_recipe) : ?>
                    <nav class="entry-jumpnav" aria-label="<?php esc_attr_e('Navigatie reteta', 'kepoli'); ?>">
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
                                        <span><?php echo get_post_meta($related->ID, '_kepoli_post_kind', true) === 'article' ? esc_html__('Citeste articolul', 'kepoli') : esc_html__('Citeste reteta', 'kepoli'); ?></span>
                                        <?php echo kepoli_icon('arrow-right'); ?>
                                    </a>
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
                <nav class="post-navigation-pro" aria-label="<?php esc_attr_e('Navigatie intre articole', 'kepoli'); ?>">
                    <?php if ($previous_post) : ?>
                        <a class="post-navigation-pro__item <?php echo esc_attr(kepoli_post_tone_class($previous_post->ID)); ?>" href="<?php echo esc_url(get_permalink($previous_post)); ?>">
                            <?php echo kepoli_post_media_markup($previous_post->ID, 'related'); ?>
                            <div class="post-navigation-pro__body">
                                <span class="post-navigation-pro__eyebrow"><?php esc_html_e('Articolul anterior', 'kepoli'); ?></span>
                                <strong><?php echo esc_html(get_the_title($previous_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($previous_post), 18, '...')); ?></span>
                            </div>
                        </a>
                    <?php endif; ?>
                    <?php if ($next_post) : ?>
                        <a class="post-navigation-pro__item <?php echo esc_attr(kepoli_post_tone_class($next_post->ID)); ?>" href="<?php echo esc_url(get_permalink($next_post)); ?>">
                            <?php echo kepoli_post_media_markup($next_post->ID, 'related'); ?>
                            <div class="post-navigation-pro__body">
                                <span class="post-navigation-pro__eyebrow"><?php esc_html_e('Articolul urmator', 'kepoli'); ?></span>
                                <strong><?php echo esc_html(get_the_title($next_post)); ?></strong>
                                <span><?php echo esc_html(wp_trim_words(get_the_excerpt($next_post), 18, '...')); ?></span>
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
