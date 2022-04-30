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

//function to add custom media field
function custom_media_add_media_custom_field()
{
?>
    <div>test</div>
<?php
}
add_action('post-html-upload-ui', 'custom_media_add_media_custom_field');


add_filter('media_meta', function ($a, $b) {
    global $wpdb;
    $check_remain = @$wpdb->get_results(
        @$wpdb->prepare(
            "SELECT DISTINCT posts.ID
            FROM 
                $wpdb->posts AS posts,
                $wpdb->postmeta AS meta,
                $wpdb->postmeta AS meta2
            WHERE
                posts.post_type = %s AND
                meta.post_id = posts.ID AND
                meta2.post_id = posts.ID AND
                meta.meta_key = %s AND
                meta.meta_value = %s AND
                meta2.meta_key = %s AND
                meta2.meta_value = %s
            ",
            "nft_data",
            "owner",
            "0",
            "apt_type",
            str_replace("_", " ", $b->post_title)
        ),
        ARRAY_A
    );
    if (get_post_meta($b->ID, "data_type", true) === "nft_type") {
        return $a . "<div style='word-break:pre-wrap'>
        " . $b->post_title . "
        <b>남은 아이템 (총 : " . count($check_remain) . "개)</b><br/>
        " . implode(", ", $check_remain) . "
        </div>
        <div>
        <b>응모한 유저 리스트</b><br/>
        </div>";
    } else {
        return $a;
    }
}, 1, 2);

add_filter('raffle_event_custom_post_metadata', function ($data, $post) {
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
        ];
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
                case "due_type":
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
