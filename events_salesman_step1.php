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

	function getSociete($fk_soc) {
		global $TSocieteCache, $user, $db, $langs;

		if(empty($TSocieteCache))$TSocieteCache = array();

		if(!isset($TSocieteCache[$fk_soc])) {

			$TSocieteCache[$fk_soc] = new Societe($db);
			$TSocieteCache[$fk_soc]->fetch($fk_soc);

		}

		return $TSocieteCache[$fk_soc];
	}


	function echoRapport($date_deb,$date_fin){
		global $db, $langs,$TCatAff;



		dol_include_once('/core/lib/date.lib.php');
		$fk_statut=GETPOST("fk_statut");
		$TData_titre = _categ();
		$TData_step = _etape();
		$TData = regroup_data($date_deb,$date_fin);
		$nb_colonnes = count($TData_titre);
		//var_dump($TCatAff,$TData_titre);
		?>
		<style type="text/css">
table#rapport_depassement td, table#rapport_depassement th {
	white-space: nowrap;
	border-right: 1px solid #D8D8D8;
	border-bottom: 1px solid #D8D8D8;
	overflow:hidden;
}

div.rotate90 {
	transform-origin: left top 0;
	transform: rotate(90deg);
	display: block;
	height: 115px;
	width: 30px;
	text-align: left;
	position: relative;
	left: 15px;
	overflow:visible;
}

.soc {
	background-color: #ddffdd;
}

.propal {
	background-color: #ddffff;
}

.propal_signed {
	background-color: #ffffdd;
}

.taux {
	background-color: #ffddff;
}

.propal_encours {
	background-color: #ffdddd;
}

.client {
	color: #009900;
}

.prospect {
	color: #ff00ff;
}

.newprospect {
	color: #0000ff;
}
</style>

		<div style="padding-bottom: 25px;">
			<table id="rapport_depassement" class="noborder" width="100%">
				<thead>
					<tr style="text-align:left;" class="liste_titre nodrag nodrop">
						<th class="liste_titre" rowspan="2">Utilisateur</th>
						<?php
ksort($TCatAff);
						 foreach ($TCatAff as $code=>$dummy){ ?>
							<th class="liste_titre" ><div class="rotate90"><?php echo $TData_step[$code] ?></div></th>
						<?php } ?>
						<th class="liste_titre soc" rowspan="2"><div class="rotate90">Nombre de tiers crées</div></th>
						<th class="liste_titre propal" rowspan="2"><div class="rotate90">Nombre de propale créées</div></th>
						<th class="liste_titre propal" rowspan="2"><div class="rotate90">Montant</div></th>
						<th class="liste_titre taux" rowspan="2"><div class="rotate90">Tx. Transfo <?php echo img_help(1,'Propales signées sur la période sur propales signées+non signées sur la période / Montant propales signées sur la période sur montant propales signées+non signées sur la période') ?></div></th>
						<th class="liste_titre propal_signed" rowspan="2"><div class="rotate90">Nombre de propale signées</div></th>
						<th class="liste_titre propal_signed" rowspan="2"><div class="rotate90">Montant</div></th>
						<th class="liste_titre propal_encours" rowspan="2"><div class="rotate90">Nombre propales encours</div></th>
						<th class="liste_titre propal_encours" rowspan="2"><div class="rotate90">Montant encours</div></th>
					</tr>
				</thead>
				<tbody>
					<?php
//pre($TData,1);exit;
					foreach ($TData as $fk_user=>$TData1){

						$user = new User($db);
						$user->fetch($fk_user);

						?>
						<tr>
							<td><?php echo $user->getNomUrl(1); ?></td>

							<?php foreach($TCatAff as $code=>$dummy) {
//var_dump($TData1);
								if(empty($TData1[$code])) {
									echo '<td>&nbsp;</td>';
								}
								else {

										$TData2 = $TData1[$code];

								echo '<td>';
								$aff_data = $TData2['nb'];

								if(!empty($TData2['TFk_soc'])) {

									$TSociete = array();
									foreach($TData2['TFk_soc'] as $fk_soc) {
										$societe = getSociete($fk_soc);

										if(($societe->client == 2 || $societe->client == 3)
										&& $societe->date_creation > strtotime('-3month')) {
											$societe->client = 7;
										}

										if($societe->client == 2 || $societe->client == 3) {
											$societe->client = 2;
										}

										$TSociete[$societe->client][] = $societe;
									}

									$TNB=array();
									if(count($TSociete[1]))$TNB['client'] = '<span class="client" title="nb. client">'.count($TSociete[1]).'</span>';
									if(count($TSociete[2]))$TNB['prospect'] = '<span class="prospect" title="nb. prospect">'.count($TSociete[2]).'</span>';
									if(count($TSociete[7]))$TNB['newprospect'] = '<span class="newprospect" title="nb. prospect de moins de 3 mois">'.count($TSociete[7]).'</span>';

									if(!empty($TNB)) {

										$aff_data.=' ('.implode(' + ',$TNB).')';

									}
								}

								echo !empty($TData1) ? '<a href="'.dol_buildpath('/pac/listactions.php',1).'?action=show_month&year='.date('Y', strtotime($date_deb)).'&month='.date('m', strtotime($date_deb)).'&day=0&usertodo='.$fk_user.'&step='.$code.'">'.$aff_data.'</a>' : '';
//								echo $aff_data;
									echo '</td>';
								}
								

							} ?>
							<td class="soc"><?php echo (int)$TData1['@']['soc_created'] ?></td>
							<td class="propal" align="right"><?php echo (int)$TData1['@']['propal']['validated'].img_help(1,$TData1['@']['propal']['validated_refs']); ?></td>
							<td class="propal" align="right"><?php echo price($TData1['@']['propal']['amount_validated']) ?></td>
							<td class="taux" align="right"><?php
							if($TData1['@']['propal']['signed'] + $TData1['@']['propal']['notsigned']>0) {
								echo round($TData1['@']['propal']['signed'] / ($TData1['@']['propal']['signed'] + $TData1['@']['propal']['notsigned']) * 100).'%
									/ '.round($TData1['@']['propal']['amount_signed'] / ($TData1['@']['propal']['amount_signed'] + $TData1['@']['propal']['amount_notsigned']) * 100).'%';
								}
								else {
									echo 'N/A';
								}

							?></td>
							<td class="propal_signed" align="right"><?php echo (int)$TData1['@']['propal']['signed'].img_help(1,$TData1['@']['propal']['signed_refs']);?></td>
							<td class="propal_signed" align="right"><?php echo price($TData1['@']['propal']['amount_signed']) ?></td>
							<td class="propal_encours" align="right"><?php echo (int)$TData1['@']['propal']['allvalidated'].img_help(1,$TData1['@']['propal']['allvalidated_refs']); ?></td>
							<td class="propal_encours" align="right"><?php echo price($TData1['@']['propal']['amount_allvalidated']) ?></td>
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

		$sql = 'SELECT acex.etape as etape, cac.code,COUNT(ac.id) AS nbEvent, acr.fk_element AS user, cac.libelle AS libelle, GROUP_CONCAT(ac.fk_soc) as fk_socs
					FROM '.MAIN_DB_PREFIX.'actioncomm ac ';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'actioncomm_resources acr ON (acr.fk_actioncomm = ac.id) ';
		$sql .= ' INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm cac ON (cac.id = ac.fk_action) ';
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_extrafields acex ON (acex.fk_object = ac.id) ';
		//$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.' ';

		$fk_usergroup = GETPOST('fk_usergroup','int');
		if($fk_usergroup >0){
			$sql .= 'INNER JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (acr.fk_element = ugu.fk_user) ';

		}


		$sql .= ' WHERE (acr.element_type = "user") AND (ac.datep BETWEEN "'.$date_deb.' 00:00:00" AND "'.$date_fin.' 23:59:59") AND percent=100 ';
		if($fk_usergroup >0){
			$sql.=" AND ugu.fk_usergroup=".	$fk_usergroup;
		}

		$sql .= ' GROUP BY acex.etape, acr.fk_element ';
		$sql .= ' ORDER BY acr.fk_element';
		//echo $sql;
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){

				if(empty($line->etape)) continue;

				$TCatAff[$line->etape] = true;

				$TData[$line->user][$line->etape]= array(
					'user' => $line->user,
					'nb' => $line->nbEvent,
					'libelle' => $line->libelle,
					'TFk_soc' => explode(',',$line->fk_socs)
				);
			}
		}
		else{
			var_dump($db);
		}
		//pre($TData,1);
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

		$sql .= 'WHERE s.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'" AND s.fournisseur=0 ';
		if($fk_usergroup >0){
                        $sql.=" AND ugu.fk_usergroup=". $fk_usergroup;
                }

		$sql .= ' GROUP BY s.fk_user_creat ';

		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$TEvent[$line->usrcreate]['@']['soc_created']=$line->nb;
			}
		}
		else {
			var_dump($db);
		}

		return $TData;

	}

	function _get_nb_propal(&$TEvent, $date_deb,$date_fin){
		global $db;

		$base_sql = 'SELECT p.total_ht as amount, p.fk_user_author, (
				SELECT sc.fk_user FROM '.MAIN_DB_PREFIX.'societe_commerciaux sc WHERE sc.fk_soc = p.fk_soc ORDER BY sc.rowid DESC LIMIT 1
			) as fk_commercial
			, (
				SELECT ec.fk_socpeople FROM '.MAIN_DB_PREFIX.'element_contact ec WHERE ec.element_id = p.rowid AND ec.fk_c_type_contact = 31 ORDER BY ec.rowid DESC LIMIT 1
			) as fk_socpeople, p.ref
			FROM '.MAIN_DB_PREFIX.'propal p
				LEFT JOIN '.MAIN_DB_PREFIX.'propal_extrafields pex ON (pex.fk_object=p.rowid)
				LEFT JOIN '.MAIN_DB_PREFIX.'user u ON (u.rowid = p.fk_user_author)
				';


		$fk_usergroup = GETPOST('fk_usergroup','int');
                if($fk_usergroup >0){
                	$base_sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'usergroup_user ugu ON (u.rowid = ugu.fk_user) ';
                }

        $base_sql.='	WHERE u.statut=1 ';
		if($fk_usergroup >0){
		 	$base_sql.=" AND ugu.fk_usergroup=". $fk_usergroup;
        }

        $sql= $base_sql.' AND (p.datep BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")
			AND p.fk_statut > 0
		GROUP BY p.rowid ';

		$resql = $db->query($sql);
//echo $sql;
		if ($resql){
			while ($line = $db->fetch_object($resql)){

				$fk_user=empty($line->fk_socpeople) ? (empty($line->fk_commercial) ? (int)$line->fk_user_author : (int)$line->fk_commercial) : (int) $line->fk_socpeople;

				$TEvent[$fk_user]['@']['propal']['validated']++;
				$TEvent[$fk_user]['@']['propal']['amount_validated']+=$line->amount;
				$TEvent[$fk_user]['@']['propal']['validated_ref'][]=$line->ref;
				$TEvent[$fk_user]['@']['propal']['validated_refs'] = empty($TEvent[$fk_user]['propal']['validated_ref']) ? '' :implode(', ', $TEvent[$fk_user]['propal']['validated_ref']);
			}

		}


		$sql= $base_sql.' AND (pex.date_signature BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")
			AND p.fk_statut IN (2,4)
		GROUP BY p.rowid ';

		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$fk_user=empty($line->fk_socpeople) ? (empty($line->fk_commercial) ? (int)$line->fk_user_author : (int)$line->fk_commercial) : (int) $line->fk_socpeople;

				$TEvent[$fk_user]['@']['propal']['signed']++;
				$TEvent[$fk_user]['@']['propal']['amount_signed']+=$line->amount;
				$TEvent[$fk_user]['@']['propal']['signed_ref'][]=$line->ref;
				$TEvent[$fk_user]['@']['propal']['signed_refs'] = empty($TEvent[$fk_user]['propal']['signed_ref']) ? '' : implode(', ', $TEvent[$fk_user]['propal']['signed_ref']);

			}
		}
		else {
			var_dump($db);
		}

		$sql= $base_sql.' AND p.fk_statut IN (1)
		GROUP BY p.rowid ';

		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				$fk_user=empty($line->fk_socpeople) ? (empty($line->fk_commercial) ? (int)$line->fk_user_author : (int)$line->fk_commercial) : (int) $line->fk_socpeople;

				$TEvent[$fk_user]['@']['propal']['allvalidated']++;
				$TEvent[$fk_user]['@']['propal']['amount_allvalidated']+=$line->amount;
				$TEvent[$fk_user]['@']['propal']['allvalidated_ref'][]=$line->ref;
				$TEvent[$fk_user]['@']['propal']['allvalidated_refs'] = implode(', ', $TEvent[$fk_user]['@']['propal']['allvalidated_ref']);
			}
		}
		else {
			var_dump($db);
		}

		$sql= $base_sql.' AND (p.datep BETWEEN "'.$date_deb.'" AND "'.$date_fin.'")
                        AND p.fk_statut IN (3)
                GROUP BY p.rowid ';

                $resql = $db->query($sql);

                if ($resql){
                        while ($line = $db->fetch_object($resql)){
                        		$fk_user=empty($line->fk_socpeople) ? (empty($line->fk_commercial) ? (int)$line->fk_user_author : (int)$line->fk_commercial) : (int) $line->fk_socpeople;

                        		$TEvent[$fk_user]['@']['propal']['notsigned']++;
                        		$TEvent[$fk_user]['@']['propal']['amount_notsigned']+=$line->amount;
                        		$TEvent[$fk_user]['@']['propal']['notsigned_ref'][]=$line->ref;
                        		$TEvent[$fk_user]['@']['propal']['notsigned_refs'] = implode(', ', $TEvent[$fk_user]['propal']['notsigned_ref']);
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
//var_dump($TData);exit;
		return $TData;
	}
	function _etape(){
		global $db,$langs;

		$TData = array();
		$sql="SELECT param FROM ".MAIN_DB_PREFIX."extrafields WHERE name='etape'";
		$res = $db->query($sql);
		$obj = $db->fetch_object($res);

		$TParam = unserialize($obj->param);
		foreach($TParam['options'] as $code=>$label) {

			$TData[$code] = $label;

		}

		natcasesort($TData);
//var_dump($TData);exit;
		return $TData;
	}
