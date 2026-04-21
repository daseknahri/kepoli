<section class="author-box">
    <img src="<?php echo esc_url(kepoli_asset_uri('writer-photo', 'svg')); ?>" alt="<?php esc_attr_e('Isalune Merovik', 'kepoli'); ?>">
    <div>
        <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
        <p><?php esc_html_e('Retete romanesti testate pentru gatit acasa, cu pasi clari si ingrediente accesibile.', 'kepoli'); ?></p>
        <p><a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a></p>
    </div>
</section>

<section class="sidebar-section">
    <h3><?php esc_html_e('Din aceeasi bucatarie', 'kepoli'); ?></h3>
    <ul class="more-list">
        <?php
        $latest = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post__not_in' => is_singular('post') ? [get_the_ID()] : [],
        ]);
        while ($latest->have_posts()) :
            $latest->the_post();
            ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
        <?php endwhile; wp_reset_postdata(); ?>
    </ul>
</section>

<section class="sidebar-section">
    <h3><?php esc_html_e('Exploreaza categoriile', 'kepoli'); ?></h3>
    <ul class="more-list more-list--stacked">
        <?php foreach (get_categories(['hide_empty' => true, 'exclude' => [1], 'orderby' => 'count', 'order' => 'DESC']) as $category) : ?>
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
    <h3><?php esc_html_e('Pagini utile', 'kepoli'); ?></h3>
    <ul class="more-list more-list--stacked">
        <li><a href="<?php echo esc_url(home_url('/despre-kepoli/')); ?>"><?php esc_html_e('Despre Kepoli', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Despre autor', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/politica-de-confidentialitate/')); ?>"><?php esc_html_e('Confidentialitate', 'kepoli'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/publicitate-si-consimtamant/')); ?>"><?php esc_html_e('Publicitate si consimtamant', 'kepoli'); ?></a></li>
    </ul>
</section>

<?php echo kepoli_ad_slot('sidebar', 'ad-slot--sidebar'); ?>
