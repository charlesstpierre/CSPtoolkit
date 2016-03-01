<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * New menu items
 * 
 * @global type $wp_post_types
 * @uses csp_menu_item_posttype_archive_metabox
 * @uses csp_menu_item_utilities_metabox
 */
function csp_add_menu_items_metabox() {
    global $wp_post_types;

    $posttypes = get_post_types(array('public' => true, '_builtin' => false));
    if (!empty($posttypes)) {
        add_meta_box('csp_menu_item_posttype_archive', _x('Archive de contenus', 'Post type archive menu item', 'csp'), 'csp_menu_item_posttype_archive_metabox', 'nav-menus', 'side', 'low');
    }
    add_meta_box('csp_menu_item_utilities', __('Utilitaires', 'csp'), 'csp_menu_item_utilities_metabox', 'nav-menus', 'side', 'low');
}

add_action('manage_nav-menus_columns', 'csp_add_menu_items_metabox');

/**
 *  Post type archive menu item
 */
function csp_menu_item_posttype_archive_metabox() {
    $posttypes = get_post_types(array('public' => true, '_builtin' => false), 'objects');
    ?>
    <div id="posttype-ptarchive" class="posttypediv">
        <div id="tabs-panel-posttype-archive" class="tabs-panel tabs-panel-active">
            <ul id ="posttype-archive-checklist" class="categorychecklist form-no-clear">
    <?php $i = -1;
    foreach ($posttypes as $pt): ?>
                    <li>
                        <label class="menu-item-title">
                            <input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo esc_attr($i) ?>][menu-item-object-id]" value="<?php echo $i ?>"> <?php echo $pt->label; ?>
                        </label>
                        <input type="hidden" class="menu-item-type" name="menu-item[<?php echo esc_attr($i) ?>][menu-item-type]" value="custom">
                        <input type="hidden" class="menu-item-title" name="menu-item[<?php echo esc_attr($i) ?>][menu-item-title]" value="<?php echo $pt->label; ?>">
                        <input type="hidden" class="menu-item-url" name="menu-item[<?php echo esc_attr($i) ?>][menu-item-url]" value="<?php echo get_post_type_archive_link($pt->name) ?>">
                        <input type="hidden" class="menu-item-classes" name="menu-item[<?php echo esc_attr($i) ?>][menu-item-classes]" value="<?php echo $pt->name ?>-archive">
                    </li>
        <?php $i--;
    endforeach; ?>
            </ul>
        </div>
        <p class="button-controls">
            <span class="list-controls">
                <a href="<?php echo admin_url('nav-menus.php?page-tab=all&selectall=1#posttype-ptarchive'); ?>" class="select-all"><?php _e('Select All') ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu') ?>" name="add-post-type-menu-item" id="submit-posttype-ptarchive">
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/**
 * Utility menu items
 */
function csp_menu_item_utilities_metabox() {
    
    $login_url = apply_filters( 'csp_login_url', wp_login_url( home_url() ) );
    $logout_url = apply_filters( 'csp_logout_url', wp_logout_url( home_url() ) );
    
    ?>
    <div id="posttype-utilities" class="posttypediv">
        <div id="tabs-panel-utilities" class="tabs-panel tabs-panel-active">
            <ul id ="utilities-checklist" class="categorychecklist form-no-clear">
                <li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="-1"> <?php _e('Formulaire de recherche', 'csp'); ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="<?php esc_attr_e('Formulaire de recherche', 'csp'); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="#search_form#">
                    <input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" value="">
                </li>
                <li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" name="menu-item[-2][menu-item-object-id]" value="-2"> <?php _e('Connexion', 'csp'); ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[-2][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[-2][menu-item-title]" value="<?php esc_attr_e('Connexion', 'csp'); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[-2][menu-item-url]" value='<?php echo $login_url; ?>'>
                    <input type="hidden" class="menu-item-classes" name="menu-item[-2][menu-item-classes]" value="login_url">
                </li>
                <li>
                    <label class="menu-item-title">
                        <input type="checkbox" class="menu-item-checkbox" name="menu-item[-3][menu-item-object-id]" value="-3"> <?php _e('Déconnexion', 'csp'); ?>
                    </label>
                    <input type="hidden" class="menu-item-type" name="menu-item[-3][menu-item-type]" value="custom">
                    <input type="hidden" class="menu-item-title" name="menu-item[-3][menu-item-title]" value="<?php esc_attr_e('Déconnexion', 'csp'); ?>">
                    <input type="hidden" class="menu-item-url" name="menu-item[-3][menu-item-url]" value='<?php echo $logout_url; ?>'>
                    <input type="hidden" class="menu-item-classes" name="menu-item[-3][menu-item-classes]" value="logout_url">
                </li>
            </ul>
        </div>
        <p class="button-controls">
            <span class="list-controls">
                <a href="<?php echo admin_url('nav-menus.php?page-tab=all&selectall=1#posttype-utilities'); ?>" class="select-all"><?php _e('Select All') ?></a>
            </span>
            <span class="add-to-menu">
                <input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e('Add to Menu') ?>" name="add-post-type-menu-item" id="submit-posttype-utilities">
                <span class="spinner"></span>
            </span>
        </p>
    </div>
    <?php
}

/*
 *  Put search form in menu
 */
add_filter('walker_nav_menu_start_el', 'replace_menu_item_with_search_form', 10, 4);

function replace_menu_item_with_search_form($item_output, $item, $depth, $args) {

    if ($item->url == '#search_form#') {

        $label = apply_filters('csp_menu_search_label_text', __('Rechercher', 'csp'));
        $placeholder = apply_filters('csp_menu_search_placeholder_text', __('Recherche', 'csp') . '…');
        $button = apply_filters('csp_menu_search_button_text', __('Lancer la recherche', 'csp'));

        $item_output = $args->before;

        $item_output .= '<form class="menu-search-form" role="search" method="get" id="menu_searchform" action="' . home_url('/') . '" >'
                . '<label class="screen-reader-text" for="s_menu">' . $label . ':</label>'
                . '<input class="menu-search-input" type="text" value="' . get_search_query() . '" name="s" id="s_menu" placeholder="' . $placeholder . '" />'
                . '<button class="menu-search-submit" type="submit" id="submit_search_menu" value="' . esc_attr__('Rechercher', 'csp') . '" >' . $button . '</button>'
                . '</form>';

        $item_output .= $args->after;
    }
    return $item_output;
}
