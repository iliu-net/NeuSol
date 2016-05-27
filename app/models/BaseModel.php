<?php

abstract class BaseModel extends DB\SQL\Mapper {
    abstract public function table_name();
    abstract public function id_column();

    public function __construct(DB\SQL $db) {
        parent::__construct($db,$this->table_name());
    }
    public function listColumn($col,$orderby='') {
      return $this->defineDict($this->id_column(),$col,$orderby);
    }
    public function defineDict($col1,$col2,$orderby='') {
      $res = [];
      $table = $this->table_name();
      $rows = $this->db->exec('SELECT '.$col1.','.$col2.' FROM '.$table.
	($orderby != '' ? 'ORDER BY '.$orderby : ''));
      foreach ($rows as $row) {
        $res[$row[$col1]] = $row[$col2];
      }
      return $res;
    }

    public function all() {
        $this->load();
        return $this->query;
    }
    public function getByColumn($col,$val) {
        $this->load([$col.'=?',$val]);
	return $this->query;
    }

    public function add() {
        $this->copyFrom('POST');
        $this->save();
    }
    
    public function getById($id) {
        $this->load([$this->id_column().'=?',$id]);
	$this->copyTo('POST');
    }

    public function edit($id) {
        $this->load([$this->id_column().'=?',$id]);
        $this->copyFrom('POST');
        $this->update();
    }

    public function delete($id) {
        $this->load([$this->id_column().'=?',$id]);
	$this->erase();
    }
}
