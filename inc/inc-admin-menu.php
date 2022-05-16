<?php

// 메뉴 등록
add_action('admin_menu', 'my_custom_menu_page');

/**
 * Display a custom menu page
 */
function my_custom_menu_page()
{
    add_menu_page("래플이벤트", "래플이벤트", "manage_options", "raffle");
    add_menu_page("래플이벤트 관리", "래플이벤트 관리", "manage_options", "raffle_options",  function () {
        include_once plugin_dir_path(FILE) . "/admin-pages/raffle_event.php";
    });
}
