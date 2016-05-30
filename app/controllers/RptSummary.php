<?php
class RptSummary extends Controller{
  public function view($f3,$params) {
    $year = (int)(isset($params['year']) ? $params['year'] : date('Y'));
    $f3->set('year',$year);

    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $f3->set('categories_long',$categories->listDesc());

    $table[0] = [  'expenses' => [],'income' => [], 'adj'=>[] ];
    for ($mn = 1; $mn < 13; ++$mn) {
      $table[$mn] = [ 'expenses' => [],'income' => [],'adj' => [] ];

      $start = sprintf('%04d-%02d-%02d', $year,$mn,1);
      $end = sprintf('%04d-%02d-%02d', $year + ($mn == 12 ? 1 : 0), $mn == 12 ? 1 : $mn+1, 1);
      
      foreach (['expenses'=>'<','income'=>'>'] as $i=>$j) {
        $rows = $this->db->exec('SELECT categoryId,catgroup,sum(amount) as totals
		FROM nsPosting
		WHERE ? <= postingDate AND postingDate < ? AND amount '.$j.' 0
		GROUP by categoryId,catgroup',[$start,$end]);

	 foreach ($rows as $row) {
	   if (!isset($table[$mn][$i][$row['categoryId']])) $table[$mn][$i][$row['categoryId']] = [];
	   $table[$mn][$i][$row['categoryId']][$row['catgroup']] = $row['totals'];

	   if (!isset($table[0][$i][$row['categoryId']])) $table[0][$i][$row['categoryId']] = [];
	   if (!isset($table[0][$i][$row['categoryId']][$row['catgroup']])) $table[0][$i][$row['categoryId']][$row['catgroup']] = 0;
	   $table[0][$i][$row['categoryId']][$row['catgroup']] += $row['totals'];
	 }
      }
    }

    // Isolate adjustments...
    $adj = [];
    foreach (array_keys($table[0]['income']) as $cat) {
      if(!isset($table[0]['expenses'][$cat])) continue;
      $adj[$cat] = $cat;
    }
    for ($mn = 0;$mn < 13;++$mn) {
      foreach ($adj as $cat) {
        foreach (['expenses','income'] as $tt) {
	  if (isset($table[$mn][$tt][$cat])) {
	    if (!isset($table[$mn]['adj'])) $table[$mn]['adj'] = [];
	    if (!isset($table[$mn]['adj'][$cat])) $table[$mn]['adj'][$cat] = [];
	    foreach ($table[$mn][$tt][$cat] as $cg => $money) {
	      if (!isset($table[$mn]['adj'][$cat][$cg])) $table[$mn]['adj'][$cat][$cg] = 0;
	      $table[$mn]['adj'][$cat][$cg] += $money;
	    }
	    unset($table[$mn][$tt][$cat]);
	  }
	}
      }
    }

    // Collate in tabular format...
    $dat = [];
    for ($mn = 0; $mn < 13; ++$mn) {
      $dat[$mn] = [];
      foreach (['expenses','income','adj'] as $tt) {
	$dat[$mn][$tt] = [];
	foreach (array_keys($table[0][$tt]) as $cat) {
	  if (isset($table[$mn][$tt][$cat])) {
	    $dat[$mn][$tt][$cat] = $table[$mn][$tt][$cat];
	  } else {
	    $dat[$mn][$tt][$cat] = [];
	  }
	}
      }
    }

    // Compute line totals...
    $totals = [];
    for ($mn = 0;$mn < 13 ; ++$mn) {
      $totals[$mn] = [];
      foreach (['expenses','income','adj'] as $tt) {
	$totals[$mn][$tt] = 0;
	foreach ($table[$mn][$tt] as $cdat) {
	  foreach ($cdat as $money) {
	    $totals[$mn][$tt] += $money;
	  }
	}
      }
    }

    $f3->set('table',$dat);
    $f3->set('totals',$totals);
    echo View::instance()->render('summary.html');
  }
}