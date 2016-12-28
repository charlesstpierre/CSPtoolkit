<?php
/**
 * Security.php
 * 
 * Functions and tasks to make WP more secure
 */
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }


/**
 * Basic security actions and filters
 * 
 * Use basic actions and filters to change WP innerworking to make it more secure.
 * 
 * - Remove metatag generator to hide that the site is ran by WordPress
 * 
 * @since 1.0.0
 */
function csp_security() {
    if (!CSP_DO_RSS) {
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
    }
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'index_rel_link');
    remove_action('wp_head', 'parent_post_rel_link', 10, 0);
    remove_action('wp_head', 'start_post_rel_link', 10, 0);
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10, 0);
    remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
    remove_action('wp_head', 'wp_generator', 10);
    if (isset($GLOBALS['sitepress'])) {
        remove_action('wp_head', array($GLOBALS['sitepress'], 'meta_generator_tag'), 10);
    }
}
add_action('plugins_loaded', 'csp_security');

/**
 * Update User_ID on activate
 * 
 * On plugin activation, changes USER_IDs to high values
 * 
 * @since 1.0.0
 * @global object $wpdb
 * @see csp_activation() in cspplugin.php:34
 */
function csp_update_user_database() {
    global $wpdb;

    // if user id 1 doesn't exist, its probably been done before.
    $count = $wpdb->get_var("SELECT COUNT(id) FROM $wpdb->users WHERE id=1;");
    if ($count == 1) { // id 1 exists
        // choose random id for admin account (id=1)
        $admin_id = rand(1000, 2000);
        // update tables to increase 

        $last_autoincrement = $wpdb->get_var("SELECT `auto_increment` FROM INFORMATION_SCHEMA.TABLES WHERE table_name = '$wpdb->users' ");

        // choose random auto increment
        $step = rand(2000, 4000);
        $new_autoincrement = $step + $last_autoincrement;
        $zero_query = $wpdb->query("ALTER TABLE $wpdb->users AUTO_INCREMENT = $new_autoincrement;");

        // increase all user ids by $new_autoincrement;
        $first_query = $wpdb->query("UPDATE $wpdb->users AS u "
                . "LEFT JOIN $wpdb->usermeta AS m ON u.ID=m.user_id "
                . "LEFT JOIN $wpdb->posts AS p ON u.ID=p.post_author "
                . "LEFT JOIN $wpdb->comments AS c ON u.ID = c.user_id "
                . "SET u.ID = u.ID + $step, "
                . "m.user_id = m.user_id + $step, "
                . "p.post_author = p.post_author + $step, "
                . "c.user_id = c.user_id + $step "
                . "WHERE u.ID > 1");

        $second_query = $wpdb->query("UPDATE $wpdb->users AS u "
                . "LEFT JOIN $wpdb->usermeta AS m ON u.ID=m.user_id "
                . "LEFT JOIN $wpdb->posts AS p ON u.ID=p.post_author "
                . "LEFT JOIN $wpdb->comments AS c ON u.ID = c.user_id "
                . "SET u.ID = $admin_id, "
                . "m.user_id = $admin_id, "
                . "p.post_author = $admin_id, "
                . "c.user_id = $admin_id "
                . "WHERE u.ID = 1;");
    }
}

/**
 * Delete insecure files on activate
 * 
 * @since 1.0.0
 * @see function csp_activation()
 */
function csp_delete_insecure_files() {

    $files = array(
        ABSPATH . 'license.txt',
        ABSPATH . 'readme.html',
        ABSPATH . 'wp-admin/install.php',
        ABSPATH . 'wp-config-sample.php'
    );
    foreach ($files as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
}

/**
 * Write security to htaccess files
 * 
 * @see function csp_activation()
 */
function csp_setup_security_htaccess() {

    $htaccess = get_home_path() . '.htaccess';

    $lines = array();
    //disallow directory browsing
    $lines[] = '# directory browsing';
    $lines[] = 'Options -Indexes';
    // protect wp-config.php
    $lines[] = '<Files "wp-config.php">';
    $lines[] = 'order allow,deny';
    $lines[] = 'deny from all';
    $lines[] = '</Files>';
    // protect all .hta(ccess) files
    $lines[] = '<Files ~ "^.*\.([Hh][Tt][Aa])">';
    $lines[] = 'order allow,deny';
    $lines[] = 'deny from all';
    $lines[] = 'satisfy all';
    $lines[] = '</Files>';
    // prevent hot linking
    $lines[] = '# Prevent image hotlinking script. Replace last URL with any image link you want.';
    $lines[] = 'RewriteEngine on';
    $lines[] = 'RewriteCond %{HTTP_REFERER} !^$';

    // get all domain
    if (defined('CSP_SITE_DOMAINS')) {
        $domains = explode('|', CSP_SITE_DOMAINS);
    } else {
        $domains = array($_SERVER['SERVER_NAME']);
    }

    foreach ($domains as $domain) {
        $lines[] = 'RewriteCond %{HTTP_REFERER} !^http(s)?://(www\.)?' . $domain . ' [NC]';
    }
    $lines[] = 'RewriteRule \.(jpg|jpeg|png|gif)$ - [NC,F,L]';
    //$lines[]  = '';

    insert_with_markers($htaccess, 'CSP_SECURITY', $lines);

    // WP-content
    $htaccess = WP_CONTENT_DIR . '/.htaccess';

    $lines = array();
    $lines[] = 'Order deny,allow';
    $lines[] = 'Deny from all';
    // allowed file types
    $allowed_mime_types = get_allowed_mime_types();
    $allowed_file_types = array_merge(
            array_keys($allowed_mime_types), array('svg', 'woff', 'woff2', 'ttf', 'eot', 'map')// fonts
    );
    $lines[] = '<Files ~ ".(' . implode('|', $allowed_file_types) . ')$">';
    $lines[] = 'Allow from all';
    $lines[] = '</Files>';
    //$lines[] = '';
    //$lines[] = '';

    insert_with_markers($htaccess, 'CSP_SECURITY', $lines);



    // WP-includes
    $htaccess = get_home_path() . WPINC . '/.htaccess';

    $lines = array();
    $lines[] = '# Block wp-includes folder and files';
    $lines[] = '<IfModule mod_rewrite.c>';
    $lines[] = 'RewriteEngine On';
    $lines[] = 'RewriteBase /';
    $lines[] = 'RewriteRule ^wp-admin/includes/ - [F,L]';
    $lines[] = 'RewriteRule !^wp-includes/ - [S=3]';
    $lines[] = 'RewriteRule ^wp-includes/[^/]+\.php$ - [F,L]';
    $lines[] = 'RewriteRule ^wp-includes/js/tinymce/langs/.+\.php - [F,L]';
    $lines[] = 'RewriteRule ^wp-includes/theme-compat/ - [F,L]';
    $lines[] = '</IfModule>';
    //$lines[] = '';
    //$lines[] = '';

    insert_with_markers($htaccess, 'CSP_SECURITY', $lines);


    // the END!
}

/**
 * Check user nicenames
 * 
 * In admin pages, checks the users database for nicename identical to username
 * and warn the administrator.
 * 
 * @since 1.0.0
 * @global object $wpdb
 * @uses filter admin_notices
 */
function csp_check_nicenames() {
    global $wpdb;

    if (!current_user_can('edit_users'))
        return;

    $search_query = "SELECT ID FROM $wpdb->users WHERE user_nicename = user_login";
    $search_results = $wpdb->get_col($search_query);
    $nb_user = count($search_results);
    if ($nb_user) {
        if ($nb_user === 1) {
            $correction_url = admin_url('user-edit.php?user_id=' . $search_results[0]);
        } else {
            $correction_url = admin_url('users.php');
        }
        $str = __('<b>Risque de sécurité:</b>', 'csp') . ' ';
        $str.= sprintf(_n('un utilisateur possède un pseudonyme identique à son identifiant.', '%1$s utilisateurs possèdent des pseudonymes identiques à leur identifiants.', $nb_user, 'csp'), $nb_user) . ' ';

        $str.= '<a href="' . $correction_url . '">' . __('Corriger la situation immédiatement.') . '</a>';

        echo '<div class="error"><p>' . $str . ' </p></div>';
    }
}

add_action('admin_notices', 'csp_check_nicenames');

/**
 * Update user nicename
 * 
 * Create a unique nicename for user, different form user_login.
 * 
 * @since 1.0.0
 * @global object $wpdb
 * @param int $user_id
 * @return void
 * @uses filter profile_update (saving profile)
 * @uses filter user_register (saving new profile)
 */
function csp_update_nicename_from_nickname($user_id) {
    global $wpdb;
    $user = get_user_by('id', $user_id);

    if ($user->user_nicename !== $user->user_login) {
        // nicename is different
        return;
    } elseif ($user->nickname !== $user->user_login) {
        $new_nicename = sanitize_title($user->nickname);
    } elseif ($user->display_name !== $user->user_login) {
        $new_nicename = sanitize_title($user->display_name);
    } else {
        $new_nicename = 'user' . time();
    }
    if ($new_nicename) {
        $wpdb->query($wpdb->prepare(
                        "UPDATE $wpdb->users "
                        . "SET user_nicename='%s' "
                        . "WHERE ID=%d;", array(
                    $new_nicename,
                    $user_id
                        )
        ));
    }
}

add_action('profile_update', 'csp_update_nicename_from_nickname', 10, 2);
add_action('user_register', 'csp_update_nicename_from_nickname', 10, 2);

/**
 * INTERFACE    
 */

/**
 * Add security interface
 * 
 * Add the security inteface to Settings
 * 
 * @since 1.0.2
 */
function csp_add_security_interface() {
    add_options_page(__('Sécurité', 'csp'), __('Sécurité', 'csp'), 'manage_options', 'csp-security', 'csp_output_security_interface');
}

add_action('admin_menu', 'csp_add_security_interface');

/**
 * Enqueue admin security CSS and JS
 */
function csp_security_enqueue_head($hook) {
    if ($hook == 'settings_page_csp-security') {
        wp_enqueue_script('csp-security', TOOLKIT_URL . 'js/admin-security.js', array('jquery'), '1.0', true);
        wp_enqueue_style('csp-security', TOOLKIT_URL . 'css/admin-security.css');
    }
}
add_action('admin_enqueue_scripts', 'csp_security_enqueue_head');

/**
 * 
 */
function csp_search_ip_blacklist() {
    
    $response = array();
    
    $ip = filter_var($_POST['ip'], FILTER_VALIDATE_IP);
    if (empty($ip)){
        $response['code'] = 'invalid';
        $response['message'] = __('L’IP est invalide.','csp');
    }else{
        $blacklist = get_option('_blacklist' . csp_get_ip($ip));
        if ( false === $blacklist){
            $response['code'] = 'missing';
            $response['message'] = __('L’IP n’est pas sur la liste noir.','csp');
        }else{
            $response['code'] = 'found';
            $response['message'] = __('L’IP est présent sur la liste noir.','csp');
        }
    }
    $return = json_encode($response);
    
    wp_die($return);
}
add_action('wp_ajax_search_ip_blacklist','csp_search_ip_blacklist');


/**
 * Output Security interface
 * 
 * @since 1.0.2
 * @global object $wpdb
 */
function csp_output_security_interface() {

    // submitted form
    if (isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'csp-security-interface')) {
        
        if (!isset($_POST['what'])) {
            $_POST['what'] = false;
        }
        switch ($_POST['what']) {
            case 'add_my_ip_whitelist':
                if ($new_whitelist = filter_var($_POST['my_ip'], FILTER_VALIDATE_IP)) {
                    add_option('_whitelist' . csp_get_ip($new_whitelist), $new_whitelist);
                    delete_option('_blacklist' . csp_get_ip($new_whitelist));
                }

                break;
            case 'add_ip_whitelist':
                if ($new_whitelist = filter_var($_POST['add_ip_whitelist'], FILTER_VALIDATE_IP)) {
                    add_option('_whitelist' . csp_get_ip($new_whitelist), $new_whitelist);
                    delete_option('_blacklist' . csp_get_ip($new_whitelist));
                }
                break;
            case 'add_ip_blacklist':
                if ($new_blacklist = filter_var($_POST['add_ip_blacklist'], FILTER_VALIDATE_IP)) {
                    add_option('_blacklist' . csp_get_ip($new_blacklist), 1);
                    delete_option('_whitelist' . csp_get_ip($new_blacklist));
                }
                break;
            case 'delete_ip_blacklist':
                if ($the_ip = filter_var($_POST['search_ip_blacklist'], FILTER_VALIDATE_IP)) {
                    delete_option( '_blacklist' . csp_get_ip($the_ip));
                    csp_gandalf_protocol_remove_ip($the_ip);
                }
                break;
            default:
                // we are removing something
                $the_list = (in_array($_POST['remove_list'], array('black', 'white'))) ? $_POST['remove_list'] : false;
                $the_ip = filter_var($_POST['remove_ip'], FILTER_VALIDATE_IP);
                if ($the_list && $the_ip) {
                    delete_option('_' . $the_list . 'list' . csp_get_ip($the_ip));
                    csp_gandalf_protocol_remove_ip($the_ip);
                }

                break;
        }
    }


    global $wpdb;
    $whitelist = $wpdb->get_col("SELECT `option_value` FROM $wpdb->options WHERE `option_name` LIKE '_whitelist%';");
    $blacklist_count = $wpdb->get_var("SELECT count(*) FROM $wpdb->options WHERE `option_name` LIKE '_blacklist%';");
    ?>
    <div class="wrap">
        <h1 class="csp-security-title"><span class="dashicons dashicons-vault"></span><?php _e('Sécurité du site', 'csp') ?></h1>
        <form id="csp-security-form" action="options-general.php?page=csp-security" method="post">
            <?php wp_nonce_field('csp-security-interface') ?>
            <input type="hidden" id="remove_list" name="remove_list" value="" />
            <input type="hidden" id="remove_ip" name="remove_ip" value="" />
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row"><?php _e('Votre adresse IP', 'csp') ?></th>
                        <td>
                            <input type="text" value="<?php echo $_SERVER['REMOTE_ADDR'] ?>" readonly="readonly" name="my_ip" />
                            <button type="submit" name="what" value="add_my_ip_whitelist" class="button-primary"><?php _e('Ajouter à la liste blanche', 'csp'); ?></button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Liste blanche', 'csp') ?></th>
                        <td>
                            <p><?php _e('Les adresses IP de la liste blanche ne subiront aucune vérification de sécurité.', 'csp') ?></p>
                            <p>
                                <input type="text" placeholder="###.###.###.###" name="add_ip_whitelist" />
                                <button type="submit" name="what" value="add_ip_whitelist" class="button-primary"><?php _e('Ajouter','csp'); ?></button>
                            </p>
                            <ul class="scroll-list">
                                <?php if (empty($whitelist)): ?>
                                    <li><?php _e('Aucune adresse IP dans la liste blanche.', 'csp') ?></li>
                                <?php endif; ?>
                                <?php foreach ($whitelist as $wip): ?>
                                    <li>
                                        <code class="security-ip-list-item"><?php echo $wip; ?></code>
                                        <a href="javascript:void(0);" data-list="white" data-ip="<?php echo $wip ?>" class="security-list-remove-item dashicons dashicons-trash"></a></li>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Liste noire', 'csp') ?></th>
                        <td>
                            <p><?php _e('Les adresses IP de la liste noire ne peuvent se connecter à l’admin et son passible d’un blocage complet à l’accès du site.', 'csp') ?></p>
                            <p><em><?php printf( __('Il y a présentement %s adresses IP sur la liste noir.','csp'), '<b>'.$blacklist_count.'</b>') ?></em></p>
                            <p>
                                <input type="text" placeholder="###.###.###.###" name="search_ip_blacklist" id="search_ip_blacklist" />
                                <button type="button" class="button-secondary" name="search_blacklist" value="search_blacklist" id="search_blacklist" ><?php _e('Chercher dans la liste noir','csp') ?></button>
                                <span id="search_blacklist_code" class="dashicons"></span><i id="search_blacklist_reponse"></i>
                                <button type="submit" class="button-primary hide-if-js" name="what" value="delete_ip_blacklist" id="delete_search_blacklist"><?php _e('Retirer de la liste noir','csp') ?></button>
                            </p>
                            
                            
                            
                            <p>
                                <input type="text" placeholder="###.###.###.###" name="add_ip_blacklist" />
                                <button type="submit" name="what" value="add_ip_blacklist"  class="button-primary"><?php _e('Ajouter','csp'); ?></button>
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
    <?php
}

/**
 * 
 * LOGIN LIMIT
 * 
 */

/**
 * Get IP
 * 
 * Simple function to return remote IP.
 * 
 * @since 1.0.0
 * @return string IP
 */
function csp_get_ip($ip = false) {
    if ($ip === false) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $_ip = '_IP_' . str_replace('.', '_', $ip);
    return $_ip;
}

/**
 * Add IP to Whitelist
 * 
 * @since 1.0.2
 * @param IP Address $ip
 */
function csp_add_to_whitelist($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        update_option('_whitelist' . $_ip, $_SERVER['REMOTE_ADDR'], false);
    }
}

/**
 * Remove IP from Whitelist
 *
 * @since 1.0.2
 * @param IP Address $ip
 */
function csp_remove_from_whitelist($ip) {
    if (filter_var($ip, FILTER_VALIDATE_IP)) {
        delete_option('_whitelist' . $_ip);
    }
}

/**
 * 
 * @param type $ip
 * @return boolean
 */
function csp_is_whitelist($ip = false) {
    $whitelist = get_option('_whitelist' . csp_get_ip($ip));
    if ($whitelist) {
        return true;
    }
    return false;
}

/**
 * Add filters and actions only if IP is not whitelisted
 */
if (false === csp_is_whitelist()) {
    remove_filter('authenticate', 'wp_authenticate_username_password', 20);
    add_filter('authenticate', 'csp_authenticate', 10, 3);
    add_action('wp', 'csp_security_404', 100);
}

/**
 * Authenticate user
 * 
 * On authenticate, pass through verification whitelist, blacklist, time delay and limited attempts
 * 
 * @since 1.0.0
 * @param null|WP_User $user
 * @param string $username
 * @param string $password
 * @return WP_User|WP_Error
 */
function csp_authenticate($user, $username, $password) {
    if ($user instanceof WP_User) {
        return $user;
    }

    if (empty($username) || empty($password)) {
        if (is_wp_error($user))
            return $user;

        $error = new WP_Error();

        if (empty($username))
            $error->add('empty_username', __('<strong>Erreur</strong>: L’identifiant est vide.'));

        if (empty($password))
            $error->add('empty_password', __('<strong>Erreur</strong>: Le mot de passe est vide.'));

        return $error;
    }

    /**
     * Filter whether the given user can be authenticated with the provided $password.
     *
     * @since 1.0.1
     *
     * @param WP_User|WP_Error $user     WP_User or WP_Error object if a previous
     *                                   callback failed authentication.
     * @param string           $password Password to check against the user.
     */
    $user = apply_filters('wp_authenticate_user', $user, $password);
    if (is_wp_error($user)){
        return $user;
    }
    
    $_ip = csp_get_ip();
    $good_login = true;
    $blacklist = get_option('_blacklist' . $_ip);

    // username exists?
    $user = get_user_by('login', $username);
    if ($blacklist) {
        $good_login = false;
    } elseif ($user === false) {
        $good_login = false;
    }
    // password correct?
    elseif (!wp_check_password($password, $user->user_pass, $user->ID)) {
        $good_login = false;
    }

    if (!$good_login && defined('CSP_DO_SECURITY') && CSP_DO_SECURITY) {

        // if whitelist go through
        if (csp_is_whitelist()) {
            return $user;
        }

        $error = new WP_Error();

        $wait_login_obj = get_transient('wait' . $_ip);
        $attempts = get_transient('attempt' . $_ip);

        // if blacklist full stop
        if ($blacklist) {
            $blacklist++;
            $test_update = update_option('_blacklist' . $_ip, $blacklist);

            if (CSP_SECURITY_MAX_BLACKLIST < $blacklist) {
                csp_gandalf_protocol_add_ip($_SERVER['REMOTE_ADDR']);
            }
            $error->add('blacklisted', __('Vous êtes sur la liste noir. Contactez l’administrateur du site.', 'csp'));
        }

        // wait delay
        elseif ($wait_login_obj !== false) {

            // if it exists, it means that user should not have tried to log in, it was too soon.
            $previous_delay = $wait_login_obj['delay'];
            $time_to_login = $wait_login_obj['time'];

            if ($previous_delay == 5) {
                $new_delay = 15;
                $new_wait_login_obj = array(
                    'delay' => $new_delay,
                    'time' => $time_to_login + ( $new_delay * 60 )
                );
                set_transient('wait' . $_ip, $new_wait_login_obj, $new_delay * 60);
                $error->add('wait_15', sprintf(
                                __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Vous deviez attendre jusqu’à %s avant de tenter une connexion. Vous devez maintenant 15 minutes (jusqu’à %s) avant de tenter une connexion.', 'csp'), date_i18n(_x('H\hi', 'Heure et minute', 'csp'), $time_to_login), date_i18n(_x('H\hi', 'Heure et minute', 'csp'), $time_to_login + ( $new_delay * 60 ))
                ));
            } elseif ($previous_delay == 15) {
                $new_delay = 60;
                $new_wait_login_obj = array(
                    'delay' => $new_delay,
                    'time' => $time_to_login + ( $new_delay * 60 )
                );
                set_transient('wait' . $_ip, $new_wait_login_obj, $new_delay * 60);
                $error->add('wait_60', sprintf(
                                __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Vous deviez attendre jusqu’à %s avant de tenter une connexion. Vous devez maintenant 60 minutes (jusqu’à %s) avant de tenter une connexion.', 'csp'), date_i18n(_x('H\hi', 'Heure et minute', 'csp'), $time_to_login), date_i18n(_x('H\hi', 'Heure et minute', 'csp'), $time_to_login + ( $new_delay * 60 ))
                ));
            } elseif ($previous_delay == 60) {
                delete_transient('wait' . $_ip);
                // blacklist the son of a gun
                update_option('_blacklist' . $_ip, 1);
                $error->add('new_blacklist', sprintf(
                                __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Vous deviez attendre jusqu’à %s avant de tenter une connexion. Vous êtes maintenant sur la liste noir. Contactez l’administrateur du site.', 'csp'), date_i18n(_x('H\hi', 'Heure et minute', 'csp'), $time_to_login)
                ));
            }
        }

        // if attemps show it
        elseif ($attempts !== false) {
            $attempt_messages = array(
                1 => __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Il vous reste 3 chances sur 5 de vous connecter. <a href="%1$s">Mot de passe oublié ?</a>', 'csp'),
                2 => __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Il ne vous reste que 2 chances sur 5 de vous connecter. Nous vous suggerons fortement d’utiliser la fonction <a href="%1$s">Mot de passe oublié ?</a>.', 'csp'),
                3 => __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Il ne vous reste qu’une seule chance de vous connecter. Vous devrez attendre 5 minutes avant de réessayer. <a href="%1$s">Mot de passe oublié ?</a>', 'csp'),
                4 => __('<strong>Mauvais identifiant ou mot de passe.</strong><br />Vous avez atteint la limite de tentative de connexion. Vous devez maintenant attendre 5 minutes avant de réessayer. <a href="%1$s">Mot de passe oublié ?</a>', 'csp')
            );
            if ($attempts == 4) {

                $new_wait_login_obj = array(
                    'delay' => 5,
                    'time' => time() + ( 5 * 60 )
                );

                delete_transient('attempt' . $_ip);
                set_transient('wait' . $_ip, $new_wait_login_obj, 5 * 60);
            } else {
                set_transient('attempt' . $_ip, $attempts + 1, 60 * 60);
            }
            $error->add(
                    'attempt', sprintf($attempt_messages[$attempts], wp_lostpassword_url()));
        } else {
            // first login error
            set_transient('attempt' . $_ip, 1, 60 * 60);
            $error->add(
                    'attempt', sprintf(__('<strong>Mauvais identifiant ou mot de passe.</strong><br />Il vous reste 4 chances sur 5 de vous connecter. <a href="%1$s">Mot de passe oublié ?</a>', 'csp'), wp_lostpassword_url()));
        }
        csp_security_log('login', $error->get_error_codes());
        return $error;
    }
    return $user;
}

/**
 * security 404
 * 
 * Checks and manages 404 errors for possible attacks
 * 
 * @global object $wpdb
 */
function csp_security_404() {

    if (csp_is_whitelist()) {
        return;
    } elseif (is_main_query() && is_404()) {

        //not for media
        $uri = $_SERVER['REQUEST_URI'];
        if (strpos($uri, '.jpg') || strpos($uri, '.gif') || strpos($uri, '.png') || strpos($uri, '.jpeg')) {
            return;
        }

        // not for legitimate pages
        global $wpdb;
        // by id?
        $pattern = '/(\?|&)p=(\d+)/';
        if (preg_match($pattern, $uri, $matches)) {
            if (false !== get_post_status(intval($matches[2]))) {
                return;
            }
        }
        // by slug?
        $pattern = '/\/([\w-%]+)\/$/';
        if (preg_match($pattern, $uri, $matches)) {
            $post_name = esc_sql($matches[1]);
            $sql = "SELECT ID, post_name, post_status, post_type
                    FROM $wpdb->posts
                    WHERE post_name IN (%s)";

            $check = $wpdb->get_results($wpdb->prepare($sql, $post_name));
            if (!empty($check)) {
                return;
            }
        }


        $_ip = csp_get_ip();
        $blacklist = get_option('_blacklist' . $_ip);
        $four_oh_four = get_transient('four_oh_four' . $_ip);
        if ($blacklist) {
            // add blacklist
            $blacklist++;
            update_option('_blacklist' . $_ip, $blacklist);
            if (CSP_SECURITY_MAX_BLACKLIST < $blacklist) {
                csp_gandalf_protocol_add_ip($_SERVER['REMOTE_ADDR']);
            }
        } elseif ($four_oh_four > CSP_SECURITY_MAX_404) {
            //blacklist
            delete_transient('four_oh_four' . $_ip);
            update_option('_blacklist' . $_ip, 1);
            return;
        } else {
            $four_oh_four++;
            set_transient('four_oh_four' . $_ip, $four_oh_four, 20 * 60);
        }
        csp_security_log('four_oh_four');
    }
}

/**
 * Gandalf protocol Add IP
 * 
 * Add IP to the Gandalf queue
 * 
 * @since 1.0.2
 * @param IP address $ip
 * @uses function csp_gandalf_protocol_write_ips()
 * @return bool Success|Failure
 */
function csp_gandalf_protocol_add_ip($ip = false) {

    if (!$ip)
        return false;

    // add the ip to gandalf queue option
    $queue = get_option('csp_gandalf_queue', array());

    if (!in_array($ip, $queue)) {
        $queue[] = $ip;

        if (count($queue < 5)) {
            csp_gandalf_protocol_write_ips($queue);
            $queue = array();
        }
        update_option('csp_gandalf_queue', $queue);
    }
    return true;
}

/**
 * Gandalf protocol Write IPs
 * 
 * Write IP to deny list of htaccess.
 * 
 * @param array $ips
 * @return bool Success|Failure
 */
function csp_gandalf_protocol_write_ips($ips = array()) {

    if (empty($ips))
        return false;

    require_once ABSPATH . 'wp-admin/includes/misc.php';

    $htaccess = ABSPATH . '.htaccess';
    $gandalf_list = extract_from_markers($htaccess, 'GANDALF');

    if (count($gandalf_list)) {
        $old_gandalf_denies = array_slice($gandalf_list, 2, count($gandalf_list) - 4);
    }

    $new_denies = array();
    for ($i = 0; $i < count($ips); $i++) {
        $new_denies[] = 'deny from ' . $ips[$i];
    }

    $update_gandalf_denies = array_unique(array_merge($new_denies, $old_gandalf_denies));

    $beginning = array(
        '<Limit GET POST>',
        'order allow,deny'
    );
    $end = array(
        'allow from all',
        '</Limit>'
    );

    $new_gandalf_list = array_merge($beginning, $update_gandalf_denies, $end);
    insert_with_markers($htaccess, 'GANDALF', $new_gandalf_list);
    return true;
}

/**
 * Gandalf protocol Remove IP
 * 
 * Remove IP address from htaccess file, and all options and transients
 * 
 * @param IP Address $ip
 * @return bool Success|Failure
 */
function csp_gandalf_protocol_remove_ip($ip = false) {

    if (!$ip)
        return false;

    $_ip = csp_get_ip($ip);

    delete_transient('four_oh_four' . $_ip);
    delete_transient('wait' . $_ip);
    delete_transient('attempt' . $_ip);
    delete_option('_blacklist' . $_ip, 1);

    require_once ABSPATH . 'wp-admin/includes/misc.php';

    $htaccess = ABSPATH . '.htaccess';
    $gandalf_list = extract_from_markers($htaccess, 'GANDALF');
    $gandalf_denies = array();

    if (count($gandalf_list)) {
        $gandalf_denies = array_slice($gandalf_list, 2, count($gandalf_list) - 4);
    }

    foreach ($gandalf_denies as $key => $deny) {
        if (($key = array_search('deny from ' . $ip, $gandalf_denies)) !== false) {
            unset($gandalf_denies[$key]);
        }
    }

    $beginning = array(
        '<Limit GET POST>',
        'order allow,deny'
    );
    $end = array(
        'allow from all',
        '</Limit>'
    );
    if (!empty($gandalf_denies)) {
        $new_gandalf_list = array_merge($beginning, $gandalf_denies, $end);
    } else {
        $new_gandalf_list = array();
    }
    insert_with_markers($htaccess, 'GANDALF', $new_gandalf_list);
    return true;
}

/**
 * Write security log
 * 
 * Write data to security log
 * 
 * @since 1.0.0
 * @param string $log Type of entry (four_oh_four, login, etc.)
 * @param string|array $data The actual data to write
 * @return bool Success|Failure
 */
function csp_security_log($log, $data = false) {

    if (empty($log)) {
        return false;
    }

    $array_log = array();

    $security_log = WP_CONTENT_DIR . '/security.log';

    $array_log = array($log);

    $array_log[] = '[' . date_i18n('c') . ']';

    $array_log[] = '[' . $_SERVER['REMOTE_ADDR'] . ']';

    if (!( $geo_info = get_transient('geo_info_' . csp_get_ip()) )) {
        $geo_info = unserialize(file_get_contents('http://www.geoplugin.net/php.gp?ip=' . $_SERVER['REMOTE_ADDR']));
        set_transient('geo_info_' . csp_get_ip(), $geo_info, DAY_IN_SECONDS);
    }

    $array_log[] = html_entity_decode('['
            . $geo_info['geoplugin_city']
            . ','
            . $geo_info['geoplugin_regionName']
            . ','
            . $geo_info['geoplugin_countryName']
            . ']', ENT_QUOTES, "utf-8");

    if ($log == 'four_oh_four') {
        $array_log[] = '[URI=' . $_SERVER['REQUEST_URI'] . ']';
    }

    if (is_array($data)) {
        $array_log = array_merge($array_log, $data);
    } elseif (false !== $data) {
        $array_log[] = $data;
    }

    error_log(implode(' ', $array_log) . "\n", 3, $security_log);
    return true;
}

/**
 * Temporary and utility function to run manual calls to functions on admin footer hook
 */
function do_something() {
    //csp_gandalf_protocol_add_ip();
    //csp_gandalf_protocol_remove_ip( '200.200.200.200' );
}

add_action('admin_footer', 'do_something');
