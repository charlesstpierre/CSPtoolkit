<?php

function csp_meta_descriptions_page() {
    add_options_page(
            __('Meta Descriptions', 'csp'), __('Meta Descriptions', 'csp'), 'manage_options', 'meta_descriptions', 'csp_output_meta_descriptions_admin_interface');
}

add_action('admin_menu', 'csp_meta_descriptions_page');

function csp_output_meta_descriptions_admin_interface() {

    $post_types = get_post_types(array('public' => true,'has_archive'=>true), 'objects');

    //debug($post_types);

    ?>
<div class="wrap">
<h1><?php _e('Meta descriptions', 'csp') ?></h1>
<p><?php _e('Les métas descriptions seront insérés dans les pages d’archives de contenus. Ces méta descriptions sont essentiels au classement dans les moteurs de recherches.', 'csp') ?></p>
<form action="options-general.php?page=meta_descriptions" class="" method="POST">
<?php wp_nonce_field('csp_meta_description_' . date('YMj'), '_metadesc_nonce'); ?>

<table class="form-table">

        <tbody>
            <?php $_name = '_csp_meta_description_front_page';
            $desc = get_option($_name, get_bloginfo('description') );
            ?>
            <tr>
                <th><label for="<?php echo $_name ?>"><?php _e('Accueil', 'csp') ?></th>
                <td><textarea class="widefat" id="<?php echo $_name ?>" name="<?php echo $_name ?>"><?php echo $desc; ?></textarea></td>
            </tr>
            
            <?php $_name = '_csp_meta_description_home';
            $desc = get_option($_name, get_bloginfo('description') );
            ?>
            <tr>
                <th><label for="<?php echo $_name ?>"><?php _e('Blogue', 'csp') ?></th>
                <td><textarea class="widefat" id="<?php echo $_name ?>" name="<?php echo $_name ?>"><?php echo $desc; ?></textarea></td>
            </tr>
    <?php
    foreach ($post_types as $post_type => $pt_obj) {
        $_name = '_csp_meta_description_' . $post_type;
        $desc = get_option($_name, $pt_obj->description);
        ?>
            <tr>
                <th><label for="<?php echo $_name ?>"><?php echo $pt_obj->label ?></label></th>
                <td><textarea class="widefat" id="<?php echo $_name ?>" name="<?php echo $_name ?>"><?php echo $desc; ?></textarea></td>
            </tr>
        <?php
    }
    ?>
            </tbody>
    </table>
    
    <p><button type="submit" class="button-primary" ><?php _e('Save') ?></button></p>
</form>
</div>
    <?php
}




function csp_save_meta_descriptions() {
    if ( isset($_POST['_metadesc_nonce']) && wp_verify_nonce( filter_input(INPUT_POST,'_metadesc_nonce'), 'csp_meta_description_' . date('YMj') )){
        
        $success = false;
        $default_language = apply_filters('wpml_default_language','fr');
        
        
        foreach ($_POST as $key=>$value){
            if ( 0 === strpos($key,'_csp_meta_description_') && !empty($value)){
                
                $what = $name = substr($key,22);
                
                if ('home'===$what){
                    $name = __('Accueil','csp');
                }elseif ('blog'===$what){
                    $name = __('Blogue','csp');
                }else{
                    $post_type = get_post_type($what);
                    if ($post_type){
                        $name = $post_type->labels->archives ;
                    }
                    
                }
                
                $desc = wp_strip_all_tags( filter_input(INPUT_POST,$key), true);
                $check = update_option($key, $desc);
                
                if ($check){
                    $success = true;
                    do_action( 'wpml_register_single_string','Meta Description', $name, $desc, false, $default_language);
                }
                
                
            }
        }
        add_action('admin_notices','csp_meta_description_save_success');
    }
    
}

add_action('admin_init', 'csp_save_meta_descriptions');

function csp_meta_description_save_success() {
    ?>
<div class="notice notice-success">
    <p><?php _e('Les méta descriptions ont été enregistrées avec success.','csp') ?></p>
</div>
        <?php
}




function csp_meta_description_to_post_types( $post_type, $post_type_object) {
    
    global $wp_post_types;

    $desc_ori = get_option('_csp_meta_description_'.$post_type);
    
    $desc = apply_filters('wpml_translate_single_string',$desc_ori, 'Meta Description', $wp_post_types[$post_type]->labels->archives );
    
    if ($desc){
        $wp_post_types[$post_type]->description = $desc;
    }
    
    
}
add_action( 'registered_post_type', 'csp_meta_description_to_post_types',10,2 );