<?php
/**
 * Post card partial.
 */
?>
<article <?php post_class('post-card'); ?>>
    <a class="post-card__visual" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <img src="<?php echo esc_url(kepoli_asset_uri('kepoli-icon')); ?>" alt="">
    </a>
    <div class="post-card__body">
        <div class="post-card__meta">
            <?php
            $category = get_the_category();
            echo $category ? esc_html($category[0]->name) . ' / ' : '';
            echo esc_html(kepoli_read_time());
            ?>
        </div>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22, '...')); ?></p>
        <a class="post-card__link" href="<?php the_permalink(); ?>">
            <?php echo kepoli_post_kind() === 'article' ? esc_html__('Citeste articolul', 'kepoli') : esc_html__('Citeste reteta', 'kepoli'); ?>
        </a>
    </div>
</article>
