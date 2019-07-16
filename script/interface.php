<?php

	require '../config.php';

	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/societe/class/societe.class.php');

	
	$get=GETPOST('get');
	$put=GETPOST('put');
	
	switch ($get) {
		case 'propals':
		
			__out(_propasals(GETPOST('code'), (int)GETPOST('start'), (int)GETPOST('end'), GETPOST('fk_user')), 'json');
						
			break;
		default:
			
			break;
	}
	
	switch ($put) {
		case 'propal':
		
			_update_proba_propal((int)GETPOST('propalid'), GETPOST('proba'), GETPOST('end'), GETPOST('special'));
						
			break;
		default:
			
			break;
	}
	
function _update_proba_propal($fk_propal, $proba,$nb_month, $special = '') {
	//TODO pouvoir signer propal directmeent à ce niveau ?
	
	global $db,$langs,$user,$conf;
	
	$p=new Propal($db);
	if($p->fetch($fk_propal)) {
		
		$p->array_options['options_proba'] = (int)$proba;
		$p->array_options['options_date_cloture_prev'] = strtotime('+'.$nb_month.' month' );
		
		
		$p->update_extrafields($user);
	}
	
	
}	


function _propasals($code, $start, $end, $fk_user = 0)
{
	global $db,$langs,$user,$conf;
	
	$PDOdb = new TPDOdb;

	$sql = 'SELECT p.rowid
			FROM ' . MAIN_DB_PREFIX. 'propal p';

	$dateField = 'p.date_valid';

	if($code == '__STATUS_SIGNED')
	{
		$sql .= '
			WHERE p.fk_statut = ' . Propal::STATUS_SIGNED;
	}
	elseif($code == '__STATUS_NOTSIGNED')
	{
		$sql .= '
			WHERE p.fk_statut = ' . Propal::STATUS_NOTSIGNED;
	}
	else
	{
		$sql.= '
			LEFT JOIN ' . MAIN_DB_PREFIX . 'propal_extrafields ex ON ex.fk_object = p.rowid
			LEFT JOIN ' . MAIN_DB_PREFIX . 'c_pac_interest pi ON pi.rowid = ex.interest
			WHERE p.fk_statut = ' . Propal::STATUS_VALIDATED;

		if(empty($code))
		{
			$sql.= '
			AND pi.code IS NULL';
		}
		else
		{
			$sql.= '
			AND pi.code = "' . $db->escape($code) . '"';
		}

		$dateField = 'ex.date_cloture_prev';
	}

	if(empty($start))
	{
		$sql.= '
			AND (' . $dateField . ' IS NULL OR ' . $dateField . ' < NOW() )';
	}
	else 
	{
		$sql.= '
			AND (' . $dateField . ' >= (NOW() + INTERVAL ' . intval($start) . ' MONTH) )';
	}

	if($end > 0)
	{
		$sql.= '
			AND (' . $dateField . ' < (NOW() + INTERVAL ' . intval($end) . ' MONTH)';

		if(empty($start))
		{
			$sql.= ' OR ' . $dateField . ' IS NULL';
		}

		$sql.= ' )';
	}
	

	if($fk_user > 0)
	{
		$sql.= '
			AND p.fk_user_author = ' . intval($fk_user);
	}

	$TRes = $PDOdb->ExecuteAsArray($sql); 

	$Tab = array();

	foreach($TRes as &$row)
	{
		$p = new Propal($db);

		if($p->fetch($row->rowid) > 0) {
			$soc = new Societe($db);
			$soc->fetch($p->socid);

			$obj = new stdClass;

			$obj->id = $p->id;
			$obj->ref = $p->ref;
			$obj->total_ht_aff = price($p->total_ht);
			$obj->customerLink = $soc->getNomUrl(1);
			$obj->link = $p->getNomUrl(1);
			$obj->total_ht = $p->total_ht;

			$Tab[] = $obj;
		}
	}

	return $Tab;
}
