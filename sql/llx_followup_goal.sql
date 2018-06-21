CREATE TABLE llx_followup_goal (
  rowid int(11) AUTO_INCREMENT PRIMARY KEY,
  fk_user int(11) NOT NULL DEFAULT 0,
  fk_cat int(11) DEFAULT 0,
  year int(4) DEFAULT NULL,
  month int(2) DEFAULT NULL,
  amount int(11) DEFAULT NULL
)ENGINE=innodb;

--
-- Index pour la table `llx_followup_goal`
--
ALTER TABLE `llx_followup_goal`
  ADD UNIQUE KEY `fk_user` (`fk_cat`,`fk_user`,`year`,`month`),
  ADD KEY `date` (`fk_cat`,`year`,`month`);
