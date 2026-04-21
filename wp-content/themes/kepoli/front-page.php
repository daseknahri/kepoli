<?php
/**
 * Front page.
 */
get_header();

$hero_image = kepoli_asset_uri('writer-photo', 'svg');
$wordmark = kepoli_asset_uri('kepoli-wordmark');
?>
<section class="home-hero" style="--hero-image: url('<?php echo esc_url($hero_image); ?>');">
    <div class="home-hero__inner">
        <img class="home-hero__brand" src="<?php echo esc_url($wordmark); ?>" alt="Kepoli">
        <h1><?php esc_html_e('Retete romanesti cu gust de acasa.', 'kepoli'); ?></h1>
        <p><?php esc_html_e('Ciorbe, feluri principale, deserturi si ghiduri simple pentru bucataria de fiecare zi.', 'kepoli'); ?></p>
        <div class="button-row">
            <a class="button" href="<?php echo esc_url(home_url('/retete/')); ?>"><?php esc_html_e('Vezi retetele', 'kepoli'); ?></a>
            <a class="button button--ghost" href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php esc_html_e('Retete publicate', 'kepoli'); ?></p>
            <h2><?php esc_html_e('De gatit saptamana aceasta', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Retete romanesti asezate clar, cu ingrediente la indemana si pasi usor de urmat.', 'kepoli'); ?></p>
    </div>
    <div class="post-grid">
        <?php
        $recipes = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 6,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'recipe',
        ]);
        while ($recipes->have_posts()) {
            $recipes->the_post();
            get_template_part('template-parts-card');
        }
        wp_reset_postdata();
        ?>
    </div>
</section>

<section class="category-band">
    <div class="section">
        <div class="section__header">
            <div>
                <p class="eyebrow"><?php esc_html_e('Categorii', 'kepoli'); ?></p>
                <h2><?php esc_html_e('Alege dupa pofta', 'kepoli'); ?></h2>
            </div>
        </div>
        <div class="category-list">
            <?php
            $categories = get_categories([
                'hide_empty' => true,
                'exclude' => [1],
                'orderby' => 'name',
            ]);
            foreach ($categories as $category) :
                ?>
                <a href="<?php echo esc_url(get_category_link($category)); ?>">
                    <strong><?php echo esc_html($category->name); ?></strong>
                    <span><?php echo esc_html(sprintf(_n('%d articol', '%d articole', $category->count, 'kepoli'), $category->count)); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="section__header">
        <div>
            <p class="eyebrow"><?php esc_html_e('Articole', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Ghiduri pentru bucatarie', 'kepoli'); ?></h2>
        </div>
        <p><?php esc_html_e('Idei de organizare, ingrediente, tehnici si meniuri care sustin retetele de zi cu zi.', 'kepoli'); ?></p>
    </div>
    <div class="post-grid">
        <?php
        $articles = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 3,
            'meta_key' => '_kepoli_post_kind',
            'meta_value' => 'article',
        ]);
        while ($articles->have_posts()) {
            $articles->the_post();
            get_template_part('template-parts-card');
        }
        wp_reset_postdata();
        ?>
    </div>
</section>

<section class="section section--tight">
    <div class="author-strip">
        <div class="author-strip__photo" style="--author-image: url('<?php echo esc_url($hero_image); ?>');"></div>
        <div class="author-strip__copy">
            <p class="eyebrow"><?php esc_html_e('Autoare', 'kepoli'); ?></p>
            <h2><?php esc_html_e('Isalune Merovik', 'kepoli'); ?></h2>
            <p><?php esc_html_e('Scriu retete romanesti intr-un stil calm si practic: ce cumperi, cum pregatesti, cum ajustezi gustul si cum pastrezi mancarea fara risipa.', 'kepoli'); ?></p>
            <a class="button" href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Citeste povestea', 'kepoli'); ?></a>
        </div>
    </div>
</section>
<?php
get_footer();
