<?php
/**
 * About Kepoli page.
 */
get_header();
?>
<?php while (have_posts()) : the_post(); ?>
    <section class="section section--tight">
        <header class="archive-header archive-header--compact">
            <?php kepoli_breadcrumbs(); ?>
            <p class="eyebrow"><?php esc_html_e('Identitate', 'kepoli'); ?></p>
            <h1><?php the_title(); ?></h1>
            <p><?php esc_html_e('Cine scrie pe Kepoli, cum alegem subiectele si ce pot astepta cititorii de la fiecare reteta sau articol publicat aici.', 'kepoli'); ?></p>
        </header>
        <div class="content-layout content-layout--single">
            <div class="entry">
                <div class="entry-content">
                    <?php the_content(); ?>
                </div>
                <div class="page-grid">
                    <section class="page-panel">
                        <p class="eyebrow"><?php esc_html_e('Ce publicam', 'kepoli'); ?></p>
                        <h2><?php esc_html_e('Retete si articole pentru bucataria de acasa', 'kepoli'); ?></h2>
                        <p><?php esc_html_e('Kepoli combina retete romanesti, idei de sezon si ghiduri utile pentru cititorii care vor sa gateasca mai clar, mai linistit si cu mai putina risipa.', 'kepoli'); ?></p>
                    </section>
                    <section class="page-panel">
                        <p class="eyebrow"><?php esc_html_e('Cum lucram', 'kepoli'); ?></p>
                        <h2><?php esc_html_e('Claritate, verificare si ajustari practice', 'kepoli'); ?></h2>
                        <p><?php esc_html_e('Textele sunt scrise pentru uz casnic, cu pasi explicati, timpi orientativi si note despre gust, textura si pastrare. Cand o informatie trebuie corectata sau clarificata, actualizam continutul.', 'kepoli'); ?></p>
                    </section>
                    <section class="page-panel">
                        <p class="eyebrow"><?php esc_html_e('Corecturi', 'kepoli'); ?></p>
                        <h2><?php esc_html_e('Ne poti scrie direct', 'kepoli'); ?></h2>
                        <p><?php esc_html_e('Pentru observatii, corecturi sau intrebari editoriale, foloseste pagina de contact sau scrie direct autoarei. Kepoli trateaza corecturile ca parte normala dintr-o publicatie utila si responsabila.', 'kepoli'); ?></p>
                    </section>
                </div>
                <div class="page-links">
                    <a href="<?php echo esc_url(home_url('/despre-autor/')); ?>"><?php esc_html_e('Despre autoare', 'kepoli'); ?></a>
                    <a href="<?php echo esc_url(home_url('/contact/')); ?>"><?php esc_html_e('Contact', 'kepoli'); ?></a>
                    <a href="<?php echo esc_url(home_url('/politica-de-confidentialitate/')); ?>"><?php esc_html_e('Politica de confidentialitate', 'kepoli'); ?></a>
                    <a href="<?php echo esc_url(home_url('/politica-de-cookies/')); ?>"><?php esc_html_e('Politica de cookies', 'kepoli'); ?></a>
                </div>
            </div>
        </div>
    </section>
<?php endwhile; ?>
<?php
get_footer();
