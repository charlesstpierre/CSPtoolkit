<?php
/**
 * Configuration for CSP Plugin
 */

/**
 * Securing steps
 * 
 * 1 Get new salt, see above
 * 2 Move wp-config.php one level above
 * 3 Delete readme.html, license.txt, wp-config-sample.php
 * 4 Edit robot.txt with the following

User-agent: *
 
Disallow: /feed/
Disallow: /trackback/
Disallow: /wp-admin/
Disallow: /wp-content/
Disallow: /wp-includes/
Disallow: /xmlrpc.php
Disallow: /wp-

 *
 * 
 */
define( 'CSP_SECURITY_MAX_404' , 50 );
define( 'CSP_SECURITY_MAX_BLACKLIST',50);
define( 'CSP_SITE_DOMAINS','csp.redirectme.net|cspdev.redirectme.net'); //pipe seperated list of parked domains

/**
 * Disable file edit
 * 
 * Disables file edit for Plugins and Themes
 */
define('DISALLOW_FILE_EDIT', true);

/**
 * Add the following to a .htaccess inside /wp-admin/
 * php_value upload_max_filesize 8M
 * php_value post_max_size 8M
 */
define('WP_MAX_MEMORY_LIMIT', '384M' );

/**
 * Do RSS
 * 
 */
define('CSP_DO_RSS',true);

/**
 * Do widgets
 * 
 * Include or not the widget functionnalities.
 */
define('CSP_DO_WIDGETS',true);

/**
 * Do geotagging
 * 
 * Include or not the geotagging functionnalities.
 */
define('CSP_DO_GEOTAGGING',true);

/**
 * Do Social metas
 * 
 * Include or not the social media metas.
 */
define('CSP_DO_SOCIALMETAS',true);



/**
 * Developper’s email
 * 
 * Used to confirm identity of developper.
 */
define('DEVELOPPER_EMAIL','parlez@charlesstpierre.com');

/**
 * Twitter account name
 * 
 * Define twitter account name for the site, or site owner
 */
//define('CSP_twittername','@something');


/**
 * Google Site Verification
 * 
 * Code for Google Webmaster Tools
 */
define('GOOGLE_SITE_VERIFICATION_CODE','WA3YXZIjfRgomaqTvXgvRBs0Q7OTwolSKU0pF2R8UH8');
/**
 * Microsoft ownership verification
 * 
 * Code for Bing Webmaster Tools
 */
//define('MICROSOFT_OWNERSHIP_VERIFICATION_CODE','code');

/**
 * Geotagging constants
 * 
 * @see http://www.gps-coordinates.net for coordinates (2015-07)
 */
define('CSP_MAIN_LAT','45.5');
define('CSP_MAIN_LONG','-73.6');
define('CSP_MAIN_PLACENAME','Montréal, Québec, Canada');
define('CSP_MAIN_REGION','ca-qc');

/**
 * Preupload image processing 
 * 
 * Maximum size
 */
define('CSP_IMAGE_MAX_WIDTH',3840);
define('CSP_IMAGE_MAX_HEIGHT',2160);
define('CSP_IMAGE_QUALITY',90);

/**
 * END of Configuration for CSP Plugin
 */
