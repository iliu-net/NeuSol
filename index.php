<?php
date_default_timezone_set('UTC');
require(__DIR__.'/vendor/autoload.php');

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
# Fixed issues with nginx...
if (isset($_SERVER['SERVER_NAME']) && ($i = strpos($_SERVER['SERVER_NAME'], ':')) !== false) {
  $_SERVER['SERVER_PORT'] = substr($_SERVER['SERVER_NAME'],$i+1);
  $_SERVER['SERVER_NAME'] = substr($_SERVER['SERVER_NAME'],0,$i);
}

$f3 = Base::instance();
//~ $f3=require('vendor/bcosca/fatfree-core/base.php');
$f3->config('config/config.ini');

define('APPNAME',basename(dirname($_SERVER['SCRIPT_NAME'])));
if (substr(APPNAME,-3) == 'Dev') {
  define('NONPROD',1);
  $f3->config('config/nonprod-config.ini');
}
$xcfg = implode('/',array_slice(explode('/',realpath(__FILE__)),0,3)) . '/config.ini';
if (is_readable($xcfg)) $f3->config($xcfg);
unset($xcfg);

$f3->config($routes);

$f3->run();
