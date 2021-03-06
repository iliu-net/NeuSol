<?php
date_default_timezone_set('UTC');

if (php_sapi_name() == 'cli') {
  define('WORKING_DIR',getcwd());
  chdir(__DIR__);
  $routes='config/cli_routes.ini';
} else {
  $routes='config/routes.ini';
}

$f3=require('lib/f3/base.php');
$f3->config('config/config.ini');

define('NEUHOME',basename(dirname(realpath(__FILE__))));
if (NEUHOME == 'NeuDev') {
  define('NONPROD',1);
  $f3->config('config/nonprod-config.ini');
}

$f3->config($routes);

$f3->run();
