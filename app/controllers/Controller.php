<?php

function env_expand($inp) {
  if (strpos($inp,'$') === false) return $inp;
  $cnv = array();
  foreach (getenv() as $k=>$v) {
    $cnv['${'.$k.'}'] = $v;
  }
  return strtr($inp,$cnv);
}

class Controller {
  protected $db;
  function __construct($f3) {
    $db_dns = env_expand($f3->get('db_dns'));
    $db_user = env_expand($f3->get('db_user'));
    $db_pass = env_expand($f3->get('db_pass'));
    $db=new DB\SQL(
       $db_dns,
       $db_user,
       $db_pass
    );
    $this->db = $db;
  }
}

