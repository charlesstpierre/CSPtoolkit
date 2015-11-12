<?php

/*
 *  Put search form in menu
 */
add_filter('walker_nav_menu_start_el','replace_menu_item_with_search_form',10,4);
function replace_menu_item_with_search_form($item_output, $item, $depth, $args){
    
    if ($item->url=='#search_form#'){
        
        $label = apply_filters('csp_menu_search_label_text', __('Rechercher','csp'));
        $placeholder = apply_filters('csp_menu_search_placeholder_text', __('Recherche','csp').'…');
        $button = apply_filters('csp_menu_search_button_text', __('Lancer la recherche','csp'));
        
        $item_output = $args->before;
        
        $item_output .= '<form class="menu-search-form" role="search" method="get" id="menu_searchform" action="'.home_url( '/' ).'" >'
                .'<label class="screen-reader-text" for="s_menu">'.$label.':</label>'
                .'<input class="menu-search-input" type="text" value="'.get_search_query().'" name="s" id="s_menu" placeholder="'.$placeholder.'" />'
                .'<button class="menu-search-submit" type="submit" id="submit_search_menu" value="'.esc_attr__('Rechercher','kaki').'" >'.$button.'</button>'
                .'</form>';
        
        $item_output .= $args->after;
        
    }
    return $item_output;
}
