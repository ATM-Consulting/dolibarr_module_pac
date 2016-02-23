<?php

	require '../config.php';

	dol_include_once('/comm/propal/class/propal.class.php');

	
	$get=GETPOST('get');
	$put=GETPOST('put');
	
	switch ($get) {
		case 'propals':
		
			__out(_propasals((int)GETPOST('min'),(int)GETPOST('max')), 'json');
						
			break;
		default:
			
			break;
	}
	
	switch ($put) {
		case 'propal':
		
			_update_proba_propal(GETPOST('propalid'), GETPOST('proba'));
						
			break;
		default:
			
			break;
	}
	
function _update_proba_propal($fk_propal, $proba) {
	global $db,$langs,$user,$conf;
	
	$p=new Propal($db);
	if($p->fetch($fk_propal)) {
		
		$p->array_options['options_proba'] = (int)$proba;
		$p->update_extrafields($user);
	}
	
	
}	

function _propasals($min,$max) {
	global $db,$langs,$user,$conf;
	
	
	/*
	 * Retourne les propals avec un taux adéquat
	 */
	
	$PDOdb=new TPDOdb; 
	$TRes = $PDOdb->ExecuteAsArray("SELECT p.rowid FROM ".MAIN_DB_PREFIX."propal p
			LEFT JOIN ".MAIN_DB_PREFIX."propal_extrafields ex ON (ex.fk_object = p.rowid)
			WHERE ex.proba < ".$max." AND ex.proba>=".$min); //useless jointure delete it
	
	$Tab=array();
	
	foreach($TRes as &$row) {
		
		$p=new Propal($db);
		if($p->fetch($row->rowid)>0) {
			
			$Tab[] = $p;
			
		}
		
	}
	
	
	
	return $Tab;
	
}
