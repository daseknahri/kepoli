<?php
/**
 * 404 template.
 */
get_header();
?>
<section class="not-found">
    <p class="eyebrow"><?php esc_html_e('404', 'kepoli'); ?></p>
    <h1><?php esc_html_e('Pagina nu a fost gasita', 'kepoli'); ?></h1>
    <p><?php esc_html_e('Reteta cautata nu este aici, dar poti cauta dupa ingredient, categorie sau numele preparatului.', 'kepoli'); ?></p>
    <?php get_search_form(); ?>
</section>
<?php
get_footer();
