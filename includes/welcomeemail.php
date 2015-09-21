<?php

$we_file = trailingslashit(str_replace('\\', '/', __FILE__));
$we_dir = trailingslashit(str_replace('\\', '/', dirname(__FILE__)));
$we_home = trailingslashit(str_replace('\\', '/', get_bloginfo('wpurl')));
$we_active = true;

$we_mime_boundary = 'Multipart_Boundary_x' . md5(time()) . 'x';

define('WE_PRODUCT_NAME', 'Message de bienvenue');
define('WE_PLUGIN_DIR_PATH', $we_dir);
define('WE_PLUGIN_DIR_URL', trailingslashit(str_replace(str_replace('\\', '/', ABSPATH), $we_home, $we_dir)));
define('WE_PLUGIN_DIRNAME', str_replace('/plugins/', '', strstr(WE_PLUGIN_DIR_URL, '/plugins/')));



$icl_settings = array(
    'header_from_email',
    'header_from_name',
    'user_subject',
    'user_body',
    'header_reply_to',
    'admin_subject',
    'admin_body',
    'reminder_subject',
    'reminder_body',
    'password_reminder_subject',
    'password_reminder_body'
);

//we_printr(get_option('active_plugins'));

function we_loaded() {

    add_action('init', 'we_init');
    add_action('admin_menu', 'we_admin_page');

    //
//	if( $settings = we_get_settings() ) {	// prevent warning on $settings use when first enabled
//		if (!$settings->disable_reminder_service) {
//			add_action('profile_update', 'we_profile_update');
//			add_filter('user_row_actions', 'we_user_col_row', 10, 2);
//		}
//	}
    //add_action('manage_users_custom_column', 'we_user_col_row', 98, 3);
    //add_filter('manage_users_columns', 'we_user_col');
    add_filter('wpmu_welcome_user_notification', 'we_mu_new_user_notification', 10, 3);

    global $we_active;

    if (is_admin() && !isset($_REQUEST['_wp_http_referer'])) {
        if (!$we_active) {
            $msg = '<div class="error"><p>' . sprintf(__('%s can not function because another plugin is conflicting. Please disable other plugins until this message disappears to fix the problem.', 'we'), WE_PRODUCT_NAME) . '</p></div>';
            add_action('admin_notices', create_function('', 'echo \'' . $msg . '\';'));
        }

        foreach ($_REQUEST as $key => $value) {
            if (substr($key, 0, 6) == 'we_') {
                if (substr($key, 0, 13) == 'we_resend_') {
                    if ($user_id = substr($key, 13)) {
                        we_send_new_user_notification($user_id, true);
                        wp_redirect(admin_url('users.php'));
                    }
                }
            }
        }
    }
}

function we_get_settings() {
    $settings = get_option('we_settings');

    if (function_exists('icl_t')) {
        global $icl_settings;

        foreach ($settings as $key => $value) {
            if (in_array($key, $icl_settings)) {
                $settings->$key = icl_t('Welcome Email', $key, $value);
            }
        }
    }

    return $settings;
}

function we_lost_password_title($content) {
    $settings = we_get_settings();

    if ($settings->password_reminder_subject) {
        if (is_multisite())
            $blogname = $GLOBALS['current_site']->site_name;
        else
            $blogname = esc_html(get_option('blogname'), ENT_QUOTES);

        $content = $settings->password_reminder_subject;
        $content = str_replace('[blog_name]', $blogname, $content);
    }

    return $content;
}

function we_lost_password_message($message, $key) {
    global $wpdb;

    $settings = we_get_settings();

    if (trim($settings->password_reminder_body)) {
        if ($user_login = $wpdb->get_var($wpdb->prepare("SELECT user_login FROM $wpdb->users WHERE user_activation_key = %s", $key))) {
            $site_url = site_url();

            if (is_multisite())
                $blogname = $GLOBALS['current_site']->site_name;
            else
                $blogname = esc_html(get_option('blogname'), ENT_QUOTES);

            $reset_url = trailingslashit(site_url()) . "wp-login.php?action=rp&key=$key&login=" . rawurlencode($user_login);
            $message = $settings->password_reminder_body; //'Someone requested that the password be reset for the following account: [site_url]' . "\n\n" . 'Username: [user_login]' . "\n\n" . 'If this was a mistake, just ignore this email and nothing will happen.' . "\n\n" . 'To reset your password, visit the following address: [reset_url]';

            $message = str_replace('[user_login]', $user_login, $message);
            $message = str_replace('[blog_name]', $blogname, $message);
            $message = str_replace('[site_url]', $site_url, $message);
            $message = str_replace('[reset_url]', $reset_url, $message);
        }
    }

    return $message;
}

function we_send_new_user_notification($user_id, $reminder = false) {
    $return = false;

    if (!$plaintext_pass = get_usermeta($user_id, 'we_plaintext_pass')) {
        $plaintext_pass = '[Your Password Here]';
    }

    if (wp_new_user_notification($user_id, $plaintext_pass, $reminder)) {
        $return = __('Welcome email sent.', 'we');
    }

    return $return;
}

function we_mu_new_user_notification($user_id, $password, $meta = '') {
    return wp_new_user_notification($user_id, $password);
}

function we_init() {
    if (!$we_settings = we_get_settings()) {
        $blog_name = get_option('blogname');

        $we_settings = new stdClass();
        $we_settings->user_subject = __('[[blog_name]] Your username and password', 'we');
        $we_settings->user_body = __('Username: [user_login]<br />Password: [user_password]<br />[login_url]', 'we');
        $we_settings->admin_subject = __('[[blog_name]] New User Registration', 'we');
        $we_settings->admin_body = sprintf(__('New user registration on your blog %s<br /><br />Username: [user_login]<br />Email: [user_email]', 'we'), $blog_name);
        $we_settings->admin_notify_user_id = 1;
        $we_settings->remind_on_profile_update = 0;
        $we_settings->header_from_name = '';
        $we_settings->header_from_email = '[admin_email]';
        $we_settings->header_reply_to = '[admin_email]';
        $we_settings->header_send_as = 'html';
        $we_settings->header_additional = '';
        $we_settings->set_global_headers = 1;
        $we_settings->password_reminder_subject = __('[[blog_name]] Forgot Password', 'csp');
        $we_settings->password_reminder_body = __('Someone requested that the password be reset for the following account: [site_url]<br /><br />Username: [user_login]<br /><br />If this was a mistake, just ignore this email and nothing will happen.<br /><br />To reset your password, visit the following address: [reset_url]', 'csp');

        add_option('we_settings', $we_settings);
    }

    if (@$we_settings->set_global_headers) {
        we_set_email_filter_headers();
    }

    add_filter('retrieve_password_title', 'we_lost_password_title', 10, 1);
    add_filter('retrieve_password_message', 'we_lost_password_message', 100, 2);
}

function we_set_email_filter_headers() {
    $we_settings = we_get_settings();

    if ($from_email = $we_settings->header_from_email) {
        add_filter('wp_mail_from', 'we_get_from_email', 1, 1);

        if ($from_name = $we_settings->header_from_name) {
            add_filter('wp_mail_from_name', 'we_get_from_name', 1, 1);
        }
    }
    if ($send_as = $we_settings->header_send_as) {
        if ($send_as == 'html') {
            add_filter('wp_mail_content_type', create_function('$i', 'return "text/html";'), 1, 1);
            add_filter('wp_mail_charset', 'we_get_charset', 1, 1);
        }
    }
}

function we_get_from_email($from_email) {
    // Get the site domain and get rid of www.
    $sitename = strtolower($_SERVER['SERVER_NAME']);
    if (substr($sitename, 0, 4) == 'www.') {
        $sitename = substr($sitename, 4);
    }
    $default_from_email = 'wordpress@' . $sitename;

    if ($default_from_email == $from_email){
        $we_settings = we_get_settings();
        $admin_email = get_option('admin_email');
        return str_replace('[admin_email]', $admin_email, $we_settings->header_from_email);
    }
    else{
        return $from_email;
    }
}

function we_get_from_name($from_name) {
    
    if ( 'WordPress' == $from_name){
        $we_settings = we_get_settings();
        $admin_email = get_option('admin_email');
        return str_replace('[admin_email]', $admin_email, $we_settings->header_from_name);
    }else{
        return $from_name;
    }
}

function we_get_charset() {
    if (!$charset = get_bloginfo('charset')) {
        $charset = 'iso-8859-1';
    }

    return $charset;
}

if (!function_exists('wp_new_user_notification')) {

    function wp_new_user_notification($user_id, $plaintext_pass = '', $reminder = false) {
        global $we_home, $current_site;
        ;

        if ($user = new WP_User($user_id)) {
            $settings = we_get_settings();

//			if (!$settings->disable_reminder_service) {
//				if (!in_array($plaintext_pass, array('[User password will appear here]', '[Your Password Here]'))) {
//					update_usermeta($user_id, 'we_plaintext_pass', $plaintext_pass); //store user password in case of reminder
//				}
//			}

            update_usermeta($user_id, 'we_last_sent', time());

            $blog_name = get_option('blogname');
            if (is_multisite()) {
                $blog_name = $current_site->site_name;
            }

            $admin_email = get_option('admin_email');

            $user_login = stripslashes($user->user_login);
            $user_email = stripslashes($user->user_email);

            if (!$reminder) {
                $user_subject = $settings->user_subject;
                $user_message = $settings->user_body;
            } else {
                $user_subject = $settings->reminder_subject;
                $user_message = $settings->reminder_body;
            }

            $admin_subject = $settings->admin_subject;
            $admin_message = $settings->admin_body;

            $first_name = $user->first_name;
            $last_name = $user->last_name;

            //Headers
            $headers = '';
            if ($reply_to = $settings->header_reply_to) {
                $headers .= 'Reply-To: ' . $reply_to . "\r\n";
            }

            if ($from_email = $settings->header_from_email) {
                $from_email = str_replace('[admin_email]', $admin_email, $from_email);
                add_filter('wp_mail_from', 'we_get_from_email', 1, 100);

                if ($from_name = $settings->header_from_name) {
                    add_filter('wp_mail_from_name', 'we_get_from_name', 1, 100);
                    $headers .= 'From: ' . $from_name . ' <' . $from_email . ">\r\n";
                } else {
                    $headers .= 'From: ' . $from_email . "\r\n";
                }
            }
            if ($send_as = $settings->header_send_as) {
                if ($send_as == 'html') {
                    if (!$charset = get_bloginfo('charset')) {
                        $charset = 'iso-8859-1';
                    }
                    $headers .= 'Content-type: text/html; charset=' . $charset . "\r\n";

                    add_filter('wp_mail_content_type', create_function('$i', 'return "text/html";'), 1, 100);
                    add_filter('wp_mail_charset', 'we_get_charset', 1, 100);
                }
            }

            if ($additional = $settings->header_additional) {
                $headers .= $additional;
            }

            $headers = str_replace('[admin_email]', $admin_email, $headers);
            $headers = str_replace('[blog_name]', $blog_name, $headers);
            $headers = str_replace('[site_url]', $we_home, $headers);
            //End Headers
            //Don't notify if the admin object doesn't exist;
            if ($settings->admin_notify_user_id) {
                //Allows single or multiple admins to be notified. Admin ID 1 OR 1,3,2,5,6,etc...
                $admins = explode(',', $settings->admin_notify_user_id);

                if (!is_array($admins)) {
                    $admins = array($admins);
                }

                global $wpdb;
                $sql = 'SELECT meta_key, meta_value
					FROM ' . $wpdb->usermeta . '
					WHERE user_ID = ' . $user_id;
                $custom_fields = array();
                if ($meta_items = $wpdb->get_results($sql)) {
                    foreach ($meta_items as $i => $meta_item) {
                        $custom_fields[$meta_item->meta_key] = $meta_item->meta_value;
                    }
                }

                $admin_message = str_replace('[blog_name]', $blog_name, $admin_message);
                $admin_message = str_replace('[admin_email]', $admin_email, $admin_message);
                $admin_message = str_replace('[site_url]', $we_home, $admin_message);
                $admin_message = str_replace('[login_url]', $we_home . 'wp-login.php', $admin_message);
                $admin_message = str_replace('[user_email]', $user_email, $admin_message);
                $admin_message = str_replace('[user_login]', $user_login, $admin_message);
                $admin_message = str_replace('[first_name]', $first_name, $admin_message);
                $admin_message = str_replace('[last_name]', $last_name, $admin_message);
                $admin_message = str_replace('[user_id]', $user_id, $admin_message);
                $admin_message = str_replace('[plaintext_password]', $plaintext_pass, $admin_message);
                $admin_message = str_replace('[user_password]', $plaintext_pass, $admin_message);
                $admin_message = str_replace('[custom_fields]', '<pre>' . print_r($custom_fields, true) . '</pre>', $admin_message);
                $admin_message = str_replace('[bp_custom_fields]', '<pre>' . print_r(we_get_bp_custom_fields($user_id), true) . '</pre>', $admin_message);

                $admin_subject = str_replace('[blog_name]', $blog_name, $admin_subject);
                $admin_subject = str_replace('[site_url]', $we_home, $admin_subject);
                $admin_subject = str_replace('[first_name]', $first_name, $admin_subject);
                $admin_subject = str_replace('[last_name]', $last_name, $admin_subject);
                $admin_subject = str_replace('[user_email]', $user_email, $admin_subject);
                $admin_subject = str_replace('[user_login]', $user_login, $admin_subject);
                $admin_subject = str_replace('[user_id]', $user_id, $admin_subject);

                foreach ($admins as $admin_id) {
                    if ($admin = new WP_User($admin_id)) {
                        wp_mail($admin->user_email, $admin_subject, $admin_message, $headers);
                    }
                }
            }

            if (!empty($plaintext_pass)) {
                $user_message = str_replace('[admin_email]', $admin_email, $user_message);
                $user_message = str_replace('[site_url]', $we_home, $user_message);
                $user_message = str_replace('[login_url]', $we_home . 'wp-login.php', $user_message);
                $user_message = str_replace('[user_email]', $user_email, $user_message);
                $user_message = str_replace('[user_login]', $user_login, $user_message);
                $user_message = str_replace('[last_name]', $last_name, $user_message);
                $user_message = str_replace('[first_name]', $first_name, $user_message);
                $user_message = str_replace('[user_id]', $user_id, $user_message);
                $user_message = str_replace('[plaintext_password]', $plaintext_pass, $user_message);
                $user_message = str_replace('[user_password]', $plaintext_pass, $user_message);
                $user_message = str_replace('[blog_name]', $blog_name, $user_message);

                $user_subject = str_replace('[blog_name]', $blog_name, $user_subject);
                $user_subject = str_replace('[site_url]', $we_home, $user_subject);
                $user_subject = str_replace('[user_email]', $user_email, $user_subject);
                $user_subject = str_replace('[last_name]', $last_name, $user_subject);
                $user_subject = str_replace('[first_name]', $first_name, $user_subject);
                $user_subject = str_replace('[user_login]', $user_login, $user_subject);
                $user_subject = str_replace('[user_id]', $user_id, $user_subject);

                wp_mail($user_email, $user_subject, $user_message, $headers);
            }
        }

        return true;
    }

} else {
    $we_active = false;
}

function we_get_bp_custom_fields($user_id) {
    global $wpdb;

    $sql = 'SELECT f.name, d.value
		FROM
			' . $wpdb->prefix . 'bp_xprofile_fields f
			JOIN ' . $wpdb->prefix . 'bp_xprofile_data d ON (d.field_id = f.id)
		WHERE d.user_id = ' . $user_id;

    $array = $wpdb->get_results($sql);
    $assoc_array = array();

    foreach ($array as $key => $value) {
        $assoc_array[$value->name] = $value->value;
    }

    return $assoc_array;
}

function we_update_settings() {
    global $icl_settings;

    $old_settings = we_get_settings();

    $settings = new stdClass();

    if ($post_settings = we_post('settings')) {
        foreach ($post_settings as $key => $value) {
            $settings->$key = stripcslashes($value);

            if (function_exists('icl_register_string') && in_array($key, $icl_settings)) {
                icl_register_string('SB Welcome Email', $key, $value);
            }
        }

        if (update_option('we_settings', $settings)) {
            we_display_message(__('Settings have been successfully saved', 'we'));
        }
    }
}

function we_display_message($msg, $error = false, $return = false) {
    $class = 'updated fade';

    if ($error) {
        $class = 'error';
    }

    $html = '<div id="message" class="' . $class . '" style="margin-top: 5px; padding: 7px;">' . $msg . '</div>';

    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

function we_settings() {
    if (we_post('submit')) {
        we_update_settings();
    }

    if (we_post('test_send')) {
        global $current_user;
        get_currentuserinfo();

        wp_new_user_notification($current_user->ID, '[User password will appear here]');
        we_display_message(sprintf(__('Test email sent to “%s”', 'csp'), $current_user->user_email));
    }

    $html = '';
    $settings = we_get_settings();

    $page_options = array(
        'general_settings_label' => array(
            'title' => __('General Settings', 'csp')
            , 'type' => 'label'
            , 'style' => 'width: 500px;'
            , 'description' => __('These settings effect all of this plugin and, in some cases, all of your site.', 'csp')
        )
        , 'settings[set_global_headers]' => array(
            'title' => __('Set Global Email Headers', 'csp')
            , 'type' => 'yes_no'
            , 'style' => 'width: 500px;'
            , 'description' => __('When set to yes this will cause all email from the site to come from the configured email and name. It also sets the content type as per the dropdown below (HTML/Plaintext). Added as a setting because some people might want to turn it off.', 'csp')
        )
        , 'settings[header_from_email]' => array(
            'title' => __('From Email Address', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Global option change the from email address for all site emails', 'csp')
        )
        , 'settings[header_from_name]' => array(
            'title' => __('From Name', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Global option change the from name for all site emails', 'csp')
        )
        , 'settings[header_send_as]' => array(
            'title' => __('Send Email As', 'csp')
            , 'type' => 'select'
            , 'style' => 'width: 100px;'
            , 'options' => array(
                'text' => 'TEXT'
                , 'html' => 'HTML'
            )
            , 'description' => __('Send email as Text or HTML (Remember to remove html from text emails).', 'csp')
        )
        , 'welcome_email_settings_label' => array(
            'title' => __('Welcome Email Settings', 'csp')
            , 'type' => 'label'
            , 'style' => 'width: 500px;'
            , 'description' => __('These settings are for the email sent to the new user on their signup.', 'csp')
        )
        , 'settings[user_subject]' => array(
            'title' => __('User Email Subject', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Subject line for the welcome email sent to the user.', 'csp')
        )
        , 'settings[user_body]' => array(
            'title' => __('User Email Body', 'csp')
            , 'type' => 'textarea'
            , 'style' => 'width: 650px; height: 500px;'
            , 'description' => __('Body content for the welcome email sent to the user.', 'csp')
        )
        , 'settings[header_additional]' => array(
            'title' => __('Additional Email Headers', 'csp')
            , 'type' => 'textarea'
            , 'style' => 'width: 550px; height: 200px;'
            , 'description' => __('Optional field for advanced users to add more headers. Don’t forget to separate headers with \r\n.', 'csp')
        )
        , 'settings[header_reply_to]' => array(
            'title' => __('Reply To Email Address', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Optional Header sent to change the reply to address for new user notification.', 'csp')
        )
        , 'welcome_email_admin_settings_label' => array(
            'title' => __('Welcome Email Admin Notification Settings', 'csp')
            , 'type' => 'label'
            , 'style' => 'width: 500px;'
            , 'description' => __('These settings are for the email sent to the admin on a new user signup.', 'csp')
        )
        , 'settings[admin_subject]' => array(
            'title' => __('Admin Email Subject', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Subject Line for the email sent to the admin user(s).', 'csp')
        )
        , 'settings[admin_body]' => array(
            'title' => __('Admin Email Body', 'csp')
            , 'type' => 'textarea'
            , 'style' => 'width: 650px; height: 300px;'
            , 'description' => __('Body content for the email sent to the admin user(s).', 'csp')
        )
        , 'settings[admin_notify_user_id]' => array(
            'title' => __('Send Admin Email To...', 'csp')
            , 'type' => 'select'
            , 'style' => 'width: 200px;'
            , 'options' => we_get_admins_array()
            , 'description' => __('This allows you to type in the User of the people who you want the admin notification to be sent to.', 'csp')
        )
//	,'password_reminder_service_settings_label'=>array(
//		'title'=>__('Password Reminder Service Settings','csp')
//		, 'type'=>'label'
//		, 'style'=>'width: 500px;'
//		, 'description'=>__('These settings are for the buttons added to the users admin screen (users.php) allowing the password to be resent by the administrator at any time.','csp')
//	)
//	,'settings[disable_reminder_service]'=>array(
//		'title'=>__('Disable Reminder Service','csp')
//		, 'type'=>'yes_no'
//		, 'style'=>'width: 500px;'
//		, 'description'=>__('Allows the admin to send users their passwords again if they forget them. Turn this off here if you want to','csp')
//	)
//	,'settings[reminder_subject]'=>array(
//		'title'=>__('Reminder Email Subject','csp')
//		, 'type'=>'text'
//		, 'style'=>'width: 500px;'
//		, 'description'=>__('Subject line for the reminder email that admin can send to a user.','csp')
//	)
//	, 'settings[reminder_body]'=>array(
//		'title'=>__('Reminder Email Body','csp')
//		, 'type'=>'textarea'
//		, 'style'=>'width: 650px; height: 500px;'
//		, 'description'=>__('Body content for the reminder email that admin can send to a user.','csp')
//	)
        , 'forgot_password_settings_label' => array(
            'title' => __('User Forgot Password Email Settings', 'csp')
            , 'type' => 'label'
            , 'style' => 'width: 500px;'
            , 'description' => __('These settings are for the email sent to the user when they use the inbuild Wordpress forgot password functionality.', 'csp')
        )
        , 'settings[password_reminder_subject]' => array(
            'title' => __('Forgot Password Email Subject', 'csp')
            , 'type' => 'text'
            , 'style' => 'width: 500px;'
            , 'description' => __('Subject line for the forgot password email that a user can send to themselves using the login screen. Use [blogname] where appropriate.', 'csp')
        )
        , 'settings[password_reminder_body]' => array(
            'title' => __('Forgot Password Message', 'csp')
            , 'type' => 'textarea'
            , 'style' => 'width: 650px; height: 500px;'
            , 'description' => __('Content for the forgot password email that the user can send to themselves via the login screen. Use [blog_name], [site_url], [reset_url] and [user_login] where appropriate. Note to use HTML in this box only if you have set the send mode to HTML. If not text will be used and any HTML ignored.', 'csp')
        )
        , 'submit' => array(
            'title' => ''
            , 'type' => 'submit'
            , 'value' => __('Update Settings', 'csp')
        )
        , 'test_send' => array(
            'title' => ''
            , 'type' => 'submit'
            , 'value' => __('Test Emails (Save first, will send to current user)', 'csp')
        )
    );

    $html .= '<div style="margin-bottom: 10px;">' . __('This page allows you to update the Wordpress welcome email and add headers to make it less likely to fall into spam. You can edit the templates for both the admin and user emails and assign admin members to receive the notifications. Use the following hooks in any of the boxes below: [site_url], [login_url], [user_email], [user_login], [plaintext_password], [blog_name], [admin_email], [user_id], [custom_fields], [first_name], [last_name], [bp_custom_fields] (buddypress custom fields .. admin only)', 'we') . '</div>';
    $html .= we_start_box('Settings');

    $html .= '<form method="POST">';
    $html .= '<table class="widefat form-table">';

    $i = 0;
    foreach ($page_options as $name => $options) {
        $options['type'] = (isset($options['type']) ? $options['type'] : '');
        $options['description'] = (isset($options['description']) ? $options['description'] : '');
        $options['class'] = (isset($options['class']) ? $options['class'] : false);
        $options['style'] = (isset($options['style']) ? $options['style'] : false);
        $options['rows'] = (isset($options['rows']) ? $options['rows'] : false);
        $options['cols'] = (isset($options['cols']) ? $options['cols'] : false);


        if ($options['type'] == 'submit') {
            $value = $options['value'];
        } else {
            $tmp_name = str_replace('settings[', '', $name);
            $tmp_name = str_replace(']', '', $tmp_name);
            $value = stripslashes(we_post($tmp_name, isset($settings->$tmp_name) ? $settings->$tmp_name : '' ));
        }
        $title = (isset($options['title']) ? $options['title'] : false);
        if ($options['type'] == 'label') {
            $title = '<strong>' . $title . '</strong>';
        }

        $html .= '	<tr class="' . ($i % 2 ? 'alternate' : '') . '">
					<th style="vertical-align: top;">
						' . $title . '
						' . ($options['description'] && $options['type'] != 'label' ? '<div style="font-size: 10px; color: gray;">' . $options['description'] . '</div>' : '') . '
					</th>
					<td style="' . ($options['type'] == 'submit' ? 'text-align: right;' : '') . '">';



        switch ($options['type']) {
            case 'label':
                $html .= $options['description'];
                break;
            case 'text':
                $html .= we_get_text($name, $value, $options['class'], $options['style']);
                break;
            case 'yes_no':
                $html .= we_get_yes_no($name, $value, $options['class'], $options['style']);
                break;
            case 'textarea':
                $html .= we_get_textarea($name, $value, $options['class'], $options['style'], $options['rows'], $options['cols']);
                break;
            case 'select':
                $html .= we_get_select($name, $options['options'], $value, $options['class'], $options['style']);
                break;
            case 'submit':
                $html .= we_get_submit($name, $value, $options['class'], $options['style']);
                break;
        }

        $html .= '		</td>
				</tr>';

        $i++;
    }

    $html .= '</table>';
    $html .= '</form>';

    $html .= we_end_box();

    return $html;
}

function we_get_admins_array() {
    $admins = new WP_User_Query(array('role' => 'administrator'));
    $admins = $admins->get_results();
    $arr = array();
    foreach ($admins as $admin) {
        $arr[$admin->ID] = $admin->display_name;
    }
    return $arr;
}

function we_printr($array = false) {
    if (!$array) {
        $array = $_POST;
    }

    echo '<pre>';
    print_r($array);
    echo '</pre>';
}

function we_get_textarea($name, $value, $class = false, $style = false, $rows = false, $cols = false) {
    $rows = ($rows ? ' rows="' . $rows . '"' : '');
    $cols = ($cols ? ' cols="' . $cols . '"' : '');
    $style = ($style ? ' style="' . $style . '"' : '');
    $class = ($class ? ' class="' . $class . '"' : '');

    return '<textarea name="' . $name . '" ' . $rows . $cols . $style . $class . '>' . esc_html($value, true) . '</textarea>';
}

function we_get_select($name, $options, $value, $class = false, $style = false) {
    $style = ($style ? ' style="' . $style . '"' : '');
    $class = ($class ? ' class="' . $class . '"' : '');

    $html = '<select name="' . $name . '" ' . $class . $style . '>';
    if (is_array($options)) {
        foreach ($options as $val => $label) {
            $html .= '<option value="' . $val . '" ' . ($val == $value ? 'selected="selected"' : '') . '>' . $label . '</option>';
        }
    }
    $html .= '</select>';

    return $html;
}

function we_get_input($name, $type = false, $value = false, $class = false, $style = false, $attributes = false) {
    $style = ($style ? ' style="' . $style . '"' : '');
    $class = ($class ? ' class="' . $class . '"' : '');
    $value = 'value="' . esc_html($value, true) . '"';
    $type = ($type ? ' type="' . $type . '"' : '');

    return '<input name="' . $name . '" ' . $value . $type . $style . $class . ' ' . $attributes . ' />';
}

function we_get_text($name, $value = false, $class = false, $style = false) {
    return we_get_input($name, 'text', $value, $class, $style);
}

function we_get_yes_no($name, $value = false, $class = false, $style = false) {
    $return = '';

    $return .= __('Yes') . ': ' . we_get_input($name, 'radio', 1, $class, $style, ($value == 1 ? 'checked="checked"' : '')) . '<br />';
    $return .= __('No') . ': ' . we_get_input($name, 'radio', 0, $class, $style, ($value == 1 ? '' : 'checked="checked"'));

    return $return;
}

function we_get_submit($name, $value = false, $class = false, $style = false) {
    if (strpos($class, 'button') === false) {
        $class .= 'button';
    }

    return we_get_input($name, 'submit', $value, $class, $style);
}

function we_start_box($title, $return = true) {
    $html = '	<div class="postbox" style="margin: 5px 0px; min-width: 0px !important;">
					<h3>' . __($title, 'we') . '</h3>
					<div class="inside">';

    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

function we_end_box($return = true) {
    $html = '</div>
		</div>';

    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}

function we_admin_page() {

    $admin_page = 'we_settings';
    $func = 'we_admin_loader';
    $access_level = 'manage_options';

    add_options_page(WE_PRODUCT_NAME, __('Welcome email', 'csp'), $access_level, $admin_page, $func);
}

function we_admin_loader() {

    $page = str_replace(WE_PLUGIN_DIRNAME, '', trim($_REQUEST['page']));

    echo '<div id="poststuff" class="wrap"><h2>' . WE_PRODUCT_NAME . '</h2>';
    echo $page();
    echo '</div>';
}

function we_post($key, $default = '', $escape = false, $strip_tags = false) {
    return we_get_superglobal($_POST, $key, $default, $escape, $strip_tags);
}

function we_session($key, $default = '', $escape = false, $strip_tags = false) {
    return we_get_superglobal($_SESSION, $key, $default, $escape, $strip_tags);
}

function we_get($key, $default = '', $escape = false, $strip_tags = false) {
    return we_get_superglobal($_GET, $key, $default, $escape, $strip_tags);
}

function we_request($key, $default = '', $escape = false, $strip_tags = false) {
    return we_get_superglobal($_REQUEST, $key, $default, $escape, $strip_tags);
}

function we_get_superglobal($array, $key, $default = '', $escape = false, $strip_tags = false) {

    if (isset($array[$key])) {
        $default = $array[$key];

        if ($escape) {
            $default = mysql_real_escape_string($default);
        }

        if ($strip_tags) {
            $default = strip_tags($default);
        }
    }

    return $default;
}

add_action('plugins_loaded', 'we_loaded');





/*
 * 
 */

add_filter('wp_mail', 'we_wp_mail');

function we_wp_mail($mail) {
    $we_settings = we_get_settings();

    if ($send_as = $we_settings->header_send_as) {
        if ($send_as == 'html') {
            // add HTML head and footer to message

            ob_start();
            if (file_exists(TEMPLATEPATH . '/email/email_header.php'))
                include(TEMPLATEPATH . '/email/email_header.php');

            echo we_filter_html_markup($mail['message']);

            if (file_exists(TEMPLATEPATH . '/email/email_footer.php'))
                include(TEMPLATEPATH . '/email/email_footer.php');

            $mail['message'] = ob_get_contents();

            ob_end_clean();
        }
    }

    return $mail;
}

function we_filter_html_markup($markup) {

    // strange markup in Lost password email, removing < and >
    $pattern = '/<?(http:\/\/[a-z0-9\.\/?=&:\-%]+)>?/i';
    $replacement = '$1';
    $markup = preg_replace($pattern, $replacement, $markup);


    if ($markup != strip_tags($markup)) // theres HTML
        return $markup;


    // add links in <a> tag
    $pattern = '/<?(http:\/\/[a-z0-9\.\/?=&:\-%]+)>?/i';
    $replacement = '<a href="$1" target="_blank">$1</a>';
    $new_markup = preg_replace($pattern, $replacement, $markup);

    $new_markup = wpautop($new_markup);



    return $new_markup;
}

/*

  add_filter ("wp_mail_content_type", "we_mail_content_type");
  function we_mail_content_type($content_type){
  return 'text/html';
  }
  //add_filter ("wp_mail_from", "we_mail_from");
  //function we_mail_from() {
  //	return get_option('admin_email');
  //}
  //
  //add_filter ("wp_mail_from_name", "we_mail_from_name");
  //function we_mail_from_name() {
  //	return get_option('blogname');
  //}
 */
?>