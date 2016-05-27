<?php

class AcctController extends Controller {
  public function index($f3,$params) {
    $accts = new Acct($this->db);
    $f3->set('accounts',$accts->all());
    echo View::instance()->render('acct_list.html');	
  }

  function validateForm($f3,$type,$id=null) {
    $query = $type->getBySName($f3->get('POST.sname'));
    if (count($query) != 0) {
      if (is_null($id) || count($query) > 1) return 'Duplicate SNAME!';
      list($match) = $query;
      if ($match->acctId != $id) return 'SNAME already in use';
    }
    if (!preg_match('/^[_A-Z][_A-Z0-9]*$/',$f3->get('POST.sname'))) {
      return 'Invalid SNAME format';
    }
    return '';
  }

  public function create($f3,$params) {
    if ($f3->exists('POST.create')) {
      $acct = new Acct($this->db);
      if (($msg = $this->validateForm($f3,$acct)) == '') {
        $acct->reset();
        $acct->add();
        $f3->reroute('/acct/msg/New entry created');
        return;
      } else {
	$f3->set('msg',$msg);
      }
    }
    $f3->set('msg',$msg);
    $f3->set('form_action',Sc::url('/acct/create'));
    $f3->set('page_head','Create Account');
    $f3->set('form_command','create');
    $f3->set('form_label','Add Account');
    echo View::instance()->render('acct_detail.html');
  }
  public function update($f3,$params) {
    $acct = new Acct($this->db);
    if ($f3->exists('POST.update')) {
      if (($msg = $this->validateForm($f3,$acct,$f3->get('POST.acctId'))) == '') {
        $acct->edit($f3->get('POST.acctId'));
        $f3->reroute('/acct/msg/Entry updated');
        return;
      } else {
        $f3->set('msg',$msg);
      }
    } else {
      $acct->getById($params['id']);
      if (!$f3->get('POST.acctId')) {
        $f3->reroute('/category/msg/Lookup Error');
	return;
      }
   }
    $f3->set('form_action',Sc::url('/acct/update'));
    $f3->set('page_head','Edit Account');
    $f3->set('form_command','update');
    $f3->set('form_label','Update');
    echo View::instance()->render('acct_detail.html');
  }
  public function delete($f3,$params) {
    if (isset($params['id'])) {
      $id = $params['id'];
      $acct = new Acct($this->db);
      $count = $acct->countPostings($id);
      if ($count > 0) {
        $f3->reroute('/acct/msg/Acct '.$id.' can not be deleted. ('.$count.' postings)');
      } else {
      	$acct->delete($id);
      	$f3->reroute('/acct/msg/Acct '.$id.' deleted!');
      }
    } else {
      $f3->reroute('/acct');
    }
  }
}
