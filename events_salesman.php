<?php
	require('config.php');
	dol_include_once('/projet/class/project.class.php');
	dol_include_once('/projet/class/task.class.php');
	dol_include_once('/user/class/user.class.php');
	dol_include_once('/core/lib/usergroups.lib.php');
	dol_include_once('/comm/propal/class/propal.class.php');
	
	
	
	llxHeader('',$langs->trans('graphPropalCommercial'));
	
	print dol_get_fiche_head($langs->trans('graphPropalCommercial'));
	print_fiche_titre($langs->trans("graphPropalCommercial"));
	_print_filtres();
	printRapport();


	
	
	
	
	function printRapport(){
		global $db, $langs;
		
		dol_include_once('/core/lib/date.lib.php');
		$fk_statut=GETPOST("fk_statut");
		$TData_titre = select_nb_categ();
		$TData = regroup_data();
		$nb_colonnes = count($TData_titre);

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
		</style>
		
		<div style="padding-bottom: 25px;">
			<table id="rapport_depassement" class="noborder" width="100%">
				<thead>
					<tr style="text-align:left;" class="liste_titre nodrag nodrop">
						<th class="liste_titre">Utilisateur</th>
						<?php foreach ($TData_titre as $colonne){ ?>
							<th class="liste_titre"><div class="rotate90"><?php echo $langs->trans($colonne['libelle'])?></div></th>
						<?php } ?>
						<th class="liste_titre"><div class="rotate90">Nombre de tiers crées</div></th>
					</tr>
				</thead>
				<tbody>
					<?php
					
					foreach ($TData as $data){
						
						$user = new User($db);
						$user->fetch($data['user']);
						?>
						<tr>
							<td><?php echo $user->getNomUrl(1) ?></td>
							
							<?php for ($i=0; $i<$nb_colonnes; $i++) {?>
								
								<td><?php foreach($TData[$user->id]['events'] as $line){
												if($TData_titre[$i]['libelle']==$line['libelle']){
													
													echo $line['nombre'];
												}
												
									}?>
									
									
								</td>
							
							<?php } ?>
							<td><?php echo $data['soc_created'] ?></td>
						</tr>
					<?php	
					}
					?>

				</tbody>
			</table>
		</div>

<?php
	}
	
	
	
	function _get_infos_events(){
		global $db;
		
		$TData = array();
		
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		if(empty(GETPOST('date_deb')))$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(empty(GETPOST('date_fin')))$date_fin=date('Y-m-d');
		
		$sql = 'SELECT COUNT(ac.id) AS nbEvent, acr.fk_element AS user, cac.libelle AS libelle FROM '.MAIN_DB_PREFIX.'actioncomm ac ';
		$sql .= 'INNER JOIN '.MAIN_DB_PREFIX.'actioncomm_resources acr ON acr.fk_actioncomm = ac.id ';
		$sql .= 'INNER JOIN '.MAIN_DB_PREFIX.'c_actioncomm cac ON cac.id = ac.fk_action ';
		$sql .= 'WHERE (acr.element_type = "user") AND (ac.percent != 100) AND (ac.datep2 BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") ';
		$sql .= 'GROUP BY cac.libelle, acr.fk_element ';
		$sql .= 'ORDER BY acr.fk_element';
		
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[] = array(
					'user' => $line->user,
					'nb' => $line->nbEvent,
					'libelle' => $line->libelle 
				);
			}
		}
		//var_dump($TData);
		return $TData;
		
	}
	
	function _get_nb_soc_created(){
		global $db;
		
		$TData = array();
		
		$date_d=str_replace('/', '-', GETPOST('date_deb'));
		$date_f=str_replace('/', '-', GETPOST('date_fin'));
		$date_deb=date('Y-m-d', strtotime($date_d));
		$date_fin=date('Y-m-d', strtotime($date_f));
		
		if(empty(GETPOST('date_deb')))$date_deb=date('Y-m-d' , strtotime(date('Y-m-d'))-(60*60*24*30));
		if(empty(GETPOST('date_fin')))$date_fin=date('Y-m-d');
		
		
		$sql = 'SELECT COUNT(s.rowid) AS socid, s.fk_user_creat AS usrcreate FROM '.MAIN_DB_PREFIX.'societe s ';
		$sql .= 'WHERE (s.datec BETWEEN "'.$date_deb.'" AND "'.$date_fin.'") ';
		$sql .= 'GROUP BY s.fk_user_creat ';
		
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[$line->usrcreate] = array(
												'nbsoc'  	  => $line->socid,
												'user_create' => $line->usrcreate 
											);
			}
		}
		return $TData;
		
	}

	function _print_filtres(){
		global $db, $langs;
		
		$id=(int) GETPOST('id');
		
		$Tform = new TFormCore($_SERVER["PHP_SELF"],'formFiltres', 'POST');
		_get_filtre($Tform);
	}
	
	function _get_filtre($form){
	    
	    print '<div class="tabBar">';
	    print '<table>';
		print '<tr>';
		print '<td>Date de début : </td>';
		print '<td>'.$form->calendrier('', 'date_deb', (GETPOST('date_deb'))? GETPOST('date_deb') : '').'</td>';
		print '</tr>';
		print '<tr>';
		print '<td>Date de fin : </td>';
		print '<td>'.$form->calendrier('', 'date_fin', (GETPOST('date_fin'))? GETPOST('date_fin') : '').'</td>';
		print '</tr>';
	
	    print '<tr><td colspan="2" align="center">'.$form->btsubmit('Valider', '').'</td></tr>';
	    print '</table>';
	    
	    print '</div>';
	}
	
	
	function regroup_data(){
		
		$TData = array();
		
		$TEvent = _get_infos_events();
		$TSoc = _get_nb_soc_created();
		$Tfinal = array();
		
		$cpt=0;
		foreach ($TEvent as $event){
			$TEvents = array();
			if (!isset($TData[$event['user']])){
				$TEvents[] = array(
								'libelle' => $event['libelle'],
								'nombre'  => $event['nb']
							);
				$TData[$event['user']] = array(
							'user' => $event['user'],
							'events' => $TEvents,
							'soc_created' => $TSoc[$event['user']]['nbsoc']
							
						);
			}else if (isset($TData[$event['user']])){
				$TEvents = array(
								'libelle' => $event['libelle'],
								'nombre'  => $event['nb']
								);

				array_push($TData[$event['user']]['events'], $TEvents);
			}
				
			
		}

		
		return $TData;
	}
	
	function select_nb_categ(){
		global $db;
		
		$TData = array();
		$sql = 'SELECT id, libelle AS libelle FROM '.MAIN_DB_PREFIX.'c_actioncomm WHERE active=1 ';
		$sql .= 'ORDER BY id';
		
		$resql = $db->query($sql);

		if ($resql){
			while ($line = $db->fetch_object($resql)){
				
				$TData[] = array(
					'libelle' => $line->libelle
				);
			}
		}
		
		return $TData;
	}
