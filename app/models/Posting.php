<?php

class Posting extends BaseModel {
  public function table_name() { return 'nsPosting'; }
  public function id_column() { return 'postingId'; }

  public function pitBalance($acct,$date) {
    list($amt,$bdate) = $this->getBalance($acct,NULL,NULL);
    if ($amt === NULL) return NULL;

    if ($date == $bdate) return $amt; // Trivial case...
    if ($date > $bdate) {
      $rows = $this->db->exec('SELECT SUM(amount) as sum FROM nsPosting '.
			      'WHERE ? < postingDate AND postingDate <= ? '.
			      'AND acctId = ?',[$bdate,$date,$acct]);
    } else {
      $rows = $this->db->exec('SELECT -SUM(amount) as sum FROM nsPosting '.
			      'WHERE ? < postingDate  AND postingDate <= ?'.
			      'AND acctId = ?',[$date,$bdate,$acct]);
    }
    foreach ($rows as $ln) {
      //print_r($ln);
      if ($ln['sum']) $amt += $ln['sum'];
    }
    return $amt;
  }
  public function getBalance($acct,$dftdate = '1970-01-01',$dftamt = 0.0) {
      $date = $dftdate;
      $amount = $dftamt;
      $rows = $this->db->exec('SELECT dateBalance,amount FROM nsBalance WHERE acctId = ?',$acct);
      foreach ($rows as $row) {
        $date = $row['dateBalance'];
	$amount = $row['amount'];
      }
      return [$amount,$date];
    }
    public function setBalance($acct,$date,$amount) {
      $rows = $this->db->exec('SELECT * FROM nsBalance WHERE acctId = ?',$acct);
      if (count($rows)) {
        $this->db->exec('UPDATE nsBalance SET dateBalance=?,amount=? WHERE acctId = ?',
			[$date,$amount,$acct]);
      } else {
	$this->db->exec('INSERT INTO nsBalance(dateBalance,amount,acctId) VALUES (?,?,?)',
			[$date,$amount,$acct]);
      }
    }

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
		[$row[CN_ACCOUNT],intval($row[CN_CATEGORY]),intval($row[CN_CATGRP]),$row[CN_DATE],$row[CN_XID],substr($row[CN_DESCRIPTION],0,40),$row[CN_AMOUNT],$row[CN_TEXT],$row[CN_DETAIL]]);

      //DEBUG
      /*file_put_contents('data/log.txt',print_r(['INSERT INTO '.$this->table().' (acctId,categoryId,catgroup,postingDate,xid,description,amount,text,detail) VALUES (?,?,?,?,?,?,?,?,?)',
		[$row[CN_ACCOUNT],$row[CN_CATEGORY],$row[CN_CATGRP],$row[CN_DATE],$row[CN_XID],$row[CN_DESCRIPTION],$row[CN_AMOUNT],$row[CN_TEXT],$row[CN_DETAIL]]],true),FILE_APPEND);*/
      //DEBUG
    }

    public function listPostings($acct,$month,$year,$cat) {
      $query = '? <= postingDate AND postingDate <= ?';
      $params = [ '', sprintf("%04d-%02d-01",$year,$month),
		  sprintf("%04d-%02d-%02d",$year,$month,date('t',mktime(0,0,0,$month,1,$year))) ];
      if ($acct) {
        $query .= ' AND acctId=?';
	$params[] = $acct;

      }
      if ($cat != 'a') {
	$query .= ' AND categoryId = ?';
	$params[] = $cat;
      }
      $params[0] = $query;

      $this->load($params,['order'=>'postingDate ASC']);

      return $this->query;
    }
    public function listPostings2($acct,$start) {
      $this->load(['acctId=? AND postingDate > ?',$acct,$start],['order'=>'postingDate ASC']);
      return $this->query;
    }
    public function search($params) {
      $this->load($params,['order'=>'postingDate ASC']);
      return $this->query;
    }

}
