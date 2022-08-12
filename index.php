<?php
date_default_timezone_set('UTC');

if (php_sapi_name() == 'cli') {
  define('WORKING_DIR',getcwd());
  chdir(__DIR__);
  $routes='config/cli_routes.ini';
} else {
  $routes='config/routes.ini';
}

# Add reverse proxy support...
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
  if (strpos($_SERVER['HTTP_X_FORWARDED_HOST'],':')) {
    list($_SERVER['SERVER_NAME'],$_SERVER['SERVER_PORT']) = explode(':',$_SERVER['HTTP_X_FORWARDED_HOST'],2);
  } else {
    $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_X_FORWARDED_HOST'];
    $_SERVER['SERVER_PORT'] = 80;
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
      if ($_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') $_SERVER['SERVER_PORT'] = 443;
    }
  }
}
$f3=require('submodules/fatfree-core/base.php');
$f3->config('config/config.ini');

define('NEUHOME',basename(dirname(realpath(__FILE__))));
if (NEUHOME == 'NeuDev') {
  define('NONPROD',1);
  $f3->config('config/nonprod-config.ini');
}

$f3->config($routes);

$f3->run();
