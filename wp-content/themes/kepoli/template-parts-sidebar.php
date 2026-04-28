<?php
$writer_name = kepoli_writer_name();
$site_name = kepoli_site_name();
?>
<section class="author-box">
    <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'jpg')); ?>" alt="<?php echo esc_attr($writer_name); ?>"<?php echo kepoli_asset_dimension_attributes('writer-photo'); ?> loading="lazy" decoding="async">
    <div>
        <h2><?php echo esc_html($writer_name); ?></h2>
        <p><?php echo esc_html(kepoli_writer_description()); ?></p>
        <p><a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Despre autoare', 'About the author')); ?></a></p>
    </div>
</section>

<section class="sidebar-section">
    <h3><?php echo esc_html(kepoli_ui_text('Recente', 'Recent')); ?></h3>
    <ul class="more-list more-list--posts">
        <?php
        $latest = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 4,
            'post__not_in' => is_singular('post') ? [get_the_ID()] : [],
        ]);
        while ($latest->have_posts()) :
            $latest->the_post();
            $category = kepoli_primary_category();
            $show_category = $category && !kepoli_is_editorial_category_slug($category->slug);
            ?>
            <li>
                <a class="more-list__post-link" href="<?php the_permalink(); ?>">
                    <div class="more-list__media"><?php echo kepoli_post_card_media_markup(get_the_ID(), 'sidebar'); ?></div>
                    <span class="more-list__body">
                        <span class="more-list__chips more-list__chips--sidebar content-chip-row">
                            <span class="content-chip content-chip--muted"><?php echo esc_html(kepoli_post_kind_label()); ?></span>
                            <?php if ($show_category) : ?>
                                <span class="content-chip content-chip--category"><?php echo esc_html($category->name); ?></span>
                            <?php endif; ?>
                        </span>
                        <span class="more-list__eyebrow"><?php echo esc_html(implode(' / ', kepoli_post_card_meta_items())); ?></span>
                        <strong><?php the_title(); ?></strong>
                    </span>
                </a>
            </li>
        <?php endwhile; wp_reset_postdata(); ?>
    </ul>
</section>

<section class="sidebar-section">
    <h3><?php echo esc_html(kepoli_ui_text('Categorii', 'Categories')); ?></h3>
    <ul class="more-list more-list--stacked">
        <?php foreach (array_slice(get_categories(['hide_empty' => true, 'exclude' => [1], 'orderby' => 'count', 'order' => 'DESC']), 0, 6) as $category) : ?>
            <li>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <span><?php echo esc_html($category->name); ?></span>
                    <strong><?php echo esc_html((string) $category->count); ?></strong>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>

<section class="sidebar-section">
    <h3><?php echo esc_html($site_name); ?></h3>
    <ul class="more-list more-list--stacked">
        <li><a href="<?php echo esc_url(kepoli_about_page_url()); ?>"><?php echo esc_html(sprintf(kepoli_ui_text('Despre %s', 'About %s'), $site_name)); ?></a></li>
        <li><a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Despre autor', 'About the author')); ?></a></li>
        <li><a href="<?php echo esc_url(kepoli_contact_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Contact', 'Contact')); ?></a></li>
        <li><a href="<?php echo esc_url(kepoli_privacy_policy_url()); ?>"><?php echo esc_html(kepoli_ui_text('Confidentialitate', 'Privacy')); ?></a></li>
        <li><a href="<?php echo esc_url(kepoli_advertising_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Publicitate si consimtamant', 'Advertising and consent')); ?></a></li>
    </ul>
</section>

<?php echo kepoli_ad_slot('sidebar', 'ad-slot--sidebar'); ?>
