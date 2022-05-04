<?php
new RaffleEvent_CustomEndpoint([
    "methods" => "POST",
    "path" => "/nft/group",
    "namespace" => "raffle_event/v1",
    "callback" => function ($request) {
        $params = $request->get_params();
        $headers = apache_request_headers();
        $post_title = $params["post_title"];
        $parent = $params["parent"];

        //본 페이지에서 요청된 것인지 판단
        if (preg_replace("/^https?:\/\//", "", $_SERVER["HTTP_ORIGIN"]) !== $headers["Host"]) {
            return new WP_REST_Response("잘못된 요청", 400);
        }

        //포스트 인서팅
        $post_id = wp_insert_post([
            "post_title" => $post_title,
            "post_type" => "raffle_nft_group",
            "post_status" => "publish",
            "post_parent" => $parent
        ]);
        if (!$post_id) {
            return new WP_REST_Response("포스트 생성 실패", 500);
        }

        $result = [
            "ID" => $post_id,
            "post_title" => $post_title,
            "edit_url" => get_edit_post_link($post_id)
        ];
        return new WP_REST_Response($result, 200);
    }
]);

new RaffleEvent_CustomEndpoint([
    "methods" => "DELETE",
    "path" => "/nft/group",
    "namespace" => "raffle_event/v1",
    "callback" => function ($request) {
        $params = $request->get_params();
        $headers = apache_request_headers();
        $post_id = @$params["ID"] ?: 0;

        //본 페이지에서 요청된 것인지 판단
        if (preg_replace("/^https?:\/\//", "", $_SERVER["HTTP_ORIGIN"]) !== $headers["Host"]) {
            return new WP_REST_Response("잘못된 요청", 400);
        }

        //포스트 딜리팅
        $result = wp_delete_post($post_id);
        if (!$result) {
            return new WP_REST_Response("포스트 삭제 실패", 500);
        }

        return new WP_REST_Response($result, 200);
    }
]);
