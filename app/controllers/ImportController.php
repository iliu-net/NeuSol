<?php
class ImportController extends Controller {
  public function getfile($f3,$params) {
    $acctsDAO = new Acct($this->db);
    $f3->set('accounts_long',$acctsDAO->listDesc());
    echo View::instance()->render('import_welcome.html');
  }
  public function doimport($f3,$params) {
    if (!$f3->exists('POST.command')) {
      $f3->reroute('/import/msg/NO command specified');
      return;
    }
    $command = $f3->get('POST.command');
    if ($command != 'preview' && $command != 'post') {
      $f3->reroute('/import/msg/Invalid command');
      return;
    }
    if (!$f3->exists('POST.rowdata')) {
      $f3->reroute('/import/msg/ROW data was lost!');
      return;
    }
    list($rows,$msg) = self::decode($f3->get('POST.rowdata'));
    if ($rows === NULL) {
      $f3->reroute('/import/msg/ROW Data error: '.$msg);
      return;
    }
    if (!$f3->exists('POST.rowdata')) {
      $f3->reroute('/import/msg/RRMAP missing');
      return;
    }
    $rr_map = self::get_rrmap($f3->get('POST.override'));
    BaseImport::init($f3,$this->db);

    if ($command == 'preview') {
      $this->previewRows($f3,$rows,$rr_map);
      return;
    }

    // Apply overrides...
    $this->applyOverrides($f3,$rows,$rr_map);

    // Insert rows (and triggers)
    $k = $this->insertRows($f3,$rows,$rr_map);
    // report results
    $f3->reroute('/import/msg/ROWS INSERTED: '.$k);

    /*
    echo Sc::go('/import','Bulk Import').' : '.Sc::go('/','Home').'<br/>';
    echo '<pre>';
    echo "\n=====\n";
    var_dump($rr_map);
    var_dump($rows);
    echo '</pre>';
    return;
    */
  }
  public function postfile($f3) {
    list($name,$file) = ["",""];


    $files = Web::instance()->receive(function($fin,$formFieldName) use(&$name,&$file) {
      $name = basename($fin['name']);
      $file = $fin['tmp_name'];
      return FALSE;
    },FALSE,FALSE);
    if (!($name && $file)) {
      $f3->reroute('/import/msg/POST file error');
      return;
    }
    BaseImport::init($f3,$this->db);
    $defacct = NULL;
    if ($f3->exists('POST.def_acct')) {
      $defacct = $f3->get('POST.def_acct');
      $accounts_long = $f3->get('accounts_long');
      if ($defacct == '#' || !isset($accounts_long[$defacct])) $defacct = NULL;
    }

    /*
    echo Sc::go('/import','Import');
    echo '<pre>';*/
    $importer = NULL;
    global $debug;
    foreach (glob($f3->get('importers').'/*.php') as $cf) {
      if ((include $cf) === FALSE) continue;
      $className = basename($cf,'.php');
      if (${className}::detect($f3,$name,$file)) {
        $importer = $className;
	break;
      }
    }
    if (is_null($importer)) {
      //$f3->reroute('/import/msg/Format not recognized: '.Sc::esc($name));
      return;
    }
    $rows = ${className}::import($f3,$name,$file,$defacct);
    if (count($rows) == 0) {
      //$f3->reroute('/import/msg/No data imported');
      return;
    }
    /*echo 'SUCCESS'.PHP_EOL;
    print_r($rows);
    return;*/
    $rr_map = [];
    $f3->set('POST.filename',$name);
    $f3->set('POST.importer',$importer);

    $this->previewRows($f3,$rows,$rr_map);    
  }
  public function previewRows($f3,&$rows,&$rr_map) {
    list ($status,$msg) = $this->applyRules($f3,$rows,$rr_map);
    if ($status) $f3->set('PARAMS.msg','<pre>'.$msg.'</pre>');

    $this->tagDuplicates($f3,$rows);

    $f3->set('rows',$rows);
    $f3->set('rowdata',self::encode($rows));
    $f3->set('rr_map', $rr_map);

    //echo '<pre>'.print_r($f3->get('accounts'),true).'</pre>';
    //echo '<pre>'.print_r($rows,true).'</pre>';
    echo View::instance()->render('import_preview.html');
    //$x = View::instance()->render('import_preview.html');echo $x; file_put_contents('data/log.txt',$x);
  }
  public function applyOverrides($f3,&$rows,$rr_map) {
    foreach ($rr_map as $uid => $cmds) {
      list($rownum,$cmd,$grp) = $cmds;
      if (BaseImport::row_uuid($rows[$rownum]) != $uid) continue;
      if ($cmd != '=' && $cmd != 'S' )
        $rows[$rownum][CN_CATEGORY] = $cmd == '~' ? '' : $cmd;

      if ($grp != '') $rows[$rownum][CN_CATGRP] = $grp;
    }
  }
  public function applyRules($f3,&$rows,&$rr_map) {
    $rules = trim(file_get_contents($f3->get('rules_file')));
    $importer = $f3->get('POST.importer');
    $account_numbers = $f3->get('accounts_numbers');

    foreach ($rows as &$row) {
      $uid = BaseImport::row_uuid($row);
      if (isset($rrmap[$uid])) continue;
      if (eval('?>'.$rules) === FALSE) {
        return [-1,print_r(error_get_last(),true)];
      }
    }
    return [0,''];
  }
  public function tagDuplicates($f3,&$rows) {
    // TODO!
  }
  public function insertRows($f3,$rows,$rr_map) {
    $k = 0;
    $triggers = trim(file_get_contents($f3->get('triggers_file')));
    $importer = $f3->get('POST.importer');
    $account_numbers = $f3->get('accounts_numbers');

    $posting = new Posting($this->db);
    foreach ($rows as &$row) {
      $uid = BaseImport::row_uuid($row);
      if (isset($rr_map[$uid])) {
        if ($rr_map[$uid][1] == 'S') continue; // SKIP row!
      }
      // Insert row...
      //$posting->importRow($row);
      $posting->newPosting($row);
      /*
      $posting->reset();
      $posting->acctId = $row[CN_ACCOUNT];
      $posting->categoryId = $row[CN_CATEGORY];
      $posting->catgroup = $row[CN_CATGRP];
      $posting->postingDate = $row[CN_DATE];
      $posting->xid = $row[CN_XID];
      $posting->description = $row[CN_DESCRIPTION];
      $posting->amount = $row[CN_AMOUNT];
      $posting->text = $row['TEXT'];
      // $posting->detail = $row[CN_DETAIL];
      $posting->save();*/
      ++$k;

      // Run triggers
      if (eval('?>'.$triggers) === FALSE) {
        return $k;
      }

    }
    return $k;
  }


  // Used by displays...
  static public function td(&$row,$txt,$key="") {
    if ($key == "") $key = $txt;
    $html = "";
    $html .= '<td sorttable_customkey="'.$key.'">';
    $html .= '<span title="'.$row[CN_TEXT].'">';
    $html .= $txt;
    $html .= '</span></td>';
    return $html;
  }
  static public function category($f3,&$row,$i,&$rr_map,$cats) {
    $uid = BaseImport::row_uuid($row);
    if (isset($rr_map[$uid])) {
      list($rnum,$selcat,$catgrp) = $rr_map[$uid];
    } else {
      $selcat = $row[CN_CATEGORY];
      $catgrp = $row[CN_CATGRP];
    }

    $html = "";
    $html .= '<td sorttable_customkey="'.$row[CN_CATEGORY].'.'.$row[CN_CATGRP].'">';

    //$html .= $row[CN_CATEGORY].'.'.$row[CN_CATGRP];
    $html .= '<input type="hidden" id="xid'.$i.'" value="'.$uid.'"/>';
    $html .= '<select id="cat'.$i.'">';

    $html .= '<option ';
    if ($row[CN_CATEGORY] == '') {
      $html.= ' value="="';
    } else {
      $html .= ' value="~"';
    }
    if ($selcat == '~') $html .= ' selected';
    $html .= '</option>';

    foreach ($cats as $k=>$v) {
      $html .= '<option ';
      if ($k == $row[CN_CATEGORY]) {
        $html .= ' value="="';
      } else {
        $html .= ' value="'.$k.'"';
      }
      if ($k == $selcat) $html .= ' selected';
      $html .= '>';
      if ($k == $selcat) $html .= '&nbsp;';
      $html .= $v;
      $html .= '</option>';
    }
    $html .= '<option value="S"'.($selcat == 'S' ? 'selected' : '').'>** skip **</option>';
    $html .= '</select>';
    $html .= '<input type="text" size=1 placeholder="'.$row[CN_CATGRP].'" value="'.(isset($rr_map[$uid]) ? $rr_map[$uid][1] : '').'" id="cgn'.$i.'" pattern="[0-9]"/>';

    $html .= '</td>';
    return $html;
  }
  static public function amount($row) {
    $html = "";
    $html .= '<td align="right"';
    if ($row[CN_AMOUNT] < 0) $html .= ' style="color:red"';

    $html .= 'sorttable_customkey="'.sprintf("%020.2f",$row['CN_AMOUNT']).'"';
    $html .='>';
    $html .= '<span title="'.$row[CN_DETAIL].'">';
    $html .= number_format($row[CN_AMOUNT],2);
    $html .= '</span></td>';
    return $html;
  }

  static public function get_rrmap($txt) {
    $rrmap = [];

    //    $f='';
    //$i = 0; //#DEBUG
    foreach (explode("\n",$txt) as $ln) {
      $ln = trim($ln);
      if ($ln == "") continue;
      //$f .= '"'.$ln.'"'.PHP_EOL;//#DEBUG
      if (!isset($count)) {
        $count = (int)$ln;
        //$f .= "ROW COUNT $count\n";//#DEBUG
        continue;
      }
      $ln = explode("\t",$ln);
      //$f .= print_r($ln,true);//#DEBUG
      if (count($ln) == 3) {
        list($uid,$rownum,$cmd) = $ln;
        $grp = '';
      } else {
        list($uid,$rownum,$cmd,$grp) = $ln;
      }
      //$f .= "CKENTRY: $uid,$rownum,$cmd,$grp\n";//#DEBUG
      //++$i;
      if ($cmd == '=' && $grp == "") continue;
      //$f .= "ADDENTRY: $uid,$rownum,$cmd,$grp\n";//#DEBUG
      $rrmap[$uid] = [$rownum,$cmd,$grp];
    }
    //$f .= "//////\n";//#DEBUG
    //$f .= "COUNT: ".$count."\nLINES: ".$i."\n";
    //$f .= print_r($rrmap,true);
    return $rrmap;
  }
  static public function encode($rows) {
    $enc = chunk_split(base64_encode(zlib_encode(serialize($rows),ZLIB_ENCODING_DEFLATE)));
    //$enc = chunk_split(base64_encode(zlib_encode(json_encode($rows),ZLIB_ENCODING_DEFLATE)));
    //$enc = zlib_encode(serialize($rows),ZLIB_ENCODING_DEFLATE);
    //$enc = unpack('H*',$enc);
    //$enc = array_shift($enc);
    //file_put_contents('data/log.js',$enc);
    return $enc;
  } 
  static public function decode($txt) {
    file_put_contents('data/log.txt',$txt);

    //$txt = pack('H*',$txt);
    $txt = base64_decode($txt);
    if ($txt === FALSE) return [NULL,'baset4 error'];
    $txt = zlib_decode($txt);
    if ($txt === FALSE) return [NULL,'zlib error'];
    //$txt = json_decode($txt);
    //if ($txt === NULL) return [NULL,'JSON '.self::json_err_msg()];
    $txt = unserialize($txt);
    return [$txt,''];
  }
}

