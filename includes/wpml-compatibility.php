<?php
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

/**
 * @since 1.0.2 Remove function override since icl_* will disappear.
 * @since 1.0.0
 */
function csp_WPML_init() {
    if (defined( 'ICL_SITEPRESS_VERSION' )){
        add_filter('body_class', 'add_lang_to_body_class');
        add_filter('wp_nav_menu', 'filter_language_menu_item');
        add_action('admin_head', 'csp_wpml_remove_icl_metabox');
    }
}
add_action('init', 'csp_WPML_init', 100); 

function csp_wpml_remove_icl_metabox() {
    $screen = get_current_screen();
    remove_meta_box('icl_div_config',$screen->post_type,'normal');
}


function wpml_language_select($args = '') {
    if (defined('ICL_SITEPRESS_VERSION')) {

        $defaults = array('display_active' => false,
            'skip_missing' => false,
            'link_label' => '%1$s (%2$s)',
            'link_label_active' => '%1$s',
            'title_label' => '',
            'title_label_active' => '',
            'item' => '<li class="%1$s"><a href="%2$s" title="%3$s">%4$s</a></li>',
            'container' => '<ul class="lang_switch">%s</ul>',
            'echo' => true);
        $r = wp_parse_args($args, $defaults);
        extract($r, EXTR_SKIP);

        $output = '';
        $languages = apply_filters('wpml_active_languages',NULL,array('skip_missing' => (int) $skip_missing) );
        
        foreach ($languages as $language) {
            $classes = array('lang-' . $language['language_code']);

            if ($language['active'] == 1) {
                if (!$display_active) {
                    continue;
                } else {
                    $classes[] = 'lang-active';
                    $link = sprintf($link_label_active, $language['translated_name'], $language['native_name']);
                    $title = sprintf($title_label_active, $language['translated_name'], $language['native_name']);
                }
            } else {
                $link = sprintf($link_label, $language['translated_name'], $language['native_name']);
                $title = sprintf($title_label, $language['translated_name'], $language['native_name']);
            }
            $output .= sprintf($item, implode(' ', $classes), $language['url'], $title, $link);
        }
        if ($container !== '' && strpos($container, '%s') !== false) {
            $output = sprintf($container, $output);
        }
        if ($echo) {
            echo $output;
        } else {
            return $output;
        }
    }
}


function add_lang_to_body_class($classes) {
    $classes[] = ICL_LANGUAGE_CODE;
    return $classes;
}

function filter_language_menu_item($nav_menu) {
    if (function_exists('icl_get_languages')) {
        preg_match_all('/#lang_(\w\w)#/', $nav_menu, $langs);
        if (empty($langs))
            return $nav_menu;

        $languages = apply_filters('wpml_active_languages',NULL);
        foreach ($langs[1] as $lang_code) {
            if (isset($languages[$lang_code])) {

                global $wp_query, $sitepress;
                $new_url = apply_filters('csp_wpml_language_menu_url',$languages[$lang_code]['url'],$languages,$lang_code);

                $nav_menu = str_replace("#lang_{$lang_code}#", $new_url, $nav_menu);
            }
        }
    }
    return $nav_menu;
}
