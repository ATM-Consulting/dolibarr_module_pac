<?php 


require 'config.php';
dol_include_once('/categories/class/categorie.class.php');


llxHeader();

$c=new Categorie($db);
$TCat = $c->get_all_categories(Categorie::TYPE_CUSTOMER, $conf->global->PAC_PARENT_CATEG_FOR_REPORT);

dol_fiche_head();


$TDate =array();
$TTotal=array();
foreach($TCat as &$cat) {

	if(empty($TTotal[$cat->id]))$TTotal[$cat->id]=array('cat'=>$cat, 'datas'=>array(999999=>0), 'fk_socs'=>array(),'nb_propal'=>0);
	
	$resultset= $db->query("SELECT f.fk_soc, f.total, f.datef FROM ".MAIN_DB_PREFIX."facture f 
			LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = f.fk_soc)
			LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe cs ON (s.rowid=cs.fk_soc)
			WHERE f.fk_statut IN (1,2) AND cs.fk_categorie = ".$cat->id."
			ORDER BY f.datef
		");
	
	if($resultset === false) {
		var_dump($db);
		exit;
	}
	
	
	while($obj = $db->fetch_object($resultset)) {
		
		$datef = strtotime($obj->datef);
		$datem = (int)date('Y',$datef);
		
		$TDate[$datem]=1;
		
		if(empty($TTotal[$cat->id]['datas'][$datem]))$TTotal[$cat->id]['datas'][$datem] = 0;
		$TTotal[$cat->id]['datas'][$datem]+=$obj->total;
		$TTotal[$cat->id]['fk_socs'][$obj->fk_soc]=1;
		
		$TTotal[$cat->id]['datas'][999999]+=$obj->total;
	}
	
	$resultset= $db->query("SELECT count(*) as nb FROM ".MAIN_DB_PREFIX."propal p 
			LEFT JOIN ".MAIN_DB_PREFIX."societe s ON (s.rowid = p.fk_soc)
			LEFT JOIN ".MAIN_DB_PREFIX."categorie_societe cs ON (s.rowid=cs.fk_soc)
			WHERE p.fk_statut IN (2) AND cs.fk_categorie = ".$cat->id."
			ORDER BY p.datep
		");
	if($resultset === false) {
		var_dump($db);
		exit;
	}
	$obj = $db->fetch_object($resultset);
	
	$TTotal[$cat->id]['nb_propal'] = $obj->nb;
	
}

ksort($TDate);

foreach($TTotal as &$row ) {
	
	$cat = &$row['cat'];
	foreach($TDate as $datem=>$dummy) {
		
		if(empty($row['datas'][$datem])) $row['datas'][$datem] = 0;
		
	}
	
	ksort($row['datas']);
	
}


echo '<table class="liste" width="100%">';

echo '<tr class="liste_titre"><td>'.$langs->trans('Category').'</td>';
echo '<td>'.$langs->trans('NbCustomer').'</td>';
echo '<td>'.$langs->trans('NbPropal').'</td>';

foreach($TDate as $datem=>$dummy) {
	
	echo '<td >'.$datem/*dol_print_date(strtotime(date($datem.'01')))*/.'</td>';
	
}

echo '<td>Total</td>';

echo '</tr>';

foreach($TTotal as &$row ) {
	
	$cat = &$row['cat'];
	
	$ways = $cat->print_all_ways(" &gt;&gt; ",'',1);
//	var_dump($cat->label, $ways);
	echo '<tr class="oddeven"><td>'.$ways[0].'</td>';
	echo '<td>'.count($row[fk_socs]).'</td>';
	echo '<td>'.$row['nb_propal'].'</td>';
	
	$old_total = 0;
	foreach($row['datas'] as $date=>$total) {
	
		$evolution =0;
		if(!empty($old_total) && $date!=999999) {
			
			$evolution = ($total - $old_total > 0 || empty($total)) ? round( ($total / $old_total) * 100) : -round( ( $old_total / $total ) * 100);
		//	var_dump(array($total,$old_total,$evolution));
		}
		
		echo '<td nowrap="nowrap" align="right">'.price($total).( empty($evolution) ? '' : ' ('.sprintf("%+d",$evolution).'%)' ).'</td>';
		
		
		$old_total = $total;
		
	}
	
	echo '</tr>';
}

echo '</table>';

dol_fiche_end();
llxFooter();
