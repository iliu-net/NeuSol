<?php

class Acct extends BaseModel {
    public function table_name() { return 'nsAcct'; }
    public function id_column() { return 'acctId'; }

    public function countPostings($id) {
      $rows = $this->db->exec('SELECT count(*) as count FROM nsPosting WHERE acctId = ?',$id);
      if (!$rows) return 0;
      if (count($rows) == 0) return 0;
      return $rows[0]['count'];
    }
    public function delete($id) {
      parent::delete($id);
      $this->db->exec('DELETE FROM nsEquity WHERE acctId =?',$id);
    }
      

    public function listDesc() {
      return $this->listColumn('description');
    }
    public function listAcctNo() {
      return $this->listColumn('acctNo');
    }
    public function listSname() {
      return $this->listColumn('sname');
    }
    public function getBySName($sname) {
      return $this->getByColumn('sname',$sname);
    }
    public function listNumbers() {
      $res = [];
      $table = $this->table_name();
      $rows = $this->db->exec('SELECT acctNo,acctId FROM '.$table);
      foreach ($rows as $row) {
	$id = $row['acctId'];
	$num = preg_replace('/[^0-9]/','',$row['acctNo']);
	if ($num == '') continue;
	$res[$num] = $id;
      }
      return $res;
    }
}
