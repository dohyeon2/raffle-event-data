<?php

foreach(glob(plugin_dir_path(FILE)."/inc/init/*.php") as $value){
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
            "category_description" => "1 이벤트 진행중 카테고리",
            "category_nicename" => "raffle-event-proceed",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "예정된",
            "category_description" => "2 이벤트 예정 카테고리",
            "category_nicename" => "raffle-event-upcomming",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "종료된",
            "category_description" => "3 이벤트 종료 카테고리",
            "category_nicename" => "raffle-event-end",
            "category_parent" => $parent
        ],
        [
            "cat_name" => "발표",
            "category_description" => "4 이벤트 발표 카테고리",
            "category_nicename" => "raffle-event-announce",
            "category_parent" => $parent
        ],
    ];
    foreach ($catarr as $value) {
        insert_category($value);
    }
}
register_activation_hook(FILE, 'raffle_event_init_plugin');
