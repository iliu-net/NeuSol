<?php
$cols = [
   'ACCOUNT','CATEGORY','CATGRP','DATE','XID','DESCRIPTION','AMOUNT','TEXT','DETAIL','NCOLS'
];

$k=0;
foreach ($cols as $j) {
   define('CN_'.$j,$k++);
}
unset($j,$k,$cols);

interface ImporterInterface {
  static public function detect($f3,$name,$file);
  static public function import($f3,$name,$file,$dacct=NULL);
}

abstract class BaseImport {
  static public function row_uuid($row) {
    return implode(':',[$row[CN_DATE],$row[CN_AMOUNT],$row[CN_XID]]);
  }
  static public function init($f3,$db) {
    $catDAO = new Category($db);
    $dict = $catDAO->listSname();
    $f3->set('categories_short',$dict);
    foreach ($dict as $i=>$j) {
      if (defined('CC_'.$j)) continue;
      define('CC_'.$j,$i);
    }
    $f3->set('categories_long',$catDAO->listDesc());
    $acctsDAO = new Acct($db);
    $f3->set('accounts',$acctsDAO->all());
    $f3->set('accounts_long',$acctsDAO->listDesc());
    $f3->set('accounts_numbers',$acctsDAO->listNumbers());
    foreach ($acctsDAO->listSname() as $i=>$j) {
      if (defined('AC_'.$j)) continue;
      define('AC_'.$j,$i);
    }
  }
}
