<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label>
        <span class="screen-reader-text"><?php echo esc_html(kepoli_ui_text('Cauta dupa:', 'Search for:')); ?></span>
        <input type="search" class="search-field" placeholder="<?php echo esc_attr(kepoli_ui_text('Cauta retete, ingrediente, articole...', 'Search recipes, ingredients, articles...')); ?>" value="<?php echo esc_attr(get_search_query()); ?>" name="s">
    </label>
    <button type="submit" class="search-submit" aria-label="<?php echo esc_attr(kepoli_ui_text('Cauta', 'Search')); ?>">
        <?php echo kepoli_icon('search'); ?>
        <span><?php echo esc_html(kepoli_ui_text('Cauta', 'Search')); ?></span>
    </button>
</form>
