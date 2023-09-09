<?php
class AppMain extends Controller{
  public function welcome($f3) {
    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    $f3->set('accounts_long',$actlst);
    $ctypes = new CategoryType($this->db);
    $ctlst = $ctypes->listDesc();
    $f3->set('category_types',$ctlst);

    if ($f3->exists('COOKIE.page') && PostingsController::valid_page($f3,$f3->get('COOKIE.page'))) {
      $page = $f3->get('COOKIE.page');
    } else {
      $page = PostingsController::default_page($f3);
    }
    list($acctId,$month,$year) = PostingsController::valid_page($f3,$page);
    $f3->set('POST.acctId',$acctId);
    $f3->set('account_id',$acctId);
    $f3->set('POST.postingDate',date('Y-m-d'));

    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $f3->set('categories_long',$categories->listDesc());

    $start = date('Y').'-01-01';
    $accounts = implode(',',array_keys($actlst));
    $reports = [];
    $table = [];
    foreach (['expenses'=>'<','income'=>'>'] as $i=>$j) {
      $rows = $this->db->exec('SELECT sname,sum(amount) as totals,nsCategory.description as category, nsCategory.categoryId as cid
		FROM nsPosting,nsCategory
		WHERE nsCategory.categoryId = nsPosting.categoryId AND postingDate > ? AND amount '.$j.' 0 AND acctId in ('.$accounts.')
		GROUP BY nsPosting.categoryId',$start);
      foreach ($rows as $row) {
	if (!isset($table[$row['sname']])) $table[$row['sname']] = [];
	$table[$row['sname']][$i] = abs($row['totals']);
	$table[$row['sname']]['cid'] = $row['cid'];
	$table[$row['sname']]['name'] = $row['category'];
      }
    }
    $rows = $this->db->exec('SELECT sname,sum(amount) as totals
		FROM nsPosting,nsCategory
		WHERE nsCategory.categoryId = nsPosting.categoryId AND postingDate > ? AND nsCategory.categoryTypeId = ? AND acctId in ('.$accounts.')
		GROUP BY nsPosting.categoryId',[$start,1]);
    foreach ($rows as $row) {
       if ($row['totals'] > 0) continue;
       $reports[$row['sname']] = -$row['totals'];
    }

    $f3->set('reports',$reports);
    $f3->set('table',$table);
    echo View::instance()->render('welcome.html');
  }
}

