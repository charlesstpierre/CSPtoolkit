<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Customize Tiny MCE
 * 
 * @param array $in Tinymce configuration
 * @return string
 */
function csp_customize_tiny_mce($in) {
    // add &nbsp
    $in['entities'] = '8239,nnbsp,8201,thinsp,160,nbsp,38,amp,60,lt,62,gt';
    $in['entity_encoding'] = 'numeric';

    $in['paste_as_text'] = true;
    $in['wordpress_adv_hidden'] = false;

    $in['toolbar1'] = 'bold,italic,superscript,subscript,removeformat,bullist,numlist,outdent,indent,blockquote,link,unlink,wp_more,wp_page,wp_help,dfw';
    $in['toolbar2'] = 'styleselect,table,narrownonbreaking,nonbreaking,charmap,searchreplace,undo,redo,visualchars,pastetext';

    $style_formats = array(
        array(
            'title' => __('Paragraph'),
            'block' => 'p',
        ),
        array(
            'title' => __('Heading 2'),
            'block' => 'h2'
        ),
        array(
            'title' => __('Heading 3'),
            'block' => 'h3'
        ),
        array(
            'title' => __('Heading 4'),
            'block' => 'h4'
        ),
        array(
            'title' => __('Heading 5'),
            'block' => 'h5'
        ),
        array(
            'title' => __('Heading 6'),
            'block' => 'h6'
        ),
        array(
            'title' => __('Éléments blocs', 'csp'),
            'items' => array(
                array(
                    'title' => __('Blockquote'),
                    'block' => 'blockquote'
                ),
                array(
                    'title' => __('Source code'),
                    'block' => 'code'
                ),
                array(
                    'title' => _x('Preformatted', 'HTML tag'),
                    'block' => 'pre'
                ),
                array(
                    'title' => __('Liste de définitions', 'csp'),
                    'items' => array(
                        array(
                            'title' => __('Liste de définitions', 'csp'),
                            'block' => 'dl'
                        ),
                        array(
                            'title' => __('Terme', 'csp'),
                            'block' => 'dt'
                        ),
                        array(
                            'title' => __('Définition', 'csp'),
                            'block' => 'dd'
                        )
                    )
                )
            )
        ),
        array(
            'title' => __('Style de caractères', 'csp'),
            'items' => array(
                array(
                    'title' => __('Couleur de contraste', 'csp'),
                    'inline' => 'strong',
                    'classes' => 'contrast-color'
                ),
                array(
                    'title' => __('Surligné', 'csp'),
                    'inline' => 'em',
                    'classes' => 'highlighted'
                ),
                array(
                    'title' => __('Petit texte', 'csp'),
                    'inline' => 'small'
                ),
                array(
                    'title' => __('Citation à l’intérieur d’un texte', 'csp'),
                    'inline' => 'q'
                )
            )
        ),
        array(
            'title'=>__('Ramener à la ligne (clear)','csp'),
            'classes'=>'clear',
            'selector'=>'p,h2,h3,h4,h5,h6,blockquote'
        )
    );
    $style_formats = apply_filters('csp_visualeditor_style_formats',$style_formats);
    
    $in['style_formats'] = json_encode($style_formats);


    return $in;
}

add_filter('tiny_mce_before_init', 'csp_customize_tiny_mce', 50);

function csp_add_tinymce_plugins($plugin_array) {
    global $tinymce_version;

    $plugin_array['narrownonbreaking'] = TOOLKIT_URL . 'js/tinymce/narrownonbreaking/plugin.js';
    $plugin_array['nonbreaking'] = TOOLKIT_URL . 'js/tinymce/nonbreaking/plugin.min.js';
    $plugin_array['searchreplace'] = TOOLKIT_URL . 'js/tinymce/searchreplace/plugin.min.js';


    if (version_compare($tinymce_version, '4100', '>=')) {
        $plugin_array['table'] = TOOLKIT_URL . 'js/tinymce/tinymce41-table/plugin.min.js';
    } elseif (version_compare($tinymce_version, '400', '>=')) {
        $plugin_array['table'] = TOOLKIT_URL . 'js/tinymce/tinymce4-table/plugin.min.js';
    }

    $plugin_array['visualchars'] = TOOLKIT_URL . 'js/tinymce/visualchars/plugin.min.js';

    return $plugin_array;
}

add_filter('mce_external_plugins', 'csp_add_tinymce_plugins');

/**
 * Enqueue admin security CSS and JS
 */
function csp_tinymce_enqueue_head($hook) {
    if ('post.php' === $hook || 'post-new.php' === $hook) {
        add_editor_style(TOOLKIT_URL . 'css/visualeditor.css');
        wp_enqueue_style('csp-visualeditor', TOOLKIT_URL . 'css/visualeditor.css');
    }
}

add_action('admin_enqueue_scripts', 'csp_tinymce_enqueue_head');

