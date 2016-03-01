<?php
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

/**
 * Outputs geotagging meta
 * 
 * Writes metatags for GEOTAGGING in the head based on configuration from wp-config.php.
 * 
 * @uses float CSP_MAIN_LAT Lattitude
 * @uses float CSP_MAIN_LONG Longitude
 * @uses string CSP_MAIN_PLACENAME ('Montréal, Québec, Canada')
 * @uses string CSP_MAIN_REGION ('ca-qc')
 * 
 * @return boolean
 */
function output_geotagging() {
    if (
            !defined('CSP_MAIN_LAT') || !defined('CSP_MAIN_LONG') || !defined('CSP_MAIN_PLACENAME') || !defined('CSP_MAIN_REGION')
    ) {
        return false;
    }
    $region = apply_filters('geotagging_region', CSP_MAIN_REGION);
    $placename = apply_filters('geotagging_placename', CSP_MAIN_PLACENAME);
    $lat = apply_filters('geotagging_lat', CSP_MAIN_LAT);
    $long = apply_filters('geotagging_long', CSP_MAIN_LONG);
    ?>
    <meta name="geo.region" content="<?php echo $region ?>">
    <meta name="geo.placename" content="<?php echo $placename ?>">
    <meta name="geo.position" content="<?php echo $lat . '; ' . $long; ?>">
    <meta name="ICBM" content="<?php echo $lat . ',' . $long; ?>">
    <?php
    return true;
}
add_action('wp_head', 'output_geotagging');
