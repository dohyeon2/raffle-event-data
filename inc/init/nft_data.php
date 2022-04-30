<?php

function nft_type_img_insert()
{
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-includes/pluggable.php');
    $files = glob(plugin_dir_path(FILE) . "assets/img/*.svg");
    $temp_folder = plugin_dir_path(FILE) . "assets/img/temp";
    foreach ($files as $key => $value) {
        if (!is_dir($temp_folder)) {
            mkdir($temp_folder);
        }
        preg_match("/\/(([^\/]*)\.[^.]*$)/", $value, $match);
        $filename = $match[1];
        $filename_without_ext = trim($match[2]);
        $tempfile = $temp_folder . $match[0];
        $copy = false;
        if (!file_exists($tempfile)) {
            $copy = copy($value, $tempfile);
        }
        $file = array(
            'name' => $filename,
            'tmp_name' => $tempfile,
        );
        global $wpdb;
        $check_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts 
                WHERE post_title = '$filename_without_ext' 
                AND post_type = 'attachment'"
            )
        );
        $id = @$check_exists[0]->ID ?: 0;
        $file_exists = count($check_exists) > 0;
        $attr = [
            "post_title" => $filename_without_ext,
            "meta_input" => [
                "data_type" => "nft_type",
                "owner" => 0
            ]
        ];
        if (!$file_exists) {
            $media = media_handle_sideload($file, $id, null, $attr);
        } else {
            wp_insert_post([
                "ID" => $id,
                "meta_input" => $attr["meta_input"]
            ]);
        }
    }
}

function nft_data_insert()
{
    $fp = fopen(plugin_dir_path(FILE) . "/assets/data/nft_list.csv", "r");
    $before = [];
    while (!feof($fp)) {
        $line = fgets($fp);
        $data = explode(",", $line);
        if ($data[3] === "") {
            $data[3] = $before[3];
        }
        $before = $data;
        if ($data[4] === "" || $data[4] === null || !isset($data[4]) || empty($data[4])) {
            continue;
        }
        $id = post_exists($data[4]);
        global $wpdb;
        $data[3] = str_replace("부동", "budong", $data[3]);
        $typename = str_replace(" ", "_", $data[3]);
        $check_exists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT ID FROM $wpdb->posts 
                WHERE post_title = '$typename' 
                AND post_type = 'attachment'"
            )
        );
        $thumb_id = @$check_exists[0]->ID ?: 0;
        $inserted_post_id = wp_insert_post([
            "ID" => $id,
            "post_title" => $data[4],
            "post_status" => "publish",
            "post_type" => "nft_data",
            "meta_input" => [
                "apt_type" => trim($data[3]),
                "owner" => 0,
            ],
        ]);
        set_post_thumbnail($inserted_post_id, $thumb_id);
    }
    fclose($fp);
}
