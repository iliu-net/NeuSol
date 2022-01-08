
DROP TABLE IF EXISTS nsAcct;
CREATE TABLE IF NOT EXISTS nsAcct (
  acctId  INT  NOT NULL  AUTO_INCREMENT ,
  description  VARCHAR  (40) NOT NULL DEFAULT "",
  acctNo VARCHAR (32) NOT NULL DEFAULT "",
  symbol VARCHAR (16) NOT NULL DEFAULT "",
  sname VARCHAR (16) NOT NULL DEFAULT "",
  remarks VARCHAR(255) NOT NULL DEFAULT "",
  hint VARCHAR(32) NOT NULL DEFAULT "",
  PRIMARY KEY (acctId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;
INSERT INTO nsAcct (acctId,description,acctNo,symbol,sname)
	VALUES (1,"Cash Account","","","CASH");
INSERT INTO nsAcct (acctId,description,acctNo,symbol,sname)
	VALUES (2,"Checking Acct","73.59.99.5543","HSBC","CHECKING");
INSERT INTO nsAcct (acctId,description,acctNo,symbol,sname)
	VALUES (3,"Savings Acct","99.94.83.003","HSBC","SAVINGS");

DROP TABLE IF EXISTS nsCategoryType;
CREATE TABLE IF NOT EXISTS nsCategoryType (
  categoryTypeId INT NOT NULL AUTO_INCREMENT,
  description VARCHAR(32) NOT NULL DEFAULT "",
  PRIMARY KEY (categoryTypeId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;
INSERT INTO nsCategoryType (categoryTypeId,description)
  VALUES (1,"expenses");
INSERT INTO nsCategoryType (categoryTypeId,description)
  VALUES (2,"income");
INSERT INTO nsCategoryType (categoryTypeId,description)
  VALUES (3,"adj");


DROP TABLE IF EXISTS nsCategory;
CREATE TABLE IF NOT EXISTS nsCategory (
  categoryId  INT  NOT NULL  AUTO_INCREMENT ,
  description  VARCHAR  (40) NOT NULL DEFAULT "",
  sname CHAR(16) NOT NULL DEFAULT "",
  remarks VARCHAR(255) NOT NULL DEFAULT "",
  categoryTypeId INT NOT NULL DEFAULT 0,
  PRIMARY KEY (categoryId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (1,"House Mortgage","MORTGAGE",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (2,"Insurance/Finance Fee","INSURANCE",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (3,"Daycare","DAYCARE",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (4,"Education","EDUCATION",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (5,"Groceries/Toiletries","GROCERIES",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (6,"Dining","DINING",1);
INSERT INTO nsCategory (categoryId, description, sname,remarks,categoryTypeId)
  VALUES (7,"Utilities","UTILS","Electricity, Water, Etc...",1);
INSERT INTO nsCategory (categoryId, description, sname,remarks,categoryTypeId)
  VALUES (8,"Telecoms","TELCOS",  "Phone, Internet, Etc...",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (9,"Transportation","TRANSPORT",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (10,"Services","SERVICES",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (11,"Entertainment","ENTERTAIN",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (12,"Holidays","HOLIDAYS",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (13,"Personal Care","PERSONAL",1);
INSERT INTO nsCategory (categoryId, description, sname,categoryTypeId)
  VALUES (14,"Clothes","CLOTHES",1);

DROP TABLE IF EXISTS nsPosting;
CREATE TABLE IF NOT EXISTS nsPosting (
  postingId  INT  NOT NULL  AUTO_INCREMENT ,
  acctId INT NOT NULL DEFAULT 0,
  categoryId INT NOT NULL DEFAULT 0,
  catgroup INT NOT NULL DEFAULT 0,
  postingDate DATE NOT NULL DEFAULT "1970-01-01",
  xid INT UNSIGNED NOT NULL DEFAULT 0,
  description  VARCHAR  (40)  NOT NULL DEFAULT "",
  amount DECIMAL (20,2) NOT NULL DEFAULT 0.0,
  text VARCHAR(1500) NOT NULL DEFAULT "",  
  detail  VARCHAR  (2040)  NOT NULL DEFAULT "",
  PRIMARY KEY (  postingId  ),
  KEY (acctId,postingDate),
  KEY (acctId,postingDate,xid,amount)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;

DROP TABLE IF EXISTS nsBalance;
CREATE TABLE IF NOT EXISTS nsBalance (
  acctId INT NOT NULL DEFAULT 0,
  dateBalance DATE NOT NULL DEFAULT "1970-01-01",
  amount DECIMAL (20,2) NOT NULL DEFAULT 0.0,
  PRIMARY KEY (acctId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci';

DROP TABLE IF EXISTS nsEquity;
CREATE TABLE IF NOT EXISTS nsEquity (
  acctId INT NOT NULL DEFAULT 0,
  positionDate DATE NOT NULL DEFAULT "1970-01-01",
  amount DECIMAL (20,2) NOT NULL DEFAULT 0.0,
  PRIMARY KEY (acctId,positionDate)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci';
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
