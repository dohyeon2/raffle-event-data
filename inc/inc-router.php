<?php
foreach (glob(dirname(__FILE__) . "/routes/*.php") as $value) {
    include_once $value;
}

add_action('rest_api_init', function () {
    //부동이 budong으로 입력된 경우 한글로 변경하는 구문
    register_rest_field("attachment", 'title', array(
        'get_callback' => function ($data) {
            if ($data['title']['rendered'] === "budong") {
                $data['title']['rendered'] = "부동";
            }
            return $data['title'];
        },
    ));
    register_rest_field("attachment", 'metadata', array(
        'get_callback' => function ($data) {
            $data = get_post_meta($data['id']);
            unset($data['wallet_address']);
            return $data;
        },
    ));
});
