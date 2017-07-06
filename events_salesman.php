<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	
	
	
	llxHeader('',$langs->trans('events_salesman'));
	
	if(GETPOST('date_deb')=='') {
		$date_deb = date('Y-m-01');
		$date_fin = date('Y-m-t');
		
	}
	else {
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
	}
	
	
	
	
	echo dol_get_fiche_head($langs->trans('events_salesman'));
	print_fiche_titre($langs->trans("events_salesman"));
	_print_filtres($date_deb,$date_fin);
	echoRapport($date_deb,$date_fin);

	$TCatAff=array();
	
	
	
	
	function echoRapport($date_deb,$date_fin){
		global $db, $langs,$TCatAff;
		
		
		
		dol_include_once('/core/lib/date.lib.php');
		$fk_statut=GETPOST("fk_statut");
		$TData_titre = _categ();
		$TData = regroup_data($date_deb,$date_fin);
		$nb_colonnes = count($TData_titre);
		//var_dump($TCatAff,$TData_titre);
		?>
		<style type="text/css">
		table#rapport_depassement td,table#rapport_depassement th {
			white-space: nowrap;
			border-right: 1px solid #D8D8D8;
			border-bottom: 1px solid #D8D8D8;
		}
		div.rotate90{
			transform-origin: left top 0;
			transform: rotate(90deg);
			display:block;
			height:230px;
			width:50px;
			text-align: left;
			position:relative;
			left:15px;
			
		}
		.soc {
			background-color:#ddffdd;
		}
		
		</style>
		
		<div style="padding-bottom: 25px;">
			<table id="rapport_depassement" class="noborder" width="100%">
				<thead>
					<tr style="text-align:left;" class="liste_titre nodrag nodrop">
						<th class="liste_titre">Utilisateur</th>
						<?php foreach ($TCatAff as $code=>$dummy){ ?>
							<th class="liste_titre"><div class="rotate90"><?php echo $TData_titre[$code] ?></div></th>
						<?php } ?>
						<th class="liste_titre soc"><div class="rotate90">Nombre de tiers crées</div></th>
					</tr>
				</thead>
				<tbody>
					<?php
					
					foreach ($TData as $fk_user=>$TData2){
						
						$user = new User($db);
						$user->fetch($fk_user);
						
						?>
						<tr>
							<td><?php echo $user->getNomUrl(1); ?></td>
							
							<?php foreach($TCatAff as $code=>$dummy) { 
							
								?><td><?php 
								echo !empty($TData2[$code]) ? '<a href="'.dol_buildpath('/comm/action/index.php',1).'?action=show_month&year='.date('Y', strtotime($date_deb)).'&month='.date('m', strtotime($date_deb)).'&day=0&usertodo='.$fk_user.'&actioncode='.$code.'">'.$TData2[$code]['nb'].'</a>' : '';
								?>
								</td>
							
							<?php } ?>
							<td class="soc"><?php echo $TData2['soc_created'] ?></td>
						</tr>
					<?php	
					}
					?>

				</tbody>
			</table>
		</div>

<?php
	}
	
	
	
	function _get_infos_events($date_deb,$date_fin){
		global $db,$TCatAff;
		
		$TData = array();
		
		$db->query("SET SQL_MODE='';");
		
		$sql = 'SELECT cac.code ,COUNT(ac.id) AS nbEvent, acr.fk_element AS user, cac.libelle AS libelle 
					FROM '.MAIN_DB_PREFIX.'actioncomm ac ';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'actioncomm_resources acr ON (acr.fk_actioncomm = ac.id) ';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm cac ON (cac.id = ac.fk_action) ';
		
		$fk_usergroup = GETPOST('fk_usergroup','int');
		if($fk_usergroup >0){
			$sql .= 'INNER JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (acr.fk_element = ugu.fk_user) ';
			
		}
		
		
		$sql .= ' WHERE (acr.element_type = "user") AND (ac.datep BETWEEN "'.$date_deb.' 00:00:00" AND "'.$date_fin.' 23:59:59") ';
		if($fk_usergroup >0){
			$sql.=" AND ugu.fk_usergroup=".	$fk_usergroup;
		}
		
		$sql .= ' GROUP BY cac.libelle, acr.fk_element ';
		$sql .= ' ORDER BY acr.fk_element';
		//echo $sql;
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$TCatAff[$line->code] = true;
				
				$TData[$line->user][$line->code] = array(
					'user' => $line->user,
					'nb' => $line->nbEvent,
					'libelle' => $line->libelle 
				);
			}
		}
		else{
			var_dump($db);
		}
		//var_dump($TData);
		return $TData;
		
	}
	
	function _get_nb_soc_created($date_deb,$date_fin){
		global $db;
		
		$TData = array();
		
		$sql = 'SELECT COUNT(s.rowid) AS nb, s.fk_user_creat AS usrcreate FROM '.MAIN_DB_PREFIX.'societe s ';
		$sql .= 'WHERE s.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" ';
		$sql .= 'GROUP BY s.fk_user_creat ';
		
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[$line->usrcreate] =$line->nb;
			}
		}
		return $TData;
		
	}

	function _print_filtres($date_deb,$date_fin){
		global $db, $langs;
		
		$id=(int) GETPOST('id');
		
		$formCore = new TFormCore($_SERVER["PHP_SELF"],'formFiltres', 'POST');
		_get_filtre($formCore,$date_deb,$date_fin);
	}
	
	function _get_filtre(&$formCore,$date_deb,$date_fin){
	    
	    echo '<div class="tabBar">';
	    echo '<table>';
		echo '<tr>';
		echo '<td>Date de début : </td>';
		echo '<td>'.$formCore->calendrier('', 'date_deb', strtotime($date_deb)).'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>Date de fin : </td>';
		echo '<td>'.$formCore->calendrier('', 'date_fin',strtotime($date_fin)).'</td>';
		echo '</tr>';
	
		global $db;
		$form=new Form($db);
		echo '<tr>';
		echo '<td>Groupe : </td>';
		echo '<td>'.$form->select_dolgroups(GETPOST('fk_usergroup'), 'fk_usergroup',1).'</td>';
		echo '</tr>';
		
		echo '<tr><td colspan="2" align="center">'.$formCore->btsubmit('Valider', '').'</td></tr>';
	    echo '</table>';
	    
	    echo '</div>';
	}
	
	
	function regroup_data($date_deb,$date_fin){
		
		$TData = array();
		
		$TEvent = _get_infos_events($date_deb,$date_fin);
		$TSoc = _get_nb_soc_created($date_deb,$date_fin);
		foreach ($TEvent as $fk_user => $data){
			$TEvent[$fk_user]['soc_created'] = $TSoc[$fk_user];
		}

		
		return $TEvent;
	}
	
	function _categ(){
		global $db,$langs;
		
		$TData = array();
		$sql = 'SELECT id,code, libelle AS libelle FROM '.MAIN_DB_PREFIX.'c_actioncomm WHERE active=1 ';
		$sql .= 'ORDER BY libelle';
		
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$label=$langs->trans('Action'.$line->code);
				if(empty($label) || $label == 'Action'.$line->code)$label = $line->libelle;
				
				$TData[$line->code] = $label;
			}
		}
		
		natcasesort($TData);
		
		return $TData;
	}
