<?php
class RptSummary extends Controller{
  public function view($f3,$params) {
    $year = (int)(isset($params['year']) ? $params['year'] : date('Y'));
    $f3->set('year',$year);

    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $f3->set('categories_long',$categories->listDesc());

    $ctypes = new CategoryType($this->db);
    $ctlst = $ctypes->listDesc();
    $f3->set('category_types',$ctlst);

    $table = [ 0 => [] ];


    for ($mn = 1; $mn < 13; ++$mn) {
      $start = sprintf('%04d-%02d-%02d', $year,$mn,1);
      $end = sprintf('%04d-%02d-%02d', $year + ($mn == 12 ? 1 : 0), $mn == 12 ? 1 : $mn+1, 1);

      $table[$mn] = [];
      foreach (array_keys($ctlst) as $ct) {
        $table[$mn][$ct] = [];
        $rows = $this->db->exec('SELECT nsPosting.categoryId as catId,catgroup,sum(amount) as totals
		FROM nsPosting,nsCategory
		WHERE ? <= postingDate AND postingDate < ? AND nsCategory.categoryId = nsPosting.categoryId AND nsCategory.categoryTypeId = ?
		GROUP by nsPosting.categoryId,catgroup',[$start,$end,$ct]);
	//if ($ct == 2) echo "<pre>ct = $ct start=$start end=$end, rows=".count($rows)."</pre>";
	//if ($ct == 2) echo "<pre>".print_r($rows,true)."</pre>";
	foreach ($rows as $row) {
	   if (!isset($table[$mn][$ct][$row['catId']])) $table[$mn][$ct][$row['categoryId']] = [];
	   $table[$mn][$ct][$row['catId']][$row['catgroup']] = $row['totals'];

	   if (!isset($table[0][$ct][$row['catId']])) $table[0][$ct][$row['catId']] = [];
	   if (!isset($table[0][$ct][$row['catId']][$row['catgroup']])) $table[0][$ct][$row['catId']][$row['catgroup']] = 0;
	   $table[0][$ct][$row['catId']][$row['catgroup']] += $row['totals'];
	}
      }
    }
    //echo '<pre>';print_r($table);echo '</pre>';
    

    // Collate in tabular format...
    $dat = [];
    for ($mn = 0; $mn < 13; ++$mn) {
      $dat[$mn] = [];
      foreach (array_keys($ctlst) as $tt) {
	$dat[$mn][$tt] = [];
	if (!isset($table[0][$tt])) continue;
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
      foreach (array_keys($ctlst) as $tt) {
	$totals[$mn][$tt] = 0;
	if (!isset($table[$mn][$tt])) continue;
	foreach ($table[$mn][$tt] as $cdat) {
	  foreach ($cdat as $money) {
	    $totals[$mn][$tt] += $money;
	  }
	}
      }
    }

    //echo '<pre>TABLE DAT'.PHP_EOL;print_r($dat);echo '</pre>';

    $f3->set('table',$dat);
    $f3->set('totals',$totals);
    echo View::instance()->render('summary.html');
    //echo '<pre>TABLE DAT'.PHP_EOL;print_r($dat);echo '</pre>';
  }
}