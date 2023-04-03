<?php
class RuleController extends Controller {
  public function index($f3,$params) {
    $acct = new Acct($this->db);
    $actlst = $acct->listSname(FALSE);
    $f3->set('accounts',$actlst);

    $categories = new Category($this->db);
    $f3->set('categories',$categories->listSname());

    $rules = new Rule($this->db);
    $rules->clearStats();
    $all = $rules->all();
    //~ echo '<pre>';print_r($all); echo '</pre>';
    Rule::sort_rules($all);
    //~ echo '<pre>';print_r($all); echo '</pre>';
    $f3->set('rules',$all);
    echo View::instance()->render('rule_list.html');
  }
  function validateForm($f3,$type,$id=null) {
    $cri = ['desc_re','text_re','min_amount','max_amount', 'detail_re', 'acctId'];

    $non_null = 0;
    foreach ($cri as $ff) {
      $pv = $f3->get('POST.'.$ff);
      if ($pv == '') {
	$f3->clear('POST.'.$ff);
      } else {
	$non_null++;
      }
    }
    if ($f3->get('POST.catgroup') == '') {
      $f3->clear('POST.catgroup');
    }
    if ($non_null == 0) return 'No matching criteria specified';

    // Check if there is already a similar rule...
    $rules = $type->all();
    foreach ($rules as $rr) {
      $test = true;
      foreach ($cri as $ff) {
	$cur = $rr->get($ff);
	if (is_null($cur) && !$f3->exists('POST.'.$ff)) continue;
	if ($cur == $f3->get('POST.'.$ff)) continue;
	$test = false;
	break;
      }
      if ($test) {
	$cid = $rr->get('ruleId');
	if ($f3->exists('POST.ruleId')) {
	  if ($f3->get('POST.ruleId') == $cid) continue;
	}
	return 'Repeated criteria as rule '.$cid;
      }
    }
    return '';
  }
  public function update($f3,$params) {
    $type = new Rule($this->db);
    if ($f3->exists('POST.update')) {
      if (($msg = $this->validateForm($f3,$type,$f3->get('POST.ruleId'))) == '') {
	$type->reset();
        $type->edit($f3->get('POST.ruleId'));
        $f3->reroute('/rule/msg/Entry updated');
      } else {
	$f3->set('msg',$msg);
      }
    } else {
      $type->getById($params['id']);
      if (!$f3->get('POST.ruleId')) {
        $f3->reroute('/rule/msg/Lookup Error');
	return;
      }
    }

    $types = new CategoryType($this->db);
    $f3->set('category_types', $types->listDesc());

    $f3->set('form_action',Sc::url('/rule/update'));
    $f3->set('page_head','Edit Rule');
    $f3->set('form_command','update');
    $f3->set('form_label','Update Rule');

    $categories = new Category($this->db);
    $f3->set('categories',$categories->listDesc());

    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    $actsel = [ '' => "** ANY ACCOUNT **" ];
    foreach ($actlst as $i=>$j) {
      $actsel[$i] = $j;
    }
    $f3->set('accounts',$actsel);

    echo View::instance()->render('rule_edit.html');
  }

  public function create($f3,$params) {
    if ($f3->exists('POST.create')) {
      $type = new Rule($this->db);
      if (($msg = $this->validateForm($f3,$type)) == '') {
	$type->reset();
        $type->add();
        $f3->reroute('/rule/msg/New entry created');
	return;
      } else {
	$f3->set('msg',$msg);
      }
    }

    $f3->set('form_action',Sc::url('/rule/create'));
    $f3->set('page_head','Create Rule');
    $f3->set('form_command','create');
    $f3->set('form_label','Add Rule');

    $categories = new Category($this->db);
    $f3->set('categories',$categories->listDesc());

    $acct = new Acct($this->db);
    $actlst = $acct->listDesc();
    $actsel = [ '' => "** ANY ACCOUNT **" ];
    foreach ($actlst as $i=>$j) {
      $actsel[$i] = $j;
    }
    $f3->set('accounts',$actsel);

    echo View::instance()->render('rule_edit.html');
  }
  public function delete($f3,$params) {
    if (isset($params['id'])) {
      $id = $params['id'];
      $rules = new Rule($this->db);
      $rules->delete($id);
      $f3->reroute('/rule/msg/Rule '.$id.' deleted!');
    } else {
      $f3->reroute('/category');
    }
  }
}
