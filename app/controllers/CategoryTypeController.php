<?php

class CategoryTypeController extends Controller {
  public function index($f3,$params) {
    $types = new CategoryType($this->db);
    $f3->set('categorytypes',$types->all());
    echo View::instance()->render('categorytype_list.html');	
  }
  public function create($f3,$params) {
    if ($f3->exists('POST.create')) {
      $type = new CategoryType($this->db);
      $type->reset();
      $type->add();
      $f3->reroute('/categorytype/msg/New entry created');
      return;
    }
    $f3->set('form_action',Sc::url('/categorytype/create'));
    $f3->set('page_head','Create Category Type');
    $f3->set('form_command','create');
    $f3->set('form_label','Add Category Type');
    echo View::instance()->render('categorytype_detail.html');
  }
  public function update($f3,$params) {
    $type = new CategoryType($this->db);
    if ($f3->exists('POST.update')) {
      $type->reset();
      $type->edit($f3->get('POST.categoryTypeId'));
      $f3->reroute('/categorytype/msg/Entry updated');
      return;
    } else {
      $type->getById($params['id']);
      if (!$f3->get('POST.categoryTypeId')) {
        $f3->reroute('/categorytype/msg/Lookup Error');
	return;
      }
    }
    $f3->set('form_action',Sc::url('/categorytype/update'));
    $f3->set('page_head','Edit Category Type');
    $f3->set('form_command','update');
    $f3->set('form_label','Update');
    echo View::instance()->render('categorytype_detail.html');
  }
  public function delete($f3,$params) {
    if (isset($params['id'])) {
      $id = $params['id'];
      $cat = new CategoryType($this->db);
      $count = $cat->countCategories($id);
      if ($count > 0) {
        $f3->reroute('/categorytype/msg/Category Type '.$id.' can not be deleted. ('.$count.' categories)');
      } else {
	$cat->delete($id);
	$f3->reroute('/categorytype/msg/Category '.$id.' deleted!');
      }
    } else {
      $f3->reroute('/categorytype');
    }
  }
}
