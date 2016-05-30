<?php

class CategoryController extends Controller {
  public function index($f3,$params) {
    $types = new CategoryType($this->db);
    $f3->set('category_types', $types->listDesc());

    $cats = new Category($this->db);
    $f3->set('categories',$cats->all());
    echo View::instance()->render('category_list.html');	
  }
  function validateForm($f3,$type,$id=null) {
    $query = $type->getBySName($f3->get('POST.sname'));
    if (count($query) != 0) {
      if (is_null($id) || count($query) > 1) return 'Duplicate SNAME!';
      list($match) = $query;
      if ($match->categoryId != $id) return 'SNAME already in use';
    }
    if (!preg_match('/^[_A-Z][_A-Z0-9]*$/',$f3->get('POST.sname'))) {
      return 'Invalid SNAME format';
    }
    return '';
  }

  public function create($f3,$params) {
    $types = new CategoryType($this->db);
    $f3->set('category_types', $types->listDesc());

    if ($f3->exists('POST.create')) {
      $type = new Category($this->db);
      if (($msg = $this->validateForm($f3,$type)) == '') {
	$type->reset();
        $type->add();
        $f3->reroute('/category/msg/New entry created');
	return;
      } else {
	$f3->set('msg',$msg);
      }
    }
    $f3->set('form_action',Sc::url('/category/create'));
    $f3->set('page_head','Create Category');
    $f3->set('form_command','create');
    $f3->set('form_label','Add Category');
    echo View::instance()->render('category_detail.html');
  }
  public function update($f3,$params) {
    $types = new CategoryType($this->db);
    $f3->set('category_types', $types->listDesc());

    $type = new Category($this->db);
    if ($f3->exists('POST.update')) {
      if (($msg = $this->validateForm($f3,$type,$f3->get('POST.categoryId'))) == '') {
	$type->reset();
        $type->edit($f3->get('POST.categoryId'));
        $f3->reroute('/category/msg/Entry updated');
	return;
      } else {
	$f3->set('msg',$msg);
      }
    } else {
      $type->getById($params['id']);
      if (!$f3->get('POST.categoryId')) {
        $f3->reroute('/category/msg/Lookup Error');
	return;
      }
    }
    $f3->set('form_action',Sc::url('/category/update'));
    $f3->set('page_head','Edit Category');
    $f3->set('form_command','update');
    $f3->set('form_label','Update');
    echo View::instance()->render('category_detail.html');
  }
  public function delete($f3,$params) {
    if (isset($params['id'])) {
      $id = $params['id'];
      $cat = new Category($this->db);
      $count = $cat->countPostings($id);
      if ($count > 0) {
        $f3->reroute('/category/msg/Category '.$id.' can not be deleted. ('.$count.' postings)');
      } else {
	$cat->delete($id);
	$f3->reroute('/category/msg/Category '.$id.' deleted!');
      }
    } else {
      $f3->reroute('/category');
    }
  }
}
