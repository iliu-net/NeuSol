<?php

class PostingsController extends Controller {
  static function default_page($f3) {
    $acctId = '';
    foreach ($f3->get('accounts') as $a=>$b) {
      $acctId = $a;
      break;
    }
    $month = date('n');
    $year = date('Y');
    return implode(',',[$acctId,$month,$year]);
  }
  static function valid_page($f3,$page) {
    if (!preg_match('/^(\d+),(\d+),(\d\d\d\d)$/',$page,$mv)) return false;
    if (!$f3->exists('accounts.'.$mv[1])) return false;
    $month = (int)$mv[2];
    if ($month < 1 or $month > 12) return false;
    return [(int)$mv[1],$month,(int)$mv[3]];
  }
      

  public function index($f3,$params) {
    $debug = 'DEBUG'.PHP_EOL;
    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    if (count($actlst) == 0) {
      $f3->reroute('/acct/msg/No accounts found, please create one!');
      return '';
    }
    $f3->set('accounts',$actlst);

    $page = self::default_page($f3);
    $debug .= "SET PAGE to default ($page)\n";
    if ($f3->exists('COOKIE.page')) {
       $debug .= "COOKIE EXISTS\n";
       $debug .= 'COOKIE: '.$f3->get('COOKIE.page').PHP_EOL;
    }
    if (isset($params['page']) && self::valid_page($f3,$params['page'])) {
      $page = $params['page'];
      $debug .= "HAS PARAMS SO CHANGE TO $page AND SET COOKIE\n";
      $f3->set('JAR.expire',time()+86400*60);
      $f3->set('COOKIE.page',$page);
    } elseif ($f3->exists('COOKIE.page') && self::valid_page($f3,$f3->get('COOKIE.page'))) {
       $page = $f3->get('COOKIE.page');
       $debug .= "SAW COOKIE, NOW PAGE is $page\n";
    }
    $debug .= "PAGE: $page\n";
    list($acctId,$month,$year) = self::valid_page($f3,$page);
    $debug .= "acctId=$acctId, month=$month, year=$year\n";

    $f3->set('account_id',$acctId);
    $f3->set('month',$month);
    $f3->set('year',$year);

    $categories = new Category($this->db);
    $f3->set('categories_short',$categories->listSname());
    $f3->set('categories_long',$categories->listDesc());

    $posting = new Posting($this->db);

    $f3->set('postings',$posting->listPostings($acctId,$month,$year));
    $f3->set('POST.acctId',$acctId);
    $day = date('d'); if ($day > '28') $day = '28';
    $f3->set('POST.postingDate',$year.'-'.$month.'-'.$day);
    $f3->set('debug_msg',$debug);
    /*$x= View::instance()->render('postings_list.html');
    file_put_contents('data/log.txt',$x);
    echo $x;*/
    echo View::instance()->render('postings_list.html');
  }
  public function crud($f3) {
    echo '<pre>';
    print_r($f3->get('POST'));
    echo '</pre>';
    return;
    if (!$f3->exists('POST.postingId')) {
      $f3->reroute('/postings/msg/Invalid CRUD operation');
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
      $f3->reroute('/postings/index/'.$acct.','.$mv[2].','.$mv[1].'/msg/'.$msg);
    } else {
      $f3->reroute('/postings/msg/'.$msg);
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
}
