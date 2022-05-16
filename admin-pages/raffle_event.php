<?php

$raffle_event_config_pages = glob(plugin_dir_path(FILE) . "admin-pages/raffle_event_config/*.php");

$page_list = [];
foreach ($raffle_event_config_pages as $value) {
    $key = basename($value);
    $page_list[$key] = $value;
}
switch ($_GET["config_page"]) {
    case "":
        include_once $page_list["default.php"];
        break;
    default:
        include_once $page_list[$_GET["page"] . ".php"];
}
