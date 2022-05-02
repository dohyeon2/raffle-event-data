<?php
var_dump("listening");

exec("bash ./gitpull.sh", $output);

var_dump($output);
