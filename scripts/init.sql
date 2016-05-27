DROP TABLE IF EXISTS nsPostingType;
DROP TABLE IF EXISTS nsAcctTypePostingType;
DROP TABLE IF EXISTS nsAcctType;


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

DROP TABLE IF EXISTS nsCategory;
CREATE TABLE IF NOT EXISTS nsCategory (
  categoryId  INT  NOT NULL  AUTO_INCREMENT ,
  description  VARCHAR  (40) NOT NULL DEFAULT "",
  sname CHAR(16) NOT NULL DEFAULT "",
  remarks VARCHAR(255) NOT NULL DEFAULT "",
  PRIMARY KEY (categoryId)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (1,"House Mortgage","MORTGAGE");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (2,"Insurance/Finance Fee","INSURANCE");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (3,"Daycare","DAYCARE");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (4,"Education","EDUCATION");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (5,"Groceries/Toiletries","GROCERIES");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (6,"Dining","DINING");
INSERT INTO nsCategory (categoryId, description, sname,remarks)
  VALUES (7,"Utilities","UTILS","Electricity, Water, Etc...");
INSERT INTO nsCategory (categoryId, description, sname,remarks)
  VALUES (8,"Telecoms","TELCOS",  "Phone, Internet, Etc...");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (9,"Transportation","TRANSPORT");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (10,"Services","SERVICES");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (11,"Entertainment","ENTERTAIN");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (12,"Holidays","HOLIDAYS");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (13,"Personal Care","PERSONAL");
INSERT INTO nsCategory (categoryId, description, sname)
  VALUES (14,"Clothes","CLOTHES");

DROP TABLE IF EXISTS nsPosting;
CREATE TABLE IF NOT EXISTS nsPosting (
  postingId  INT  NOT NULL  AUTO_INCREMENT ,
  acctId INT NOT NULL DEFAULT 0,
  categoryId INT NOT NULL DEFAULT 0,
  catgroup INT NOT NULL DEFAULT 0,
  postingDate DATE NOT NULL DEFAULT "1970-01-01",
  xid INT NOT NULL DEFAULT 0,
  description  VARCHAR  (40)  NOT NULL DEFAULT "",
  amount DECIMAL (20,2) NOT NULL DEFAULT 0.0,
  text VARCHAR(1500) NOT NULL DEFAULT "",  
  detail  VARCHAR  (2040)  NOT NULL DEFAULT "",
  PRIMARY KEY (  postingId  ),
  KEY (acctId,postingDate),
  KEY (acctId,postingDate,xid,amount)
) DEFAULT CHARSET='utf8'  COLLATE='utf8_general_ci'  AUTO_INCREMENT=1;

