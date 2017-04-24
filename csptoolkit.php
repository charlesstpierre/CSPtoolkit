<?php

/*
  Plugin Name: Support CharlesStPierre.com
  Plugin URI: http://charlesstpierre.com
  Description: Fonctionnalités de support et personnalisation
  Version: 1.1.8
  Author: Charles St-Pierre
  Author URI: http://charlesstpierre.com
  Text Domain: csp
  Domain Path: /lang

  Changelog
  v.1.1.8
  Retrait des éditeurs de code, paramètrable
  Refonte code de securite.php
  
  v.1.1.7
  Correction de compatibilité avec Relevanssi
  Ajout de Related Posts (query et widget)
  Désactivation des Emojis
  Amélioration de compatibilité Messages Système
  Mise à jour compatibilité avec Google XML Sitemap

  v.1.1.6
  Widget Articles récents, ajout de filtre de pattern
  Amélioration de la sécurité
 
  v.1.1.5
  Ré-écriture de la config
  Securité: Ajout d’un paramètre pour appliquer le blocage des IPs
  Compresse et archive les logs

  v1.1.4
  Ajout TinyMCE Class clear
  Interface des Meta Descriptions pour les archives de contenus, l’accueil et le blogue.


  v1.1.3
  Ajout du support des descriptions pour les Pages

  v1.1.2
  Augmentation de la taille de l’image pour le tag OG:IMAGE

  v1.1.1
  Amélioration de TinyMCE

  v1.1.0
  Ajout du fil RSS des Communiqués
  Traductions anglaises complétés
  Correction de Bug htaccess
  Amélioration de l’interface de Sécurité
  Vérification de l’adresse IP sur le tableau de bord

  v1.0.9
  Correction Core
  Ajout du format woff2 au HTaccess
  Correction de Sécurité

  v1.0.8   Amélioration better widget
  Correction de WelcomeEmail qui interceptait tous les From

  v1.0.7   Retour de Welcome Email, maintenant Messages système
  Ajout WPML Config.xml pour gérer les traductions
  Uniformisation linguistique (fr_CA)

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

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

define('TOOLKIT_VERSION', '1.1.4');
define('TOOLKIT_URL', plugin_dir_url(__FILE__));
define('TOOLKIT_CONFIG', WP_CONTENT_DIR . '/csp-config.php');

function csp_include_files() {
    if (!file_exists(TOOLKIT_CONFIG)) {
        file_put_contents(TOOLKIT_CONFIG, csp_write_opening_config());
    }
    require_once TOOLKIT_CONFIG;

    require_once 'includes/security.php';

    require_once 'includes/debug.php';
    require_once 'includes/meta_descriptions.php';
    require_once 'includes/theme_helper.class.php';

    require_once 'includes/tinymce.php';
    require_once 'includes/system-messages.php';
    require_once 'includes/dashboardwidget.php';
    require_once 'includes/emailshield.php';
    require_once 'includes/upload-processor.php';

    require_once 'includes/menu-items.php';
    require_once 'includes/wpml-compatibility.php';

    require_once 'includes/related-posts.php';

    if (defined('CSP_DO_SOCIALMETAS') && CSP_DO_SOCIALMETAS) {
        require_once 'includes/socialmetas.php';
    }
    if (defined('CSP_DO_GEOTAGGING') && CSP_DO_GEOTAGGING) {
        require_once 'includes/geotagging.php';
    }
    if (defined('CSP_DO_WIDGETS') && CSP_DO_WIDGETS) {
        require_once 'includes/better-widgets.php';
    }
    if (defined('CSP_DISABLE_EMOJIS') && CSP_DISABLE_EMOJIS) {
        require_once 'includes/disable-emojis.php';
    }
}

csp_include_files();

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
            'tested' => '4.6', // which version of WordPress is your plugin tested up to?
            'readme' => 'README.md', // which file to use as the readme for the version number
            'access_token' => '', // Access private repositories by authorizing under Appearance > Github Updates when this example plugin is installed
        );
        new WP_GitHub_Updater($config);
    }
}

add_action('plugins_loaded', 'cspplugin_init', 2);

/**
 * Trigger plugins activation functions
 * 
 * Calls the different functions needed on activation
 * 
 * @see function csp_update_user_database() in includes/security.php
 */
function csp_activation() {
    csp_write_options_to_config();
    require_once 'includes/security.php';
    csp_update_user_database();
    csp_setup_security_htaccess();
    csp_delete_insecure_files();
}

/**
 * Write options to configuration file
 * 
 * @since 1.0.0
 * @update 1.1.5 Better writing of the config
 * @update 1.1.8 Add OPTION DISALLOW_FILE_EDIT
 */
function csp_write_options_to_config() {

    $lines = array();
    //$lines[] = '#comment';
    //$lines[] = 'define("CONSTANT",value);';

    $lines[] = '# Security';
    
    $lines[] = 'if (!defined(\'DISALLOW_FILE_EDIT\')):';
    $lines[] = 'define("DISALLOW_FILE_EDIT",' . (defined('DISALLOW_FILE_EDIT') ? bool2string(DISALLOW_FILE_EDIT) : 'true') . ');';
    $lines[] = 'endif;';
    $lines[] = 'define("CSP_DO_SECURITY",' . (defined('CSP_DO_SECURITY') ? bool2string(CSP_DO_SECURITY) : 'true') . ');';
    $lines[] = 'define("CSP_SECURITY_MAX_404",' . (defined('CSP_SECURITY_MAX_404') ? CSP_SECURITY_MAX_404 : '50') . ');';
    $lines[] = 'define("CSP_SECURITY_MAX_BLACKLIST",' . (defined('CSP_SECURITY_MAX_BLACKLIST') ? CSP_SECURITY_MAX_BLACKLIST : '50') . ');';

    $lines[] = '# pipe separated list of parked domains';
    $lines[] = 'define("CSP_SITE_DOMAINS","' . (defined('CSP_SITE_DOMAINS') ? CSP_SITE_DOMAINS : $_SERVER['SERVER_NAME']) . '" );';

    $lines[] = 'define("CSP_DO_RSS",' . (defined('CSP_DO_RSS') ? bool2string(CSP_DO_RSS) : 'true') . ');';
    $lines[] = 'define("CSP_DO_WIDGETS",' . (defined('CSP_DO_WIDGETS') ? bool2string(CSP_DO_WIDGETS) : 'true') . ');';
    $lines[] = 'define("CSP_DO_GEOTAGGING",' . (defined('CSP_DO_GEOTAGGING') ? bool2string(CSP_DO_GEOTAGGING) : 'true') . ');';
    $lines[] = 'define("CSP_DO_SOCIALMETAS",' . (defined('CSP_DO_SOCIALMETAS') ? bool2string(CSP_DO_SOCIALMETAS) : 'true') . ');';
    $lines[] = 'define("CSP_DISABLE_EMOJIS",' . (defined('CSP_DISABLE_EMOJIS') ? bool2string(CSP_DISABLE_EMOJIS) : 'true') . ');';

    $lines[] = '# Developper’s email: Used to confirm identity of developper.';
    $lines[] = 'define("DEVELOPPER_EMAIL","' . (defined('DEVELOPPER_EMAIL') ? DEVELOPPER_EMAIL : 'parlez@charlesstpierre.com') . '");';

    $lines[] = '# Twitter account name: Define twitter account name for the site, or site owner';
    if (defined('CSP_twittername')) {
        $lines[] = 'define("CSP_twittername","' . CSP_twittername . '");';
    } else {
        $lines[] = '//define("CSP_twittername","@something");';
    }

    $lines[] = '# Google Site Verification: Code for Google Webmaster Tools';
    $lines[] = 'define("GOOGLE_SITE_VERIFICATION_CODE","' . (defined('GOOGLE_SITE_VERIFICATION_CODE') ? GOOGLE_SITE_VERIFICATION_CODE : 'WA3YXZIjfRgomaqTvXgvRBs0Q7OTwolSKU0pF2R8UH8') . '");';

    $lines[] = '# Microsoft ownership verification: Code for Bing Webmaster Tools';
    if (defined('MICROSOFT_OWNERSHIP_VERIFICATION_CODE')) {
        $lines[] = 'define("MICROSOFT_OWNERSHIP_VERIFICATION_CODE","' . MICROSOFT_OWNERSHIP_VERIFICATION_CODE . '");';
    } else {
        $lines[] = '//define("MICROSOFT_OWNERSHIP_VERIFICATION_CODE","code");';
    }

    $lines[] = '# Geocalisation: http://www.gps-coordinates.net for coordinates (2015-07)';
    $lines[] = 'define("CSP_MAIN_LAT","' . (defined('CSP_MAIN_LAT') ? CSP_MAIN_LAT : '45.5') . '");';
    $lines[] = 'define("CSP_MAIN_LONG","' . (defined('CSP_MAIN_LONG') ? CSP_MAIN_LONG : '-73.6') . '");';
    $lines[] = 'define("CSP_MAIN_PLACENAME","' . (defined('CSP_MAIN_PLACENAME') ? CSP_MAIN_PLACENAME : 'Montréal, Québec, Canada') . '");';
    $lines[] = 'define("CSP_MAIN_REGION","' . (defined('CSP_MAIN_REGION') ? CSP_MAIN_REGION : 'ca-qc') . '");';

    $lines[] = '# Pre-upload image processing: maximum size and quality';
    $lines[] = 'define("CSP_IMAGE_MAX_WIDTH",' . (defined('CSP_IMAGE_MAX_WIDTH') ? CSP_IMAGE_MAX_WIDTH : '3840') . ');';
    $lines[] = 'define("CSP_IMAGE_MAX_HEIGHT",' . (defined('CSP_IMAGE_MAX_HEIGHT') ? CSP_IMAGE_MAX_HEIGHT : '2160') . ');';
    $lines[] = 'define("CSP_IMAGE_QUALITY",' . (defined('CSP_IMAGE_QUALITY') ? CSP_IMAGE_QUALITY : '90') . ');';

    insert_with_markers(TOOLKIT_CONFIG, 'ConfigurationCSPToolkit', $lines);
}

function csp_write_opening_config() {
    $opening = "<?php\n\n// Exit if accessed directly\nif (!defined('ABSPATH')) { exit; }\n\n";
    return $opening;
}

register_activation_hook(__FILE__, 'csp_activation');



/**
 * CSP Update Config on Update
 */
function csp_update_config() {
    if ( 1===version_compare(TOOLKIT_VERSION, get_option('csp_toolkit_version')) ){
        csp_write_options_to_config();
        update_option('csp_toolkit_version',TOOLKIT_VERSION,true);
    }
}
add_action('upgrader_process_complete','csp_update_config');




function bool2string($val) {
    return var_export( (bool)$val , true);
}