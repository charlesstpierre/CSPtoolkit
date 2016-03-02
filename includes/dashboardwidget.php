<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function csp_add_dashboard_widgets() {

    wp_add_dashboard_widget(
            'csp_support_dashboard_widget', // Widget slug.
            __('Support CharlesStPierre.com', 'csp'), // Title.
            'csp_support_dashboard_widget_function' // Display function.
    );
}

add_action('wp_dashboard_setup', 'csp_add_dashboard_widgets');

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function csp_support_dashboard_widget_function() {
    
    // IP WHITELIST
    $current_ip = $_SERVER['REMOTE_ADDR'];
    
    if ( current_user_can('manage_options') && false === get_option('_whitelist'.csp_get_ip($current_ip)) ){
        ?>
        <div style="padding-left:2em;position:relative;"><span class="dashicons dashicons-warning" style="position:absolute;left:0;color:red;font-size:1.5em;"></span>
        <p>
            <b><?php printf( __('Votre IP actuel (%s) n’est pas sur la liste blanche.','csp'),$current_ip) ?></b>
            <?php printf( __('Si cette ordinateur n’est pas public, et que vous l’utilisez fréquemment, <%s>ajoutez votre IP à la liste blanche<%s>.','csp'),'a href="'.admin_url('options-general.php?page=csp-security').'"','/a'); ?>
        </p>
        </div>
        <?php
    }
    
    
    // CSP RSS FEED
    $rss = fetch_feed('http://charlesstpierre.com/category/communications-client/feed/');

    if (is_wp_error($rss) && is_admin()) {
        printf('<p class="error">' . __('<strong>Erreur RSS</strong>: %s', 'csp') . '</p>', $rss->get_error_message());
    } elseif (0 !== $rss->get_item_quantity()) {

        echo '<p><strong>' . __('Communiqués:', 'csp') . '</strong></p>';
        echo '<ul>';

        foreach ($rss->get_items(0, 5) as $item) {
            $publisher = '';
            $site_link = '';
            $link = '';
            $content = '';
            $date = '';
            $link = esc_url(strip_tags($item->get_link()));
            $title = esc_html($item->get_title());
            $content = $item->get_content();
            $excerpt = wp_html_excerpt($content, 250) . '… ';

            echo "<li><a class=\"rsswidget\" href=\"$link\" target=\"_blank\">$title</a>\n<div class=\"rssSummary\">$excerpt</div>\n";
        }

        echo '<ul>';
    }
    
    // GENERIC LINKS
    ?>
    <p><?php _e('Besoin d’un coup de main? Je demeure à votre disposition.', 'csp'); ?></p>
    <ul>
        <li><a href="http://charlesstpierre.com/espace-client/" target="_blank"><?php _e('Espace client', 'csp') ?></a></li>
        <li><a href="http://charlesstpierre.com/contactez-moi/" target="_blank"><?php _e('Demande de support', 'csp') ?></a></li>
        <li><a href="#" target="_blank"><?php _e('Base de connaissances', 'csp') ?></a> <i><?php _e('Bientôt', 'csp') ?></i></li>
    </ul>
    <?php
}
