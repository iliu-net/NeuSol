<?php
class BackupController extends Controller{
  const DATABASE_FILE = 'database.sql';

  public function do_fetch($f3,$backup) {
    $backup = $f3->get('backup_dir').'/'.basename($backup);
    if (!is_file($backup)) {
      $f3->error(404);
      return;
    }
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename='.basename($backup));
    header('Content-Length: ' . filesize($backup));
    readfile($backup);
  }
  public function fetch($f3,$params) {
    if (!isset($params['backup'])) {
      $f3->error(404);
      return;
    }
    $this->do_fetch($f3,$params['backup']);
  }

  public function dobackup($f3,$params) {
    $zipname = self::genfilename($f3);
    self::zipBackup($f3,$this->db,$zipname);
    if (isset($params['dl'])) {
      $this->do_fetch($f3,$zipname);
    } else {
      $f3->set('msg', 'Created backup: '.$zipname);
      $this->view($f3,$params);
    }
  }

  public function del($f3,$params) {
    if (!isset($params['backup'])) {
      $f3->error(405);
      return;
    }
    $backup = $f3->get('backup_dir').'/'.basename($params['backup']);
    if (!is_file($backup)) {
      $f3->error(404);
      return;
    }
    unlink($backup);
    $f3->set('msg',"Removed backup file $backup");
    $this->view($f3,$params);
  }

  public function restoreUrl($f3,$params) {
    if (!isset($params['backup'])) {
      $f3->error(405);
      return;
    }
    $backup = $f3->get('backup_dir').'/'.basename($params['backup']);
    if (!is_file($backup)) {
      $f3->error(404);
      return;
    }
    ob_start();
    self::zipRestore($f3,$this->db,$backup);
    $msg = ob_get_contents();
    ob_end_clean();

    $f3->set('msg',"Restored backup file $backup\n<pre>$msg</pre>");
    $this->view($f3,$params);
  }

  public function view($f3,$params) {
    $bd = $f3->get('backup_dir');
    $bb = [];
    foreach (glob($bd.'/bak*.zip') as $j) {
      $n = basename($j);
      $bb[$n] = stat($j);
    }
    $f3->set('backups',$bb);

    echo View::instance()->render('backup.html');
  }
  static public function backupSql($db,$fp,$tables = NULL) {
    if (!is_array($tables) || count($tables) == 0) {
      $tables = [];
      $rows = $db->exec('SHOW TABLES');
      foreach ($rows as $row) {
	$tables[] = array_shift($row);
      }
    }
    foreach ($tables as $table) {
      fwrite($fp,'DROP TABLE IF EXISTS '.$table.';'.PHP_EOL);
      $rows = $db->exec('SHOW CREATE TABLE '.$table);
      fwrite($fp,$rows[0]['Create Table'].';'.PHP_EOL);

      $rows = $db->exec('SELECT * FROM '.$table);
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
  }
  static public function restoreSql($db,$fp,$fsz=0) {
    /* This is just a very basic implementation */
    $count = 0;
    while (!feof($fp)) {
      $sql = '';
      while (($ln=fgets($fp)) !== FALSE) {
	$count += strlen($ln);
        $sql .= $ln;
	if (substr(rtrim($ln),-1,1) == ';') break;
      }
      if (php_sapi_name() == 'cli') {
	if ($fsz) {
	  echo "\r" . number_format($count*100.0/$fsz,3) . ' % ';
	} else {
	  echo "\r" . number_format($count);
	}
      }

      if ($sql) $db->exec($sql);
    }
    if (php_sapi_name() == 'cli') echo PHP_EOL;
  }

  static public function zipBackup($f3,$db,$fname) {
    $zip = new ZipArchive;
    $zip->open($fname,ZipArchive::CREATE|ZipArchive::OVERWRITE);
    foreach (glob($f3->get('importers').'/*.php') as $cf) {
      $zip->addFile($cf);
    }
    foreach (['rules_file','triggers_file'] as $cf) {
      $cf = $f3->get($cf);
      if (!is_file($cf)) continue;
      $zip->addFile($cf);
    }
    $mem = fopen('php://temp','r+');
    self::backupSql($db,$mem);
    rewind($mem);
    $zip->addFromString(self::DATABASE_FILE,stream_get_contents($mem));
    fclose($mem);
    $zip->close();
  }
  static public function zipRestore($f3,$db,$zipname) {
    $zip = new ZipArchive();
    $zip->open($zipname);
    for ($i=0; $i < $zip->numFiles; $i++) {
      $stat = $zip->statIndex($i);
      if ($stat['name'] == self::DATABASE_FILE) {
	echo "Unpacking SQL\n";
	$fp = fopen('php://temp','r+');
	fwrite($fp,$zip->getFromIndex($i));
	$fsz = ftell($fp);
	rewind($fp);
	self::restoreSql($db,$fp,$fsz);
	fclose($fp);
      } else {
	echo "Extracting ".$stat['name'].PHP_EOL;
	$zip->extractTo('.',$stat['name']);
      }
    }
    $zip->close();
  }

  static public function genfilename($f3) {
    //return preg_replace('/\/+$/','',$f3->get('backup_dir')).
    //  '/bak'.date('Y-m-d_His').'.zip';
    return preg_replace('/\/+$/','',$f3->get('backup_dir')).
      '/bak'.date('Y-m-d').'.zip';
  }

  public function backup($f3) {
    list($args,) = Sc::cli_only();
    if (count($args) == 0) {
      $zipname = self::genfilename($f3);
    } else {
      $zipname = Sc::cli_path(array_shift($args));
    }
    echo 'Creating backup file '.$zipname.PHP_EOL;
    self::zipBackup($f3,$this->db,$zipname);
  }
  public function obackup($f3) {
    list($args,) = Sc::cli_only();

    $fp = STDOUT;
    $close = FALSE;
    if (count($args) > 0) {
      $name = array_shift($args);
      if ($name != '-') {
	$fp = fopen(Sc::cli_path($name),"w");
	$close = TRUE;
      }
    }
    self::backupSql($this->db, $fp, $args);
    if ($close) fclose($fp);
  }
  public function orestore($f3) {
    list($args,) = Sc::cli_only();
    if (count($args) == 0) {
      $fp = STDIN;
      $close = FALSE;
      $sz = 0;
    } else {
      $name = array_shift($args);
      $fp = fopen(Sc::cli_path($name),"r");
      $close = TRUE;
      $sz = filesize(Sc::cli_path($name));
    }
    if (php_sapi_name() == 'cli') {
      // disable output buffering
      while (ob_get_level()) ob_end_flush();
      // turn implicit flush
      ob_implicit_flush(1);
    }

    self::restoreSql($this->db,$fp,$sz);
    if ($close) fclose($fp);
  }
  public function restore($f3,$params) {
    if (php_sapi_name() == 'cli') {
      list($args,) = Sc::cli_only();
      if (count($args) == 0) die("No Backup File specified\n");

      $zipname = Sc::cli_path(array_shift($args));
      if (!is_file($zipname)) die("$zipname: Does not exist\n");

      // disable output buffering
      while (ob_get_level()) ob_end_flush();
      // turn implicit flush
      ob_implicit_flush(1);

      echo 'Restoring from backup file '.$zipname.PHP_EOL;
      self::zipRestore($f3,$this->db,$zipname);
    } else {
      $f3->error(405);
      return;
    }

  }
  public function purge($f3,$params) {
    if (!isset($params['count'])) {
      $f3->error(404);
      return;
    }
    $versions = intval($params['count']);
    if ($versions == 0) {
      $f3->error(404);
      return;
    }
    if ($versions < 0) {
      $txt = TRUE;
      $versions = -$versions;
    } else {
      $txt = FALSE;
    }

    $bd = $f3->get('backup_dir');
    $items = glob($bd.'/bak*.zip');
    arsort($items);

    for ($i = 0 ; $i < $versions && count($items) > 0 ; $i++) {
      array_shift($items);
    }

    $msg = '';
    $rem = 0;

    if (count($items) == 0) {
      $msg = 'No valid backups found';
    } else {
      foreach ($items as $f) {
	if (!is_file($f)) continue;
	if (unlink($f)) {
	  $rem++;
	  $msg .= 'Removed '.$f.PHP_EOL;
	} else {
	  $msg .= 'Error removing '.$f.PHP_EOL;
	}
      }
    }

    if ($txt) {
      header('Content-Type: text/plain');
      echo 'Removed '.$rem.($rem == 1 ? ' file' : ' files').PHP_EOL;
      echo $msg.PHP_EOL;
    } else {
      if ($msg != '')
	$f3->set('msg','<pre>Removed '.$rem.($rem == 1 ? ' file' : ' files').PHP_EOL.$msg.PHP_EOL.'</pre>');
      $this->view($f3,[]);
    }
  }
}
