<?php
/**
 * Post card partial.
 */

$category = kepoli_primary_category();
$tone_class = kepoli_post_tone_class();
$kind_label = kepoli_post_kind_label();
$show_category = $category && !kepoli_is_editorial_category_slug($category->slug);
?>
<article <?php post_class('post-card ' . $tone_class); ?>>
    <a class="post-card__visual" href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
        <?php echo kepoli_post_card_media_markup(get_the_ID(), 'card'); ?>
    </a>
    <div class="post-card__body">
        <div class="post-card__eyebrow content-chip-row">
            <span class="content-chip content-chip--muted"><?php echo esc_html($kind_label); ?></span>
            <?php if ($show_category) : ?>
                <a class="content-chip content-chip--category" href="<?php echo esc_url(get_category_link($category)); ?>"><?php echo esc_html($category->name); ?></a>
            <?php endif; ?>
        </div>
        <?php echo kepoli_render_post_card_meta(); ?>
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22, '...')); ?></p>
    </div>
</article>
