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

<?php echo kepoli_ad_slot('sidebar', 'ad-slot--sidebar'); ?>
