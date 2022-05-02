<?php
var_dump("listening");

exec("git pull", $output);

var_dump($output);
