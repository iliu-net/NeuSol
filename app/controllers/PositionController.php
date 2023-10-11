<?php

class PositionController extends Controller {
  public function index($f3,$params) {
    $f3->set('msg',isset($params['msg']) ? $params['msg'] : '');
    $year = (int)(isset($params['year']) ? $params['year'] : date('Y'));
    $f3->set('year',$year);

    $adding = FALSE;
    if (isset($params['pos'])) {
      if (preg_match('/^(\d\d\d\d)-\d\d-\d\d$/', $params['pos'], $mv)) {
	$f3->set('year',$year = $mv[1]);
	$adding = $params['pos'];
      } else {
	$f3->set('msg', 'Invalid date for adding column!');
      }
    }

    $acct = new Acct($this->db);
    $actlst = $acct->listDesc(FALSE);
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    ksort($actlst);

    $posDAO = new Position($this->db);

    $positions = $posDAO->getpos('? <= positionDate AND positionDate <= ?',
				 [$year.'-01-01',$year.'-12-31']);
    if ($adding) {
      $postingDAO = new Posting($this->db);
      $positions[$adding] = [];
      foreach (array_keys($actlst) as $acctId) {
	$res = $postingDAO->pitBalance($acctId,$adding);
	$positions[$adding][$acctId] = $res;
      }
    }
    # Hide positions if needed...
    $shown = $acct->listDesc();
    foreach (array_keys($actlst) as $acctId) {
      if (isset($shown[$acctId])) continue;
      $sum = 0;
      foreach ($positions as $position => $vv) {
	if (!isset($vv[$acctId])) continue;
	$sum += $vv[$acctId];
      }
      if ($sum == 0) {
	foreach (array_keys($positions) as $position) {
	  unset($positions[$position][$acctId]);
	}
	unset($actlst[$acctId]);
      }
    }

    ksort($positions);

    $f3->set('accounts',$actlst);
    $f3->set('positions',$positions);

    $all = [];
    foreach ($acct->all() as $j) {
      $all[$j->acctId] = $j->cast();
    }
    $f3->set('acct_all',$all);

    echo View::instance()->render('positions.html');
    //~ echo '<pre>';
    //~ print_r($positions);
    //~ echo '</pre>';
  }

  public function save($f3,$params) {
    if (!$f3->exists('POST.cols')) {
      $f3->reroute('/positions/msg/Invalid data');
      return;
    }
    $cols = explode(',',$f3->get('POST.cols'));
    $acctDAO = new Acct($this->db);
    $acctlst = $acctDAO->listSName();
    $posDAO = new Position($this->db);

    $year = date('Y');

    foreach ($cols as $date) {
      $year = substr($date,0,4);
      $suffix = 'x'.str_replace('-','_',$date);
      foreach ($acctlst as $id=>$sname) {
	$post = 'POST.i'.$id.$suffix;
	if (!$f3->exists($post)) continue;
	$amt = $f3->get($post);
	$posDAO->modify($id,$date,CNum::parse($amt));
      }
    }
    $f3->reroute('/positions/ymsg/'.$year.'/Updated');
  }
  public function drop($f3,$params) {
    if (!isset($params['pos'])) {
      $f3->reroute('/positions/msg/Missing position');
      return;
    }
    if (!preg_match('/^\d\d\d\d-\d\d-\d\d$/',$params['pos'])) {
      $f3->reroute('/positions/msg/Invalid date position');
      return;
    }
    $pos = $params['pos'];
    $posDAO = new Position($this->db);
    $posDAO->remove($pos);
    $f3->reroute('/positions/msg/Removing date position');
  }
  public function report($f3,$params) {
    $report = isset($params['fmt']) ? $params['fmt'] : 'rpt_positions';
    $f3->set('report', $report);
    if (!is_file('app/views/'.$report.'.html')) {
      echo '<pre>report: '.$report.PHP_EOL;
      print_r($params);
      return;
    }
    if (isset($params['period'])) {
      if (preg_match('/^\d\d\d\d$/',$params['period'])) {
	$f3->set('period',$params['period']);
	$start = $params['period'].'-01-01';
	$end = $params['period'].'-12-31';
	$f3->set('mode','year');
      } else if (preg_match('/^p\d\d\d\d$/',$params['period'])) {
	$year = intval(substr($params['period'],1,4));
	$f3->set('period',$year);
	$start = ($year-1).'-12-31';
	$end = $year.'-12-31';
	$f3->set('mode','year+');
      } else if (preg_match('/^\d\d\d\d-\d\d-\d\d$/',$params['period'])) {
	$f3->set('period',$period = $params['period']);
	$f3->set('mode','single');
      } else {
	$f3->error(405);
	return;
      }
    } else if (isset($params['start']) && isset($params['end'])) {
      $f3->set('start',$start = $params['start']);
      $f3->set('end',$end = $params['end']);
      if (preg_match('/^\d\d\d\d$/',$start) && preg_match('/^\d\d\d\d$/',$end)) {
	$f3->set('start',$start = $start.'-12-31');
	$f3->set('end',$end = $end.'-12-31');
	$f3->set('mode','multiyear');
      } else {
	$f3->set('mode','any');
      }
    } else {
      if ($report == 'rpt_portfolio') {
	$f3->set('mode','single');
      } else {
	//~ $f3->set('mode','year');
	//~ $f3->set('period',$period = date('Y'));
	//~ $start = $period.'-01-01';
	//~ $end = $period.'-12-31';
	$f3->set('mode','year+');
	$f3->set('period',$period = date('Y'));
	$start = (((int)$period)-1).'-12-31';
	$end = $period.'-12-31';
      }
    }

    $acct = new Acct($this->db);
    $actlst = $acct->listDesc(FALSE);
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    ksort($actlst);

    $posDAO = new Position($this->db);
    if ($f3->get('mode') == 'single') {
      $poslst = $posDAO->listPos();
      if (count($poslst) == 0) {
	$f3->reroute('/positions/msg/No positions found, please create one');
	return;
      }
      $f3->set('avail_positions',$poslst);
      if (!isset($period)) {
	$tmp = array_keys($poslst);
	$period = $tmp[0];
	unset($tmp);
	$f3->set('period',$period);
      } else if (!isset($poslst[$period])) {
	$f3->reroute('/positions/msg/Postion '.$period.' does not exist');
	return;
      }
      $positions = $posDAO->getpos('? = positionDate',[$period]);
    } else if ($f3->get('mode') == 'multiyear') {
      $positions = $posDAO->getpos('? <= positionDate AND positionDate <= ? AND positionDate LIKE "%-12-31"',[$start,$end]);
    } else {
      $positions = $posDAO->getpos('? <= positionDate AND positionDate <= ?',[$start,$end]);
    }
    ksort($positions);

    # Hide positions if needed...
    $shown = $acct->listDesc();
    foreach (array_keys($actlst) as $acctId) {
      if (isset($shown[$acctId])) continue;
      $sum = 0;
      foreach ($positions as $position => $vv) {
	if (!isset($vv[$acctId])) continue;
	$sum += $vv[$acctId];
      }
      if ($sum == 0) {
	foreach (array_keys($positions) as $position) {
	  unset($positions[$position][$acctId]);
	}
	unset($actlst[$acctId]);
      }
    }

    $f3->set('accounts',$actlst);
    $f3->set('accounts_short',$acct->listSName(FALSE));
    $f3->set('positions',$positions);

    echo View::instance()->render($report.'.html');
  }
  static public function yrnav($base_url,$year) {
    return 'Year: '.
	    Sc::go(sprintf('%s%s',$base_url,$year-1),'&lt;&lt;').
	    ' <input type="text" id="form_yearnav" name="yearnav" maxlength=4 size=4 patter="[0-9]" value="'.
	    $year.'" onchange="chgev_yrnav(\''.Sc::url($base_url).'\')" /> '.
	    Sc::go(sprintf('%s%s',$base_url,$year+1),'&gt;&gt;').' '.
	    ' '.Sc::jslnk('chgev_yrnav(\''.Sc::url($base_url).'\')','Go');
  }

}
