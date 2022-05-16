<?php

foreach (glob(dirname(__FILE__) . "/custom-post/*.php") as $value) {
    include_once $value;
}