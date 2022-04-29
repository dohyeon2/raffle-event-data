<?php

// 메뉴 등록
add_action('admin_menu', 'my_custom_menu_page');

/**
 * Display a custom menu page
 */
function my_custom_menu_page()
{
    add_menu_page("래플이벤트", "래플이벤트", "manage_options", "raffle");
}

foreach (glob(dirname(__FILE__) . "/custom-post/*.php") as $value) {
    include_once $value;
}