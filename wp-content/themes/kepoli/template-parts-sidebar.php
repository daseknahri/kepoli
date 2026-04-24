<section class="author-box">
    <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'jpg')); ?>" alt="<?php esc_attr_e('Isalune Merovik', 'kepoli'); ?>"<?php echo kepoli_asset_dimension_attributes('writer-photo'); ?> loading="lazy" decoding="async">
    <div>
        <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
        <p><?php esc_html_e('Retete romanesti scrise pentru gatit acasa, cu pasi clari si ingrediente accesibile.', 'kepoli'); ?></p>
        <p><a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a></p>
    </div>
</section>

<section class="sidebar-section">
    <h3><?php esc_html_e('Recente', 'kepoli'); ?></h3>
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
            $show_category = $category && $category->slug !== 'articole';
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
    <h3><?php esc_html_e('Categorii', 'kepoli'); ?></h3>
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
    <h3><?php esc_html_e('Kepoli', 'kepoli'); ?></h3>
    <ul class="more-list more-list--stacked">
        <li><a href="<?php echo esc_url(home_url('/despre-kepoli/')); ?>"><?php esc_html_e('Despre Kepoli', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Despre autor', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/politica-de-confidentialitate/')); ?>"><?php esc_html_e('Confidentialitate', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/publicitate-si-consimtamant/')); ?>"><?php esc_html_e('Publicitate si consimtamant', 'kepoli'); ?></a></li>
    </ul>
</section>

<?php echo kepoli_ad_slot('sidebar', 'ad-slot--sidebar'); ?>
