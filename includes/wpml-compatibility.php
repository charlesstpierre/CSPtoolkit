<?php

function create_WPML_dummy_function() {
    if (defined( 'ICL_SITEPRESS_VERSION' )){
        if (!function_exists('icl_object_id')) {
            /**
             * Empty function prevent accident if CSP Plugin not present.
             * 
             * Does nothing.
             */
            function icl_object_id($element_id, $element_type = 'post', $return_original_if_missing = false, $ulanguage_code = null) {
                return $element_id;
            }

        }
        add_filter('body_class', 'add_lang_to_body_class');
        add_filter('wp_nav_menu', 'filter_language_menu_item');
    }
}

add_action('init', 'create_WPML_dummy_function', 100);

function wpml_language_select($args = '') {
    if (function_exists('icl_get_languages')) {

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
        $languages = icl_get_languages('skip_missing=' . (int) $skip_missing);
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

        $languages = icl_get_languages('skip_missing=0');
        foreach ($langs[1] as $lang_code) {
            if (isset($languages[$lang_code])) {

                global $wp_query, $sitepress;
                $new_url = $languages[$lang_code]['url'];

                $nav_menu = str_replace("#lang_{$lang_code}#", $new_url, $nav_menu);
            }
        }
    }
    return $nav_menu;
}
