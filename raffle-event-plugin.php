<?php

/**
 * Plugin Name: 래플이벤트 플러그인
 * Description: 래플이벤트 데이터 저장을 위한 사항을 제공하는 플러그인입니다
 */

define("FILE", __FILE__);
define("PLUGIN_PATH", plugin_dir_path(__FILE__));
date_default_timezone_set('Asia/Seoul');


foreach (glob(dirname(__FILE__) . "/tools/*.php") as $key => $value) {
    include_once $value;
}
foreach (glob(dirname(__FILE__) . "/class/*.php") as $key => $value) {
    require_once $value;
}
foreach (glob(dirname(__FILE__) . "/inc/*.php") as $key => $value) {
    include_once $value;
}
