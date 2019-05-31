CREATE TABLE llx_c_pac_interest (
  rowid int(11) NOT NULL PRIMARY KEY,
  code varchar(100) DEFAULT NULL,
  label varchar(100) DEFAULT NULL,
  active int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB;