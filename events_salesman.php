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
	
	
	$db->query("SET SQL_MODE='';");
	
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
		.propal {
			background-color:#ddffff;
		}
		.propal_signed {
			background-color:#ffffdd;
		}
		.taux {
			background-color:#ffddff;
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
						<th class="liste_titre propal"><div class="rotate90">Nombre de propale créées</div></th>
						<th class="liste_titre propal"><div class="rotate90">Montant</div></th>
						<th class="liste_titre taux"><div class="rotate90">Tx. Transfo <?php echo img_help(1,'Propales ouvertes sur la période sur propales signées sur la période / Montant propales ouvertes sur la période sur montant propales signées sur la période') ?></div></th>
						<th class="liste_titre propal_signed"><div class="rotate90">Nombre de propale signées</div></th>
						<th class="liste_titre propal_signed"><div class="rotate90">Montant</div></th>
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
							<td class="soc"><?php echo (int)$TData2['soc_created'] ?></td>
							<td class="propal" align="right"><?php echo (int)$TData2['propal']['validated'].img_help(1,$TData2['propal']['validated_refs']); ?></td>
							<td class="propal" align="right"><?php echo price($TData2['propal']['amount_validated']) ?></td>
							<td class="taux" align="right"><?php 
								if($TData2['propal']['validated']>0) {
									echo round($TData2['propal']['signed'] / $TData2['propal']['validated'] * 100).'% 
									/ '.round($TData2['propal']['amount_signed'] / $TData2['propal']['amount_validated'] * 100).'%'; 
								}
								else {
									echo 'N/A';
								}
								
							?></td>
							<td class="propal_signed" align="right"><?php echo (int)$TData2['propal']['signed'].img_help(1,$TData2['propal']['signed_refs']);?></td>
							<td class="propal_signed" align="right"><?php echo price($TData2['propal']['amount_signed']) ?></td>
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
	
	function _get_nb_soc_created(&$TEvent, $date_deb,$date_fin){
		global $db;
		
		$TData = array();
		
		$sql = 'SELECT COUNT(s.rowid) AS nb, s.fk_user_creat AS usrcreate FROM '.MAIN_DB_PREFIX.'societe s ';

		$fk_usergroup = GETPOST('fk_usergroup','int');
                if($fk_usergroup >0){
                        $sql .= 'INNER JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (s.fk_user_creat = ugu.fk_user) ';
                        
                }

		$sql .= 'WHERE s.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" ';
		if($fk_usergroup >0){
                        $sql.=" AND ugu.fk_usergroup=". $fk_usergroup;
                }

		$sql .= ' GROUP BY s.fk_user_creat ';
		
		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$TEvent[$line->usrcreate]['soc_created']=$line->nb;
			}
		}
		else {
			var_dump($db);
		}

		return $TData;
		
	}
	
	function _get_nb_propal(&$TEvent, $date_deb,$date_fin){
		global $db;
		
		$sql = 'SELECT COUNT(p.rowid) as nb , SUM(p.total_ht) as amount, p.fk_user_author, ec.fk_socpeople, GROUP_CONCAT(p.ref) as refs
			FROM '.MAIN_DB_PREFIX.'propal p 
				LEFT JOIN '.MAIN_DB_PREFIX.'element_contact ec ON (ec.element_id = p.rowid AND fk_c_type_contact = 31)
				LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = ec.fk_socpeople OR u.rowid = p.fk_user_author) ';

		$fk_usergroup = GETPOST('fk_usergroup','int');
                if($fk_usergroup >0){
                        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (u.rowid = ugu.fk_user) ';
                }

		$sql.='	WHERE u.statut=1 ';
		 if($fk_usergroup >0){
                        $sql.=" AND ugu.fk_usergroup=". $fk_usergroup;
                }
		
		$sql.= ' AND (p.datep BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")
			AND p.fk_statut > 0
		GROUP BY u.rowid ';
		
		$resql = $db->query($sql);
//echo $sql;		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				

				$fk_user=empty($line->fk_socpeople) ? (int)$line->fk_user_author : (int) $line->fk_socpeople;
				$TEvent[$fk_user]['propal']['validated']=$line->nb;
				$TEvent[$fk_user]['propal']['amount_validated']=$line->amount;
				$TEvent[$fk_user]['propal']['validated_refs']=$line->refs;
				
			}
		}
		
		
		$sql = 'SELECT COUNT(p.rowid) as nb , SUM(p.total_ht) as amount, p.fk_user_author, ec.fk_socpeople, GROUP_CONCAT(p.ref) as refs
			FROM '.MAIN_DB_PREFIX.'propal p
				LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields pex ON (pex.fk_object=p.rowid)
				LEFT JOIN '.MAIN_DB_PREFIX.'element_contact ec ON (ec.element_id = p.rowid AND fk_c_type_contact = 31)
				LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = ec.fk_socpeople OR u.rowid = p.fk_user_author) ';

		$fk_usergroup = GETPOST('fk_usergroup','int');
                if($fk_usergroup >0){
                        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (u.rowid = ugu.fk_user) ';
                }

		$sql.='	WHERE u.statut=1 ';
		if($fk_usergroup >0){
                        $sql.=" AND ugu.fk_usergroup=". $fk_usergroup;
                }
		$sql.= ' AND (pex.date_signature BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")
			AND p.fk_statut IN (2,4)
		GROUP BY u.rowid ';
		
		$resql = $db->query($sql);
		
		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$fk_user=empty($line->fk_socpeople) ? (int)$line->fk_user_author : (int) $line->fk_socpeople;
				$TEvent[$fk_user]['propal']['signed']=$line->nb;
				$TEvent[$fk_user]['propal']['amount_signed']=$line->amount;
				$TEvent[$fk_user]['propal']['signed_refs']=$line->refs;
			}
		}
		else {
			var_dump($db);
		}
		
		
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
		
		_get_nb_soc_created($TEvent, $date_deb,$date_fin);
		_get_nb_propal($TEvent, $date_deb,$date_fin);
		
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
