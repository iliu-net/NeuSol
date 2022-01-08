DROP TABLE IF EXISTS nsRule;
CREATE TABLE IF NOT EXISTS nsRule (
  ruleId INT NOT NULL AUTO_INCREMENT,
  pri INT NOT NULL DEFAULT 0,

  /* Target category */
  categoryId INT NOT NULL DEFAULT 0,
  catgroup INT,

  /* Matching criteria */
  acctId INT,
  desc_re VARCHAR(40),
  text_re VARCHAR(128),

  min_amount INT,
  max_amount INT,

  detail_re VARCHAR(128),

  /* Misc comments */
  remark VARCHAR(256),

  /* Statistics */
  last_match DATE NOT NULL DEFAULT "1970-01-01",
  total_matches INT NOT NULL DEFAULT 0,
  ytd_matches INT NOT NULL DEFAULT 0,

  PRIMARY KEY (ruleId),
  KEY (pri,ruleId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci';
