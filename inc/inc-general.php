<?php

add_action('admin_enqueue_scripts', 'enqueue_style');

function enqueue_style()
{
    //스타일 및 js 등록
    wp_register_script('main-script', plugins_url('/assets/js/index.js', FILE), null, null, true);
    wp_enqueue_media();
    wp_enqueue_script('main-script');

    $screen = get_current_screen()->id;
    $filename = plugins_url("/assets/js/$screen.js", FILE);
    if (file_exists(PLUGIN_PATH . "/assets/js/$screen.js")) {
        wp_register_script("$screen-script", $filename, ["jquery"], null, true);
        wp_enqueue_media();
        wp_enqueue_script("$screen-script");
        wp_localize_script(
            "$screen-script",
            'ajax_object',
            array('ajaxurl' => admin_url('admin-ajax.php'))
        );
    }
}

add_filter('raffle_event_custom_post_metadata', function ($data, $post) {
    //커스텀 포스트 공통 메타데이타
    $user = wp_get_current_user();
    $newdata = [];
    $types = ["raffle_event_post", "media", "nft_data"];
    global $wpdb;
    $regexp = "-[^:]*:?" . $post["id"];
    $newdata['event'] = $wpdb->get_results("SELECT DISTINCT post.ID AS id FROM
                $wpdb->posts AS post,
                $wpdb->postmeta AS meta
            WHERE
                post.ID = meta.post_id AND
                meta.meta_key = 'nft_list' AND
                meta.meta_value REGEXP '$regexp'
        ", ARRAY_A);
    if (count($newdata['event']) > 0) {
        foreach ($newdata['event'] as $key => $value) {
            $eventpost = @get_post($value["id"]) ?: false;
            if (!$eventpost) {
                delete_post_meta($value["id"], 'nft_list');
            }
        }
        $event = get_post(@$newdata['event'][0]["id"]);
        $newdata['event'] = [
            "due_type" => get_post_meta($event->ID, "due_type", true),
            "condition" => get_post_meta($event->ID, "condition", true),
            "event_id" => $newdata['event'][0]["id"],
            "duplication" => get_post_meta($eventpost->ID, "duplication", true),
            "nft_list" => get_post_meta($eventpost->ID, "nft_list", true),
        ];
        $newdata["event"]["nft_list"] =  array_map(function ($x) {
            return preg_replace("/[^-]*-.*?:?(\d+)/", "$1", $x);
        }, $newdata["event"]["nft_list"]);
    }
    if ($post['type'] === "raffle_event_post") {
        if (count($post['categories']) > 0) {
            $newdata["status"] = get_category($post['categories'][0])->slug;
            $newdata["status"] = preg_replace("/.*-([^-]+)$/", "$1", $newdata["status"]);
        } else {
            $newdata["status"] =  false;
        }
    }
    if (in_array($post['type'], $types)) {
        foreach ($data as $key => $value) {
            switch ($key) {
                case "apt_type":
                    $value = $value[0] === "budong" ? "부동" : $value[0];
                    break;
                case "bg_img":
                case "char_img":
                    $value = wp_get_attachment_url($value[0]);
                    break;
                case "pariticipants_list":
                    if ($user->ID !== 0 && in_array($user->ID, $value)) {
                        $newdata["participated"] =  true;
                    }
                case "owner":
                    if ($value[0] * 1 !== 0) {
                        $newdata["soldout"] =  true;
                    } else {
                        $newdata["soldout"] =  false;
                    }
                case "end_date":
                case "end_time":
                case "end_time_int":
                case "full_count":
                case "nft_list_ids":
                case "start_date":
                case "start_time":
                case "start_time_int":
                case "due_type":
                    $value = $value[0];
                    break;
                case "nft_list":
                    $value = unserialize($value[0]);
                    break;
            }
            $newdata[$key] = $value;
        }
    }
    return $newdata;
}, 10, 2);
