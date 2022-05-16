<?php
//nft응모 api
new raffe_event_custom_endpoint([
    "methods" => "POST",
    "path" => "/nft",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        $params = $request->get_params();
        $user = wp_get_current_user();
        $user_id = $user->ID;
        $item_id = $params['item_id'];
        $default_complete_message = "화섬 아파트 NFT 래플 이벤트 응모가 완료되었습니다!\n이벤트 발표는 다음주 목요일에 홈페이지에 공개됩니다 : )";
        $complete_message = $default_complete_message;
        $itemname = @get_post($item_id)->post_title;
        $wallet_address = @get_user_meta($user_id, "wallet_address", true);
        if (!$wallet_address) {
            return new WP_REST_Response("지갑 주소 정보가 없습니다.", 404);
        }
        if (!$user_id) {
            return new WP_REST_Response("유저를 찾을 수 없습니다.", 404);
        }
        $data = [];
        $data["participants_list"] = $user_id;
        $data["participates_date"] = $user_id . "=" . time();

        global $wpdb;
        $regexp = "-[^:]*:?" . $item_id;
        $event = $wpdb->get_results("SELECT DISTINCT post.ID AS id FROM
                $wpdb->posts AS post,
                $wpdb->posts AS group_post,
                $wpdb->postmeta AS meta
            WHERE
                ( post.ID = meta.post_id AND
                post.post_type = 'raffle_event_post' AND
                meta.meta_key = 'nft_list' AND
                meta.meta_value REGEXP '$regexp' )
        ", ARRAY_A);
        if (count($event) === 0) {
            $event = $wpdb->get_results("SELECT DISTINCT group_post.post_parent AS id FROM
            $wpdb->posts AS group_post,
            $wpdb->postmeta AS meta
                WHERE
                ( group_post.ID = meta.post_id AND
                meta.meta_key = 'nft_list' AND
                    meta.meta_value REGEXP '$regexp' )
            ", ARRAY_A);
        }
        if (count($event) > 0) {
            $eventpost = array_filter($event, function ($value, $keys) {
                $eventpost = @get_post($value["id"]) ?: false;
                if (!$eventpost) {
                    delete_post_meta($value["id"], 'nft_list');
                    return false;
                } else {
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH);
            $eventpost = $eventpost[0];
            $event_id = $eventpost['id'];
            $event_instance = new RaffleEvent_EventData($event_id);
            $event_instance->update_event_status();
            $event_status = $event_instance->status;
            if (!$event_status) {
                return new WP_REST_Response("이벤트가 없습니다.", 400);
            }
            if ($event_status !== "raffle-event-proceed") {
                return new WP_REST_Response("이벤트가 진행중이 아닙니다.", 400);
            }
            $event = [
                "due_type" => get_post_meta($event_id, "due_type", true),
                "condition" => get_post_meta($event_id, "condition", true),
                "duplication" => get_post_meta($event_id, "duplication", true),
                "nft_list" => get_post_meta($event_id, "nft_list", true),
            ];
            if (get_post_meta($item_id, "owner", true) * 1 !== 0) {
                return new WP_REST_Response("이미 분양된 아이템입니다.", 409);
            }
            switch ($event["condition"]) {
                case "shark_in_mars":
                    $complete_condition_user_condition = "\"userId\":" . $user_id;
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
                            WHERE p.post_type = 'post'
                            AND p.post_status IN ('publish','draft')
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
                        return new WP_REST_Response("응모에 실패했습니다.", 409);
                    }
                    if ($event["duplication"] === "0" && in_array($item_id, $event["nft_list"])) {
                        return new WP_REST_Response("중복참여금지", 400);
                    }
                    $complete_message = "떡상어를 무사히 화성에 보낸 것이 확인되어
                    $itemname 분양이 완료되었습니다! 축하드려요~ :D";
                    break;
                case "-":
                default:
                    break;
            }
        } else {
            return new WP_REST_Response("이벤트에 등록되지 않은 아이템입니다.", 400);
        }
        //10번 이벤트 참여했는지 확인
        if ($event["due_type"] !== "full") {
            $count = RaffleEvent_EventData::get_all_participates_count_of_user_in_event($user_id, $event_id);
            if ($count >= 10) {
                return new WP_REST_Response("이벤트에 10번이상 참여해 해당 이벤트에 다시 참여할 수 없습니다.", 409);
            }
        }
        foreach ($data as $key => $value) {
            if ($exist = in_array($value, get_post_meta($item_id, $key))) {
                return new WP_REST_Response("이미 응모한 유저입니다.", 409);
            }
            add_post_meta($item_id, $key, $value);
        }
        if ($event["due_type"] === "full") {
            update_post_meta($item_id, "owner", $user_id);
            if ($complete_message === $default_complete_message) {
                $complete_message = "분양에 성공했습니다.";
            }
        }
        return new WP_REST_Response($complete_message, 200);
    },
]);

//nft응모 현황 모두 불러오기 api
new raffe_event_custom_endpoint([
    "methods" => "GET",
    "path" => "/participants",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        global $wpdb;
        $params = $request->get_params();
        $page = @$params["page"] * 1 ?: 1;
        $user = wp_get_current_user()->ID ?: 0;
        $event_id = $params['event_id'] * 1 ?: 0;
        $nft_list = $event_id ? RaffleEvent_EventData::get_all_nft_ids($event_id) : [];
        $event_items_sql = $event_id ? 'AND posts.ID IN (' . implode(",", $nft_list) . ')' : "";
        $user_condition = $user ? "AND meta.meta_value = " . $user : "";
        $user_regexp = "AND meta2.meta_value REGEXP concat('^',meta.meta_value,'=.*')";
        $data_per_page = @$params["data_per_page"] * 1 ?: 10;
        $offset = ($page - 1) * $data_per_page;
        $from_to_where_query = $wpdb->prepare("FROM 
            $wpdb->postmeta AS meta,
            $wpdb->postmeta AS meta2,
            $wpdb->posts AS posts
        WHERE
            meta.post_id = posts.ID AND
            meta2.post_id = posts.ID AND
            meta.meta_key = 'participants_list' AND
            meta2.meta_key = 'participates_date'
            $user_condition
            $event_items_sql
            $user_regexp
        ",);
        $results = $wpdb->get_results("SELECT DISTINCT posts.ID AS 'nft_id', posts.post_title AS 'title', meta.meta_value AS 'participant_id', REGEXP_REPLACE(meta2.meta_value,'^.*=','') AS 'participates_date'
            $from_to_where_query
            ORDER BY participates_date DESC
            LIMIT $data_per_page
            OFFSET $offset
        ", ARRAY_A);
        $count = $user ? count($wpdb->get_results("SELECT posts.ID
        $from_to_where_query
        ", ARRAY_A)) : RaffleEvent_EventData::get_all_participates_count_of_event($event_id);
        $new_result = [];
        $new_result["data"] = [];
        $new_result["whole_items"] = $count;
        $new_result["page"] = $page;
        $new_result["data_per_page"] = $data_per_page;
        $new_result["max_page"] = ceil($new_result["whole_items"] / $data_per_page);
        foreach ($results as $key => $value) {
            extract($value);
            $value["test"] = $user_regexp;
            $user = get_user_by("id", $participant_id);
            $value["display_name"] = $user->data->display_name ?: "없는 유저입니다.";
            $value["wallet_address"] = get_user_meta($participant_id, "wallet_address", true);
            $value["title"] = $title ?: get_post($nft_id)->post_title;
            $value["participates_date"] = date("Y-m-d H:i:s", $participates_date);
            $value["event_id"] = RaffleEvent_EventData::get_event_id_from_item_id($value["nft_id"]);
            $value["event_title"] = get_post($value["event_id"])->post_title;
            $value["event_link"] = RaffleEvent_EventData::get_event_link_by_id($value["event_id"]);
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

//nft wallet 정보 변경 api
new raffe_event_custom_endpoint([
    "methods" => "POST",
    "path" => "/walletaddress",
    "namespace" => "raffle_event/v1",
    "endpoint_callback" => function ($request) {
        $params = $request->get_params();
        $input = $params["input"];
        $user = wp_get_current_user();
        if ($user->ID === 0) return new WP_REST_Response("유저를 찾을 수 없습니다.", 404);
        $update = update_user_meta($user->ID, 'wallet_address', $input);
        if (!$update && get_user_meta($user->ID, 'wallet_address', true) !== $input) return new WP_REST_Response("등록에 실패했습니다.", 500);
        return new WP_REST_Response($user, 200);
    }
]);
