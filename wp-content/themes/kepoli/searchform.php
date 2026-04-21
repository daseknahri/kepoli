<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php esc_html_e('Cauta dupa:', 'kepoli'); ?></span>
        <input type="search" class="search-field" placeholder="<?php esc_attr_e('Cauta retete, ingrediente, articole...', 'kepoli'); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
    </label>
    <button type="submit" class="search-submit" aria-label="<?php esc_attr_e('Cauta', 'kepoli'); ?>">
        <?php echo kepoli_icon('search'); ?>
        <span><?php esc_html_e('Cauta', 'kepoli'); ?></span>
    </button>
</form>
