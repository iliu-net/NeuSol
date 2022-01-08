<?php

function sort_rules_by_prio($a,$b) {
  if ($a['pri'] == $b['pri']) {
    return ($a['last_match'] <=> $b['last_match']);
  }
  return $a['pri'] <=> $b['pri'];
}
class Rule extends BaseModel {
  static public function sort_rules(&$all) {
    usort($all,'sort_rules_by_prio');
  }
  static public function update_counters($db) {
    $rules = new Rule($this->db);
    $all = $rules->all();
  }
  public function table_name() { return 'nsRule'; }
  public function id_column() { return 'ruleId'; }

  public function qcheck($desc_re) {
    $rows = $this->db->exec('SELECT * FROM nsRule WHERE desc_re = ? '.
			'AND acctId IS NULL '.
			'AND text_re IS NULL '.
			'AND min_amount IS NULL '.
			'AND max_amount IS NULL '.
			'AND detail_re IS NULL', $desc_re);
    return count($rows);
  }
  public function clearStats() {
    $this->db->exec('UPDATE nsRule SET ytd_matches=0 '.
		    'WHERE ytd_matches <> 0 '.
		    'AND YEAR(last_match) <> YEAR(CURDATE())');
  }
  public function updateRuleStat($rid) {
    $this->db->exec('UPDATE nsRule SET '.
			'last_match = CURDATE(), '.
			'total_matches = total_matches + 1, '.
			'ytd_matches = ytd_matches + 1 '.
			'WHERE ruleId = ?', $rid);
  }
}
