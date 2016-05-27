<?php
class BackupController extends Controller{
  public function backup($f3) {
    list($args,) = Sc::cli_only();
    if (count($args) == 0) {
      $fp = STDOUT;
    } else {
      $name = array_shift($args);
      $fp = fopen(Sc::cli_path($name),"w");
      if ($fp === FALSE) die("$name: Unable to open\n");
    }
    if (count($args) == 0) {
      $args = [];
      $rows = $this->db->exec('SHOW TABLES');
      foreach ($rows as $row) {
	$args[] = array_shift($row);
      }
    }
    foreach ($args as $table) {
      fwrite($fp,'DROP TABLE IF EXISTS '.$table.';'.PHP_EOL);
      $rows = $this->db->exec('SHOW CREATE TABLE '.$table);
      fwrite($fp,$rows[0]['Create Table'].';'.PHP_EOL);

      $rows = $this->db->exec('SELECT * FROM '.$table);
      foreach ($rows as $row) {
        $ks = [];
	$vs = [];
	foreach ($row as $k=>$v) {
	  if (is_null($v)) continue;
	  $ks[] = $k;
	  if (is_numeric($v)) {
	    $vs[] = $v;
	  } else {
	    $vs[] = '"'.preg_replace("/\n/",'\n',addslashes($v)).'"';
	  }
	}
	if (count($vs)) fwrite($fp,'INSERT INTO '.$table.' ('.implode(',',$ks).') VALUES ('.implode(',',$vs).');'.PHP_EOL);
      }
    }
    fclose($fp);
  }
  public function restore($f3,$params) {
    list($args,) = Sc::cli_only();
    if (count($args) == 0) {
      $fp = STDIN;
    } else {
      $name = array_shift($args);
      $fp = fopen(Sc::cli_path($name),"r");
      if ($fp === FALSE) die("$name: Unable to open\n");
    }
    /* This is just a very basic implementation */
    while (!feof($fp)) {
      $sql = '';
      while (($ln=fgets($fp)) !== FALSE) {
        $sql .= $ln;
	if (substr(rtrim($ln),-1,1) == ';') break;
      }
      if ($sql) $this->db->exec($sql);
    }
    fclose($fp);
  }

}