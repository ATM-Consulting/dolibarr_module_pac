<?php

	require '../config.php';

	dol_include_once('/comm/propal/class/propal.class.php');
	dol_include_once('/societe/class/societe.class.php');

	
	$get=GETPOST('get');
	$put=GETPOST('put');
	
	switch ($get) {
		case 'propals':
		
			__out(_propasals((int)GETPOST('min'),(int)GETPOST('max'),GETPOST('special'),GETPOST('fk_user')), 'json');
						
			break;
		default:
			
			break;
	}
	
	switch ($put) {
		case 'propal':
		
			_update_proba_propal(GETPOST('propalid'), GETPOST('proba'), GETPOST('special'));
						
			break;
		default:
			
			break;
	}
	
function _update_proba_propal($fk_propal, $proba, $special = '') {
	//TODO pouvoir signer propal directmeent à ce niveau ?
	
	global $db,$langs,$user,$conf;
	
	$p=new Propal($db);
	if($p->fetch($fk_propal)) {
		
		$p->array_options['options_proba'] = (int)$proba;
		$p->update_extrafields($user);
	}
	
	
}	

function _propasals($min,$max,$special='',$fk_user = 0) {
	global $db,$langs,$user,$conf;
	
	
	/*
	 * Retourne les propals avec un taux adéquat
	 */
	
	$PDOdb=new TPDOdb; 
	
	if(!empty($special)) {
			
		if($special=='signed') {
			$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p	WHERE p.fk_statut = 2 AND p.date_valid> (NOW() - INTERVAL 30 DAY) "; 
			
		}
		else if($special=='notsigned') {
			$sql = "SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p	WHERE p.fk_statut = 3 AND p.date_valid> (NOW() - INTERVAL 30 DAY)"; 
			
		}
		
					
	}
	else{
		$sql ="SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p
				LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields ex ON (ex.fk_object = p.rowid)
				WHERE ex.proba < ".$max." AND ex.proba>=".$min; 
	}
	
	if($fk_user>0) {
		$sql.= " AND p.fk_user_author = ".$fk_user;
	}
	
	$TRes = $PDOdb->ExecuteAsArray($sql); 
				
	$Tab=array();
	
	foreach($TRes as &$row) {
		
		$p=new Propal($db);
		if($p->fetch($row->rowid)>0) {
			$soc = new Societe($db);
			$soc->fetch($p->socid);
			
			$p->total_ht_aff = price($p->total_ht);
			$p->customerLink = $soc->getNomUrl(1);
			
			$Tab[] = $p;
			
		}
		
	}
	
	
	
	return $Tab;
	
}
