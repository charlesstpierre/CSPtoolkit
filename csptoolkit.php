<?php

/*
  Plugin Name: Support CharlesStPierre.com
  Plugin URI: http://charlesstpierre.com
  Description: Fonctionnalités de support et personnalisation
  Version: 1.0
  Author: Charles St-Pierre
  Author URI: http://charlesstpierre.com

  Changelog

  v1.0     Base. Ajout de gestion de courriel de bienvenue

 */

include 'includes/GitHub-Plugin-Updater/updater.php';

include 'includes/debug.php';
include 'includes/security.php';
include 'includes/theme_helper.class.php';

include 'includes/tinymce.php';
include 'includes/welcomeemail.php';
include 'includes/dashboardwidget.php';
include 'includes/emailshield.php';
include 'includes/upload-processor.php';

include 'includes/wpml-compatibility.php';

if (CSP_DO_SOCIALMETAS) {
    include 'includes/socialmetas.php';
}
if (CSP_DO_GEOTAGGING) {
    include 'includes/geotagging.php';
}
if (CSP_DO_WIDGETS) {
    include 'includes/better-widgets.php';
}

/**
 * CSP Plugin Init
 * 
 * On plugins_loaded, initiate the plugin
 * 
 * - loads text domain
 * 
 * @see plugins_loaded
 */
function cspplugin_init() {

    // loading text domain
    load_plugin_textdomain('csp', false, dirname(plugin_basename(__FILE__)) . '/lang/');

    //activating auto update from github
    if (is_admin()) {
        $config = array(
            'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
            'proper_folder_name' => 'csptoolkit', // this is the name of the folder your plugin lives in
            'api_url' => 'https://api.github.com/repos/charlesstpierre/csptoolkit', // the github API url of your github repo
            'raw_url' => 'https://raw.github.com/charlesstpierre/csptoolkit/master', // the github raw url of your github repo
            'github_url' => 'https://github.com/charlesstpierre/csptoolkit', // the github url of your github repo
            'zip_url' => 'https://github.com/charlesstpierre/csptoolkit/zipball/master', // the zip url of the github repo
            'sslverify' => true, // wether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
            'requires' => '4.0', // which version of WordPress does your plugin require?
            'tested' => '4.3', // which version of WordPress is your plugin tested up to?
            'readme' => 'README.md', // which file to use as the readme for the version number
            'access_token' => '', // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
        );
        new WPGitHubUpdater($config);
    }
}

add_action('plugins_loaded', 'cspplugin_init');

/**
 * Trigger plugins activation functions
 * 
 * Calls the different functions needed on activation
 * 
 * @see function csp_update_user_database() in includes/security.php
 */
function csp_activation() {
    csp_update_user_database();
    csp_setup_security_htaccess();
    csp_delete_insecure_files();
}

register_activation_hook(__FILE__, 'csp_activation');
