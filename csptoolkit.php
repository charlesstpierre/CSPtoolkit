<?php

/*
  Plugin Name: Support CharlesStPierre.com
  Plugin URI: http://charlesstpierre.com
  Description: Fonctionnalités de support et personnalisation
  Version: 1.0.6
  Author: Charles St-Pierre
  Author URI: http://charlesstpierre.com

  Changelog
  v1.0.6   Correction de coquille

  v1.0.5   Ajout de l’élément de menu Archive de type de post
           Corrections de Notice PHP
           Amélioration de la gestion des configurations

  v1.0.4   Ajout de l’élément de menu Formulaire de recherche

  v1.0.3   Retrait de Welcome Email, code incompatible avec nouvelles versions de WP
 
  v1.0.2   Interface Sécurité, meilleur gestion des attaques

  v1.0.1   Corrections fonctionnalités de sécurité

  v1.0.0   Base. Ajout de gestion de courriel de bienvenue

 */

define('TOOLKIT_URL', plugin_dir_url(__FILE__));
define('TOOLKIT_CONFIG', WP_CONTENT_DIR.'/csp-config.php');


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
    
    require_once TOOLKIT_CONFIG;

    include 'includes/debug.php';
    include 'includes/security.php';
    include 'includes/theme_helper.class.php';

    include 'includes/tinymce.php';
    include 'includes/welcomeemail.php';
    include 'includes/dashboardwidget.php';
    include 'includes/emailshield.php';
    include 'includes/upload-processor.php';

    include 'includes/menu-items.php';
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

    //activating auto update from github
    if (is_admin()) {
        include 'includes/GitHub-Plugin-Updater/updater.php';
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
        new WP_GitHub_Updater($config);
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
    csp_write_options_to_config();
    include 'includes/security.php';
    csp_update_user_database();
    csp_setup_security_htaccess();
    csp_delete_insecure_files();
}

function csp_write_options_to_config(){
    
    if (file_exists(TOOLKIT_CONFIG)) {
        $file = implode(' ', file(TOOLKIT_CONFIG));
    }else{
        $file = '';
        file_put_contents(TOOLKIT_CONFIG, "<?php\n\n");
    }
    if (false === strpos($file, '# BEGIN ConfigurationCSPToolkit')) {

        $lines = array();
        //$lines[] = '#comment';
        //$lines[] = 'define("CONSTANT",value);';
        
        
        $lines[] = '# Security';
        $lines[] = 'define("CSP_SECURITY_MAX_404",50);';
        $lines[] = 'define("CSP_SECURITY_MAX_BLACKLIST",50);';
        $lines[] = '# pipe separated list of parked domains';
        $server_name = $_SERVER['SERVER_NAME'];
        $lines[] = 'define("CSP_SITE_DOMAINS","'. $server_name .'" );';

        $lines[] = 'define("CSP_DO_RSS",true);';
        $lines[] = 'define("CSP_DO_WIDGETS",true);';
        $lines[] = 'define("CSP_DO_GEOTAGGING",true);';
        $lines[] = 'define("CSP_DO_SOCIALMETAS",true);';
        
        $lines[] = '# Developper’s email: Used to confirm identity of developper.';
        $lines[] = 'define("DEVELOPPER_EMAIL","parlez@charlesstpierre.com");';
        $lines[] = '# Twitter account name: Define twitter account name for the site, or site owner';
        $lines[] = '//define("CSP_twittername","@something");';
        $lines[] = '# Google Site Verification: Code for Google Webmaster Tools';
        $lines[] = 'define("GOOGLE_SITE_VERIFICATION_CODE","WA3YXZIjfRgomaqTvXgvRBs0Q7OTwolSKU0pF2R8UH8");';
        $lines[] = '# Microsoft ownership verification: Code for Bing Webmaster Tools';
        $lines[] = '//define("MICROSOFT_OWNERSHIP_VERIFICATION_CODE","code");';
        
        
        $lines[] = '# Geocalisation: http://www.gps-coordinates.net for coordinates (2015-07)';
        $lines[] = 'define("CSP_MAIN_LAT","45.5");';
        $lines[] = 'define("CSP_MAIN_LONG","-73.6");';
        $lines[] = 'define("CSP_MAIN_PLACENAME","Montréal, Québec, Canada");';
        $lines[] = 'define("CSP_MAIN_REGION","ca-qc");';
        
        $lines[] = '# Pre-upload image processing: maximum size and quality';
        $lines[] = 'define("CSP_IMAGE_MAX_WIDTH",3840);';
        $lines[] = 'define("CSP_IMAGE_MAX_HEIGHT",2160);';
        $lines[] = 'define("CSP_IMAGE_QUALITY",90);';
        
        insert_with_markers(TOOLKIT_CONFIG, 'ConfigurationCSPToolkit', $lines);
    }

}

register_activation_hook(__FILE__, 'csp_activation');
