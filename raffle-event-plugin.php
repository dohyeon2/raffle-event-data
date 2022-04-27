<?php

define("FILE", __FILE__);
define("PLUGIN_PATH", plugin_dir_path(__FILE__));
/**
 * Plugin Name: 래플이벤트 플러그인
 * Description: 래플이벤트 데이터 저장을 위한 사항을 제공하는 플러그인입니다
 */

foreach (glob(dirname(__FILE__) . "/class/*.php") as $key => $value) {
    require_once $value;
}
foreach (glob(dirname(__FILE__) . "/inc/*.php") as $key => $value) {
    include_once $value;
}

function raffle_event_init_plugin()
{
    //카테고리 등록
    function insert_category($catarr)
    {
        if (!($id = category_exists($catarr["cat_name"], key_exists("category_parent", $catarr) ? $catarr["category_parent"] :  null))) {
            return wp_insert_category($catarr, true);
        } else {
            return $id;
        }
    }
    $parent = insert_category([
        "cat_name" => "래플이벤트",
        "category_description" => "래플 이벤트 관리를 위한 최상위 카테고리",
        "category_nicename" => "hwasum-raffle-event",
    ]);
    $catarr = [
        [
            "cat_name" => "진행중",
            "category_description" => "이벤트 진행중 카테고리",
            "category_nicename" => "raffle-event-proceed",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "예정된",
            "category_description" => "이벤트 예정 카테고리",
            "category_nicename" => "raffle-event-upcomming",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "종료된",
            "category_description" => "이벤트 종료 카테고리",
            "category_nicename" => "raffle-event-end",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "발표",
            "category_description" => "이벤트 발표 카테고리",
            "category_nicename" => "raffle-event-announce",
            "category_parent" => $parent
        ],
    ];
    foreach ($catarr as $value) {
        insert_category($value);
    }
}
register_activation_hook(__FILE__, 'raffle_event_init_plugin');
