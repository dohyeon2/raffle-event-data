<?php

new raffe_event_custom_endpoint([
    "methods" => "GET",
    "path" => "/announce(?:\/(?P<id>\d+))?",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        //이벤트 발표 불러오기 rest api
        $params = $request->get_params();
        $id = $params["id"] ?: null;
        $raffle_users_page = $params["raffle_users_page"] ?: 1;
        $raffle_users_per_page = $params["raffle_users_per_page"] ?: 10;
        $parent_id = $params["parent_id"] ?: null;
        $attr = [];
        $attr["post_parent"] = $parent_id;
        $attr["p"] = $id;
        $attr["post_type"] = "raffle_announce";
        $query = new WP_Query($attr);
        $posts = array_map(function ($v) {
            $author = get_user_by("ID", $v->post_author);
            $profile_image_id = get_user_meta($v->post_author, "_user_img_id", true);
            $author = [
                "data" => [
                    "display_name" => $author->data->display_name,
                    "profile_image" => wp_get_attachment_url($profile_image_id)
                ]
            ];
            return [
                "ID" => $v->ID,
                "author" => $author,
                "post_title" => $v->post_title,
                "post_date" => $v->post_date,
            ];
        }, $query->posts);
        $result = [
            "whole_items" => $query->found_posts,
            "max_page" => $query->max_num_pages,
            "posts" => $posts,
        ];
        if ($id) {
            $post = get_post($id);
            $raffle_user_list = json_decode(get_post_meta($id, "raffle_users", true));
            usort($raffle_user_list, function ($a, $b) {
                if (!(bool)$b->user_name) {
                    return  -1;
                } else {
                    return 1;
                }
            });
            $raffle_user_list_sliced = array_slice($raffle_user_list, ($raffle_users_page - 1) * $raffle_users_per_page, $raffle_users_per_page);
            $result["post"] = [
                "ID" => $id,
                "author" => $posts[0]["author"],
                "post_title" => $post->post_title,
                "post_date" => $post->post_date,
                "post_content" => $post->post_content,
                "caption" => get_post_meta($post->ID, 'caption', true),
                "raffle_users" => $raffle_user_list_sliced,
                "raffle_users_whole_items" => count($raffle_user_list),
                "raffle_users_max_page" => ceil(count($raffle_user_list) / $raffle_users_per_page),
            ];
            unset($result["posts"]);
        }
        return new WP_REST_Response($result);
    }
]);
