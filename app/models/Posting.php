<?php

class Posting extends BaseModel {
    public function table_name() { return 'nsPosting'; }
    public function id_column() { return 'postingId'; }

    public function get_uids($from,$to,$accts=[]) {
      $sql = 'SELECT postingDate,amount,xid FROM nsPosting WHERE ? <= postingDate AND postingDate <= ?';
      if (count($accts)) {
        $sql .= ' AND acctId IN ('.implode(',',$accts).')';
      }
      $rows = $this->db->exec($sql,[$from,$to]);
      $res = [];
      foreach ($rows as $row) {
        $uid = implode(':',[$row['postingDate'],(float)$row['amount'],$row['xid']]);
	$res[$uid] = $uid;
      }
      return $res;
    }


    public function newPosting($row) {
    /*
      $pdo = $this->db->pdo();
      $query1 = $pdo->prepare('INSERT INTO '.$this->table().' (categoryId,catgroup,postingDate,xid,description,amount) VALUES (?,?,?,?,?,?)');
      $query1->execute([$row[CN_CATEGORY],$row[CN_CATGRP],$row[CN_DATE],$row[CN_XID],$row[CN_DESCRIPTION],$row[CN_AMOUNT]]);
      $lastId =  $pdo->lastInsertId();
      $query2-> $pdo->prepare('INSERT INTO '.$this->table().'Details (postingId,text,detail) VALUES (?,?,?)');
      $query2->$pdo->execute([$lastId,$row[CN_TEXT],$row[CN_DETAIL]]);
      */
      /*
      $this->db->exec('INSERT INTO '.$this->table().' (acctId,categoryId,catgroup,postingDate,xid,description,amount) VALUES (?,?,?,?,?,?,?)',
		[$row[CN_ACCOUNT,$row[CN_CATEGORY],$row[CN_CATGRP],$row[CN_DATE],$row[CN_XID],$row[CN_DESCRIPTION],$row[CN_AMOUNT]]);
      $lastId =  $this->db->pdo()->lastInsertId();
      $this->db->exec('INSERT INTO '.$this->table().'Details (postingId,text,detail) VALUES (?,?,?)',[$lastId,$row[CN_TEXT],$row[CN_DETAIL]]);
      */
      $this->db->exec('INSERT INTO '.$this->table().' (acctId,categoryId,catgroup,postingDate,xid,description,amount,text,detail) VALUES (?,?,?,?,?,?,?,?,?)',
		[$row[CN_ACCOUNT],$row[CN_CATEGORY],$row[CN_CATGRP],$row[CN_DATE],$row[CN_XID],$row[CN_DESCRIPTION],$row[CN_AMOUNT],$row[CN_TEXT],$row[CN_DETAIL]]);

      //DEBUG
      file_put_contents('data/log.txt',print_r(['INSERT INTO '.$this->table().' (acctId,categoryId,catgroup,postingDate,xid,description,amount,text,detail) VALUES (?,?,?,?,?,?,?,?,?)',
		[$row[CN_ACCOUNT],$row[CN_CATEGORY],$row[CN_CATGRP],$row[CN_DATE],$row[CN_XID],$row[CN_DESCRIPTION],$row[CN_AMOUNT],$row[CN_TEXT],$row[CN_DETAIL]]],true),FILE_APPEND);
      //DEBUG
    }

    public function listPostings($acct,$month,$year) {
        $this->load(['acctId=? AND ? <= postingDate AND postingDate <= ?',
			$acct, sprintf("%04d-%02d-01",$year,$month),
			sprintf("%04d-%02d-%02d",$year,$month,date('t',mktime(0,0,0,$month,1,$year)))],
		    ['order'=>'postingDate ASC']);
	//$this->load(['acctId=?',$acct]);
	//$this->load();
        return $this->query;
    }
}
