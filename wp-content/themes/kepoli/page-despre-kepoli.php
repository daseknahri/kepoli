<?php
/**
 * About publication page.
 */
get_header();
$site_name = kepoli_site_name();
?>
<?php while (have_posts()) : the_post(); ?>
    <section class="section section--tight">
        <header class="archive-header archive-header--compact">
            <?php kepoli_breadcrumbs(); ?>
            <p class="eyebrow"><?php echo esc_html(kepoli_ui_text('Identitate', 'About the publication')); ?></p>
            <h1><?php the_title(); ?></h1>
            <p><?php echo esc_html(sprintf(kepoli_ui_text('Cine scrie pe %s, cum alegem subiectele si ce pot astepta cititorii de la fiecare reteta sau articol publicat aici.', 'Who writes for %s, how topics are chosen, and what readers can expect from each recipe or article published here.'), $site_name)); ?></p>
        </header>
        <div class="content-layout content-layout--single">
            <div class="entry">
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
                <?php echo kepoli_newsletter_cta('newsletter-cta--compact newsletter-cta--about'); ?>
                <div class="page-links">
                    <a href="<?php echo esc_url(kepoli_author_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Despre autoare', 'About the author')); ?></a>
                    <a href="<?php echo esc_url(kepoli_contact_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Contact', 'Contact')); ?></a>
                    <a href="<?php echo esc_url(kepoli_advertising_page_url()); ?>"><?php echo esc_html(kepoli_ui_text('Publicitate si consimtamant', 'Advertising and consent')); ?></a>
                    <a href="<?php echo esc_url(kepoli_privacy_policy_url()); ?>"><?php echo esc_html(kepoli_ui_text('Politica de confidentialitate', 'Privacy policy')); ?></a>
                    <a href="<?php echo esc_url(kepoli_cookie_policy_url()); ?>"><?php echo esc_html(kepoli_ui_text('Politica de cookies', 'Cookie policy')); ?></a>
                </div>
            </div>
        </div>
    </section>
<?php endwhile; ?>
<?php
get_footer();
