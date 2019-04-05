CREATE TABLE llx_c_order_type (
  rowid int(11) NOT NULL DEFAULT '0' PRIMARY KEY,
  date_cre datetime DEFAULT NULL,
  date_maj datetime DEFAULT NULL,
  label varchar(100) DEFAULT NULL,
  active int(11) NOT NULL DEFAULT '0',
  entity int(11) NOT NULL DEFAULT '0',
  code varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;