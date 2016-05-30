<?php

class CategoryType extends BaseModel {
    public function table_name() { return 'nsCategoryType'; }
    public function id_column() { return 'categoryTypeId'; }

    public function listDesc() {
      return $this->listColumn('description');
    }
    public function countCategories($id) {
      $rows = $this->db->exec('SELECT count(*) as count FROM nsCategory WHERE categoryTypeId = ?',$id);
      if (!$rows) return 0;
      if (count($rows) == 0) return 0;
      return $rows[0]['count'];
    }
}
