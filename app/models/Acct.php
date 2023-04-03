<?php

class Acct extends BaseModel {
    public function table_name() { return 'nsAcct'; }
    public function id_column() { return 'acctId'; }

    public function defineDict($col1,$col2,$orderby='',$hide=TRUE) {
      $res = [];
      $table = $this->table_name();
      $rows = $this->db->exec('SELECT '.$col1.','.$col2.' FROM '.$table.
	($hide ? ' WHERE hide=0 ' : '') .
	($orderby != '' ? 'ORDER BY '.$orderby : ''));
      foreach ($rows as $row) {
        $res[$row[$col1]] = $row[$col2];
      }
      return $res;
    }
    public function listColumn($col,$orderby='',$hide=TRUE) {
      return $this->defineDict($this->id_column(),$col,$orderby,$hide);
    }
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
    public function listDesc($hide=TRUE) {
      return $this->listColumn('description','',$hide);
    }
    public function listAcctNo() {
      return $this->listColumn('acctNo');
    }
    public function listSname($hide=TRUE) {
      return $this->listColumn('sname','',$hide);
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
