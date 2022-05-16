<?php
foreach (glob(dirname(__FILE__) . "/ajax/*.php") as $value) {
    include_once $value;
}