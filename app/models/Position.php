<?php

class Position extends BaseModel {
  public function table_name() { return 'nsEquity'; }
  public function id_column() { return ['acctId','positionDate']; }

  public function listPos($where = 1,$params = []) {
    $rows = $this->db->exec('SELECT DISTINCT positionDate FROM '.$this->table_name().' WHERE '.$where,$params);
    $res = [];
    foreach ($rows as $r) {
      $res[$r['positionDate']] = $r['positionDate'];
    }
    krsort($res);
    return $res;
  }

  public function getpos($where,$params) {
    $rows = $this->db->exec('SELECT acctId,positionDate,amount FROM '.$this->table_name().' WHERE '.$where, $params);
    $res = [];
    foreach ($rows as $r) {
      if (!isset($res[$r['positionDate']])) $res[$r['positionDate']] = [];
      $res[$r['positionDate']][$r['acctId']] = $r['amount'];
    }
    return $res;
  }
  public function remove($pos) {
    $this->db->exec('DELETE FROM '.$this->table_name().' WHERE positionDate = ?',[$pos]);
  }

  public function modify($acct,$date,$amt) {
    $rows = $this->db->exec('SELECT count(*) as cnt FROM '.$this->table_name().
			    ' WHERE acctId = ? AND positionDate = ?',
			    [ $acct,$date ]);
    list($rows) = $rows;
    print_r($rows);
    if ($rows['cnt'] == 0) {
      $this->db->exec('INSERT INTO '.$this->table_name().' (acctId,positionDate,amount) VALUES (?,?,?)',
		      [ $acct, $date, $amt ] );
    } else {
      $this->db->exec('UPDATE '.$this->table_name().' SET amount=? WHERE acctId = ? AND positionDate = ?',
		      [ $amt, $acct, $date ]);
    }
  }
}
