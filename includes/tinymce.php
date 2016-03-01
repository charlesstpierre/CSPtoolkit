<?php
// Exit if accessed directly
if (!defined('ABSPATH')) { exit; }

function customize_tiny_mce($in){
    // add &nbsp
    $in['entities'] = '8201,thinsp,160,nbsp,38,amp,60,lt,62,gt';
    $in['entity_encoding'] = 'named';
    //debug_log($in);
    
    return $in;
}
add_filter('tiny_mce_before_init','customize_tiny_mce',10000);
