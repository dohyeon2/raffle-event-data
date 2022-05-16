<?php
add_action('wp_ajax_raffle_event_make_announce', 'raffle_event_make_announce_callback');

//이벤트 발표하기 ajax
function raffle_event_make_announce_callback()
{
    $post_id = $_POST["pid"];
    $event_post = get_post($post_id);
    $announce_id = RaffleEvent_EventData::check_event_is_announced($post_id);
    if ($announce_id !== false) {
        echo wp_send_json_error([
            "mssg" => "이미 발표된 이벤트입니다.\n발표 게시물 편집으로 바로 이동할까요?\n",
            "data" => [
                "edit_url" => RaffleEvent_EventData::get_raffle_event_announce_edit_url($post_id)
            ]
        ], 409);
        wp_die();
        exit;
    }
    $raffle = RaffleEvent_EventData::make_raffle($post_id);
    $raffle_post_meta_array = array_map(function ($key, $value) {
        return [
            "nft_name" => $key,
            "user_name" => get_user_by("ID", $value)->display_name,
            "nft_wallet" => get_user_meta($value, "wallet_address", true)
        ];
    }, array_keys($raffle), array_values($raffle));
    $announce_id = wp_insert_post([
        "post_type" => "raffle_announce",
        "post_author" => 1,
        "post_parent" => $post_id,
        "post_title" => "(" . $event_post->post_title . ") 당첨자 발표",
        "post_content" => "(" . $event_post->post_title . ")의 당첨자를 발표합니다!
        당첨되신 분들 축하드리고 입력해주신 지갑으로 NFT 발송해드리겠삼",
        "meta_input" => [
            "caption" => "*축하드려영~~",
            "raffle_users" => json_encode($raffle_post_meta_array, JSON_UNESCAPED_UNICODE)
        ]
    ]);
    if (!$announce_id) {
        echo wp_send_json_error("발표 게시물을 생성하던 중 문제가 발생했습니다.", 500);
    }
    echo wp_send_json_success([
        "mssg" => "성공적으로 발표가 만들어졌습니다.\n발표 게시물 편집으로 바로 이동할까요?",
        "data" => [
            "edit_url" => RaffleEvent_EventData::get_raffle_event_announce_edit_url($post_id)
        ]
    ], 200);
    wp_die();
}
