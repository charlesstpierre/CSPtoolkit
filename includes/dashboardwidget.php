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
    ?>
    <p><?php _e('Besoin d’un coup de main? Je demeure à votre disposition.', 'csp'); ?></p>
    <ul>
        <li><a href="http://charlesstpierre.com/espace-client/" target="_blank"><?php _e('Espace client', 'csp') ?></a></li>
        <li><a href="http://charlesstpierre.com/contactez-moi/" target="_blank"><?php _e('Demande de support', 'csp') ?></a></li>
        <li><a href="#" target="_blank"><?php _e('Base de connaissances', 'csp') ?></a> <i><?php _e('Bientôt', 'csp') ?></i></li>
    </ul>
    <?php
}
