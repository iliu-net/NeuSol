<?php

class Category extends BaseModel {
    public function table_name() { return 'nsCategory'; }
    public function id_column() { return 'categoryId'; }

    public function listDesc() {
      return $this->listColumn('description');
    }
    public function listSname() {
      return $this->listColumn('sname');
    }
    public function getBySName($sname) {
      return $this->getByColumn('sname',$sname);
    }
    public function countPostings($id) {
      $rows = $this->db->exec('SELECT count(*) as count FROM nsPosting WHERE categoryId = ?',$id);
      if (!$rows) return 0;
      if (count($rows) == 0) return 0;
      return $rows[0]['count'];
    }

}
