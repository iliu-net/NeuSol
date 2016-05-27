<?php

class Controller {
  protected $db;
  function __construct($f3) {
    $db=new DB\SQL(
       $f3->get('db_dns'),
       $f3->get('db_user'),
       $f3->get('db_pass')
    );	
    $this->db = $db;
  }
}
