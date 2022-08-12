<?php
function rule_preg_match($pattern, $text) {
  if (substr($pattern,0,1) != '/') {
    $pattern = '/'.$pattern.'/i';
  }
  return preg_match($pattern,$text);
}

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
    if (!$f3->exists('POST.override')) {
      $f3->reroute('/import/msg/RRMAP missing');
      return;
    }
    $rr_map = self::get_rrmap($f3->get('POST.override'));
    BaseImport::init($f3,$this->db);

    // Create any quick add new rules...
    $qadd_rules = self::get_qrules($f3->get('POST.qrules'));
    $msg = self::add_rules($this->db, $qadd_rules);
    if ($msg != '') {
      $this->previewRows($f3,$rows,$rr_map,$msg);
      return;
    }

    if ($command == 'preview') {
      $this->previewRows($f3,$rows,$rr_map);
      return;
    }

    // Apply overrides...
    $this->applyOverrides($f3,$rows,$rr_map);

    // Update rule stats...
    self::update_rule_stats($this->db,$rows);

    // Insert rows (and triggers)
    $k = $this->insertRows($f3,$rows,$rr_map);
    // report results
    $f3->reroute('/import/msg/ROWS INSERTED: '.$k);

    //~ echo Sc::go('/import','Bulk Import').' : '.Sc::go('/','Home').'<br/>';
    //~ echo '<pre>';
    //~ print_r($rows);
    //~ print_r($rr_map);
    //~ print_r($qadd_rules);
    //~ print_r($f3->get('POST.qrules'));

    //~ echo '</pre>';
    //~ echo Sc::go('/import','Bulk Import').' : '.Sc::go('/','Home').'<br/>';

    /*
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

    $implist = array_merge(glob($f3->get('importers').'/*.php'),
				glob('app/importers/*.php'));

    foreach ($implist as $cf) {
      if ((include $cf) === FALSE) continue;
      $className = basename($cf,'.php');
      if ($className::detect($f3,$name,$file)) {
        $importer = $className;
	break;
      }
    }
    if (is_null($importer)) {
      $f3->reroute('/import/msg/Format not recognized: '.Sc::esc($name));
      return;
    }
    $rows = $className::import($f3,$name,$file,$defacct);
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
  public function previewRows($f3,&$rows,&$rr_map,$imsg=NULL) {
    list ($status,$msg) = $this->applyRules($f3,$rows,$rr_map);
    if ($status) {
      if ($imsg) {
	$f3->set('PARAMS.msg',
			"Add Rules:\n".
			$imsg .
			"Apply Rules:\n".
			$msg);
      } else {
	$f3->set('PARAMS.msg',$msg);
      }
    } else {
	if ($imsg) $f3->set('PARAMS.msg',$imsg);
    }

    $this->tagDuplicates($f3,$rows,$rr_map);

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
      if ($cmd != '=' && $cmd != 'S' && $cmd != 'D')
        $rows[$rownum][CN_CATEGORY] = $cmd == '~' ? '' : $cmd;

      if ($grp != '') $rows[$rownum][CN_CATGRP] = $grp;
    }
  }
  public function applyRules($f3,&$rows,&$rrmap) {
    $phprules = trim(file_get_contents($f3->get('rules_file')));
    $importer = $f3->get('POST.importer');
    $account_numbers = $f3->get('accounts_numbers');

    //~ echo '<pre>';
    //~ print_r($rr_map);
    //~ echo '</pre>';
    foreach ($rows as &$row) {
      $uid = BaseImport::row_uuid($row);
      if (isset($rrmap[$uid])) continue;

      $old = serialize($row);
      if (eval('?>'.$phprules) === FALSE) {
        return [-1,print_r(error_get_last(),true)];
      }
      $new = serialize($row);
      if (!isset($row['PHPRULE_MATCH'])) {
	$row['PHPRULE_MATCH'] = $new != $old;
      }
      if (!$row['PHPRULE_MATCH']) {
	# OK, use the rules table from v1.3
	if (!isset($rules)) {
	  $rules = (new Rule($this->db))->all();
	  Rule::sort_rules($rules);
	}
	$found = false;
	foreach ($rules as $rr) {
	  if (!is_null($rr->acctId)) {
	    if ($rr->acctId != $row[CN_ACCOUNT]) continue;
	  }
	  if (!is_null($rr->desc_re)) {
	    if (!rule_preg_match($rr->desc_re,$row[CN_DESCRIPTION])) continue;
	  }
	  if (!is_null($rr->text_re)) {
	    if (!rule_preg_match($rr->text_re,$row[CN_TEXT])) continue;
	  }
	  if (!is_null($rr->detail_re)) {
	    if (!rule_preg_match($rr->detail_re,$row[CN_DETAIL])) continue;
	  }
	  if (!is_null($rr->min_amount)) {
	    if ($row[CN_AMOUNT] < $rr->min_amount) continue;
	  }
	  if (!is_null($rr->max_amount)) {
	    if ($row[CN_AMOUNT] > $rr->max_amount) continue;
	  }
	  // If we got here that means that we matched all specified
	  // criterias
	  $row[CN_CATEGORY] = $rr->categoryId;
	  if (!is_null($rr->catgroup)) $row[CN_CATGRP] = $rr->catgroup;
	  $row['RULE_MATCH'] = $rr->ruleId;
	  $found = true;
	  break;
	}
	if (!$found && isset($row['RULE_MATCH'])) {
	  $row[CN_CATEGORY] = '';
	  $row[CN_CATGRP] = '';
	  unset($row['RULE_MATCH']);
	}
      }
    }
    return [0,''];
  }
  public function tagDuplicates($f3,&$rows,&$rrmap) {
    $accts = [];
    foreach ($rows as $row) {
      if (!isset($begin)) {
        $begin = $row[CN_DATE];
      } else {
        if ($row[CN_DATE] < $begin) $begin = $row[CN_DATE];
      }
      if (!isset($end)) {
        $end = $row[CN_DATE];
      } else {
        if ($row[CN_DATE] > $end) $end = $row[CN_DATE];
      }
      if ($row[CN_ACCOUNT] != '') $accts[$row[CN_ACCOUNT]] = $row[CN_ACCOUNT];
    }
    if (!(isset($begin) && isset($end))) return;

    $postingDAO = new Posting($this->db);
    $duptab = [];//DEBUG
    $ruid = $postingDAO->get_uids($begin,$end,$accts);
    $duptab[] = $ruid;//DEBUG
    $i = 0;
    foreach ($rows as $row) {
      $uid = BaseImport::row_uuid($row);
      $rnum = $i++;
      if (!isset($ruid[$uid])) continue;
      $rrmap[$uid] = [$rnum,'D',''];
    }
    $duptab[] = $rrmap;//DEBUG
    $f3->set('duptab',$duptab);//DEBUG
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
        if ($rr_map[$uid][1] == 'S' || $rr_map[$uid][1] == 'D') continue; // SKIP row!
      }

      //~ echo('<pre>');
      //~ print_r($row);
      //~ echo ('</pre>');
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
    if ($selcat == 'D') {
      $html .= '<option value="D" selected>** DUPLICATE ROW **</option>';
    }
    $html .= '</select>';
    $html .= '<input type="text" size=1 placeholder="'.$row[CN_CATGRP].'" value="'.(isset($rr_map[$uid]) ? $rr_map[$uid][2] : '').'" id="cgn'.$i.'" pattern="[0-9]"/>';

    $html .= '</td>';
    return $html;
  }
  static public function amount($row) {
    $html = "";
    $html .= '<td align="right"';
    if ($row[CN_AMOUNT] < 0) $html .= ' style="color:red"';

    $html .= 'sorttable_customkey="'.sprintf("%020.2f",$row[CN_AMOUNT]).'"';
    $html .='>';
    $html .= '<span title="'.$row[CN_DETAIL].'">';
    $html .= number_format($row[CN_AMOUNT],2);
    $html .= '</span></td>';
    return $html;
  }
  static public function get_qrules($txt) {
    $qrules = [];

    foreach (explode("\n",$txt) as $ln) {
      $ln = trim($ln);
      if ($ln == "") continue;

      if (!isset($count)) {
        $count = (int)$ln;
        continue;
      }
      $ln = explode("\t",$ln,5);
      # $uid, $rownum, $catopt, $catgrp, $desc_re
      if (count($ln) != 5) continue; // No RE, so skip it!
      $uid = array_shift($ln);
      if (intval($ln[1]) == 0) continue; // No matchin $catopt
      $qrules[$uid] = $ln;
    }
    return $qrules;
  }
  static public function add_rules($db, $qadd_list) {
    $msg = '';

    $rm = new Rule($db);

    foreach ($qadd_list as $uid => $rr) {
      list($rownum, $catopt, $catgrp, $desc_re) = $rr;
      $chk = $rm->qcheck($desc_re);
      if ($chk) {
	$msg .= 'Duplicate rule match /'.$desc_re.'/'.PHP_EOL;
	continue;
      }
      $rm->reset();

      $rm->pri = 65;
      $rm->categoryId = $catopt;
      if ($catgrp) $rm->catgroup = $catgrp;
      $rm->desc_re = $desc_re;
      $rm->remark = 'Quick Rule ('.date('Y-m-d').')';
      $rm->add();
      $rm->reset();
    }
    return $msg;
  }
  static public function update_rule_stats($db, $rows) {
    foreach ($rows as $rr) {
      if (!isset($rr['RULE_MATCH'])) continue;
      if (!isset($rm)) {
	$rm = new Rule($db);
	$rm->clearStats();
      }
      $rm->updateRuleStat($rr['RULE_MATCH']);
    }
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
    //file_put_contents('data/log.txt',$txt);
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

  static public function uploadForm($opts=[]) {
    if (isset($opts['label'])) {
      $label = $opts['label'];
      unset($opts['label']);
    } else {
      $label = 'Select file to upload';
    }
    $tag = '';
    $tag .= '<form method="post" enctype="multipart/form-data"';
    foreach ($opts as $k => $v) {
      if (preg_match('/^\d+$/',$k)) {
        $tag .= ' '.$v;
      } else {
	$tag .= ' '.$k.'="'.$v.'"';
      }
    }
    $tag .=  '>'.$label;
    $tag .= '<input type="file" name="fileToUpload" id="fileToUpload" onchange="form.submit()"/>';
    $f3 = Sc::f3();
    $tag .= 'Default Account: <select id="def_acct" name="def_acct">';
    $tag .= ' <option value="#">* automatic *</option>';
    foreach ($f3->get('accounts_long') as $k=>$v) {
      $tag .= '<option value="'.$k.'"';
      if ($f3->exists('POST.def_acct')) {
        if ($f3->get('POST.def_acct') == $v) $tag .= ' selected';
      }
      $tag .= '>'.$v.'</option>';
    }
    $tag .= '</select>';
    $tag .= '</form>';
    return $tag;
  }

}

