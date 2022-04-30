<?php
//nft응모 데이터 반영
new raffe_event_custom_endpoint([
    "methods" => "POST",
    "path" => "/nft/(?P<id>\d+)",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        $params = $request->get_params();
        $id = $params['id'];
        $data = [];
        $data["participants_list"] = $params['user'];
        $data["participants_item"] = $params['user'] . "=" . $params['item_id'];
        $data["wallet_address"] = $params['user'] . "=" . $params['wallet_address'];
        $data["participates_date"] = $params['user'] . "=" . (time() + (HOUR_IN_SECONDS * 9));

        global $wpdb;
        $regexp = "-[^:]*:?" . $params["item_id"];
        $event = $wpdb->get_results("SELECT DISTINCT post.ID AS id FROM
                $wpdb->posts AS post,
                $wpdb->postmeta AS meta
            WHERE
                post.ID = meta.post_id AND
                meta.meta_key = 'nft_list' AND
                meta.meta_value REGEXP '$regexp'
        ", ARRAY_A);
        if (count($event) > 0) {
            foreach ($event as $key => $value) {
                $eventpost = @get_post($value["id"]) ?: false;
                if (!$eventpost) {
                    delete_post_meta($value["id"], 'nft_list');
                }
            }
            $eventpost = get_post(@$event[0]["id"]);
            $event = [
                "due_type" => get_post_meta($eventpost->ID, "due_type", true),
                "condition" => get_post_meta($eventpost->ID, "condition", true),
            ];
            switch ($event["condition"]) {
                case "shark_in_mars":
                    $complete_condition_user_condition = "\"userId\":" . $params['user'];
                    $complete_condition = $wpdb->get_results(
                        "SELECT DISTINCT
                        post.ID
                    FROM
                        (
                            SELECT 
                            ID, post_title AS title, post_excerpt AS excerpt FROM $wpdb->posts p
                            JOIN $wpdb->term_relationships tr ON (p.ID = tr.object_id)
                            JOIN $wpdb->term_taxonomy tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                            JOIN $wpdb->terms t ON (tt.term_id = t.term_id)
                            WHERE p.post_type='post'
                            AND p.post_status = 'publish'
                            AND tt.taxonomy = 'category'
                            AND t.slug = 'hwasum-game'
                        ) AS post,
                        $wpdb->postmeta AS meta,
                        $wpdb->postmeta AS meta2
                    WHERE
                        meta.post_id = post.ID AND
                        meta2.post_id = post.ID AND
                        meta.meta_key = 'numSharkInMars' AND
                        meta.meta_value >= 1 AND
                        meta2.meta_key = 'userDatas' AND
                        meta2.meta_value REGEXP '$complete_condition_user_condition'
                    LIMIT 1
                    ",
                        ARRAY_A
                    ) ?: [];
                    if (count($complete_condition) === 0) {
                        return new WP_REST_Response("응모에 실패했습니다.", 400);
                    }
                    if ($event["due_type"] === "full") {
                        update_post_meta($id, "owner", $params['user']);
                    }
                    break;
                case "-":
                default:
                    break;
            }
        }

        foreach ($data as $key => $value) {
            if ($exist = in_array($value, get_post_meta($id, $key))) {
                return new WP_REST_Response("이미 응모한 유저입니다.", 409);
            }
            add_post_meta($id, $key, $value);
        }
        return new WP_REST_Response("정상적으로 응모 되었습니다.", 200);
    },
]);

//nft응모 현황 모두 불러오기
new raffe_event_custom_endpoint([
    "methods" => "GET",
    "path" => "/participants",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        global $wpdb;
        $params = $request->get_params();
        $page = @$params["page"] * 1 ?: 1;
        $user = wp_get_current_user()->ID ?: 0;
        $user_condition = $user ? "AND meta.meta_value = " . $user : "";
        $data_per_page = @$params["data_per_page"] * 1 ?: 10;
        $offset = ($page - 1) * $data_per_page;
        $results = $wpdb->get_results("SELECT DISTINCT posts.post_type AS 'type', posts.ID AS nft_id, meta.meta_value AS participant_id, meta3.meta_value AS 'wallet_address', meta4.meta_value AS 'participates_date', posts.post_title AS 'title', post2.post_title AS 'event_title', post2.ID AS 'event_id'
        FROM 
            $wpdb->postmeta AS meta,
            $wpdb->postmeta AS meta2,
            $wpdb->postmeta AS meta3,
            $wpdb->postmeta AS meta4,
            $wpdb->postmeta AS meta5,
            $wpdb->posts AS posts,
            $wpdb->posts AS post2
        WHERE
            meta.post_id = posts.ID AND
            meta3.post_id = posts.ID AND
            meta2.post_id = posts.ID AND
            meta4.post_id = posts.ID AND
            meta5.post_id = post2.ID AND
            meta.meta_key = 'participants_list' AND
            (
                (meta2.meta_key = 'data_type' AND meta2.meta_value = 'nft_type') OR
                (posts.ID = meta.post_id AND posts.post_type = 'nft_data')
            ) AND
            meta3.meta_key = 'wallet_address' AND
            meta3.meta_value REGEXP concat('^',meta.meta_value,'=') AND
            meta4.meta_key = 'participates_date' AND 
            meta4.meta_value REGEXP concat('^',meta.meta_value,'=') AND
            meta5.meta_key = 'nft_list' AND meta5.meta_value REGEXP concat(posts.ID,'\";')
            $user_condition
        ORDER BY participates_date DESC
        LIMIT $data_per_page
        OFFSET $offset
        ", ARRAY_A);
        $ids = $wpdb->get_results("SELECT DISTINCT posts.ID, meta.meta_value
               FROM 
                   $wpdb->postmeta AS meta,
                   $wpdb->postmeta AS meta2,
                   $wpdb->posts AS posts
               WHERE
                   meta.post_id = posts.ID AND
                   meta2.post_id = posts.ID AND
                   meta.meta_key = 'pariticipants_list' AND
                   (
                       (meta2.meta_key = 'data_type' AND meta2.meta_value = 'nft_type') OR
                       (posts.ID = meta.post_id AND posts.post_type = 'nft_data')
                   )
                   $user_condition
        ", ARRAY_A);
        $new_result = [];
        $new_result["data"] = [];
        $new_result["whole_items"] = count($ids);
        $new_result["page"] = $page;
        $new_result["data_per_page"] = $data_per_page;
        $new_result["max_page"] = ceil($new_result["whole_items"] / $data_per_page);
        foreach ($results as $key => $value) {
            extract($value);
            $value["display_name"] = get_user_by("id", $participant_id)->data->display_name ?: "없는 유저입니다.";
            $value["wallet_address"] = preg_replace("/^" . $participant_id . "=/", "", $value["wallet_address"]) ?: "주소 정보가 없습니다.";
            $value["participates_date"] = preg_replace("/^" . $participant_id . "=/", "", $value["participates_date"]) ?: "참여 일자 정보가 없습니다.";
            $value["participates_date"] = date("Y-m-d H:i:s", $value["participates_date"] * 1);
            $value["title"] = $value["title"] === "budong" ? "부동" : $value["title"];
            $value["event_status"] = get_cat_name(@wp_get_post_categories($value["event_id"])[0]) ?: "ERROR";
            $value["is_announced"] = get_post_meta($value['event_id'], "is_announced", true) ?: false;
            switch ($value["event_status"]) {
                case "종료된":
                    $value["event_status"] = $value["is_announced"] ? "종료" : "추첨중";

                case "예정된":
                    $value["event_status"] = "예정";
                case "진행중":
                default:
                    $value["event_status"] = $value["event_status"];
                    if (get_post_meta($value["event_id"], "due_type", true) === "full") {
                        if ($user * 1 === @get_post_meta($value["nft_id"], "owner", true) * 1) {
                            $value["event_status"] = "분양 성공!";
                        }
                    }
            }
            $new_result["data"][$key] = $value;
        }
        return new WP_REST_Response($new_result, 200);
    }
]);
