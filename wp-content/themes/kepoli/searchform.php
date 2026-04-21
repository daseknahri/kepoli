<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php esc_html_e('Cauta dupa:', 'kepoli'); ?></span>
        <input type="search" class="search-field" placeholder="<?php esc_attr_e('Cauta retete, ingrediente, articole...', 'kepoli'); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
    </label>
    <button type="submit" class="search-submit" aria-label="<?php esc_attr_e('Cauta', 'kepoli'); ?>">
        <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M10.7 4a6.7 6.7 0 0 1 5.28 10.82l3.6 3.6-1.42 1.42-3.6-3.6A6.7 6.7 0 1 1 10.7 4m0 2a4.7 4.7 0 1 0 0 9.4 4.7 4.7 0 0 0 0-9.4"/></svg>
        <span><?php esc_html_e('Cauta', 'kepoli'); ?></span>
    </button>
</form>
