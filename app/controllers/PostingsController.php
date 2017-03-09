<?php

class PostingsController extends Controller {
  static function default_page($f3) {
    $acctId = '';
    if ($f3->exists('accounts_long')) {
      foreach ($f3->get('accounts_long') as $a=>$b) {
	$acctId = $a;
	break;
      }
    } else {
      foreach ($f3->get('accounts') as $a=>$b) {
	$acctId = $a;
	break;
      }
    }
    $month = date('n');
    $year = date('Y');
    return implode(',',[$acctId,$month,$year,'a']);
  }
  static function valid_page($f3,$page) {
    if (!preg_match('/^(\d+),(\d+),(\d\d\d\d),([a0-9]\d*)$/',$page,$mv)) return false;
    if ((int)$mv != 0 && !$f3->exists('accounts.'.$mv[1])) return false;
    $month = (int)$mv[2];
    if ($month < 1 or $month > 12) return false;
    return [(int)$mv[1],$month,(int)$mv[3],$mv[4]];
  }


  public function index($f3,$params) {
    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    $f3->set('accounts_long',$actlst);
    $actsel = [ 0 => "** All Accounts **" ];
    foreach ($actlst as $i=>$j) {
      $actsel[$i] = $j;
    }
    $f3->set('accounts',$actsel);

    $page = self::default_page($f3);
    if (isset($params['page']) && self::valid_page($f3,$params['page'])) {
      $page = $params['page'];
      $f3->set('JAR.expire',time()+86400*60);
      $f3->set('COOKIE.page',$page);
    } elseif ($f3->exists('COOKIE.page') && self::valid_page($f3,$f3->get('COOKIE.page'))) {
       $page = $f3->get('COOKIE.page');
    }
    list($acctId,$month,$year,$selcat) = self::valid_page($f3,$page);

    $f3->set('account_id',$acctId);
    $f3->set('month',$month);
    $f3->set('year',$year);
    $f3->set('category_page',$selcat);
    if ($selcat.'' != '0' && $selcat.'' != 'a' && !$f3->exists('POST.categoryId')) {
      $f3->set('POST.categoryId',$selcat);
    } else {
      if ($f3->exists('COOKIE.lastCategory')) {
        $f3->set('POST.categoryId',$f3->get('COOKIE.lastCategory'));
      }
    }
    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $cc = $categories->listDesc();
    $f3->set('categories_long',$cc);
    $catopts = [ 'a' => "** All Categories **", '0' => 'No category' ];
    foreach ($cc as $i=>$j) {
      $catopts[$i] = $j;
    }
    $f3->set('categories_opt',$catopts);

    $posting = new Posting($this->db);

    $f3->set('postings',$posting->listPostings($acctId,$month,$year,$selcat));
    $f3->set('POST.acctId',$acctId);
    $day = date('d'); if ($day > '28') $day = '28';
    $f3->set('POST.postingDate',$year.'-'.$month.'-'.$day);

    if ($acctId && $selcat == 'a') {
      $f3->set('bal_title','Starting Balance');
      $f3->set('bal_header','Balance');
      $f3->set('balance',$posting->pitBalance($acctId,date('Y-m-d',mktime(12,0,0,$month,1,$year)-86400)));
    } else {
      $f3->set('bal_title','');
      $f3->set('bal_header','Running Total');
      $f3->set('balance',0.0);
    }

    echo View::instance()->render('postings_list.html');
  }
  public function crud($f3,$params) {
  /*
    echo '<pre>';
    print_r([$f3->get('POST'),$params]);
    echo '</pre>';
    return;
    */
    if ($f3->exists('POST.categoryId')) {
      $f3->set('JAR.expire',time()+86400*60);
      $f3->set('COOKIE.lastCategory',$f3->get('POST.categoryId'));
    }

    $next = isset($params['next']) ? '/'.$params['next'].'/' : '/postings/';
    if (!$f3->exists('POST.postingId')) {
      $f3->reroute($next.'msg/Invalid CRUD operation');
      return;
    }
    $posting = new Posting($this->db);
    $id = $f3->get('POST.postingId');
    if ($id) {
      // Update
      $posting->edit($id);
      $msg = 'Updated '.$id;
    } else {
      // Create
      $posting->add();
      $msg = 'Created entry';
    }
    $acct = $f3->get('POST.acctId');
    $date = $f3->get('POST.postingDate');
    if (preg_match('/^(\d\d\d\d)-(\d\d)-\d\d$/',$date,$mv)) {
      $f3->reroute($next.'index/'.$acct.','.$mv[2].','.$mv[1].'/msg/'.$msg);
    } else {
      $f3->reroute($next.'msg/'.$msg);
    }
  }
  public function delete($f3,$params) {
    if (isset($params['id'])) {
      $id = $params['id'];
      $posting = new Posting($this->db);
      $posting->delete($id);
      $f3->reroute('/postings/msg/Record '.$id.' deleted!');
    } else {
      $f3->reroute('/postings');
    }
  }

  public function balance($f3,$params) {
    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    $f3->set('accounts',$actlst);

    $page = self::default_page($f3);
    if (isset($params['acct']) && isset($actlst[$params['acct']])) {
      list($acctId,$month,$year,$selcat) = self::valid_page($f3,$page);
      $page = implode(',',[$params['acct'],$month,$year,$selcat]);
      $f3->set('JAR.expire',time()+86400*60);
      $f3->set('COOKIE.page',$page);
    } elseif ($f3->exists('COOKIE.page') && self::valid_page($f3,$f3->get('COOKIE.page'))) {
       $page = $f3->get('COOKIE.page');
    }
    list($acctId,$month,$year,$selcat) = self::valid_page($f3,$page);
    if ($selcat.'' != '0' && $selcat.'' != 'a' && !$f3->exists('POST.categoryId')) {
      $f3->set('POST.categoryId',$selcat);
    } else {
      if ($f3->exists('COOKIE.lastCategory')) {
        $f3->set('POST.categoryId',$f3->get('COOKIE.lastCategory'));
      }
    }

    $f3->set('account_id',$acctId);

    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $cc = $categories->listDesc();
    $f3->set('categories_long',$cc);

    $posting = new Posting($this->db);
    list($amount,$start) = $posting->getBalance($acctId);
    $f3->set('postings',$posting->listPostings2($acctId,$start));
    $f3->set('bal_title','Starting Balance');
    $f3->set('bal_header','Balance');
    $f3->set('balance',$amount);

    $f3->set('POST.acctId',$acctId);
    $f3->set('POST.postingDate',date('Y').'-'.date('m').'-'.date('d'));
    echo View::instance()->render('postings_balance.html');


  }
  public function newbalance($f3,$params) {
    /*echo '<pre>';
    print_r([$f3->get('POST'),$params]);
    echo '</pre>';*/
    $acctId = $f3->get('POST.acctId');
    $dateBalance = $f3->get('POST.dateBalance');
    $amount = $f3->get('POST.amount');

    $posting = new Posting($this->db);
    $posting->setBalance($acctId,$dateBalance,$amount);
    $f3->reroute($next.'msg/Balanced Account '.$acctId);
    return;
  }
}
