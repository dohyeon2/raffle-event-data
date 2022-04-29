<?php
add_action('admin_enqueue_scripts', 'enqueue_style');

function enqueue_style()
{
    wp_register_script('main-script', plugins_url('/assets/js/index.js', FILE), null, null, true);
    wp_enqueue_media();
    wp_enqueue_script('main-script');

    $screen = get_current_screen()->id;
    $filename = plugins_url("/assets/js/$screen.js", FILE);
    if (file_exists(PLUGIN_PATH . "/assets/js/$screen.js")) {
        wp_register_script("$screen-script", $filename, ["jquery"], null, true);
        wp_enqueue_media();
        wp_enqueue_script("$screen-script");
    }
}

add_filter('rest_pre_serve_request',function($served, $result, $request, $server){
    do_action("raffle_event_rest_pre_serve_request", $served, $result, $request, $server);
    return $served;
},10,4);