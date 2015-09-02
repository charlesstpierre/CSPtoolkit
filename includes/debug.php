<?php
/**
 * Is Developper?
 * 
 * Verify if current user is the developper based on his email address.
 * 
 * @since 1.0.0
 * @global object $current_user
 * @return boolean
 */
function is_dev() {
    global $current_user;
    
    if (CSP_IS_DEBUG || $current_user->user_email === DEVELOPPER_EMAIL) {
        return true;
    } else {
        return false;
    }
}

/**
 * Debug function
 * 
 * Output to screen the value of the $var variable. If $dumps, uses var_dump instead of print_r
 * 
 * @since 1.0.0
 * @param mixed $var Any variable or scalar
 * @param boolean $dump Use var_dump instead of print_r
 * @see print_r(), var_dump()
 *
 */
function debug($var, $dump = false) {
    if (is_dev()) {
        if (true === WP_DEBUG) {
            echo '<pre class="alert">';
            if ($dump) {
                var_dump($var);
            } else {
                print_r($var);
            }
            echo '</pre>';
        }
    }
}

/**
 * Debug to debug.log
 * 
 * Writes to wp-content/debug.log the value of the $var variable.
 * 
 * @param mixed $var Any variable or scalar
 */
function debug_log($var) {
    if (is_dev()) {
        if (true === WP_DEBUG) {
            if (is_array($var) || is_object($var)) {
                error_log(var_export($var, true));
            } else {
                error_log($var);
            }
        }
    }
}

/**
 * Output webmaster tools site verifications
 * 
 * Writes the meta tags for both Google Site Verification and Microsoft Ownership Verification
 *  * 
 * @uses filter wp_head
 * @since 1.0.0
 */
function csp_output_webmaster_tools_site_verification(){
    if (defined('GOOGLE_SITE_VERIFICATION_CODE')){
        echo '<meta name="google-site-verification" content="'. GOOGLE_SITE_VERIFICATION_CODE .'" />';
    }
    if (defined('MICROSOFT_OWNERSHIP_VERIFICATION_CODE')){
        echo '<meta name="msvalidate.01" content="'. MICROSOFT_OWNERSHIP_VERIFICATION_CODE .'" />';        
    }
}
add_action('wp_head','csp_output_webmaster_tools_site_verification');