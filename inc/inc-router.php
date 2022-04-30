<?php
foreach (glob(dirname(__FILE__) . "/routes/*.php") as $value) {
    include_once $value;
}
