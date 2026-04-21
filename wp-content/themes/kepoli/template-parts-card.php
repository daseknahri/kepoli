<?php
/**
 * Post card partial.
 */

$category = kepoli_primary_category();
$tone_class = kepoli_post_tone_class();
$kind_label = kepoli_post_kind_label();
?>
<article <?php post_class('post-card ' . $tone_class); ?>>
    <a class="post-card__visual" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <div class="post-card__badges">
            <span class="post-card__badge"><?php echo esc_html($kind_label); ?></span>
            <?php if ($category) : ?>
                <span class="post-card__badge post-card__badge--ghost"><?php echo esc_html($category->name); ?></span>
            <?php endif; ?>
        </div>
        <img src="<?php echo esc_url(kepoli_asset_uri('kepoli-icon')); ?>" alt="">
    </a>
    <div class="post-card__body">
        <div class="post-card__meta">
            <?php
            echo esc_html(get_the_date('j M Y'));
            echo ' / ';
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
