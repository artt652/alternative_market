<?php

ob_start();
Define('ALLOW_RUNNING_WITH_ERRORS', 1);
chdir(dirname(__FILE__) . '/../../');

include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");

Define('WAIT_FOR_MAIN_CYCLE', 0);
set_time_limit(0);

include_once("./load_settings.php");
include_once(DIR_MODULES . "home_market/home_market.class.php");

$name = gr('name');

$mkt = new home_market();
$mkt->exportModule($name);
