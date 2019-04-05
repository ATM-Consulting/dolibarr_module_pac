ALTER TABLE `llx_followup_goal`
  ADD UNIQUE KEY `fk_user` (`fk_cat`,`fk_user`,`year`,`month`),
  ADD KEY `date` (`fk_cat`,`year`,`month`);
